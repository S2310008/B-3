<?php

require __DIR__.'/vendor/autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

// 基本設定
const BASE_SYLLABUS_URL_PREFIX = 'https://web.wakayama-u.ac.jp/syllabus/';

// subject_list.csv: スクレイピング対象の科目リストを読み込む
$subjectListCsvPath = __DIR__ . '/subject_list.csv';
$subject_suffixes = [];

if (!file_exists($subjectListCsvPath)) {
    die("エラー: CSVファイル '{$subjectListCsvPath}' が見つかりません。\n");
}

if (($handle = fopen($subjectListCsvPath, "r")) !== FALSE) {
    $headers = fgetcsv($handle);
    if (isset($headers[0]) && str_starts_with($headers[0], "\xEF\xBB\xBF")) { // BOM除去
        $headers[0] = substr($headers[0], 3);
    }
    $headers = array_map('trim', $headers);

    $colCodeIndex = array_search('科目コード', $headers);
    $colSuffixIndex = array_search('URLサフィックス', $headers);

    if ($colCodeIndex === false || $colSuffixIndex === false) {
        die("エラー: CSVヘッダーに '科目コード' または 'URLサフィックス' が見つかりません。\n");
    }

    while (($row = fgetcsv($handle)) !== FALSE) {
        if (empty($row[$colCodeIndex]) || empty($row[$colSuffixIndex])) {
            echo "警告: CSVの空行または不正な行をスキップ: " . implode(',', $row) . "\n";
            continue;
        }
        $subject_suffixes[$row[$colCodeIndex]] = $row[$colSuffixIndex];
    }
    fclose($handle);
    echo "subject_list.csvから " . count($subject_suffixes) . " 件の科目を読み込みました。\n";
} else {
    die("エラー: CSVファイル '{$subjectListCsvPath}' を開けませんでした。\n");
}

// curriculum_list.csv: メタデータ（メジャー分類など）を読み込む
$metaCsvPath = __DIR__ . '/curriculum_list.csv';
$all_meta_data = [];

if (!file_exists($metaCsvPath)) {
    echo "警告: メタ情報CSV '{$metaCsvPath}' が見つかりません。\n";
} else {
    if (($metaHandle = fopen($metaCsvPath, "r")) !== FALSE) {
        $metaHeaders = fgetcsv($metaHandle);
        if (isset($metaHeaders[0]) && str_starts_with($metaHeaders[0], "\xEF\xBB\xBF")) { // BOM除去
            $metaHeaders[0] = substr($metaHeaders[0], 3);
        }
        $metaHeaders = array_map('trim', $metaHeaders);

        $metaColCodeIndex = array_search('科目コード', $metaHeaders);
        $metaMajorColIndex = array_search('tag1', $metaHeaders);

        if ($metaColCodeIndex === false || $metaMajorColIndex === false) {
            echo "エラー: メタ情報CSVのヘッダーに '科目コード' または 'tag1' が見つかりません。\n";
        } else {
            while (($metaRow = fgetcsv($metaHandle)) !== FALSE) {
                $metaRow = array_map('trim', $metaRow);
                if (count($metaHeaders) !== count($metaRow)) {
                    error_log("警告: メタ情報CSVの行の要素数がヘッダーと一致しません: " . implode(',', $metaRow));
                    continue;
                }
                if (isset($metaRow[$metaColCodeIndex]) && !empty($metaRow[$metaColCodeIndex])) {
                    $item = array_combine($metaHeaders, $metaRow);
                    $all_meta_data[$metaRow[$metaColCodeIndex]] = $item;
                } else {
                    error_log("警告: メタ情報CSVで科目コードが空のためスキップ: " . implode(',', $metaRow));
                }
            }
        }
        fclose($metaHandle);
        echo "curriculum_list.csvから " . count($all_meta_data) . " 件のメタデータを読み込みました。\n";
    } else {
        echo "警告: メタ情報CSV '{$metaCsvPath}' を開けませんでした。\n";
    }
}

// 全ての抽出データを格納する配列
$all_extracted_data = [];

// cURL初期化
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

echo "\n--- シラバススクレイピング開始 ---\n";

// 各科目をループしてスクレイピング
foreach ($subject_suffixes as $code => $suffix) {
    if (empty($code) || empty($suffix)) {
        echo "警告: 空の科目定義をスキップします。\n";
        continue;
    }

    // メタデータを取得
    $currentSubjectMeta = $all_meta_data[$code] ?? [];
    $majorClassification = $currentSubjectMeta['tag1'] ?? '';

    // 特定メジャー（IS, XD, NC）に該当するか判定
    $isInfoScienceMajor = in_array($majorClassification, ['IS', 'XD', 'NC']);

    $full_url = BASE_SYLLABUS_URL_PREFIX . $suffix;
    echo "アクセス中: " . $full_url . "\n";

    curl_setopt($ch, CURLOPT_URL, $full_url);
    $html_content = curl_exec($ch);

    if ($html_content === false) {
        echo "エラー: cURLエラー - " . curl_error($ch) . " (URL: " . $full_url . ")\n";
        continue;
    }

    if (str_contains($html_content, '指定されたページが見つかりませんでした') || str_contains($html_content, 'Not Found')) {
        echo "警告: ページが見つかりません (404等): " . $full_url . "\n";
        continue;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html_content);
    $xpath = new DOMXPath($dom);

    // 抽出データ用の配列を初期化
    $extracted_info = [
        '科目名'       => null,
        '時間割コード' => $code,
        '曜限'         => null,
        '単位数'       => null,
        '開講区分'     => null,
        '教員名'       => null,
        '授業の概要'   => null,
        '関連科目'     => null,
    ];
    // メタデータをマージ
    $extracted_info = array_merge($extracted_info, $currentSubjectMeta);

    // 基本情報の抽出 (tabs-1)
    $tab1_content_node = $xpath->query('//div[@id="tabs-1"]')->item(0);
    if ($tab1_content_node) {
        // 各項目を抽出
        $subject_name_node = $xpath->query('.//th[contains(text(), "開講科目名")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($subject_name_node) { $extracted_info['科目名'] = trim($subject_name_node->textContent); }

        $day_period_node = $xpath->query('.//th[contains(., "曜限")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($day_period_node) { $extracted_info['曜限'] = trim($day_period_node->textContent); }

        $credit_node = $xpath->query('.//th[contains(text(), "単位数")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($credit_node) {
            preg_match('/(\d+(\.\d+)?)/', trim($credit_node->textContent), $matches);
            $extracted_info['単位数'] = isset($matches[1]) ? (float)$matches[1] : null;
        }
        
        $offering_division_node = $xpath->query('.//th[contains(., "開講区分")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($offering_division_node) { $extracted_info['開講区分'] = trim($offering_division_node->textContent); }

        // 教員名の抽出
        $instructor_name_node = $xpath->query('.//th[contains(., "主担当教員")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($instructor_name_node) {
            $extracted_info['教員名'] = trim($instructor_name_node->textContent);
        }

    } else {
        echo "警告: 基本情報タブ（tabs-1）が見つかりません: " . $full_url . "\n";
    }

    // 詳細情報の抽出 (tabs-2)
    $tab2_content_node = $xpath->query('//div[@id="tabs-2"]')->item(0);
    if ($tab2_content_node) {
        
        // 授業の概要・ねらい
        $overview_aim_node = $xpath->query('.//th[contains(text(), "授業の概要・ねらい")]/following-sibling::td[@class="syllabus-break-word"]', $tab2_content_node)->item(0);
        if ($overview_aim_node) {
            $extracted_info['授業の概要'] = trim(strip_tags($overview_aim_node->textContent)); 
        }

        // 履修を推奨する関連科目
        $related_subjects_node = $xpath->query('.//th[contains(text(), "履修を推奨する関連科目")]/following-sibling::td[@class="syllabus-break-word"]', $tab2_content_node)->item(0);
        if ($related_subjects_node) {
            $extracted_info['関連科目'] = trim(strip_tags($related_subjects_node->textContent));
        }
    } else {
        // 詳細情報タブ(tabs-2)がどの科目でも見つからない場合に警告を出す
        echo "警告: 詳細情報タブ（tabs-2）が見つかりません: " . $full_url . "\n";
    }

    // 抽出データを配列に追加
    $all_extracted_data[] = $extracted_info;
    echo "  -> 抽出完了: " . ($extracted_info['科目名'] ?? '不明') . " (コード: " . $code . ")\n";
    sleep(1); // サーバー負荷軽減
}

curl_close($ch);

echo "\n--- スクレイピング完了 ---\n";

// Firebaseへのデータ保存
$serviceAccountPath = __DIR__ . '/keys/b3app-b0c29-firebase-adminsdk-fbsvc-4d2307d6ce.json'; 

try {
    $factory = (new Factory)
        ->withServiceAccount($serviceAccountPath)
        ->withDatabaseUri('https://b3app-b0c29-default-rtdb.firebaseio.com'); 
    $database = $factory->createDatabase();
    $databasePath = 'syllabus_subjects'; 

    echo "\n--- Firebaseへの保存開始 ---\n";

    foreach ($all_extracted_data as $subject_data) {
        if (!empty($subject_data['時間割コード'])) {
            $subject_code = $subject_data['時間割コード'];
            $database->getReference($databasePath . '/' . $subject_code)->set($subject_data);
            echo "保存完了: " . $subject_code . " - " . ($subject_data['科目名'] ?? '不明') . "\n";
        } else {
            echo "警告: 時間割コードがないため保存できませんでした。\n";
        }
    }
    echo "\n--- Firebaseへの保存完了 ---\n";

} catch (Exception $e) {
    echo "\nFirebase保存エラー: " . $e->getMessage() . "\n";
}
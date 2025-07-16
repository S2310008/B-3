<?php

require __DIR__.'/vendor/autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

// --- シラバスURLの基本設定 ---
const BASE_SYLLABUS_URL_PREFIX = 'https://web.wakayama-u.ac.jp/syllabus/';

// --- スクレイピング対象科目リストをCSVから読み込む ---
$subjectListCsvPath = __DIR__ . '/subject_list.csv'; // 科目リストCSVファイルのパス

$subject_suffixes = [];

if (!file_exists($subjectListCsvPath)) {
    die("エラー: 科目リストCSVファイル '{$subjectListCsvPath}' が見つかりません。\n");
}

if (($handle = fopen($subjectListCsvPath, "r")) !== FALSE) {
    $headers = fgetcsv($handle);
    if (isset($headers[0]) && str_starts_with($headers[0], "\xEF\xBB\xBF")) {
        $headers[0] = substr($headers[0], 3);
    }
    $headers = array_map('trim', $headers);

    $colCodeIndex = array_search('科目コード', $headers);
    $colSuffixIndex = array_search('URLサフィックス', $headers);

    if ($colCodeIndex === false || $colSuffixIndex === false) {
        die("エラー: 科目リストCSVのヘッダーに '科目コード' または 'URLサフィックス' が見つかりません。\n");
    }

    while (($row = fgetcsv($handle)) !== FALSE) {
        if (empty($row[$colCodeIndex]) || empty($row[$colSuffixIndex])) {
            echo "警告: CSVの空の行または不正な形式の行をスキップします: " . implode(',', $row) . "\n";
            continue;
        }
        $subject_suffixes[$row[$colCodeIndex]] = $row[$colSuffixIndex];
    }
    fclose($handle);
    echo "CSVファイルから " . count($subject_suffixes) . " 件の科目を読み込みました。\n";
} else {
    die("エラー: 科目リストCSVファイル '{$subjectListCsvPath}' を開けませんでした。\n");
}

// --- curriculum_list.csv からメタデータ（メジャー分類など）を読み込む ---
$metaCsvPath = __DIR__ . '/curriculum_list.csv'; // メタ情報CSVファイルへのパス

$all_meta_data = [];

if (!file_exists($metaCsvPath)) {
    echo "警告: メタ情報CSVファイル '{$metaCsvPath}' が見つかりません。一部情報が不足する可能性があります。\n";
} else {
    if (($metaHandle = fopen($metaCsvPath, "r")) !== FALSE) {
        $metaHeaders = fgetcsv($metaHandle);
        if (isset($metaHeaders[0]) && str_starts_with($metaHeaders[0], "\xEF\xBB\xBF")) {
            $metaHeaders[0] = substr($metaHeaders[0], 3);
        }
        $metaHeaders = array_map('trim', $metaHeaders);

        $metaColCodeIndex = array_search('科目コード', $metaHeaders);
        $metaMajorColIndex = array_search('tag1', $metaHeaders); 

        if ($metaColCodeIndex === false || $metaMajorColIndex === false) {
            echo "エラー: メタ情報CSVのヘッダーに '科目コード' または 'メジャー' が見つかりません。メタデータを読み込めません。\n";
        } else {
            while (($metaRow = fgetcsv($metaHandle)) !== FALSE) {
                $metaRow = array_map('trim', $metaRow);
                if (count($metaHeaders) !== count($metaRow)) {
                    error_log("警告: メタ情報CSVの行の要素数がヘッダーと一致しません。行をスキップします: " . implode(',', $metaRow));
                    continue;
                }
                if (isset($metaRow[$metaColCodeIndex]) && !empty($metaRow[$metaColCodeIndex])) {
                    $item = array_combine($metaHeaders, $metaRow);
                    $all_meta_data[$metaRow[$metaColCodeIndex]] = $item;
                } else {
                    error_log("警告: メタ情報CSVで '科目コード' がないか空であるため、この行はスキップされます: " . implode(',', $metaRow));
                }
            }
        }
        fclose($metaHandle);
        echo "メタ情報CSVファイルから " . count($all_meta_data) . " 件のメタデータを読み込みました。\n";
    } else {
        echo "警告: メタ情報CSVファイル '{$metaCsvPath}' を開けませんでした。\n";
    }
}

$all_extracted_data = []; // 抽出した全ての科目データを格納する配列

// --- cURLセッションの初期化 ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 注意: 本番環境では適切に設定すること
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

echo "--- シラバススクレイピング開始 ---\n";

// --- 各科目URLをループし、情報をスクレイピング ---
foreach ($subject_suffixes as $code => $suffix) {
    if (empty($code) || empty($suffix)) {
        echo "警告: 空の科目定義をスキップします。\n";
        continue;
    }

    // 科目コードに対応するメタデータを取得
    $currentSubjectMeta = $all_meta_data[$code] ?? [];
    $majorClassification = $currentSubjectMeta['tag1'] ?? ''; // CSVの「メジャー」列から取得

    // ★変更1: IS, XD, NCの科目かどうかを判断（詳細情報を抽出するかどうか）
    $isInfoScienceMajor = (
        $majorClassification === 'IS' ||
        $majorClassification === 'XD' ||
        $majorClassification === 'NC'
        // CSVの「メジャー」列に 'IS_XD', 'IS_NC_XD' のような複合メジャーがある場合、
        // str_contains を使うか、CSVのメジャー列を 'IS', 'XD', 'NC' の単一に絞るかで調整。
        // 今回のCSVでは IS,XD,NC が単独で使われているのでこれでOK。
    );

    $full_url = BASE_SYLLABUS_URL_PREFIX . $suffix;
    echo "アクセス中: " . $full_url . "\n";

    curl_setopt($ch, CURLOPT_URL, $full_url);
    $html_content = curl_exec($ch);

    if ($html_content === false) {
        echo "エラー: cURLエラー - " . curl_error($ch) . " (URL: " . $full_url . ")\n";
        continue; 
    }

    if (strpos($html_content, '指定されたページが見つかりませんでした') !== false || strpos($html_content, 'Not Found') !== false) {
        echo "警告: ページが見つかりません (404エラーの可能性): " . $full_url . "\n";
        continue;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html_content);
    $xpath = new DOMXPath($dom);

    // ★変更2: 抽出情報を格納する配列を初期化
    // 授業の概要と関連科目は、条件によってnullのままか、値が入る
    // 教員名は最初からnullで初期化し、常に抽出を試みる
    $extracted_info = [
        '科目名' => null,
        '時間割コード' => $code,
        '曜限'=> null,
        '単位数' => null,
        '開講区分' => null,
        '教員名' => null, // 教員名は全科目で抽出
        '授業の概要' => null, // 情報学領域のみ抽出
        '関連科目' => null,   // 情報学領域のみ抽出
    ];
    // メタデータも抽出情報に結合して含める
    $extracted_info = array_merge($extracted_info, $currentSubjectMeta);

    // --- 1つ目のタブ (id="tabs-1") から基本情報を抽出（変更なし） ---
    $tab1_content_node = $xpath->query('//div[@id="tabs-1"]')->item(0);

    if ($tab1_content_node) {
        $subject_name_node = $xpath->query('.//th[contains(text(), "開講科目名")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($subject_name_node) { $extracted_info['科目名'] = trim($subject_name_node->textContent); }

        $day_period_node = $xpath->query('.//th[contains(., "曜限")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($day_period_node) { $extracted_info['曜限'] = trim($day_period_node->textContent); }

        $credit_node = $xpath->query('.//th[contains(text(), "単位数")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($credit_node) {
            $credit_text = trim($credit_node->textContent);
            preg_match('/(\d+(\.\d+)?)/', $credit_text, $matches);
            $extracted_info['単位数'] = isset($matches[1]) ? (float)$matches[1] : null;
        }
        
        $offering_division_node = $xpath->query('.//th[contains(., "開講区分")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($offering_division_node) { $extracted_info['開講区分'] = trim($offering_division_node->textContent); }

        // ★変更3: 教員名の抽出ロジックはここに置き、常に実行される
        $instructor_name_node = $xpath->query('.//th[contains(., "教員名")]/following-sibling::td[@class="syllabus-top-info syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($instructor_name_node) {
            $extracted_info['教員名'] = trim($instructor_name_node->textContent);
        }

    } else {
        echo "警告: タブ1（基本情報）のコンテンツが見つかりませんでした。(URL: " . $full_url . ")\n";
    }

    // --- 2つ目のタブ (id="tabs-2") から詳細情報を抽出（条件付き） ---
    $tab2_content_node = $xpath->query('//div[@id="tabs-2"]')->item(0);

    // ★変更4: IS, XD, NCメジャーの科目のみ、概要と関連科目を抽出
    if ($tab2_content_node && $isInfoScienceMajor) { 
        $overview_aim_node = $xpath->query('.//th[contains(text(), "授業の概要・ねらい")]/following-sibling::td[@class="syllabus-break-word"]', $tab2_content_node)->item(0);
        if ($overview_aim_node) {
            $full_text = trim(strip_tags($overview_aim_node->textContent));
            $extracted_info['授業の概要'] = $full_text; 
        }

        $related_subjects_node = $xpath->query('.//th[contains(text(), "履修を推奨する関連科目")]/following-sibling::td[@class="syllabus-break-word"]', $tab2_content_node)->item(0);
        if ($related_subjects_node) {
            $extracted_info['関連科目'] = trim(strip_tags($related_subjects_node->textContent));
        }
    } else if ($tab2_content_node && !$isInfoScienceMajor) {
        // 情報学領域外の科目の場合、詳細抽出はスキップし、ログ出力
        echo "情報: タブ2のコンテンツは情報学領域外（IS/XD/NC以外）の科目のため詳細抽出をスキップしました。(URL: " . $full_url . ")\n";
        // extracted_info['授業の概要'] と extracted_info['関連科目'] はnullのまま
    } else {
        echo "警告: タブ2（授業の概要・ねらいなど）のコンテンツが見つかりませんでした。(URL: " . $full_url . ")\n";
    }

    // --- 抽出したデータを全体の配列に追加は変更なし ---
    $all_extracted_data[] = $extracted_info;
    echo "  -> 抽出完了: " . ($extracted_info['科目名'] ?? '不明な科目') . " (コード: " . ($extracted_info['時間割コード'] ?? '不明') . ")\n";
    sleep(1);
} // --- foreach ループの終了 ---

curl_close($ch);

echo "\n--- 全てのスクレイピングが完了しました ---\n";

// --- Firebaseへの保存は変更なし ---
$serviceAccountPath = __DIR__ . '/keys/b3app-b0c29-firebase-adminsdk-fbsvc-4d2307d6ce.json'; 

try {
    $factory = (new Factory)
        ->withServiceAccount($serviceAccountPath)
        ->withDatabaseUri('https://b3app-b0c29-default-rtdb.firebaseio.com'); 
    $database = $factory->createDatabase();
    $databasePath = 'syllabus_subjects'; 

    echo "\n--- Firebaseへのデータ保存を開始します ---\n";

    foreach ($all_extracted_data as $subject_data) {
        if (!empty($subject_data['時間割コード'])) {
            $subject_code = $subject_data['時間割コード'];
            $database->getReference($databasePath . '/' . $subject_code)->set($subject_data);
            echo "保存完了: 科目コード " . $subject_code . " - " . ($subject_data['科目名'] ?? '不明') . "\n";
        } else {
            echo "警告: 時間割コードがないため、この科目はFirebaseに保存できませんでした。\n";
        }
    }
    echo "\n--- Firebaseへのデータ保存が完了しました ---\n";

} catch (Exception $e) {
    echo "\nFirebaseへのデータ保存中にエラーが発生しました: " . $e->getMessage() . "\n";
}

?>
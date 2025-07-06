<?php

// Composerのオートロードファイルを読み込み（Firebaseを使うため必須）
// Firebase SDKをインストール済みであることを確認してください
require __DIR__.'/vendor/autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount; // ★この行も必要です！

// --- URLの基本設定 ---
// 和歌山大学シラバスの共通プレフィックス
const BASE_SYLLABUS_URL_PREFIX = 'https://web.wakayama-u.ac.jp/syllabus/S1/S1_';

// --- スクレイピング対象科目リストをCSVから読み込む ---
$subjectListCsvPath = __DIR__ . '/subject_list_for_scraping.csv'; // 科目リストCSVファイルのパス

$subject_suffixes = []; // 科目リストを格納する配列を初期化

if (!file_exists($subjectListCsvPath)) {
    die("エラー: 科目リストCSVファイル '{$subjectListCsvPath}' が見つかりません。\n");
}

if (($handle = fopen($subjectListCsvPath, "r")) !== FALSE) {
    // BOM (Byte Order Mark) を処理しながらヘッダー行を読み込む
    $headers = fgetcsv($handle);
    if (isset($headers[0]) && str_starts_with($headers[0], "\xEF\xBB\xBF")) {
        $headers[0] = substr($headers[0], 3);
    }
    $headers = array_map('trim', $headers); // ヘッダーの空白をトリム

    // ヘッダーに '科目コード' と 'URLサフィックス' が含まれていることを確認
    $colCodeIndex = array_search('科目コード', $headers);
    $colSuffixIndex = array_search('URLサフィックス', $headers);

    if ($colCodeIndex === false || $colSuffixIndex === false) {
        die("エラー: 科目リストCSVのヘッダーに '科目コード' または 'URLサフィックス' が見つかりません。\n");
    }

    while (($row = fgetcsv($handle)) !== FALSE) {
        // 行が空でないことを確認し、十分な要素があるかチェック
        if (empty($row[$colCodeIndex]) || empty($row[$colSuffixIndex])) {
             // 空行や不正な行を警告してスキップ。exit; はしない。
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


$all_extracted_data = []; // 抽出した全ての科目データを格納する配列

// --- cURLセッションの初期化（ループの外で一度だけ行います） ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // ページ内容を文字列として取得
// !!! 注意: SSL証明書エラー回避のための設定（本番環境では推奨されません） !!!
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
// ---

echo "--- シラバススクレイピング開始 ---\n";

// --- ここから foreach ループを開始する ---
// $subject_suffixesの「キー」が$codeに、「値」が$suffixにそれぞれ格納されます。
foreach ($subject_suffixes as $code => $suffix) {
    // 完全なシラバスURLを生成
    $full_url = BASE_SYLLABUS_URL_PREFIX . $suffix;

    echo "アクセス中: " . $full_url . "\n"; // アクセス中のURLを表示

    curl_setopt($ch, CURLOPT_URL, $full_url); // ループ内でURLをセット
    $html_content = curl_exec($ch);

    if ($html_content === false) {
        echo "エラー: cURLエラー - " . curl_error($ch) . " (URL: " . $full_url . ")\n";
        // ★致命的なエラーでなければ次の科目に進むため continue; に変更
        continue; 
    }

    // HTMLコンテンツが取得できたか確認（例: 404ページでないか）
    if (strpos($html_content, '指定されたページは見つかりませんでした') !== false || strpos($html_content, 'Not Found') !== false) {
        echo "警告: ページが見つかりません (404エラーの可能性): " . $full_url . "\n";
        // ★次の科目に進むため continue; に変更
        continue;
    }

    // DOMDocumentでHTMLを解析
    $dom = new DOMDocument();
    @$dom->loadHTML($html_content); // @でHTMLエラーを抑制
    $xpath = new DOMXPath($dom);

    $extracted_info = [
        '科目名' => null,
        '時間割コード' => $code, // ループのキーから科目コードを保存
        '曜限'=> null,
        '単位数' => null,
        '授業の概要' => null,
        '関連科目' => null,
        '開講区分' => null, // ★追加: 開講区分
    ];

    // --- 1つ目のタブ (id="tabs-1") から情報を抽出 ---
    $tab1_content_node = $xpath->query('//div[@id="tabs-1"]')->item(0);

    if ($tab1_content_node) {
        // 科目名: 「開講科目名」の<th>の隣の<td>
        $subject_name_node = $xpath->query('.//th[contains(text(), "開講科目名")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($subject_name_node) {
            $extracted_info['科目名'] = trim($subject_name_node->textContent);
        }
        // 時間割コード（テーブルからも取得可能だが、既に$codeで持っている）
        $code_from_table_node = $xpath->query('.//th[contains(text(), "時間割コード")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($code_from_table_node) {
            // $extracted_info['時間割コード'] = trim($code_from_table_node->textContent); // こちらで上書きしても良い
        }

        // 曜限
        $day_period_node = $xpath->query('.//th[contains(text(), "曜限")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($day_period_node) {
            $extracted_info['曜限'] = trim($day_period_node->textContent);
        }

        // 単位数: 「単位数」の<th>の隣の<td>
        $credit_node = $xpath->query('.//th[contains(text(), "単位数")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($credit_node) {
            $credit_text = trim($credit_node->textContent);
            preg_match('/(\d+(\.\d+)?)/', $credit_text, $matches);
            $extracted_info['単位数'] = isset($matches[1]) ? (float)$matches[1] : null;
        }
        
        // ★追加：開講区分もここで抽出
        $offering_division_node = $xpath->query('.//th[contains(text(), "開講区分")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($offering_division_node) {
            $extracted_info['開講区分'] = trim($offering_division_node->textContent);
        }

    } else {
        echo "警告: タブ1（基本情報）のコンテンツが見つかりませんでした。(URL: " . $full_url . ")\n";
    }

    // --- 2つ目のタブ (id="tabs-2") から情報を抽出 ---
    $tab2_content_node = $xpath->query('//div[@id="tabs-2"]')->item(0);

    if ($tab2_content_node) {
        // 授業の概要と狙い: 「授業の概要・ねらい」の<th>の隣の<td>
        $overview_aim_node = $xpath->query('.//th[contains(text(), "授業の概要・ねらい")]/following-sibling::td[@class="syllabus-break-word"]', $tab2_content_node)->item(0);
        if ($overview_aim_node) {
            $full_text = trim(strip_tags($overview_aim_node->textContent));

            // 日本語対応のためmb_strposを使用。`php.ini`で`mbstring`を有効にする必要があります。
            // 「狙い」は抽出しない方針なので、この分割ロジックは削除またはコメントアウト
            // $aim_position = mb_strpos($full_text, '本授業の目標は', 0, 'UTF-8');
            // if ($aim_position !== false) {
            //     $extracted_info['授業の概要'] = trim(mb_substr($full_text, 0, $aim_position, 'UTF-8'));
            // } else {
                 $extracted_info['授業の概要'] = $full_text; // 「狙い」を抽出しないなら、全文を概要にする
            // }
        }

        // 関連科目: 「履修を推奨する関連科目」の<th>の隣の<td>
        $related_subjects_node = $xpath->query('.//th[contains(text(), "履修を推奨する関連科目")]/following-sibling::td[@class="syllabus-break-word"]', $tab2_content_node)->item(0);
        if ($related_subjects_node) {
            $extracted_info['関連科目'] = trim(strip_tags($related_subjects_node->textContent));
        }
    } else {
        echo "警告: タブ2（授業の概要・ねらいなど）のコンテンツが見つかりませんでした。(URL: " . $full_url . ")\n";
    }

    // --- 抽出したデータを全体の配列に追加 ---
    $all_extracted_data[] = $extracted_info;
    echo "  -> 抽出完了: " . ($extracted_info['科目名'] ?? '不明な科目') . " (コード: " . $extracted_info['時間割コード'] . ")\n"; // 抽出した時間割コードを表示

    sleep(1); // サーバーに負荷をかけないよう1秒待機
} // --- foreach ループはここで終わる！ ---

// ★不要な二重の curl_close($ch); を削除します
curl_close($ch);

echo "\n--- 全てのスクレイピングが完了しました ---\n";

// ★CSV保存のロジックは削除またはコメントアウトします。
// if (!empty($all_extracted_data)) { ... }


// --- Firebaseへの保存 ---
// ★この部分のコメントアウトをすべて解除し、パスを設定します。
// ★Firebase ConsoleでダウンロードしたJSONファイルの正確なパスに修正してください。
$serviceAccountPath = __DIR__ . '/keys/b3app-b0c29-firebase-adminsdk-fbsvc-4d2307d6ce.json'; 

try {
    // Factory と Database インスタンスの作成
    $factory = (new Factory)
        ->withServiceAccount($serviceAccountPath)
        ->withDatabaseUri('https://b3app-b0c29-default-rtdb.firebaseio.com'); 
    $database = $factory->createDatabase();
    $databasePath = 'syllabus_subjects'; // Firebase Realtime Databaseに保存するルートパス

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

// 抽出された全データを表示（確認用）
//print_r($all_extracted_data);

?>
<?php

// --- URLの基本設定と取得したい科目リスト ---

// 和歌山大学シラバスの共通プレフィックス
// この部分は、すべてのシラバスURLで共通している始まりの部分です
const BASE_SYLLABUS_URL_PREFIX = 'https://web.wakayama-u.ac.jp/syllabus/S1/S1_'; // あなたの指定通り 'S1_' で終わる

// スクレイピングしたい科目ごとのユニークなURLサフィックス（末尾の部分）
// ここに、**あなたが取得したい全ての科目について、正確な科目コードとそれに対応するURLの末尾部分**を追加してください。
// キーが科目コード、値がその科目のURLの 'S1_' 以降の文字列全体です。
$subject_suffixes = [
    'S1408230_S1' => 'S1408230_S1_ja_JP_70.html',   // 例：HCI基礎の科目コードと対応するURLの末尾
    'S1408240_S1' => 'S1408240_S1_ja_JP_111.html', // 例：HCI応用の科目コードと対応するURLの末尾
    'S1408260_S1' => 'S1408260_S1_ja_JP_4.html', // 計算機システム・OS
    'S1405860_S1' => 'S1405860_S1_ja_JP_6.html', //情報システム実験
    'S1408480_S1' => 'S1408480_S1_ja_JP_8.html', //情報デザイン
    'S1408390_S1' => 'S1408390_S1_ja_JP_10.html', //情報学セミナー1
    'S1405850_S1' => 'S1405850_S1_ja_JP_12.html', //データ構造とアルゴリズム
    'S1407250_S1' => 'S1407250_S1_ja_JP_80.html', //デザイン企画論A
    'S1407260_S1' => 'S1407260_S1_ja_JP_118.html', //デザイン企画論B
    '' => '',
    // 'Sxxxxxxx' => 'Sxxxxxxx_S1_ja_JP_YYY.html', // 取得したい他の科目を追加
];

$all_extracted_data = []; // 抽出した全ての科目データを格納する配列

// --- cURLセッションの初期化（ループの外で一度だけ行います） ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // ページ内容を文字列として取得
// !!! 注意: SSL証明書エラー回避のための設定（本番環境では非推奨） !!!
// これは一時的な対処です。安全な環境ではphp.iniでcacert.pemを設定することを強く推奨します。
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
// ---

echo "--- シラバススクレイピング開始 ---\n";

// --- ここから foreach ループを開始する ---
// $subject_suffixesの「キー」が$codeに、「値」が$suffixにそれぞれ格納されます。
foreach ($subject_suffixes as $code => $suffix) {
    // 完全なシラバスURLを生成
    // 例: 'https://web.wakayama-u.ac.jp/syllabus/S1/S1_' + 'S1408240_S1_ja_JP_111.html'
    $full_url = BASE_SYLLABUS_URL_PREFIX . $suffix;

    echo "アクセス中: " . $full_url . "\n"; // アクセス中のURLを表示

    curl_setopt($ch, CURLOPT_URL, $full_url); // ループ内でURLをセット
    $html_content = curl_exec($ch);

    // cURLセッションの終了は、ループの最後にまとめて行います。
    // ここで curl_close($ch); は不要です。
    // if ($html_content === false) { ... } の中にある exit; も注意が必要です。
    // エラー時にexit;するとループが中断されます。すべてのエラーを把握したいならcontinue;が適切です。
    // 今回はとりあえずexit;のままにしておきます。

    if ($html_content === false) {
        echo "エラー: cURLエラー - " . curl_error($ch) . " (URL: " . $full_url . ")\n";
        exit; // エラーが発生したらスクリプト全体を終了
    }

    // HTMLコンテンツが取得できたか確認（例: 404ページでないか）
    if (strpos($html_content, '指定されたページは見つかりませんでした') !== false || strpos($html_content, 'Not Found') !== false) {
        echo "警告: ページが見つかりません (404エラーの可能性): " . $full_url . "\n";
        // ここも exit; だとループが止まるので、次の科目に進むなら continue; に変更
        // 今回はとりあえず exit; のままにしておきます
        exit;
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
    ];

    // --- 1つ目のタブ (id="tabs-1") から情報を抽出 ---
    $tab1_content_node = $xpath->query('//div[@id="tabs-1"]')->item(0);

    if ($tab1_content_node) {
        // 科目名: 「開講科目名」の<th>の隣の<td>
        $subject_name_node = $xpath->query('.//th[contains(text(), "開講科目名")]/following-sibling::td[@class="syllabus-break-word"]', $tab1_content_node)->item(0);
        if ($subject_name_node) {
            $extracted_info['科目名'] = trim($subject_name_node->textContent);
        }
        // ここに単位数などのtab1からの抽出ロジックも続きます
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
            $aim_position = mb_strpos($full_text, '本授業の目標は', 0, 'UTF-8');
            if ($aim_position !== false) {
                $extracted_info['授業の概要'] = trim(mb_substr($full_text, 0, $aim_position, 'UTF-8'));
                //$extracted_info['狙い'] = trim(mb_substr($full_text, $aim_position, null, 'UTF-8'));
            } else {
                $extracted_info['授業の概要'] = $full_text;
                //$extracted_info['狙い'] = null;
            }
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

curl_close($ch); // ループ終了後にcURLセッションを閉じる

// ...（ループはここで終わる）...

curl_close($ch); // ループ終了後にcURLセッションを閉じる

echo "\n--- 全てのスクレイピングが完了しました ---\n";

// --- ここからCSVファイルへの保存処理を追加 ---

if (!empty($all_extracted_data)) {
    $csv_file_path = __DIR__ . '/syllabus_data.csv';
    
    // ファイルを書き込みモードで開く
    $file = fopen($csv_file_path, 'w');

    if ($file) {
        // 1. 文字化け対策（BOMをファイルの先頭に書き込む）
        // これにより、日本語を含むCSVをExcelで開いた際の文字化けを防ぎます。
        fputs($file, "\xEF\xBB\xBF");

        // 2. ヘッダー行を書き込む
        // データのキー（連想配列のキー）を取得してヘッダーとして使用
        $header = array_keys($all_extracted_data[0]);
        fputcsv($file, $header);

        // 3. データ行を書き込む
        // 全ての科目データをループで1行ずつ書き込む
        foreach ($all_extracted_data as $subject_data) {
            fputcsv($file, $subject_data);
        }

        // 4. ファイルを閉じる
        fclose($file);

        echo "データを " . $csv_file_path . " に保存しました。\n";

    } else {
        echo "エラー: ファイルの書き出しに失敗しました。\n";
    }

} else {
    echo "抽出されたデータがありませんでした。ファイルは作成されません。\n";
}

// 抽出された全データを表示（確認用）
print_r($all_extracted_data);



?>
<?php

// Composerのオートロードファイルを読み込み（Firebaseを使う場合）
require __DIR__.'/vendor/autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

// --- URLの基本設定と取得したい科目リスト ---

// 和歌山大学シラバスの共通プレフィックス
// この部分は、すべてのシラバスURLで共通している始まりの部分です
const BASE_SYLLABUS_URL_PREFIX = 'https://web.wakayama-u.ac.jp/syllabus/S1/S1_';

// スクレイピングしたい科目ごとのユニークなURLサフィックス（末尾の部分）
// ここに、**あなたが取得したい全ての科目について、正確な科目コードとそれに対応するURLの末尾部分**を追加してください。
// キーが科目コード、値がその科目のURLの 'S1_' 以降の文字列全体です。
$subject_suffixes = [
    //ISカリキュラム左から順に
    //3セメスタ
    'S1407400' => 'S1407400_S1_ja_JP_95.html',//ウェブデザイン演習A
    'S1407410' => 'S1407410_S1_ja_JP_133.html',//ウェブデザイン演習Ｂ
    'S1408230' => 'S1408230_S1_ja_JP_70.html',//ＨＣＩ基礎
    'S1408240' => 'S1408240_S1_ja_JP_111.html',//（人数制限科目）ＨＣＩ応用
    'S1407610' => 'S1407610_S1_ja_JP_89.html',//（人数制限科目）データサイエンス概論１
    'S1407620' => 'S1407620_S1_ja_JP_128.html',//（人数制限科目）データサイエンス概論２
    'S1408250' => 'S1408250_S1_ja_JP_109.html',//情報ネットワーク１
    'S1405850' => 'S1405850_S1_ja_JP_12.html',
    'S1408260' => 'S1408260_S1_ja_JP_4.html',
    //4セメスタ
    'S1408270' => 'S1408270_S1_ja_JP_158.html',
    'S1400910' => 'S1400910_S1_ja_JP_148.html',
    'S1407460' => 'S1407460_S1_ja_JP_259.html',
    'S1408280' => 'S1408280_S1_ja_JP_226.html',
    'S1408290' => 'S1408290_S1_ja_JP_239.html',
    'S1408300' => 'S1408300_S1_ja_JP_161.html',
    'S1407210' => 'S1407210_S1_ja_JP_218.html',
    'S1407220' => 'S1407220_S1_ja_JP_255.html',
    'S1408310' => 'S1408310_S1_ja_JP_152.html',
    'S1408320' => 'S1408320_S1_ja_JP_225.html',
    'S1408330' => 'S1408330_S1_ja_JP_264.html',
    'S1401370' => 'S1401370_S1_ja_JP_147.html',
    'S1401580' => 'S1401580_S1_ja_JP_168.html',
    //5セメスタ
    'S1403290' => 'S1403290_S1_ja_JP_98.html',
    'S1408340' => 'S1408340_S1_ja_JP_135.html',
    'S1408350' => 'S1408350_S1_ja_JP_22.html',
    'S1400580' => 'S1400580_S1_ja_JP_19.html',
    'S1408360' => 'S1408360_S1_ja_JP_96.html',
    'S1405860' => 'S1405860_S1_ja_JP_6.html',
    'S1408390' => 'S1408390_S1_ja_JP_10.html',
    //6セメスタ
    'S1408400' => 'S1408400_S1_ja_JP_223.html',
    'S1408410' => 'S1408410_S1_ja_JP_261.html',
    'S1407700' => 'S1407700_S1_ja_JP_164.html',
    'S1408420' => 'S1408420_S1_ja_JP_222.html',
    'S1408430' => 'S1408430_S1_ja_JP_260.html',
    'S1408440' => 'S1408440_S1_ja_JP_186.html',

    //NCカリキュラム
    //3セメスタ
    'S1401870' => 'S1401870_S1_ja_JP_85.html',
    'S1408450' => 'S1408450_S1_ja_JP_145.html',
    //4セメスタ
    'S1408460' => 'S1408460_S1_ja_JP_199.html',
    'S1408470' => 'S1408470_S1_ja_JP_237.html',
    //5セメスタ
    'S1405190' => 'S1405190_S1_ja_JP_24.html',
    'S1405150' => 'S1405150_S1_ja_JP_124.html',
    'S1406210'=> 'S1406210_S1_ja_JP_40.html',
    //6セメスタ
    'S1405160' => 'S1405160_S1_ja_JP_163.html',
    'S1405740' => 'S1405740_S1_ja_JP_162.html',

    //XDカリキュラム
    //3セメスタ
    'S1408480' => 'S1408480_S1_ja_JP_8.html',
    //4セメスタ
    'S1408490' => 'S1408490_S1_ja_JP_229.html',
    'S1408500' => 'S1408500_S1_ja_JP_266.html',
    'S1401120' => 'S1401120_S1_ja_JP_220.html',
    //5セメスタ
    'S1408510' => 'S1408510_S1_ja_JP_29.html',
    'S1402320' => 'S1402320_S1_ja_JP_123.html',
    'S1407250' => 'S1407250_S1_ja_JP_80.html',
    'S1407260' => 'S1407260_S1_ja_JP_118.html',
    'S1408520' => 'S1408520_S1_ja_JP_101.html',
    //6セメスタ
    'S1408530' => 'S1408530_S1_ja_JP_247.html',
    'S1406860' => 'S1406860_S1_ja_JP_196.html',
    'S1408540' => 'S1408540_S1_ja_JP_252.html',
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
//print_r($all_extracted_data);

// --- Firebaseへの保存 ---
// ★この部分のコメントアウトをすべて解除し、パスを設定します。
// ★Firebase ConsoleでダウンロードしたJSONファイルの正確なパスに修正してください。
$serviceAccountPath = __DIR__ . '/keys/b3app-b0c29-firebase-adminsdk-fbsvc-4d2307d6ce.json';
try {
    // Factory と Database インスタンスの作成
    $factory = (new Factory)
        ->withServiceAccount($serviceAccountPath)
        ->withDatabaseUri('https://b3app-b0c29-default-rtdb.firebaseio.com'); // ★この行を追加！
    $database = $factory->createDatabase();
    $databasePath = 'syllabus_subjects'; // Firebase Realtime Databaseに保存するルートパス

    echo "\n--- Firebaseへのデータ保存を開始します ---\n";

    foreach ($all_extracted_data as $subject_data) {
        if (!empty($subject_data['時間割コード'])) {
            $subject_code = $subject_data['時間割コード'];
            // 科目コードをキーとして、その科目の全データを保存
            // 例: /syllabus_subjects/S1408230 にデータが保存されます
            $database->getReference($databasePath . '/' . $subject_code)->set($subject_data);
            echo "保存完了: 科目コード " . $subject_code . " - " . ($subject_data['科目名'] ?? '不明') . "\n";
        } else {
            echo "警告: 時間割コードがないため、この科目はFirebaseに保存できませんでした。\n";
            // デバッグのため、時間割コードがない場合のデータ内容も表示できます
            // print_r($subject_data);
        }
    }
    echo "\n--- Firebaseへのデータ保存が完了しました ---\n";

} catch (Exception $e) {
    echo "\nFirebaseへのデータ保存中にエラーが発生しました: " . $e->getMessage() . "\n";
}

?>
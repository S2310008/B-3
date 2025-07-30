<?php

require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

// --- 設定 ---
$serviceAccountPath = __DIR__ . '/keys/b3app-b0c29-firebase-adminsdk-fbsvc-4d2307d6ce.json'; // ★あなたのJSONファイル名に置き換える！
$databaseUrl = 'https://b3app-b0c29-default-rtdb.firebaseio.com/'; // ★あなたのデータベースURLに置き換える！

// マップ情報CSVファイルへのパス
$mapCsvPath = __DIR__ . '/curriculum_map.csv'; // ★CSVファイル名を指定

// Firebaseに保存するパス
$firebaseMapPath = 'curriculum_map_positions'; 

// --- CSVからマップデータを読み込む ---
$mapData = [];
if (!file_exists($mapCsvPath)) {
    die("エラー: マップデータCSVファイル '{$mapCsvPath}' が見つかりません。作成してください。\n");
}

if (($handle = fopen($mapCsvPath, "r")) !== FALSE) {
    $headers = fgetcsv($handle); 
    if (isset($headers[0]) && str_starts_with($headers[0], "\xEF\xBB\xBF")) {
        $headers[0] = substr($headers[0], 3);
    }
    $headers = array_map('trim', $headers);

    $colCodeIndex = array_search('科目コード', $headers);
    if ($colCodeIndex === false) {
        die("エラー: マップデータCSVのヘッダーに '科目コード' が見つかりません。データを読み込めません。\n");
    }

    while (($row = fgetcsv($handle)) !== FALSE) {
        $row = array_map('trim', $row);
        if (count($headers) !== count($row)) {
            error_log("警告: CSVの行の要素数がヘッダーと一致しません。行をスキップします: " . implode(',', $row));
            continue;
        }
        if (isset($row[$colCodeIndex]) && !empty($row[$colCodeIndex])) {
            $item = array_combine($headers, $row);
            $mapData[$item['科目コード']] = $item;
        } else {
            error_log("警告: '科目コード' がないか空であるため、この行はスキップされます: " . implode(',', $row));
        }
    }
    fclose($handle);
    echo "CSVファイルから " . count($mapData) . " 件のマップデータを読み込みました。\n";
} else {
    die("エラー: マップデータCSVファイル '{$mapCsvPath}' を開けませんでした。\n");
}

// --- Firebaseへの保存 ---
try {
    $factory = (new Factory)
        ->withServiceAccount($serviceAccountPath)
        ->withDatabaseUri($databaseUrl);

    $database = $factory->createDatabase();

    echo "\n--- カリキュラムマップデータのFirebaseへの保存を開始します ---\n";
    foreach ($mapData as $code => $data) {
        $database->getReference($firebaseMapPath . '/' . $code)->set($data);
        echo "保存完了: マップデータ 科目コード " . $code . "\n";
    }
    echo "\n--- カリキュラムマップデータのFirebaseへの保存が完了しました ---\n";

} catch (Exception | Kreait\Firebase\Exception\FirebaseException $e) {
    echo "\nFirebaseへのマップデータ保存中にエラーが発生しました: " . $e->getMessage() . "\n";
}

?>
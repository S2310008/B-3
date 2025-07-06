<?php

// Composerのオートロードファイルを読み込み
require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

// --- 設定 ---
// Firebase サービスアカウントキーへのパス (try.phpと同じパス)
$serviceAccountPath = __DIR__ . '/keys/b3app-b0c29-firebase-adminsdk-fbsvc-4d2307d6ce.json'; // ★あなたのJSONファイル名に置き換える！

// Realtime DatabaseのURL (try.phpと同じURL)
$databaseUrl = 'https://b3app-b0c29-default-rtdb.firebaseio.com/'; // ★あなたのデータベースURLに置き換える！

// メタ情報CSVファイルへのパス (先ほど作成したCSVファイル)
$metaCsvPath = __DIR__ . '/curriculum.csv';

// Firebaseに保存するパス (新しいパスを設定)
$firebaseMetaPath = 'curriculum_meta';

// --- CSVからメタデータを読み込む ---
$metaData = [];
if (!file_exists($metaCsvPath)) {
    die("エラー: メタ情報CSVファイル '{$metaCsvPath}' が見つかりません。作成してください。\n");
}

if (($handle = fopen($metaCsvPath, "r")) !== FALSE) {
    // ヘッダー行を読み込む
    $headers = fgetcsv($handle); 

    // BOM (Byte Order Mark) がある場合、最初のヘッダーから削除
    if (isset($headers[0]) && str_starts_with($headers[0], "\xEF\xBB\xBF")) {
        $headers[0] = substr($headers[0], 3);
    }
    // ヘッダーの空白をトリム
    $headers = array_map('trim', $headers);

    // ヘッダーに '科目コード' が含まれていることを確認
    $colCodeIndex = array_search('科目コード', $headers);
    if ($colCodeIndex === false) {
        die("エラー: メタ情報CSVのヘッダーに '科目コード' が見つかりません。メタデータを読み込めません。\n");
    }

    while (($row = fgetcsv($handle)) !== FALSE) {
        $row = array_map('trim', $row); // 各要素の空白をトリム

        // ヘッダーと行の要素数が一致しない場合、警告してスキップ
        if (count($headers) !== count($row)) {
            error_log("警告: CSVの行の要素数がヘッダーと一致しません。行をスキップします: " . implode(',', $row));
            continue;
        }

        // '科目コード' が存在し、空でないことを確認
        if (isset($row[$colCodeIndex]) && !empty($row[$colCodeIndex])) {
            $item = array_combine($headers, $row); // ヘッダーをキーとして連想配列を作成
            $metaData[$item['科目コード']] = $item; // 科目コードをキーとする
        } else {
            error_log("警告: '科目コード' がないか空であるため、この行はスキップされます: " . implode(',', $row));
        }
    }
    fclose($handle);
    echo "CSVファイルから " . count($metaData) . " 件のメタデータを読み込みました。\n";
} else {
    die("エラー: メタ情報CSVファイル '{$metaCsvPath}' を開けませんでした。\n");
}

// --- Firebaseへの保存 ---
try {
    $factory = (new Factory)
        ->withServiceAccount($serviceAccountPath)
        ->withDatabaseUri($databaseUrl);

    $database = $factory->createDatabase();

    echo "\n--- カリキュラムメタデータのFirebaseへの保存を開始します ---\n";
    foreach ($metaData as $code => $data) {
        // 特定の科目コードのメタデータを保存
        $database->getReference($firebaseMetaPath . '/' . $code)->set($data);
        echo "保存完了: メタデータ 科目コード " . $code . "\n";
    }
    echo "\n--- カリキュラムメタデータのFirebaseへの保存が完了しました ---\n";

} catch (Exception $e) {
    echo "\nFirebaseへのメタデータ保存中にエラーが発生しました: " . $e->getMessage() . "\n";
}

?>
<?php
ini_set('display_errors', 1); // エラー表示をオン（デバッグ用）
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

function get_all_subjects_from_firebase(): array
{
    // サービスアカウントキーのパス (ダウンロードしたJSONファイルへのパス)
    // ★ 'your-project-id-firebase-adminsdk-xxxxx-xxxxxxxxxx.json' をあなたの正確なファイル名に置き換える
    $serviceAccountPath = __DIR__ . '/keys/b3app-b0c29-firebase-adminsdk-fbsvc-4d2307d6ce.json'; // 正しいパスとファイル名に変更

    // Realtime DatabaseのURL (Firebaseコンソールから取得)
    $databaseUrl = 'https://b3app-b0c29-default-rtdb.firebaseio.com/'; // あなたのFirebaseプロジェクトIDに置き換えてください

    try {
        // Firebase Factory を初期化
        // ServiceAccount クラスを使用するために use Kreait\Firebase\ServiceAccount; が必要
        $factory = (new Factory)
            ->withServiceAccount($serviceAccountPath)
            ->withDatabaseUri($databaseUrl);

        // Realtime Database サービスを取得
        $database = $factory->createDatabase();

        // 'subjects' パスから全てのデータを取得（Firebaseのデータ構造に合わせて調整）
        // ここは 'syllabus_subjects' で合っているはず
        $reference = $database->getReference('syllabus_subjects'); // ★'subjects' から 'syllabus_subjects' に変更済みであることを確認
        $snapshot = $reference->getSnapshot();

        // データを配列として取得
        $allSubjects = $snapshot->getValue();

        // Firebaseから取得したデータがnullの場合、空の配列を返す
        return $allSubjects ?? [];

    } catch (Exception $e) {
        // エラーハンドリング（ログに記録するなど）
        error_log('Firebaseデータ取得エラー: ' . $e->getMessage());
        // ★デバッグのため、エラーを画面にも出力
        echo "Firebaseデータ取得エラー: " . $e->getMessage() . "<br>";
        return []; // エラー時は空の配列を返す
    }
}
?>
<?php require_once 'read_csv.php'; // CSV読み込み部品をインポート ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>和歌山大学シラバス</title>
    <style>body { font-family: sans-serif; }</style>
</head>
<body>
    <h1>科目一覧</h1>
    <ul>
        <?php
        $all_subjects = get_all_subjects_from_csv();
        if (empty($all_subjects)) {
            echo "<p>表示する科目がありません。CSVファイルを確認してください。</p>";
        } else {
            foreach ($all_subjects as $code => $subject_data) {
                // detail.phpに時間割コードをパラメータとして渡すリンクを生成
                echo '<li><a href="detail.php?code=' . urlencode($code) . '">' . htmlspecialchars($subject_data['科目名']) . '</a></li>';
            }
        }
        ?>
    </ul>
</body>
</html>
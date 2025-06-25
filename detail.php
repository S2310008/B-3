<?php require_once 'read_csv.php'; // CSV読み込み部品をインポート ?>
<?php
// URLから科目コードを取得 (?code=S1408230 の部分)
$current_code = $_GET['code'] ?? '';

// 全ての科目データをCSVから取得
$all_subjects = get_all_subjects_from_csv();

// 該当する科目データを取得
$subject = $all_subjects[$current_code] ?? null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($subject['科目名'] ?? '科目が見つかりません'); ?></title>
    <style>
        body { font-family: sans-serif; }
        .container { max-width: 800px; margin: auto; padding: 20px; }
        .detail-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .detail-table th, .detail-table td { border: 1px solid #ddd; padding: 12px; text-align: left; vertical-align: top; }
        .detail-table th { background-color: #f2f2f2; width: 150px; }
        .overview { white-space: pre-wrap; /* 概要の改行をそのまま表示 */ }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($subject): // 科目が見つかった場合 ?>
            <h1><?php echo htmlspecialchars($subject['科目名']); ?></h1>
            <table class="detail-table">
                <tr><th>時間割コード</th><td><?php echo htmlspecialchars($subject['時間割コード']); ?></td></tr>
                <tr><th>曜限</th><td><?php echo htmlspecialchars($subject['曜限']); ?></td></tr>
                <tr><th>単位数</th><td><?php echo htmlspecialchars($subject['単位数']); ?></td></tr>
                <tr><th>関連科目</th><td><?php echo htmlspecialchars($subject['関連科目']); ?></td></tr>
                <tr><th class="overview">授業の概要</th><td class="overview"><?php echo htmlspecialchars($subject['授業の概要']); ?></td></tr>
            </table>
            <p style="margin-top: 20px;"><a href="index.php">← 一覧に戻る</a></p>

        <?php else: // 該当する科目が見つからなかった場合 ?>
            <h1>科目が見つかりません</h1>
            <p>指定された時間割コードの科目は存在しません。</p>
            <p><a href="index.php">← 一覧に戻る</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
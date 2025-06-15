<?php
// cURLセッションの初期化
$ch = curl_init();
// データを抽出したいページのURLを指定
curl_setopt($ch, CURLOPT_URL, "http://xn--dkqp0gri91r38rn1wmlurtz.com/");
// 文字列で取得するように設定
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// URLの情報を取得する指示
$result = curl_exec($ch);
// cURLセッションの終了
curl_close($ch);

// 以下でタイトルを抽出
// ここを修正しました: <title> タグと適切なバックスラッシュを使用
if (preg_match("/<title>(.*?)<\/title>/i", $result, $matches)) {
    $tle = $matches[1];
    echo "ページのタイトル: " . $tle . "\n";
} else {
    echo "タイトルが見つかりませんでした。\n";
}
?>
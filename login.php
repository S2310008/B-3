<?php
// 新規登録時
$password = $_POST['password']; // ユーザーが入力したパスワード
$hashedPassword = password_hash($password, PASSWORD_DEFAULT); // デフォルトのアルゴリズム（現在Bcrypt）でハッシュ化
// $hashedPassword をデータベースに保存

// ログイン時
$email = $_POST['email'];
$inputPassword = $_POST['password'];

// データベースから $email に対応するユーザーのハッシュ化されたパスワードを取得

if (password_verify($inputPassword, $dbHashedPassword)) {
    // パスワードが一致
    session_start();
    $_SESSION['user_id'] = $userId; // ユーザーIDなどをセッションに保存
    echo json_encode(['success' => true, 'message' => 'ログイン成功']);
} else {
    // パスワードが一致しない
    echo json_encode(['success' => false, 'message' => 'メールアドレスまたはパスワードが異なります。']);
}
?>
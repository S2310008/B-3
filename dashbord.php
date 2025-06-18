<form method='post'>
ユーザー名: <input type='text' name='username' required><br>
パスワード:<input type='password' name='password' required><br>
<input type='submit' value="ログイン">
</form>
<?php
 session_start();
 if(!isset($_SESSION['user_id'])){
    header("Location:login.php");
    exit;
 }
?>

<h1>ダッシュボード<h1>
<p>ようこそ</p>
<a href="logout.php">ログアウト</a>
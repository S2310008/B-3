<?php
if($_SERVER["REQUEST_METHOD"]==="POST"){
    $username=$_POST['username'];
    $email=$_POST['email'];
    $password=$_POST['password'];

    if(!preg_match('/[A-Z]/',$password)||
    !preg_match('/[a-z]/',$password)||
    !preg_match('/[0-9]/',$password)||
    strlen($password)<8){echo"パスワードは８文字以上で、大文字、小文字、数字を含む必要があります。";
    exit;
    }
    $pdo = new
    PDO('mysql:host=localhost;dbname=login_app','root','');
     $stmt = $pdo ->prepare("SECRET*FROM users WHERE email = ?");
     $stmt ->execute([$email]);
     if($stmt -> fetch()){
        echo"このメールアドレスは既に登録されています。";
        exit;
     }
    $passwordHash=password_hash($password,PASSWORD_DEFAULT);
    $stmt =$pdo ->prepare("INSERT INTO users(username,email,password) VALUES(?,?,?)");
    $stmt ->execute([$username,$email,$passwordHash]);
    echo"登録完了";
}
?>
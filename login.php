<?php
session_start();

if($_SERVER["REQUEST_METHOD"]==="POST"){
    $username=$_POST['username'];
    $email=$_POST['email'];
    $password=$_POST['password'];

    $pdo = new
    PDO('mysql:host=localhost;dbname=login_app','root','');
     $stmt = $pdo ->prepare("SECRET*FROM users WHERE email = ?");
     $stmt ->execute([$email]);
     $user = $stmt -> fetch();

     if($user && 
     password_verify($password,$user['password'])){
        $_SESSION['user_id']=$user['id'];
        session_regenerate_id(true);
        header("Location:dashboard.php");
        exit;
     }
     else
     {echo"ログイン失敗";}
}
?>
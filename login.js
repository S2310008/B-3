// firebase-config.jsからauthオブジェクトをインポート
import { auth } from "./firebase-config2.js";

// Firebase Authenticationの関数をインポート
import { signInWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-auth.js";

// DOM要素を取得
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const loginButton = document.getElementById('login-button');
const toRegisterButton = document.getElementById('to-register-button');

// ログインボタンのクリックイベント
loginButton.addEventListener('click', () => {
    const email = emailInput.value;
    const password = passwordInput.value;

    signInWithEmailAndPassword(auth, email, password)
        .then((userCredential) => {
            console.log("ログイン成功:", userCredential.user);
            alert("ログインに成功しました。");
            window.location.href = 'index.html'; 
        })
        .catch((error) => {
            console.error("ログイン失敗:", error.message);
            alert("ログインに失敗しました: " + error.message);
        });
});

// 「初めて利用する」ボタンのクリックイベント
toRegisterButton.addEventListener('click', () => {
    window.location.href = 'register.html';
});
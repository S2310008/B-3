// firebase-config.jsからauthオブジェクトをインポート
import { auth } from "./firebase-config2.js";

// Firebase Authenticationの関数をインポート
import { createUserWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-auth.js";

// DOM要素を取得
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const termsCheckbox = document.getElementById('terms');
const registerButton = document.getElementById('register-button');

// 登録ボタンのクリックイベント
registerButton.addEventListener('click', () => {
    const email = emailInput.value;
    const password = passwordInput.value;

    if (!termsCheckbox.checked) {
        alert("利用規約に同意してください。");
        return;
    }

    createUserWithEmailAndPassword(auth, email, password)
        .then((userCredential) => {
            console.log("ユーザー登録成功:", userCredential.user);
            alert("ユーザー登録が完了しました。ホーム画面へ移動します。");
            window.location.href = 'home.html'; 
        })
        .catch((error) => {
            console.error("ユーザー登録失敗:", error.message);
            alert("ユーザー登録に失敗しました: " + error.message);
        });
});
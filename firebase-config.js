// Firebase SDK の設定情報をここに貼り付けます
// Firebase コンソールでプロジェクト設定から取得できます
const firebaseConfig = {
  apiKey: "YOUR_API_KEY", 
  authDomain: "b3app-b0c29.firebaseapp.com", 
  databaseURL: "https://b3app-b0c29-default-rtdb.firebaseio.com/", 
  projectId: "b3app-b0c29", 
  storageBucket: "b3app-b0c29.appspot.com", 
  messagingSenderId: "1000910332829", 
  appId: "1:1000910332829:web:a1bc233e638afed9b90574" // あなたのApp IDに置き換えてください
};

// Firebase アプリを初期化
if (!firebase.apps.length) {
  firebase.initializeApp(firebaseConfig);
}

// Realtime Database の参照を取得
const database = firebase.database();
import { initializeApp } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-app.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-auth.js";


const firebaseConfig = {
  apiKey: "AIzaSyAMVDs2C7zKjame91CzT3LJkaz9WszBPHU", 
  authDomain: "b3app-b0c29.firebaseapp.com", 
  databaseURL: "https://b3app-b0c29-default-rtdb.firebaseio.com/", 
  projectId: "b3app-b0c29", 
  storageBucket: "b3app-b0c29.appspot.com", 
  messagingSenderId: "1000910332829", 
  appId: "1:1000910332829:web:a1bc233e638afed9b90574" 
};

const app = initializeApp(firebaseConfig);
export const auth = getAuth(app); 
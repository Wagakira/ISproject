
  import { initializeApp } from "https://www.gstatic.com/firebasejs/11.4.0/firebase-app.js";
  import { getAnalytics } from "https://www.gstatic.com/firebasejs/11.4.0/firebase-analytics.js";

const firebaseConfig = {
    apiKey: "AIzaSyBpXplEUGIj0J7TEmWACymMOWr2e58jKds",
    authDomain: "is-project-dvt.firebaseapp.com",
    projectId: "is-project-dvt",
    storageBucket: "is-project-dvt.firebasestorage.app",
    messagingSenderId: "598630649750",
    appId: "1:598630649750:web:c0feefaba7d053bb7a17b7",
    measurementId: "G-49B74F3QYR"
  };

  const app = initializeApp(firebaseConfig);
  const analytics = getAnalytics(app);

  function googleSignIn() {
    const provider = new firebase.auth.GoogleAuthProvider();
    auth.signInWithPopup(provider)
        .then(result => {
            alert("Welcome, " + result.user.displayName);
            window.location.href = "dashboard.html"; 
        })
        .catch(error => {
            console.error(error);
            alert("Google Sign-In failed!");
        });
}

function emailSignUp(event) {
    event.preventDefault();
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    auth.createUserWithEmailAndPassword(email, password)
        .then(userCredential => {
            alert("Account created successfully!");
            window.location.href = "dashboard.html"; 
        })
        .catch(error => {
            console.error(error);
            alert(error.message);
        });
}
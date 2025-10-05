// Firebase Configuration v9 (Compatibility Version)
// Your web app's Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyAL1qfUGstU2DzY864pTzZwxf812JN4jkM",
    authDomain: "skillsxchange-26855.firebaseapp.com",
    databaseURL: "https://skillsxchange-26855-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "skillsxchange-26855",
    storageBucket: "skillsxchange-26855.firebasestorage.app",
    messagingSenderId: "61175608249",
    appId: "1:61175608249:web:ebd30cdd178d9896d2fc68",
    measurementId: "G-V1WLV98X63"
};

// Initialize Firebase
if (typeof firebase !== 'undefined') {
    firebase.initializeApp(firebaseConfig);
    window.firebaseAuth = firebase.auth();
    console.log('✅ Firebase v9 (compat) initialized successfully');
} else {
    console.error('❌ Firebase SDK not loaded');
}

// Initialize authentication when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeFirebaseAuth();
});

function initializeFirebaseAuth() {
    if (!window.firebaseAuth) {
        console.error('❌ Firebase Auth not available');
        return;
    }

    // Listen for authentication state changes
    window.firebaseAuth.onAuthStateChanged(function(user) {
        if (user) {
            console.log('✅ Firebase user signed in:', user.uid);
            handleFirebaseSignIn(user);
        } else {
            console.log('ℹ️ No Firebase user (Laravel auth may be active)');
            // Don't automatically sign out - let Laravel handle authentication
        }
    });
}

// Handle Firebase sign in
function handleFirebaseSignIn(user) {
    // Get the ID token
    user.getIdToken().then(function(idToken) {
        console.log('✅ Firebase ID token obtained');
        // Send token to Laravel backend
        authenticateWithLaravel(idToken, 'google');
    }).catch(function(error) {
        console.error('❌ Error getting ID token:', error);
    });
}

// Handle Firebase sign out
function handleFirebaseSignOut() {
    console.log('ℹ️ Firebase sign out handled - Laravel auth remains active');
    // Don't redirect automatically - let Laravel handle authentication state
}

// Authenticate with Laravel backend
function authenticateWithLaravel(idToken, provider, isRegistration = false) {
    fetch('/auth/firebase/callback', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            firebase_token: idToken,
            provider: provider,
            is_registration: isRegistration
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (isRegistration) {
                window.location.href = '/profile/edit';
            } else {
                window.location.href = '/dashboard';
            }
        } else {
            console.error('❌ Authentication failed:', data.message);
            alert('Authentication failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('❌ Network error:', error);
        alert('Network error. Please try again.');
    });
}

// Google Sign-In functionality
function handleGoogleSignIn() {
    if (typeof firebase !== 'undefined') {
        const provider = new firebase.auth.GoogleAuthProvider();
        firebase.auth().signInWithPopup(provider)
            .then((result) => {
                console.log('✅ Google Sign-In successful');
            })
            .catch((error) => {
                console.error('❌ Google Sign-In Error:', error);
                alert('Google Sign-In failed. Please try again.');
            });
    } else {
        alert('Firebase is not loaded. Please refresh the page and try again.');
    }
}

// Google Registration functionality  
function handleGoogleRegistration() {
    if (typeof firebase !== 'undefined') {
        const provider = new firebase.auth.GoogleAuthProvider();
        firebase.auth().signInWithPopup(provider)
            .then((result) => {
                console.log('✅ Google Registration successful');
                // The handleFirebaseSignIn will be called automatically
            })
            .catch((error) => {
                console.error('❌ Google Registration Error:', error);
                alert('Google Registration failed. Please try again.');
            });
    } else {
        alert('Firebase is not loaded. Please refresh the page and try again.');
    }
}

// Make functions globally available
window.handleGoogleSignIn = handleGoogleSignIn;
window.handleGoogleRegistration = handleGoogleRegistration;
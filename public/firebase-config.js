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

// Initialize Firebase using global firebase object (v9 compat)
let app;
let database;
let analytics;

try {
    // Check if Firebase is available
    if (typeof firebase !== 'undefined') {
        // Initialize Firebase app
        app = firebase.initializeApp(firebaseConfig);
        database = firebase.database();
        analytics = firebase.analytics();
        auth = firebase.auth();
        
        console.log('✅ Firebase v9 (compat) initialized successfully');
        
        // Make available globally
        window.firebaseApp = app;
        window.firebaseDatabase = database;
        window.firebaseAnalytics = analytics;
        window.firebaseAuth = auth;
        window.firebaseConfig = firebaseConfig;
        
        // Initialize Firebase Authentication
        initializeFirebaseAuth();
        
    } else {
        console.error('❌ Firebase not loaded. Make sure Firebase CDN is included.');
    }
    
} catch (error) {
    console.error('❌ Error initializing Firebase:', error);
}

// Firebase Authentication Functions
function initializeFirebaseAuth() {
    if (!window.firebaseAuth) {
        console.error('❌ Firebase Auth not available');
        return;
    }

    // Listen for authentication state changes
    window.firebaseAuth.onAuthStateChanged(function(user) {
        if (user) {
            console.log('✅ User signed in:', user.uid);
            handleFirebaseSignIn(user);
        } else {
            console.log('❌ User signed out');
            handleFirebaseSignOut();
        }
    });
}

// Handle Firebase sign in
function handleFirebaseSignIn(user) {
    // Get the ID token
    user.getIdToken().then(function(idToken) {
        // Send token to Laravel backend
        authenticateWithLaravel(idToken, 'email');
    }).catch(function(error) {
        console.error('❌ Error getting ID token:', error);
    });
}

// Handle Firebase registration
function handleFirebaseRegistration(user) {
    // Check if email is verified first
    if (!user.emailVerified) {
        console.log('❌ Email not verified, redirecting to verification page');
        window.location.href = '/firebase/verify-email';
        return;
    }

    // Get the ID token
    user.getIdToken().then(function(idToken) {
        // Send token to Laravel backend for registration
        authenticateWithLaravel(idToken, 'email', true);
    }).catch(function(error) {
        console.error('❌ Error getting ID token:', error);
    });
}

// Handle Firebase sign out
function handleFirebaseSignOut() {
    // Redirect to login page
    if (window.location.pathname !== '/login') {
        window.location.href = '/login';
    }
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
            console.log('✅ Laravel authentication successful');
            // Redirect based on whether it's registration or login
            if (isRegistration) {
                window.location.href = data.redirect_url || '/profile/complete';
            } else {
                window.location.href = data.redirect_url || '/dashboard';
            }
        } else {
            console.error('❌ Laravel authentication failed:', data.message);
        }
    })
    .catch(error => {
        console.error('❌ Error authenticating with Laravel:', error);
    });
}

// Firebase Authentication Methods
window.firebaseAuthMethods = {
    // Sign in with email and password
    signInWithEmail: function(email, password) {
        return window.firebaseAuth.signInWithEmailAndPassword(email, password);
    },
    
    // Sign up with email and password
    signUpWithEmail: function(email, password) {
        return window.firebaseAuth.createUserWithEmailAndPassword(email, password);
    },
    
    // Sign in with Google
    signInWithGoogle: function() {
        const provider = new firebase.auth.GoogleAuthProvider();
        return window.firebaseAuth.signInWithPopup(provider);
    },
    
    // Sign out
    signOut: function() {
        return window.firebaseAuth.signOut();
    },
    
    // Send email verification
    sendEmailVerification: function() {
        return window.firebaseAuth.currentUser.sendEmailVerification();
    },
    
    // Send password reset email
    sendPasswordResetEmail: function(email) {
        return window.firebaseAuth.sendPasswordResetEmail(email);
    }
};
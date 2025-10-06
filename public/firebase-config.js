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
    try {
        firebase.initializeApp(firebaseConfig);
        window.firebaseAuth = firebase.auth();
        console.log('✅ Firebase v9 (compat) initialized successfully');
        console.log('🔍 Firebase config:', firebaseConfig);
        
        // Test Firebase Auth availability
        if (window.firebaseAuth) {
            console.log('✅ Firebase Auth is available');
        } else {
            console.error('❌ Firebase Auth is not available');
        }
    } catch (error) {
        console.error('❌ Firebase initialization error:', error);
    }
} else {
    console.error('❌ Firebase SDK not loaded');
}

// Initialize authentication when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeFirebaseAuth();
    
    // Handle redirect result if user was redirected back from Google
    if (window.firebaseAuth) {
        firebase.auth().getRedirectResult()
            .then((result) => {
                if (result.credential) {
                    console.log('✅ Redirect sign-in successful:', result);
                    // The handleFirebaseSignIn will be called automatically via onAuthStateChanged
                }
            })
            .catch((error) => {
                console.error('❌ Redirect sign-in error:', error);
            });
    }
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
        // Determine provider based on sign-in method
        const provider = user.providerData && user.providerData.length > 0 ? 
            user.providerData[0].providerId.replace('google.com', 'google') : 'google';
        // Send token to Laravel backend
        authenticateWithLaravel(idToken, provider);
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
            is_registration: isRegistration || window.location.pathname.includes('register')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Use the redirect URL from the server response
            if (data.redirect_url) {
                window.location.href = data.redirect_url;
            } else if (isRegistration) {
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
    console.log('🔍 Starting Google Sign-In process...');
    
    if (typeof firebase === 'undefined' || !firebase.auth) {
        console.error('❌ Firebase is not loaded');
        alert('Firebase is not loaded. Please refresh the page and try again.');
        return;
    }

    console.log('✅ Firebase is loaded, proceeding with Google Sign-In...');

    try {
        const provider = new firebase.auth.GoogleAuthProvider();
        console.log('✅ Google Auth Provider created');
        
        // Add additional scopes if needed
        provider.addScope('email');
        provider.addScope('profile');
        console.log('✅ Scopes added to provider');
        
        // Set custom parameters
        provider.setCustomParameters({
            prompt: 'select_account'
        });
        console.log('✅ Custom parameters set');

        console.log('🔄 Attempting Google Sign-In popup...');
        firebase.auth().signInWithPopup(provider)
            .then((result) => {
                console.log('✅ Google Sign-In successful:', result);
                // The handleFirebaseSignIn will be called automatically via onAuthStateChanged
            })
            .catch((error) => {
                console.error('❌ Google Sign-In Error Details:', {
                    code: error.code,
                    message: error.message,
                    email: error.email,
                    credential: error.credential
                });
                
                // Handle specific error cases
                if (error.code === 'auth/popup-closed-by-user') {
                    console.log('ℹ️ User closed the popup');
                    return; // Don't show error for user cancellation
                } else if (error.code === 'auth/cancelled-popup-request') {
                    console.log('ℹ️ Popup request was cancelled');
                    return; // Don't show error for cancellation
                } else if (error.code === 'auth/popup-blocked') {
                    alert('Popup was blocked by your browser. Please allow popups for this site and try again.');
                } else if (error.code === 'auth/internal-error') {
                    console.error('❌ Internal error details:', error);
                    console.log('🔄 Trying redirect method as fallback...');
                    
                    // Try redirect method as fallback
                    firebase.auth().signInWithRedirect(provider)
                        .then(() => {
                            console.log('✅ Redirect initiated');
                        })
                        .catch((redirectError) => {
                            console.error('❌ Redirect also failed:', redirectError);
                            alert('Google Sign-In configuration error. Please contact support or try again later.');
                        });
                } else {
                    alert('Google Sign-In failed: ' + error.message);
                }
            });
    } catch (error) {
        console.error('❌ Error initializing Google Sign-In:', error);
        alert('Error initializing Google Sign-In. Please refresh the page and try again.');
    }
}

// Make function globally available
window.handleGoogleSignIn = handleGoogleSignIn;
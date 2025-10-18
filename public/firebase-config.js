/**
 * Firebase Configuration for SkillsXchangee
 * Database only - for video call signaling
 * Authentication removed to prevent session conflicts
 */

// Firebase configuration
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

// Global Firebase variables
window.firebaseInitialized = false;
window.firebaseDatabase = null;
window.firebaseConfig = firebaseConfig;

// Initialize Firebase (Database only)
function initializeFirebase() {
    try {
        // Skip Firebase on login pages
        if (window.location.pathname.includes('/login') || 
            window.location.pathname.includes('/register') ||
            window.location.pathname.includes('/password/reset')) {
            console.log('Skipping Firebase on login page');
            return;
        }
        
        console.log('🔍 Initializing Firebase (Database only)...');
        
        // Check if Firebase is available
        if (typeof firebase === 'undefined') {
            console.error('❌ Firebase SDK not loaded');
            window.firebaseInitialized = false;
            return;
        }
        
        // Initialize Firebase app
        if (!firebase.apps.length) {
            firebase.initializeApp(firebaseConfig);
        }
        
        console.log('🔍 Initializing Firebase Database...');
        window.firebaseDatabase = firebase.database();
        console.log('✅ Firebase Database initialized');
        
        // Test database connection
        console.log('🔍 Testing database connection...');
        const testRef = window.firebaseDatabase.ref('.info/connected');
        testRef.on('value', (snapshot) => {
            if (snapshot.val() === true) {
                console.log('✅ Firebase Database connected');
            } else {
                console.log('⚠️ Firebase Database disconnected');
            }
        });
        
        window.firebaseInitialized = true;
        console.log('✅ Firebase (Database only) initialized successfully');
        
        // Fire ready event
        window.dispatchEvent(new CustomEvent('firebaseReady'));
        
    } catch (error) {
        console.error('❌ Firebase initialization error:', error);
        window.firebaseInitialized = false;
    }
}

// Wait for Firebase to be ready
function waitForFirebaseReady(callback) {
    if (window.firebaseInitialized && window.firebaseDatabase) {
        callback();
    } else {
        setTimeout(() => waitForFirebaseReady(callback), 100);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeFirebase);
} else {
    initializeFirebase();
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        firebaseConfig,
        initializeFirebase,
        waitForFirebaseReady
    };
}
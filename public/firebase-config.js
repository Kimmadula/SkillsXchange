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
        
        console.log('✅ Firebase v9 (compat) initialized successfully');
        
        // Make available globally
        window.firebaseApp = app;
        window.firebaseDatabase = database;
        window.firebaseAnalytics = analytics;
        window.firebaseConfig = firebaseConfig;
        
    } else {
        console.error('❌ Firebase not loaded. Make sure Firebase CDN is included.');
    }
    
} catch (error) {
    console.error('❌ Error initializing Firebase:', error);
}
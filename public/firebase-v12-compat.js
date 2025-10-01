// Firebase v12 Compatibility Layer
// This provides a compatibility layer for Firebase v12 modules

class FirebaseV12Compat {
    constructor() {
        this.database = null;
        this.initialized = false;
        this.initPromise = null;
    }

    async init() {
        if (this.initialized) return this.database;
        if (this.initPromise) return this.initPromise;

        this.initPromise = this._initializeFirebase();
        return this.initPromise;
    }

    async _initializeFirebase() {
        try {
            // Import Firebase v12 modules
            const { initializeApp } = await import('https://www.gstatic.com/firebasejs/12.3.0/firebase-app.js');
            const { getDatabase } = await import('https://www.gstatic.com/firebasejs/12.3.0/firebase-database.js');
            const { getAnalytics } = await import('https://www.gstatic.com/firebasejs/12.3.0/firebase-analytics.js');

            // Firebase Configuration
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
            const app = initializeApp(firebaseConfig);
            this.database = getDatabase(app);
            const analytics = getAnalytics(app);

            console.log('✅ Firebase v12 compatibility layer initialized');

            // Make available globally
            window.firebaseApp = app;
            window.firebaseDatabase = this.database;
            window.firebaseAnalytics = analytics;
            window.firebaseConfig = firebaseConfig;

            this.initialized = true;
            return this.database;

        } catch (error) {
            console.error('❌ Error initializing Firebase v12 compatibility layer:', error);
            throw error;
        }
    }

    // Compatibility methods that work like Firebase v8
    async set(path, data) {
        const { set, ref } = await import('https://www.gstatic.com/firebasejs/12.3.0/firebase-database.js');
        return set(ref(this.database, path), data);
    }

    async push(path, data) {
        const { push, ref } = await import('https://www.gstatic.com/firebasejs/12.3.0/firebase-database.js');
        return push(ref(this.database, path), data);
    }

    onValue(path, callback) {
        import('https://www.gstatic.com/firebasejs/12.3.0/firebase-database.js').then(({ onValue, ref }) => {
            onValue(ref(this.database, path), callback);
        });
    }

    onChildAdded(path, callback) {
        import('https://www.gstatic.com/firebasejs/12.3.0/firebase-database.js').then(({ onChildAdded, ref }) => {
            onChildAdded(ref(this.database, path), callback);
        });
    }

    off(path) {
        import('https://www.gstatic.com/firebasejs/12.3.0/firebase-database.js').then(({ off, ref }) => {
            off(ref(this.database, path));
        });
    }
}

// Create global instance
window.firebaseCompat = new FirebaseV12Compat();

// Auto-initialize
window.firebaseCompat.init().then(() => {
    console.log('✅ Firebase v12 compatibility layer ready');
}).catch(error => {
    console.error('❌ Failed to initialize Firebase v12 compatibility layer:', error);
});

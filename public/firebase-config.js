// Google OAuth Configuration
// This file now handles Google OAuth authentication instead of Firebase

console.log('✅ Google OAuth configuration loaded');

// Initialize Google OAuth when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeGoogleAuth();
});

function initializeGoogleAuth() {
    console.log('✅ Google OAuth initialized');
    // Google OAuth will be handled by the backend
}

// Google Sign-In functionality
function handleGoogleSignIn() {
    // Redirect to Google OAuth endpoint
    window.location.href = '/auth/google';
}

// Google Registration functionality  
function handleGoogleRegistration() {
    // Redirect to Google OAuth endpoint with registration flag
    window.location.href = '/auth/google?registration=true';
}

// Make functions globally available
window.handleGoogleSignIn = handleGoogleSignIn;
window.handleGoogleRegistration = handleGoogleRegistration;
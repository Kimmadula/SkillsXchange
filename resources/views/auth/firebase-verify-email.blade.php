@extends('layouts.guest')

@section('content')
<div class="auth-card">
    <div class="auth-header">
        <h2 class="auth-title">Verify Your Email</h2>
        <p class="auth-subtitle">Please check your email and click the verification link to continue</p>
    </div>

    <div class="auth-body">
        <!-- Email Verification UI -->
        <div id="email-verification-container">
            <div class="verification-icon">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            
            <div class="verification-content">
                <h3 class="verification-title">Check Your Email</h3>
                <p class="verification-message">
                    We've sent a verification link to your email address. Please check your inbox and click the link to verify your account.
                </p>
                
                <div class="email-info">
                    <strong>Email:</strong> <span id="user-email">Loading...</span>
                </div>
                
                <div class="verification-actions">
                    <button id="resend-verification-btn" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Resend Verification Email
                    </button>
                    
                    <button id="check-verification-btn" class="btn btn-outline-primary">
                        <i class="fas fa-sync me-2"></i>Check Verification Status
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="verification-loading" class="auth-loading" style="display: none;">
            <div class="loading-spinner"></div>
            <p id="loading-message">Processing...</p>
        </div>

        <!-- Error Messages -->
        <div id="verification-error" class="alert alert-danger" style="display: none;">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="error-message"></span>
        </div>

        <!-- Success Messages -->
        <div id="verification-success" class="alert alert-success" style="display: none;">
            <i class="fas fa-check-circle me-2"></i>
            <span id="success-message"></span>
        </div>

        <!-- Verification Status -->
        <div id="verification-status" class="verification-status" style="display: none;">
            <div class="status-content">
                <div class="status-icon">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
                <div class="status-message">
                    <h4>Email Verified!</h4>
                    <p>Your email has been successfully verified. You can now complete your profile.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="auth-footer">
        <p class="text-center">
            <a href="{{ route('firebase.login') }}" class="auth-link">
                <i class="fas fa-arrow-left me-1"></i>Back to Login
            </a>
        </p>
        <p class="text-center">
            <small class="text-muted">
                Didn't receive the email? Check your spam folder or try resending.
            </small>
        </p>
    </div>
</div>

<style>
.verification-icon {
    text-align: center;
    margin-bottom: 2rem;
}

.verification-icon i {
    font-size: 4rem;
    color: #3b82f6;
}

.verification-content {
    text-align: center;
}

.verification-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #374151;
}

.verification-message {
    color: #6b7280;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.email-info {
    background: #f8f9fa;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
    text-align: left;
}

.verification-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
}

.verification-status {
    text-align: center;
    padding: 2rem;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.status-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.status-icon i {
    font-size: 2rem;
}

.status-message h4 {
    margin: 0 0 0.5rem 0;
    color: #059669;
}

.status-message p {
    margin: 0;
    color: #6b7280;
}

.auth-loading {
    text-align: center;
    padding: 2rem;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f4f6;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.auth-footer {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.auth-link {
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
}

.auth-link:hover {
    color: #374151;
    text-decoration: underline;
}

@media (max-width: 640px) {
    .verification-actions {
        flex-direction: column;
    }
    
    .status-content {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Firebase to be loaded
    if (typeof window.firebaseAuthMethods === 'undefined') {
        console.error('Firebase Auth methods not loaded');
        showError('Firebase authentication not available. Please refresh the page.');
        return;
    }

    const resendBtn = document.getElementById('resend-verification-btn');
    const checkBtn = document.getElementById('check-verification-btn');
    const loadingDiv = document.getElementById('verification-loading');
    const errorDiv = document.getElementById('verification-error');
    const successDiv = document.getElementById('verification-success');
    const statusDiv = document.getElementById('verification-status');
    const userEmailSpan = document.getElementById('user-email');

    // Show loading state
    function showLoading(message = 'Processing...') {
        loadingDiv.style.display = 'block';
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
        statusDiv.style.display = 'none';
        document.getElementById('loading-message').textContent = message;
    }

    // Hide loading state
    function hideLoading() {
        loadingDiv.style.display = 'none';
    }

    // Show error message
    function showError(message) {
        errorDiv.style.display = 'block';
        successDiv.style.display = 'none';
        statusDiv.style.display = 'none';
        document.getElementById('error-message').textContent = message;
    }

    // Show success message
    function showSuccess(message) {
        successDiv.style.display = 'block';
        errorDiv.style.display = 'none';
        statusDiv.style.display = 'none';
        document.getElementById('success-message').textContent = message;
    }

    // Show verification status
    function showVerificationStatus() {
        statusDiv.style.display = 'block';
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
        
        // Redirect to profile completion after a delay
        setTimeout(() => {
            window.location.href = '/profile/complete';
        }, 3000);
    }

    // Get current user email
    function getCurrentUserEmail() {
        if (window.firebaseAuth && window.firebaseAuth.currentUser) {
            return window.firebaseAuth.currentUser.email;
        }
        return 'Unknown';
    }

    // Initialize page
    function initializePage() {
        const email = getCurrentUserEmail();
        userEmailSpan.textContent = email;
        
        // Check if user is already verified
        checkVerificationStatus();
    }

    // Check verification status
    function checkVerificationStatus() {
        if (!window.firebaseAuth || !window.firebaseAuth.currentUser) {
            showError('No user found. Please log in again.');
            return;
        }

        const user = window.firebaseAuth.currentUser;
        
        // Reload user to get latest verification status
        user.reload().then(() => {
            if (user.emailVerified) {
                // Update verification status in Laravel
                updateLaravelVerificationStatus(user);
            } else {
                hideLoading();
            }
        }).catch(error => {
            console.error('Error checking verification status:', error);
            hideLoading();
        });
    }

    // Update verification status in Laravel
    function updateLaravelVerificationStatus(user) {
        user.getIdToken().then(function(idToken) {
            fetch('/auth/firebase/verify-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    firebase_token: idToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.is_verified) {
                    showVerificationStatus();
                } else {
                    hideLoading();
                }
            })
            .catch(error => {
                console.error('Error updating verification status:', error);
                hideLoading();
            });
        }).catch(error => {
            console.error('Error getting ID token:', error);
            hideLoading();
        });
    }

    // Resend verification email
    resendBtn.addEventListener('click', function() {
        if (!window.firebaseAuth || !window.firebaseAuth.currentUser) {
            showError('No user found. Please log in again.');
            return;
        }

        showLoading('Sending verification email...');

        window.firebaseAuthMethods.sendEmailVerification()
            .then(function() {
                hideLoading();
                showSuccess('Verification email sent! Please check your inbox.');
            })
            .catch(function(error) {
                console.error('Error sending verification email:', error);
                hideLoading();
                showError(getErrorMessage(error.code));
            });
    });

    // Check verification status
    checkBtn.addEventListener('click', function() {
        showLoading('Checking verification status...');
        checkVerificationStatus();
    });

    // Auto-check verification status every 30 seconds
    setInterval(checkVerificationStatus, 30000);

    // Get user-friendly error messages
    function getErrorMessage(errorCode) {
        const errorMessages = {
            'auth/too-many-requests': 'Too many requests. Please wait before trying again.',
            'auth/network-request-failed': 'Network error. Please check your connection.',
            'auth/user-not-found': 'User not found. Please log in again.',
            'auth/invalid-email': 'Invalid email address.',
            'auth/user-disabled': 'This account has been disabled.',
            'auth/operation-not-allowed': 'Email verification is not enabled.',
        };

        return errorMessages[errorCode] || 'An error occurred. Please try again.';
    }

    // Initialize the page
    initializePage();
});
</script>
@endsection

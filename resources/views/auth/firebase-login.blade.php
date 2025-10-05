@extends('layouts.guest')

@section('content')
<div class="auth-card">
    <div class="auth-header">
        <h2 class="auth-title">Sign In with Firebase</h2>
        <p class="auth-subtitle">Choose your preferred sign-in method</p>
    </div>

    <div class="auth-body">
        <!-- Firebase Authentication UI -->
        <div id="firebase-auth-container">
            <!-- Email/Password Form -->
            <div class="auth-form-section">
                <h3 class="form-section-title">Email & Password</h3>
                <form id="email-auth-form" class="auth-form">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-full" id="signin-btn">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-full" id="signup-btn">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>
            </div>

            <!-- Social Sign-in -->
            <div class="auth-form-section">
                <div class="divider">
                    <span>or</span>
                </div>
                <div class="social-auth">
                    <button class="btn btn-google" id="google-signin-btn">
                        <i class="fab fa-google me-2"></i>Sign in with Google
                    </button>
                </div>
            </div>

            <!-- Password Reset -->
            <div class="auth-form-section">
                <div class="text-center">
                    <button type="button" class="btn btn-link" id="forgot-password-btn">
                        Forgot your password?
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="auth-loading" class="auth-loading" style="display: none;">
            <div class="loading-spinner"></div>
            <p>Authenticating...</p>
        </div>

        <!-- Error Messages -->
        <div id="auth-error" class="alert alert-danger" style="display: none;">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="error-message"></span>
        </div>

        <!-- Success Messages -->
        <div id="auth-success" class="alert alert-success" style="display: none;">
            <i class="fas fa-check-circle me-2"></i>
            <span id="success-message"></span>
        </div>
    </div>

    <div class="auth-footer">
        <p class="text-center">
            <a href="{{ route('login') }}" class="auth-link">
                <i class="fas fa-arrow-left me-1"></i>Back to Traditional Login
            </a>
        </p>
    </div>
</div>

<style>
.auth-form-section {
    margin-bottom: 2rem;
}

.form-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #374151;
}

.divider {
    text-align: center;
    margin: 1.5rem 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e5e7eb;
}

.divider span {
    background: white;
    padding: 0 1rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.social-auth {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.btn-google {
    background: #4285f4;
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-google:hover {
    background: #3367d6;
    transform: translateY(-1px);
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Firebase to be loaded
    if (typeof window.firebaseAuthMethods === 'undefined') {
        console.error('Firebase Auth methods not loaded');
        return;
    }

    const emailForm = document.getElementById('email-auth-form');
    const signinBtn = document.getElementById('signin-btn');
    const signupBtn = document.getElementById('signup-btn');
    const googleSigninBtn = document.getElementById('google-signin-btn');
    const forgotPasswordBtn = document.getElementById('forgot-password-btn');
    const loadingDiv = document.getElementById('auth-loading');
    const errorDiv = document.getElementById('auth-error');
    const successDiv = document.getElementById('auth-success');

    // Show loading state
    function showLoading() {
        loadingDiv.style.display = 'block';
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
    }

    // Hide loading state
    function hideLoading() {
        loadingDiv.style.display = 'none';
    }

    // Show error message
    function showError(message) {
        errorDiv.style.display = 'block';
        successDiv.style.display = 'none';
        document.getElementById('error-message').textContent = message;
    }

    // Show success message
    function showSuccess(message) {
        successDiv.style.display = 'block';
        errorDiv.style.display = 'none';
        document.getElementById('success-message').textContent = message;
    }

    // Sign in with email and password
    signinBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        if (!email || !password) {
            showError('Please enter both email and password');
            return;
        }

        showLoading();

        window.firebaseAuthMethods.signInWithEmail(email, password)
            .then(function(userCredential) {
                console.log('Sign in successful:', userCredential.user);
                showSuccess('Sign in successful! Redirecting...');
            })
            .catch(function(error) {
                console.error('Sign in error:', error);
                hideLoading();
                showError(getErrorMessage(error.code));
            });
    });

    // Sign up with email and password
    signupBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        if (!email || !password) {
            showError('Please enter both email and password');
            return;
        }

        if (password.length < 6) {
            showError('Password must be at least 6 characters long');
            return;
        }

        showLoading();

        window.firebaseAuthMethods.signUpWithEmail(email, password)
            .then(function(userCredential) {
                console.log('Sign up successful:', userCredential.user);
                showSuccess('Account created successfully! Please check your email for verification.');
            })
            .catch(function(error) {
                console.error('Sign up error:', error);
                hideLoading();
                showError(getErrorMessage(error.code));
            });
    });

    // Sign in with Google
    googleSigninBtn.addEventListener('click', function(e) {
        e.preventDefault();
        showLoading();

        window.firebaseAuthMethods.signInWithGoogle()
            .then(function(result) {
                console.log('Google sign in successful:', result.user);
                showSuccess('Google sign in successful! Redirecting...');
            })
            .catch(function(error) {
                console.error('Google sign in error:', error);
                hideLoading();
                showError(getErrorMessage(error.code));
            });
    });

    // Forgot password
    forgotPasswordBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const email = document.getElementById('email').value;

        if (!email) {
            showError('Please enter your email address first');
            return;
        }

        showLoading();

        window.firebaseAuthMethods.sendPasswordResetEmail(email)
            .then(function() {
                hideLoading();
                showSuccess('Password reset email sent! Please check your inbox.');
            })
            .catch(function(error) {
                console.error('Password reset error:', error);
                hideLoading();
                showError(getErrorMessage(error.code));
            });
    });

    // Get user-friendly error messages
    function getErrorMessage(errorCode) {
        const errorMessages = {
            'auth/user-not-found': 'No account found with this email address',
            'auth/wrong-password': 'Incorrect password',
            'auth/invalid-email': 'Invalid email address',
            'auth/user-disabled': 'This account has been disabled',
            'auth/too-many-requests': 'Too many failed attempts. Please try again later',
            'auth/email-already-in-use': 'An account with this email already exists',
            'auth/weak-password': 'Password is too weak',
            'auth/invalid-credential': 'Invalid email or password',
            'auth/popup-closed-by-user': 'Sign-in popup was closed',
            'auth/cancelled-popup-request': 'Sign-in was cancelled',
            'auth/network-request-failed': 'Network error. Please check your connection'
        };

        return errorMessages[errorCode] || 'An error occurred. Please try again.';
    }
});
</script>
@endsection

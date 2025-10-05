@extends('layouts.guest')

@section('content')
<div class="auth-card">
    <div class="auth-header">
        <h2 class="auth-title">Create Account with Firebase</h2>
        <p class="auth-subtitle">Choose your preferred registration method</p>
    </div>

    <div class="auth-body">
        <!-- Firebase Registration UI -->
        <div id="firebase-register-container">
            <!-- Email/Password Registration Form -->
            <div class="auth-form-section">
                <h3 class="form-section-title">Email & Password</h3>
                <form id="email-register-form" class="auth-form">
                    <div class="form-group">
                        <label for="reg-email" class="form-label">Email Address</label>
                        <input type="email" id="reg-email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-password" class="form-label">Password</label>
                        <input type="password" id="reg-password" name="password" class="form-input" required minlength="6">
                        <small class="form-help">Password must be at least 6 characters long</small>
                    </div>
                    <div class="form-group">
                        <label for="reg-confirm-password" class="form-label">Confirm Password</label>
                        <input type="password" id="reg-confirm-password" name="confirm_password" class="form-input" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-full" id="register-btn">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>
            </div>

            <!-- Social Registration -->
            <div class="auth-form-section">
                <div class="divider">
                    <span>or</span>
                </div>
                <div class="social-auth">
                    <button class="btn btn-google" id="google-register-btn">
                        <i class="fab fa-google me-2"></i>Register with Google
                    </button>
                </div>
            </div>

            <!-- Profile Completion Notice -->
            <div class="auth-form-section">
                <div class="info-notice">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> After Firebase registration, you'll be asked to complete your profile with additional information like skills and preferences.
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="register-loading" class="auth-loading" style="display: none;">
            <div class="loading-spinner"></div>
            <p>Creating your account...</p>
        </div>

        <!-- Error Messages -->
        <div id="register-error" class="alert alert-danger" style="display: none;">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="error-message"></span>
        </div>

        <!-- Success Messages -->
        <div id="register-success" class="alert alert-success" style="display: none;">
            <i class="fas fa-check-circle me-2"></i>
            <span id="success-message"></span>
        </div>
    </div>

    <div class="auth-footer">
        <p class="text-center">
            <a href="{{ route('register') }}" class="auth-link">
                <i class="fas fa-arrow-left me-1"></i>Back to Traditional Registration
            </a>
        </p>
        <p class="text-center">
            <a href="{{ route('login') }}" class="auth-link">
                Already have an account? Sign in
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

.form-help {
    color: #6b7280;
    font-size: 0.875rem;
    margin-top: 0.25rem;
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

.info-notice {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 1rem;
    color: #0369a1;
    font-size: 0.875rem;
    line-height: 1.5;
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
    margin: 0.5rem 0;
    display: inline-block;
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

    const registerForm = document.getElementById('email-register-form');
    const registerBtn = document.getElementById('register-btn');
    const googleRegisterBtn = document.getElementById('google-register-btn');
    const loadingDiv = document.getElementById('register-loading');
    const errorDiv = document.getElementById('register-error');
    const successDiv = document.getElementById('register-success');

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

    // Register with email and password
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('reg-email').value;
        const password = document.getElementById('reg-password').value;
        const confirmPassword = document.getElementById('reg-confirm-password').value;

        if (!email || !password || !confirmPassword) {
            showError('Please fill in all fields');
            return;
        }

        if (password !== confirmPassword) {
            showError('Passwords do not match');
            return;
        }

        if (password.length < 6) {
            showError('Password must be at least 6 characters long');
            return;
        }

        showLoading();

        window.firebaseAuthMethods.signUpWithEmail(email, password)
            .then(function(userCredential) {
                console.log('Registration successful:', userCredential.user);
                showSuccess('Account created successfully! Please verify your email to continue.');
                
                // Send verification email
                return window.firebaseAuthMethods.sendEmailVerification();
            })
            .then(function() {
                // Redirect to email verification page
                window.location.href = '/firebase/verify-email';
            })
            .catch(function(error) {
                console.error('Registration error:', error);
                hideLoading();
                showError(getErrorMessage(error.code));
            });
    });

    // Register with Google
    googleRegisterBtn.addEventListener('click', function(e) {
        e.preventDefault();
        showLoading();

        window.firebaseAuthMethods.signInWithGoogle()
            .then(function(result) {
                console.log('Google registration successful:', result.user);
                showSuccess('Google registration successful! Redirecting to profile completion...');
                
                // Handle Firebase registration
                handleFirebaseRegistration(result.user);
            })
            .catch(function(error) {
                console.error('Google registration error:', error);
                hideLoading();
                showError(getErrorMessage(error.code));
            });
    });

    // Real-time password confirmation validation
    document.getElementById('reg-confirm-password').addEventListener('input', function() {
        const password = document.getElementById('reg-password').value;
        const confirmPassword = this.value;
        
        if (confirmPassword && password !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
            this.classList.add('border-red-500');
        } else {
            this.setCustomValidity('');
            this.classList.remove('border-red-500');
        }
    });

    // Handle Firebase registration
    function handleFirebaseRegistration(user) {
        // Get the ID token
        user.getIdToken().then(function(idToken) {
            // Send token to Laravel backend for registration
            authenticateWithLaravel(idToken, 'email', true);
        }).catch(function(error) {
            console.error('❌ Error getting ID token:', error);
            hideLoading();
            showError('Error processing registration. Please try again.');
        });
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
                console.log('✅ Laravel registration successful');
                // Redirect to profile completion
                window.location.href = data.redirect_url || '/profile/complete';
            } else {
                console.error('❌ Laravel registration failed:', data.message);
                hideLoading();
                showError(data.message || 'Registration failed. Please try again.');
            }
        })
        .catch(error => {
            console.error('❌ Error registering with Laravel:', error);
            hideLoading();
            showError('Registration failed. Please try again.');
        });
    }

    // Get user-friendly error messages
    function getErrorMessage(errorCode) {
        const errorMessages = {
            'auth/email-already-in-use': 'An account with this email already exists',
            'auth/invalid-email': 'Invalid email address',
            'auth/weak-password': 'Password is too weak',
            'auth/operation-not-allowed': 'Email/password accounts are not enabled',
            'auth/popup-closed-by-user': 'Registration popup was closed',
            'auth/cancelled-popup-request': 'Registration was cancelled',
            'auth/network-request-failed': 'Network error. Please check your connection',
            'auth/too-many-requests': 'Too many attempts. Please try again later'
        };

        return errorMessages[errorCode] || 'An error occurred during registration. Please try again.';
    }
});
</script>
@endsection

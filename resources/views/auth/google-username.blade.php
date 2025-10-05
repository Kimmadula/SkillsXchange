@extends('layouts.guest')

@section('content')
<div class="auth-card">
    <div class="auth-header">
        <h2 class="auth-title">Complete Your Profile</h2>
        <p class="auth-subtitle">Choose a username to complete your Google sign-in</p>
    </div>

    <div class="auth-body">
        <!-- User Info Display -->
        <div class="user-info-display mb-4">
            <div class="user-avatar">
                <img id="user-avatar" src="" alt="User Avatar" class="avatar-img">
            </div>
            <div class="user-details">
                <h4 id="user-name" class="user-name"></h4>
                <p id="user-email" class="user-email"></p>
            </div>
        </div>

        <!-- Username Form -->
        <form id="username-form" class="auth-form">
            <div class="form-group">
                <label for="username" class="form-label">Choose Your Username</label>
                <input type="text" id="username" name="username" class="form-input" required 
                       placeholder="Enter your desired username" minlength="3" maxlength="50">
                <div class="form-help">
                    <small class="text-muted">Username must be 3-50 characters long and unique</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-full" id="complete-signin-btn">
                    <i class="fas fa-check me-2"></i>Complete Sign In
                </button>
                <button type="button" class="btn btn-outline-secondary btn-full" id="cancel-btn">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
            </div>
        </form>

        <!-- Loading Indicator -->
        <div id="loading-indicator" class="loading-indicator" style="display: none;">
            <div class="spinner"></div>
            <p>Completing sign-in...</p>
        </div>

        <!-- Error Messages -->
        <div id="error-message" class="alert alert-danger" style="display: none;">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="error-text"></span>
        </div>

        <!-- Success Messages -->
        <div id="success-message" class="alert alert-success" style="display: none;">
            <i class="fas fa-check-circle me-2"></i>
            <span id="success-text"></span>
        </div>
    </div>
</div>

<style>
.user-info-display {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.user-avatar {
    margin-right: 1rem;
}

.avatar-img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #007bff;
}

.user-details {
    flex: 1;
}

.user-name {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
}

.user-email {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
}

.loading-indicator {
    text-align: center;
    padding: 2rem;
}

.spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #007bff;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.form-help {
    margin-top: 0.25rem;
}

.alert {
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.375rem;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const usernameForm = document.getElementById('username-form');
    const usernameInput = document.getElementById('username');
    const completeBtn = document.getElementById('complete-signin-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const loadingIndicator = document.getElementById('loading-indicator');
    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');

    // Get user info from Firebase
    let currentUser = null;
    
    if (window.firebaseAuth && window.firebaseAuth.currentUser) {
        currentUser = window.firebaseAuth.currentUser;
        displayUserInfo(currentUser);
    } else {
        // If no user, redirect to login
        window.location.href = '/firebase-login';
    }

    function displayUserInfo(user) {
        const avatarImg = document.getElementById('user-avatar');
        const userName = document.getElementById('user-name');
        const userEmail = document.getElementById('user-email');

        if (user.photoURL) {
            avatarImg.src = user.photoURL;
        } else {
            avatarImg.src = '/images/default-avatar.png'; // Fallback image
        }

        userName.textContent = user.displayName || 'Google User';
        userEmail.textContent = user.email || '';
    }

    // Username validation
    usernameInput.addEventListener('input', function() {
        const username = this.value.trim();
        const isValid = username.length >= 3 && username.length <= 50 && /^[a-zA-Z0-9_]+$/.test(username);
        
        if (username.length > 0) {
            if (isValid) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        } else {
            this.classList.remove('is-valid', 'is-invalid');
        }
    });

    // Form submission
    usernameForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = usernameInput.value.trim();
        
        if (!username || username.length < 3 || username.length > 50) {
            showError('Username must be 3-50 characters long');
            return;
        }

        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            showError('Username can only contain letters, numbers, and underscores');
            return;
        }

        completeGoogleSignIn(username);
    });

    // Cancel button
    cancelBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to cancel? You will be signed out.')) {
            window.firebaseAuthMethods.signOut().then(() => {
                window.location.href = '/firebase-login';
            });
        }
    });

    function completeGoogleSignIn(username) {
        showLoading();
        hideMessages();

        // Get Firebase ID token
        currentUser.getIdToken().then(function(idToken) {
            // Send to Laravel backend with username
            fetch('/auth/firebase/google-callback', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    firebase_token: idToken,
                    username: username,
                    provider: 'google'
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showSuccess('Sign-in completed successfully! Redirecting...');
                    setTimeout(() => {
                        window.location.href = data.redirect_url || '/dashboard';
                    }, 1500);
                } else {
                    if (data.message === 'Username already exists') {
                        showError('This username is already taken. Please choose another one.');
                    } else {
                        showError(data.message || 'Failed to complete sign-in');
                    }
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error completing Google sign-in:', error);
                showError('An error occurred. Please try again.');
            });
        }).catch(error => {
            hideLoading();
            console.error('Error getting Firebase token:', error);
            showError('Authentication error. Please try again.');
        });
    }

    function showLoading() {
        loadingIndicator.style.display = 'block';
        completeBtn.disabled = true;
        cancelBtn.disabled = true;
    }

    function hideLoading() {
        loadingIndicator.style.display = 'none';
        completeBtn.disabled = false;
        cancelBtn.disabled = false;
    }

    function showError(message) {
        errorMessage.style.display = 'block';
        document.getElementById('error-text').textContent = message;
        successMessage.style.display = 'none';
    }

    function showSuccess(message) {
        successMessage.style.display = 'block';
        document.getElementById('success-text').textContent = message;
        errorMessage.style.display = 'none';
    }

    function hideMessages() {
        errorMessage.style.display = 'none';
        successMessage.style.display = 'none';
    }
});
</script>
@endsection

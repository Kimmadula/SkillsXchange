@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5 dark-theme">
    <div class="container">
        @if(!$user)
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                User data not found. Please try refreshing the page.
            </div>
        @else
        <!-- Header -->
        <div class="row mb-4 mb-md-5">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h1 class="h2 fw-bold text-gradient mb-2">Edit Profile</h1>
                        <p class="text-muted">Update your personal information and skills</p>
                    </div>
                    <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Profile
                    </a>
                </div>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                @if(session('status') === 'profile-updated')
                    <strong>Profile updated successfully!</strong><br>
                    <small>Your username and email have been updated.</small>
                @elseif(session('status') === 'password-updated')
                    <strong>Password updated successfully!</strong><br>
                    <small>Your password has been changed.</small>
                @else
                    {{ session('status') }}
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->has('username'))
            <div class="alert alert-danger">
                Username errors: {{ $errors->first('username') }}
            </div>
        @endif

        @if($errors->has('email'))
            <div class="alert alert-danger">
                Email errors: {{ $errors->first('email') }}
            </div>
        @endif

        <div class="row profile-sections-container">
            <!-- Personal Info Section -->
            <div class="col-lg-8 mb-4">
                <div class="profile-card personal-info-card">
                    <div class="card-header-section">
                        <h3 class="profile-card-title">Personal Info</h3>
                        <div class="section-indicator personal-info-indicator">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="needs-validation">
                        @csrf
                        @method('PATCH')
                        
                        <!-- Profile Photo -->
                        <div class="profile-photo-section mb-4">
                            <div class="profile-photo-container">
                                @if($user->photo && file_exists(storage_path('app/public/' . $user->photo)))
                                    <img src="{{ asset('storage/' . $user->photo) }}" alt="Profile Photo" id="photoPreview" class="profile-photo">
                                @else
                                    <div id="photoPreview" class="profile-photo-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="profile-photo-actions">
                                <label for="photo" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-upload me-1"></i>Upload new picture
                                </label>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="deletePhotoBtn">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </button>
                                <input type="file" class="d-none" id="photo" name="photo" accept="image/*" onchange="previewPhoto(this)">
                            </div>
                            @error('photo')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Name Field -->
                        <div class="form-group mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                   id="username" name="username" value="{{ old('username', $user->username) }}" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email Field -->
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Skills Display (Read-only) -->
                        <div class="skills-section mb-4">
                            <label class="form-label">Skills & Expertise</label>
                            <div class="skills-display-container">
                                @if($user->skills->count() > 0)
                                    <div class="skills-container">
                                        @foreach($user->skills as $skill)
                                            <span class="skill-badge {{ $skill->skill_id == $user->skill_id ? 'skill-badge-primary' : 'skill-badge-secondary' }}">
                                                {{ $skill->skill_name }}
                                                @if($skill->skill_id == $user->skill_id)
                                                    <i class="fas fa-star ms-1" title="Primary Skill"></i>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                    <small class="text-muted">Skills cannot be edited from profile. Contact admin to modify skills.</small>
                                @else
                                    <p class="text-muted">No skills assigned. Contact admin to add skills to your profile.</p>
                                @endif
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="d-flex justify-content-start">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save personal info
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password Section -->
            <div class="col-lg-4 mb-4">
                <div class="profile-card password-card">
                    <div class="card-header-section">
                        <h3 class="profile-card-title">Change password</h3>
                        <div class="section-indicator password-indicator">
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>
                    
                    <form method="POST" action="{{ route('profile.password.update') }}" class="needs-validation">
                        @csrf
                        @method('PUT')
                        
                        <!-- Old Password Field -->
                        <div class="form-group mb-3">
                            <label for="current_password" class="form-label">Old password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" 
                                       id="current_password" name="current_password" required autocomplete="current-password">
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- New Password Field -->
                        <div class="form-group mb-4">
                            <label for="password" class="form-label">New password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" 
                                       id="password" name="password" required autocomplete="new-password">
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password_icon"></i>
                                </button>
                            </div>
                            <div class="form-text">Minimum 8 characters</div>
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm New Password Field -->
                        <div class="form-group mb-4">
                            <label for="password_confirmation" class="form-label">Confirm new password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" 
                                       id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('password_confirmation')">
                                    <i class="fas fa-eye" id="password_confirmation_icon"></i>
                                </button>
                            </div>
                            @error('password_confirmation', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                Change
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!-- Delete Account Modal -->
        <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                        <p class="text-danger"><strong>Warning:</strong> All your data will be permanently removed.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" action="{{ route('profile.destroy') }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Ensure functions are available immediately
console.log('Script loading started...');

// Global functions - defined outside of DOMContentLoaded to ensure they're available immediately

// Photo preview functionality
function previewPhoto(input) {
    try {
        console.log('previewPhoto function called');
        const preview = document.getElementById('photoPreview');
        const file = input.files[0];
        
        if (!preview) {
            console.error('Photo preview element not found');
            return;
        }
        
        if (!file) {
            console.log('No file selected');
            return;
        }
        
        console.log('File selected:', file.name, file.type);
        
        const reader = new FileReader();
        reader.onload = function(e) {
            console.log('File read successfully');
            if (preview.classList.contains('profile-photo-placeholder')) {
                preview.className = 'profile-photo';
                preview.src = e.target.result;
                preview.alt = 'Profile Photo';
                console.log('Updated preview to image');
            } else {
                preview.src = e.target.result;
                console.log('Updated existing image preview');
            }
        };
        reader.onerror = function(e) {
            console.error('Error reading file:', e);
        };
        reader.readAsDataURL(file);
    } catch (error) {
        console.error('Error in previewPhoto:', error);
    }
}

// Delete photo functionality
function deletePhoto() {
    try {
        console.log('deletePhoto function called');
        const preview = document.getElementById('photoPreview');
        const fileInput = document.getElementById('photo');
        
        if (!preview) {
            console.error('Photo preview element not found');
            return;
        }
        
        if (!fileInput) {
            console.error('File input element not found');
            return;
        }
        
        // Reset file input
        fileInput.value = '';
        
        // Reset preview to placeholder
        preview.className = 'profile-photo-placeholder';
        preview.innerHTML = '<i class="fas fa-user"></i>';
        preview.removeAttribute('src');
        preview.removeAttribute('alt');
        
        console.log('Photo deleted successfully');
    } catch (error) {
        console.error('Error in deletePhoto:', error);
    }
}

// Password toggle functionality - Global scope
function togglePassword(fieldId) {
    try {
        console.log('togglePassword called for field:', fieldId);
        
        const passwordField = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '_icon');
        
        if (!passwordField) {
            console.error('Password field not found:', fieldId);
            return;
        }
        
        if (!icon) {
            console.error('Password icon not found:', fieldId + '_icon');
            return;
        }
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            console.log('Password shown for:', fieldId);
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            console.log('Password hidden for:', fieldId);
        }
    } catch (error) {
        console.error('Error in togglePassword:', error);
    }
}

// Make functions globally available
window.togglePassword = togglePassword;
window.previewPhoto = previewPhoto;
window.deletePhoto = deletePhoto;

// Debug: Check if functions are available
console.log('Functions available:', {
    previewPhoto: typeof previewPhoto,
    deletePhoto: typeof deletePhoto,
    togglePassword: typeof togglePassword
});

// Test if functions are accessible from global scope
console.log('Global functions available:', {
    previewPhoto: typeof window.previewPhoto,
    deletePhoto: typeof window.deletePhoto,
    togglePassword: typeof window.togglePassword
});

// Test the function immediately
console.log('Testing togglePassword function...');
if (typeof window.togglePassword === 'function') {
    console.log('✅ togglePassword is available globally');
} else {
    console.error('❌ togglePassword is NOT available globally');
}

// Alternative: Define function directly on window object as backup
window.togglePassword = function(fieldId) {
    try {
        console.log('togglePassword called for field:', fieldId);
        
        const passwordField = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '_icon');
        
        if (!passwordField) {
            console.error('Password field not found:', fieldId);
            return;
        }
        
        if (!icon) {
            console.error('Password icon not found:', fieldId + '_icon');
            return;
        }
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            console.log('Password shown for:', fieldId);
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            console.log('Password hidden for:', fieldId);
        }
    } catch (error) {
        console.error('Error in togglePassword:', error);
    }
};

console.log('✅ togglePassword function redefined on window object');

// Form submission handling
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up form handling...');
    
    // Photo functionality event listeners
    const photoInput = document.getElementById('photo');
    const deletePhotoBtn = document.getElementById('deletePhotoBtn');
    
    if (photoInput) {
        console.log('Photo input found, adding event listener');
        photoInput.addEventListener('change', function() {
            console.log('Photo input changed, calling previewPhoto');
            previewPhoto(this);
        });
    } else {
        console.error('Photo input not found');
    }
    
    if (deletePhotoBtn) {
        console.log('Delete photo button found, adding event listener');
        deletePhotoBtn.addEventListener('click', function() {
            console.log('Delete photo button clicked, calling deletePhoto');
            deletePhoto();
        });
    } else {
        console.error('Delete photo button not found');
    }
    
    // Password toggle button event listeners
    const passwordToggleButtons = document.querySelectorAll('.password-toggle-btn');
    passwordToggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const fieldId = this.getAttribute('data-field');
            console.log('Password toggle button clicked for field:', fieldId);
            togglePassword(fieldId);
        });
    });
    
    // Profile form handling
    const profileForm = document.querySelector('form[action="{{ route("profile.update") }}"]');
    if (profileForm) {
        console.log('Profile form found');
        
        profileForm.addEventListener('submit', function(e) {
            console.log('Profile form submitting...');
            console.log('Form method:', this.method);
            console.log('Form action:', this.action);
            
            // Check if the hidden _method field exists
            const methodField = this.querySelector('input[name="_method"]');
            if (methodField) {
                console.log('Method field value:', methodField.value);
            } else {
                console.error('No _method field found!');
            }
        });
    }
    
    // Password form handling
    const passwordForm = document.querySelector('form[action="{{ route("profile.password.update") }}"]');
    if (passwordForm) {
        console.log('Password form found');
        
        passwordForm.addEventListener('submit', function(e) {
            console.log('Password form submitting...');
            console.log('Form method:', this.method);
            console.log('Form action:', this.action);
            
            // Check if the hidden _method field exists
            const methodField = this.querySelector('input[name="_method"]');
            if (methodField) {
                console.log('Method field value:', methodField.value);
            } else {
                console.error('No _method field found!');
            }
        });
    }
});
</script>
@endpush

@push('styles')
<style>
/* Profile Card Styles */
.profile-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    height: fit-content;
}

/* Personal Info Card - Left Side */
.personal-info-card {
    border-left: 4px solid #6366f1;
    margin-right: 20px;
}

/* Password Card - Right Side */
.password-card {
    border-left: 4px solid #f59e0b;
    margin-left: 20px;
    position: relative;
}

/* Visual Separator */
.password-card::before {
    content: '';
    position: absolute;
    left: -30px;
    top: 50%;
    transform: translateY(-50%);
    width: 2px;
    height: 60%;
    background: linear-gradient(to bottom, transparent, #d1d5db, transparent);
    z-index: 1;
}

/* Profile Sections Container */
.profile-sections-container {
    gap: 0;
    position: relative;
}

/* Card Header Section */
.card-header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.profile-card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

/* Section Indicators */
.section-indicator {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.personal-info-indicator {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
}

.password-indicator {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

/* Profile Photo Styles */
.profile-photo-section {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.profile-photo-container {
    flex-shrink: 0;
}

.profile-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e5e7eb;
}

.profile-photo-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #f3f4f6;
    border: 3px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #9ca3af;
}

.profile-photo-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}

/* Form Styles */
.form-group {
    margin-bottom: 1rem;
}

.form-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    display: block;
}

.form-control {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

/* Specific styling for profile form inputs */
.personal-info-card .form-control {
    max-width: 400px; /* Limit the maximum width */
    width: 100%;
}

/* Make email field slightly wider than username */
.personal-info-card .form-control[name="email"] {
    max-width: 450px;
}

.form-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    outline: none;
}

/* Password Input Group */
.password-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.password-input-group .form-control {
    padding-right: 40px; /* Make room for the toggle button inside */
    padding-left: 12px;
}

.password-toggle-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 6px;
    border-radius: 4px;
    transition: all 0.2s ease;
    z-index: 10;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.password-toggle-btn:hover {
    color: #374151;
    background: rgba(0, 0, 0, 0.05);
}

.password-toggle-btn:focus {
    outline: none;
    color: #6366f1;
    background: rgba(99, 102, 241, 0.1);
}

.password-toggle-btn:active {
    background: rgba(0, 0, 0, 0.1);
}

/* Make the eye icon look more integrated */
.password-toggle-btn i {
    transition: all 0.2s ease;
}

.password-toggle-btn:hover i {
    transform: scale(1.1);
}

/* Ensure the input field doesn't interfere with the button */
.password-input-group .form-control:focus + .password-toggle-btn {
    color: #6366f1;
}

.form-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 4px;
}

/* Skills Display */
.skills-display-container {
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
}

.skills-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
}

.skill-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.skill-badge-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.2);
}

.skill-badge-secondary {
    background: #e5e7eb;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

/* Button Styles */
.btn-primary {
    background: #6366f1;
    border: 1px solid #6366f1;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: #4f46e5;
    border-color: #4f46e5;
    transform: translateY(-1px);
}

.btn-outline-primary {
    border: 1px solid #6366f1;
    color: #6366f1;
    background: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-outline-primary:hover {
    background: #6366f1;
    color: white;
}

.btn-outline-secondary {
    border: 1px solid #d1d5db;
    color: #6b7280;
    background: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-outline-secondary:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.btn-link {
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.2s ease;
}

.btn-link:hover {
    color: #dc2626;
}

/* Validation Styles */
.needs-validation .form-control:invalid {
    border-color: #dc2626;
}

.needs-validation .form-control:valid {
    border-color: #10b981;
}

.invalid-feedback {
    color: #dc2626;
    font-size: 0.75rem;
    margin-top: 4px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-photo-section {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .profile-photo-actions {
        flex-direction: row;
        justify-content: center;
    }
    
    .profile-card {
        padding: 16px;
    }
    
    /* On mobile, make inputs full width again */
    .personal-info-card .form-control {
        max-width: 100%;
    }
}

/* On larger screens, keep the limited width */
@media (min-width: 769px) {
    .personal-info-card .form-control {
        max-width: 400px;
    }
    
    .personal-info-card .form-control[name="email"] {
        max-width: 450px;
    }
}

/* Dark theme adjustments */
.dark-theme .profile-card {
    background: #1f2937;
    border-color: #374151;
}

.dark-theme .profile-card-title {
    color: #f9fafb;
    border-bottom-color: #374151;
}

.dark-theme .form-label {
    color: #e5e7eb;
}

.dark-theme .form-control {
    background: #374151;
    border-color: #4b5563;
    color: #f9fafb;
}

.dark-theme .form-control:focus {
    border-color: #6366f1;
    background: #374151;
}

.dark-theme .form-text {
    color: #9ca3af;
}

.dark-theme .skills-display-container {
    background: #374151;
    border-color: #4b5563;
}

.dark-theme .password-toggle-btn {
    color: #9ca3af;
}

.dark-theme .password-toggle-btn:hover {
    color: #e5e7eb;
    background: rgba(255, 255, 255, 0.1);
}

.dark-theme .password-toggle-btn:focus {
    color: #6366f1;
    background: rgba(99, 102, 241, 0.2);
}

.dark-theme .password-toggle-btn:active {
    background: rgba(255, 255, 255, 0.15);
}

.dark-theme .card-header-section {
    border-bottom-color: #374151;
}

.dark-theme .profile-card-title {
    color: #f9fafb;
}

.dark-theme .personal-info-card {
    border-left-color: #6366f1;
}

.dark-theme .password-card {
    border-left-color: #f59e0b;
}

.dark-theme .password-card::before {
    background: linear-gradient(to bottom, transparent, #4b5563, transparent);
}
</style>
@endpush

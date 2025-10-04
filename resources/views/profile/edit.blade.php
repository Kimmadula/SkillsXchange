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
                    Profile updated successfully!
                @elseif(session('status') === 'password-updated')
                    Password updated successfully!
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

        <form method="POST" action="{{ route('profile.test') }}" enctype="multipart/form-data" class="needs-validation" novalidate>
            @csrf
            <input type="hidden" name="_method" value="PATCH">
            
            <!-- Debug info -->
            <input type="hidden" name="debug" value="1">

            <div class="row">
                <!-- Profile Photo -->
                <div class="col-lg-6 mb-4">
                    <div class="dashboard-card">
                        <h3 class="h5 fw-bold text-gradient mb-4">
                            <i class="fas fa-camera me-2"></i>Profile Photo
                        </h3>
                        
                        <div class="mb-4">
                            <label class="form-label">Upload Profile Photo</label>
                            <div class="d-flex align-items-center gap-3">
                                <div class="profile-photo-preview">
                                    @if($user->photo && file_exists(storage_path('app/public/' . $user->photo)))
                                        <img src="{{ asset('storage/' . $user->photo) }}" alt="Current Photo" id="photoPreview" class="preview-image">
                                    @else
                                        <div id="photoPreview" class="preview-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*" onchange="previewPhoto(this)">
                                    <div class="form-text">Upload a profile photo (max 2MB)</div>
                                </div>
                            </div>
                            @error('photo')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="col-lg-6 mb-4">
                    <div class="dashboard-card">
                        <h3 class="h5 fw-bold text-gradient mb-4">
                            <i class="fas fa-cog me-2"></i>Account Information
                        </h3>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                   id="username" name="username" value="{{ old('username', $user->username) }}" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skills Display (Read-only) -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="dashboard-card">
                        <h3 class="h5 fw-bold text-gradient mb-4">
                            <i class="fas fa-tools me-2"></i>Skills & Expertise
                        </h3>
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
                </div>
            </div>

            <!-- Password Update Section -->
            <div class="row">
                <div class="col-12">
                    <div class="dashboard-card">
                        <h3 class="h5 fw-bold text-gradient mb-4">
                            <i class="fas fa-lock me-2"></i>Update Password
                        </h3>
                        <p class="text-muted mb-4">Ensure your account is using a long, random password to stay secure.</p>
                        
                        <form method="POST" action="{{ route('password.update') }}" class="needs-validation" novalidate>
                            @csrf
                            <input type="hidden" name="_method" value="PUT">
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="current_password" class="form-label">Current Password *</label>
                                    <input type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" 
                                           id="current_password" name="current_password" required autocomplete="current-password">
                                    @error('current_password', 'updatePassword')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="password" class="form-label">New Password *</label>
                                    <input type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" 
                                           id="password" name="password" required autocomplete="new-password">
                                    @error('password', 'updatePassword')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="password_confirmation" class="form-label">Confirm New Password *</label>
                                    <input type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" 
                                           id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                                    @error('password_confirmation', 'updatePassword')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="h5 fw-bold text-gradient mb-0">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </h3>
                                <p class="text-muted mb-0">Update your profile information</p>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Photo preview functionality
function previewPhoto(input) {
    const preview = document.getElementById('photoPreview');
    const file = input.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="preview-image">`;
        };
        reader.readAsDataURL(file);
    }
}

// Debug form submission
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up form debugging...');
    
    const profileForm = document.querySelector('form[action="{{ route('profile.update') }}"]');
    if (profileForm) {
        console.log('Profile form found:', profileForm);
        console.log('Profile form method field:', profileForm.querySelector('input[name="_method"]'));
        
        profileForm.addEventListener('submit', function(e) {
            console.log('Profile form submitted');
            console.log('Form method:', this.method);
            console.log('Form action:', this.action);
            
            // Check if the hidden _method field exists
            const methodField = this.querySelector('input[name="_method"]');
            if (methodField) {
                console.log('Method field value:', methodField.value);
            } else {
                console.error('No _method field found!');
            }
            
            // Check all form data
            const formData = new FormData(this);
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            // Debug form submission
            console.log('Form submitting with method:', this.method, 'and _method:', methodField ? methodField.value : 'NOT FOUND');
        });
    } else {
        console.error('Profile form not found!');
    }
    
    const passwordForm = document.querySelector('form[action="{{ route('password.update') }}"]');
    if (passwordForm) {
        console.log('Password form found:', passwordForm);
        console.log('Password form method field:', passwordForm.querySelector('input[name="_method"]'));
        
        passwordForm.addEventListener('submit', function(e) {
            console.log('Password form submitted');
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
    } else {
        console.error('Password form not found!');
    }
});
</script>
@endpush

@push('styles')
<style>
.profile-photo-preview {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-placeholder {
    font-size: 2rem;
    color: #6c757d;
}

.skills-display-container {
    padding: 1rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background: #f8f9fa;
}

.skills-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.skill-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
}

.skill-badge-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

.skill-badge-secondary {
    background: #e9ecef;
    color: #6c757d;
    border: 1px solid #dee2e6;
}

.needs-validation .form-control:invalid,
.needs-validation .form-select:invalid {
    border-color: #dc3545;
}

.needs-validation .form-control:valid,
.needs-validation .form-select:valid {
    border-color: #198754;
}
</style>
@endpush

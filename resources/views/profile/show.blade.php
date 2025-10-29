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
        <!-- Profile Header -->
        <div class="row mb-4 mb-md-5">
            <div class="col-12">
                <div class="dashboard-card dashboard-card--stats slide-up">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-4">
                            <form id="photoUploadForm" method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                @if($user->is_verified)
                                <input type="file" id="photoInput" name="photo" accept="image/*" style="display: none;" onchange="uploadPhoto()">
                                <label for="photoInput" style="cursor: pointer; display: inline-block;">
                                @endif
                                    @if($user->photo && file_exists(storage_path('app/public/' . $user->photo)))
                                        <div class="position-relative" style="width:96px; height:96px; border-radius:50%; overflow:hidden; border:2px solid rgba(99,102,241,.35);">
                                            <img src="{{ asset('storage/' . $user->photo) }}" alt="Profile Photo" style="width:100%; height:100%; object-fit:cover;">
                                            @if($user->is_verified)
                                                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background:rgba(0,0,0,.35); opacity:0; transition:opacity .2s;">
                                                    <i class="fas fa-camera text-light"></i>
                                                </div>
                                            @else
                                                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background:rgba(0,0,0,.35);">
                                                    <i class="fas fa-lock text-light" title="Photo locked until admin verification"></i>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center justify-content-center" style="width:96px; height:96px; border-radius:50%; background:rgba(99,102,241,.08); border:2px dashed rgba(99,102,241,.35); color:#94a3b8;">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                    @endif
                                @if($user->is_verified)
                                </label>
                                @endif
                            </form>
                        </div>
                        <div class="profile-info">
                            <h1 class="h2 fw-bold text-gradient mb-1">{{ $user->name }}</h1>
                            <p class="text-muted mb-2">{{ $user->email }}</p>
                            <div class="profile-badges">
                                <span class="badge bg-primary me-2">{{ ucfirst($user->role) }}</span>
                                <span class="badge bg-info">{{ ucfirst($user->plan) }} Plan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Information -->
        @if(session('status') === 'password-updated')
        <div class="alert alert-success mb-3">
            <i class="fas fa-check-circle me-2"></i>
            Your password has been updated successfully.
        </div>
        @endif

        @if(!$user->is_verified)
        <div class="alert alert-warning mb-3">
            <i class="fas fa-info-circle me-2"></i>
            Awaiting admin verification. You can edit personal details (except email). Profile photo is locked. After verification, most fields become read‑only; only your profile photo and username will remain editable.
        </div>
        @else
        <div class="alert alert-success mb-3">
            <i class="fas fa-check-circle me-2"></i>
            Account verified. You may update your profile photo and change your username (password required for username). Other profile details are locked.
        </div>
        @endif

        <div class="row">
            <!-- Personal Information -->
            <div class="col-lg-8 mb-4">
                <div class="dashboard-card dashboard-card--stats slide-up">
                    <h3 class="h5 fw-bold text-gradient mb-4">
                        <i class="fas fa-user me-2"></i>Personal Information
                    </h3>
                    <div class="profile-info-list">
                        <div class="info-item">
                            <span class="info-label">Full Name</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ $user->name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Username</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ $user->username }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ $user->email }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Gender</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ ucfirst($user->gender) }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Birth Date</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ $user->bdate ? $user->bdate->format('M j, Y') : 'Not provided' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Age</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ $user->bdate ? $user->bdate->diffInYears(now()) . ' years old' : 'Not provided' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Address</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ $user->address ?: 'Not provided' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Member Since</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ $user->created_at->format('M j, Y') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Skills</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ $acquiredSkills ? $acquiredSkills->count() : 0 }} skills</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="dashboard-card dashboard-card--stats slide-up">
                    <h3 class="h5 fw-bold text-gradient mb-4">
                        <i class="fas fa-cog me-2"></i>Account Settings
                    </h3>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary btn-action mb-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </button>
                        <button type="button" class="btn btn-success btn-action mb-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </div>
                </div>
            </div>

            <!-- Skills Information -->
            <div class="col-lg-4 mb-4">
                <div class="dashboard-card dashboard-card--stats slide-up">
                    <h3 class="h5 fw-bold text-gradient mb-4">
                        <i class="fas fa-trophy me-2 text-warning"></i>Skill Lists
                        <small class="text-muted d-block" style="font-size: 0.8rem; font-weight: normal;">All your skills</small>
                    </h3>
                    @if($acquiredSkills && $acquiredSkills->count() > 0)
                        @php
                            $allUserSkills = $user->skills;
                            $acquiredSkillsList = $user->getAcquiredSkills();
                            $registeredSkills = $allUserSkills->filter(function($skill) use ($acquiredSkillsList) {
                                return !$acquiredSkillsList->contains('skill_id', $skill->skill_id);
                            });
                        @endphp

                        <!-- Registered Skills -->
                        @if($registeredSkills->count() > 0)
                            <div class="mb-3">
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-user-plus me-1"></i>Registered Skills
                                </h6>
                                <div class="skills-container">
                                    @foreach($registeredSkills as $skill)
                                        <span class="skill-pill skill-pill-registered">
                                            {{ $skill->name }}
                                            @if($skill->skill_id == ($user->skill_id ?? null))
                                                <i class="fas fa-star ms-1" title="Primary Skill"></i>
                                            @else
                                                <i class="fas fa-user-plus ms-1" title="Registered skill"></i>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Acquired Skills -->
                        @if($acquiredSkillsList->count() > 0)
                            <div class="mb-3">
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-graduation-cap me-1"></i>Acquired Skills
                                </h6>
                                <div class="skills-container">
                                    @foreach($acquiredSkillsList as $skill)
                                        <span class="skill-pill skill-pill-acquired">
                                            {{ $skill->name }}
                                            <i class="fas fa-graduation-cap ms-1" title="Acquired through trading"></i>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    @else
                        <div class="text-muted mb-3">
                            <p>No skills added yet.</p>
                            <a href="{{ route('trades.matches') }}" class="btn btn-sm btn-outline-primary">Start trading to acquire new skills</a>
                        </div>
                    @endif
                </div>

                <!-- User Feedback & Ratings Section -->
                <div class="dashboard-card dashboard-card--stats slide-up mt-4">
                    <h3 class="h5 fw-bold text-gradient mb-4">
                        <i class="fas fa-star me-2 text-warning"></i>Feedback & Ratings
                        <small class="text-muted d-block" style="font-size: 0.8rem; font-weight: normal;">What others say about you</small>
                    </h3>

                    <div id="user-feedback-section">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading feedback...</span>
                            </div>
                            <p class="text-muted mt-2">Loading your feedback...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Danger Zone -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card dashboard-card--stats slide-up border-danger">
                    <h3 class="h5 fw-bold text-danger mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                    </h3>
                    <p class="text-muted mb-3">Once you delete your account, there is no going back. Please be certain.</p>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <i class="fas fa-trash me-2"></i>Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProfileForm" method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('patch')
                <div class="modal-body">
                    @if($user->is_verified)
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        After admin verification, only your username can be changed. Please confirm your password to save changes.
                    </div>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="{{ $user->username }}" required>
                        </div>
                        <div class="col-12 mb-1">
                            <label for="username_current_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="username_current_password" name="current_password" required>
                        </div>
                    </div>
                    @else
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstname" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" value="{{ $user->firstname }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lastname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" value="{{ $user->lastname }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="middlename" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middlename" name="middlename" value="{{ $user->middlename }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="male" {{ $user->gender == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ $user->gender == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ $user->gender == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bdate" class="form-label">Birth Date</label>
                            <input type="date" class="form-control" id="bdate" name="bdate" value="{{ $user->bdate ? $user->bdate->format('Y-m-d') : '' }}">
                            <div class="form-text text-info">
                                <i class="fas fa-info-circle me-1"></i>Birth date is editable now. After admin verification it will be locked.
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="{{ $user->username }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" readonly>
                        <div class="form-text text-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>Email cannot be edited at this time. Email editing will be available in a future update and can only be changed once.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3">{{ $user->address }}</textarea>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('profile.password.update') }}">
                @csrf
                @method('put')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="change_current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="change_current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="change_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="change_password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="change_password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="change_password_confirmation" name="password_confirmation" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-key me-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                <form method="POST" action="{{ route('profile.destroy') }}" id="deleteAccountForm">
                    @csrf
                    @method('delete')
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="deleteAccountForm" class="btn btn-danger">Delete Account</button>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Blur active element before any modal opens to avoid aria-hidden focus warnings
    document.addEventListener('show.bs.modal', function () {
        if (document.activeElement && typeof document.activeElement.blur === 'function') {
            document.activeElement.blur();
        }
    });
    document.addEventListener('hide.bs.modal', function () {
        if (document.activeElement && typeof document.activeElement.blur === 'function') {
            document.activeElement.blur();
        }
        setTimeout(function () { document.body.focus(); }, 0);
    });
    document.addEventListener('hidden.bs.modal', function () {
        setTimeout(function () { document.body.focus(); }, 0);
    });
    // Handle profile photo upload
    function uploadPhoto() {
        const fileInput = document.getElementById('photoInput');
        const file = fileInput ? fileInput.files[0] : null;
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            showAlert('File size must be less than 2MB', 'danger');
            return;
        }
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showAlert('Please select a valid image file (JPEG, PNG, GIF, or WebP)', 'danger');
            return;
        }

        const form = document.getElementById('photoUploadForm');
        const formData = new FormData();
        formData.append('photo', file);
        formData.append('_method', 'PATCH');

        // optimistic overlay
        const img = form.querySelector('img');
        const overlay = document.createElement('div');
        overlay.className = 'position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
        overlay.style.background = 'rgba(0,0,0,.35)';
        overlay.innerHTML = '<i class="fas fa-spinner fa-spin text-light"></i>';
        if (img && img.parentElement) img.parentElement.appendChild(overlay);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.success && data.photo_url) {
                if (img) {
                    img.src = data.photo_url + '?t=' + Date.now();
                }
                showAlert('Profile photo updated successfully', 'success');
            } else {
                showAlert((data && data.message) || 'Failed to update profile photo', 'danger');
            }
        })
        .catch(() => showAlert('Failed to update profile photo', 'danger'))
        .finally(() => {
            if (overlay && overlay.parentElement) overlay.parentElement.removeChild(overlay);
            if (fileInput) fileInput.value = '';
        });
    }

    // Handle edit profile form submission
    const editProfileForm = document.getElementById('editProfileForm');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const saveBtn = document.getElementById('saveProfileBtn');
            const originalText = saveBtn.innerHTML;

            // Show loading state
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';

            // Submit form via AJAX
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update profile information in real-time
                    updateProfileInfo(data.user);

                    // Show success message
                    showAlert('Profile updated successfully!', 'success');

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
                    modal.hide();
                } else {
                    // Show error message
                    showAlert(data.message || 'Failed to update profile', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while updating profile', 'danger');
            })
            .finally(() => {
                // Reset button state
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            });
        });
    }

    // Function to update profile information in real-time
    function updateProfileInfo(user) {
        // Update name
        const nameElement = document.querySelector('.profile-info h1');
        if (nameElement) {
            nameElement.textContent = user.name;
        }

        // Update email
        const emailElement = document.querySelector('.profile-info p');
        if (emailElement) {
            emailElement.textContent = user.email;
        }

        // Update personal information
        const infoItems = document.querySelectorAll('.info-item');
        infoItems.forEach(item => {
            const label = item.querySelector('.info-label').textContent.toLowerCase();
            const valueElement = item.querySelector('.info-value');

            switch(label) {
                case 'full name':
                    valueElement.textContent = user.name;
                    break;
                case 'username':
                    valueElement.textContent = user.username;
                    break;
                case 'email':
                    valueElement.textContent = user.email;
                    break;
                case 'gender':
                    valueElement.textContent = user.gender.charAt(0).toUpperCase() + user.gender.slice(1);
                    break;
                case 'birth date':
                    if (user.bdate) {
                        const date = new Date(user.bdate);
                        valueElement.textContent = date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    }
                    break;
                case 'age':
                    if (user.bdate) {
                        const birthDate = new Date(user.bdate);
                        const today = new Date();
                        let age = today.getFullYear() - birthDate.getFullYear();
                        const monthDiff = today.getMonth() - birthDate.getMonth();
                        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                            age--;
                        }
                        valueElement.textContent = age + ' years old';
                    }
                    break;
                case 'address':
                    valueElement.textContent = user.address || 'Not provided';
                    break;
            }
        });
    }

    // Function to show alerts
    function showAlert(message, type) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        // Insert at the top of the container
        const container = document.querySelector('.container');
        container.insertBefore(alertDiv, container.firstChild);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Make uploadPhoto function globally available
    window.uploadPhoto = uploadPhoto;

    // Load user feedback and ratings
    console.log('DOM loaded, starting feedback load for user ID: {{ $user->id }}');
    console.log('loadUserFeedback function available:', typeof window.loadUserFeedback);
    loadUserFeedback();
});

// Test function to verify JavaScript is working
window.testFunction = function testFunction() {
    console.log('Test function called!');
    alert('JavaScript is working! Function: ' + typeof window.loadUserFeedback);
};

// Make function globally accessible
window.loadUserFeedback = async function loadUserFeedback() {
    try {
        console.log('Loading user feedback for user ID: {{ $user->id }}');

        // Test if the container exists
        const container = document.getElementById('user-feedback-section');
        if (!container) {
            console.error('Container user-feedback-section not found!');
            return;
        }
        console.log('Container found:', container);

        // Add timeout to prevent infinite loading
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

        const response = await fetch(`/api/user-ratings/{{ $user->id }}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            signal: controller.signal
        });

        clearTimeout(timeoutId);
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Response error:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Response data:', data);
        console.log('Ratings array:', data.ratings);
        console.log('Total count:', data.total_count);

        if (data.success) {
            if (data.ratings && data.ratings.length > 0) {
                console.log('First rating sample:', data.ratings[0]);
                displayUserFeedback(data.ratings);
            } else {
                console.log('No ratings found');
                displayNoFeedback();
            }
        } else {
            console.log('API returned success: false, message:', data.message);
            displayNoFeedback();
        }
    } catch (error) {
        console.error('Error loading user feedback:', error);
        if (error.name === 'AbortError') {
            console.error('Request timed out');
            displayError('Request timed out. Please try again.');
        } else {
            console.error('Full error details:', error);
            displayError('Failed to load feedback. Please refresh the page. Error: ' + error.message);
        }
    }
};

window.displayUserFeedback = function displayUserFeedback(ratings) {
    const container = document.getElementById('user-feedback-section');

    if (!ratings || ratings.length === 0) {
        displayNoFeedback();
        return;
    }

    console.log('Displaying feedback for', ratings.length, 'ratings');

    // Calculate average ratings
    const avgOverall = ratings.reduce((sum, r) => sum + (r.overall_rating || 0), 0) / ratings.length;
    const avgCommunication = ratings.reduce((sum, r) => sum + (r.communication_rating || 0), 0) / ratings.length;
    const avgHelpfulness = ratings.reduce((sum, r) => sum + (r.helpfulness_rating || 0), 0) / ratings.length;
    const avgKnowledge = ratings.reduce((sum, r) => sum + (r.knowledge_rating || 0), 0) / ratings.length;


    const showAverageRatings = false; // Set to true to show average ratings section

    container.innerHTML = `
        ${showAverageRatings ? `
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="rating-summary">
                        <h6 class="text-muted mb-3"><i class="fas fa-chart-bar me-2"></i>Average Ratings</h6>
                        <div class="rating-item mb-2">
                            <span class="rating-label">Overall Experience:</span>
                            <div class="stars">${generateStars(avgOverall)}</div>
                            <span class="rating-value">${avgOverall.toFixed(1)}/5</span>
                        </div>
                        <div class="rating-item mb-2">
                            <span class="rating-label">Communication:</span>
                            <div class="stars">${generateStars(avgCommunication)}</div>
                            <span class="rating-value">${avgCommunication.toFixed(1)}/5</span>
                        </div>
                        <div class="rating-item mb-2">
                            <span class="rating-label">Helpfulness:</span>
                            <div class="stars">${generateStars(avgHelpfulness)}</div>
                            <span class="rating-value">${avgHelpfulness.toFixed(1)}/5</span>
                        </div>
                        <div class="rating-item mb-2">
                            <span class="rating-label">Knowledge:</span>
                            <div class="stars">${generateStars(avgKnowledge)}</div>
                            <span class="rating-value">${avgKnowledge.toFixed(1)}/5</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="feedback-stats">
                        <h6 class="text-muted mb-3"><i class="fas fa-info-circle me-2"></i>Feedback Summary</h6>
                        <div class="stat-item">
                            <i class="fas fa-star text-warning"></i>
                            <span>Total Ratings: ${ratings.length}</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-comments text-info"></i>
                            <span>Written Feedback: ${ratings.filter(r => r.written_feedback).length}</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-clock text-secondary"></i>
                            <span>Most Recent: ${new Date(ratings[0].created_at).toLocaleDateString()}</span>
                        </div>
                    </div>
                </div>
            </div>
        ` : ''}

        <div class="feedback-list mt-4">
            ${ratings.slice(0, 5).map(rating => {
                // Check if rater exists
                if (!rating.rater) {
                    console.warn('Rating missing rater:', rating);
                }

                const raterName = rating.rater ? rating.rater.name : 'Anonymous User';
                const raterUsername = rating.rater ? `@${rating.rater.username}` : '';

                return `
                    <div class="feedback-item border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>${raterName}</strong>
                                ${raterUsername ? `<span class="text-muted ms-1">${raterUsername}</span>` : ''}
                                <small class="text-muted d-block">${new Date(rating.created_at).toLocaleDateString()}</small>
                            </div>
                            <div class="rating-display">
                                <div class="stars">${generateStars(rating.overall_rating)}</div>
                                <small class="text-muted">${rating.overall_rating}/5</small>
                            </div>
                        </div>
                        ${rating.written_feedback ? `
                            <div class="feedback-text mt-2 p-2 bg-light rounded">
                                <p class="mb-0">"${escapeHtml(rating.written_feedback)}"</p>
                            </div>
                        ` : ''}
                        <div class="rating-breakdown mt-2">
                            <small class="text-muted">
                                <span class="me-2">
                                    <i class="fas fa-comments text-primary"></i> Communication: ${generateStars(rating.communication_rating)} ${rating.communication_rating}/5
                                </span>
                                <span class="me-2">
                                    <i class="fas fa-hands-helping text-success"></i> Helpfulness: ${generateStars(rating.helpfulness_rating)} ${rating.helpfulness_rating}/5
                                </span>
                                <span>
                                    <i class="fas fa-brain text-info"></i> Knowledge: ${generateStars(rating.knowledge_rating)} ${rating.knowledge_rating}/5
                                </span>
                            </small>
                        </div>
                        ${rating.session_type ? `
                            <div class="mt-2">
                                <span class="badge bg-secondary">${formatSessionType(rating.session_type)}</span>
                                ${rating.session_duration ? `<span class="badge bg-info ms-1">${rating.session_duration} minutes</span>` : ''}
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('')}
            ${ratings.length > 5 ? `
                <div class="text-center mt-3">
                    <small class="text-muted">Showing 5 of ${ratings.length} total ratings</small>
                </div>
            ` : ''}
        </div>
    `;
}

window.displayNoFeedback = function displayNoFeedback() {
    const container = document.getElementById('user-feedback-section');
    container.innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-star fa-3x text-muted mb-3"></i>
            <p class="text-muted mb-0">No feedback or ratings yet</p>
            <small class="text-muted">Complete skill exchange sessions to receive feedback from other users!</small>
        </div>
    `;
}

window.displayError = function displayError(message) {
    const container = document.getElementById('user-feedback-section');
    container.innerHTML = `
        <div class="alert alert-warning text-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
        </div>
    `;
}

function displayLoginRequired() {
    const container = document.getElementById('user-feedback-section');
    container.innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-lock fa-3x text-muted mb-3"></i>
            <p class="text-muted mb-0">Please log in to view your ratings</p>
            <small class="text-muted">Sign in to see your feedback and ratings from skill exchange sessions!</small>
        </div>
    `;
}

function hideRatingsSection() {
    const container = document.getElementById('user-feedback-section');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-eye-slash fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">Ratings are private</p>
                <small class="text-muted">Only the user can view their own ratings and feedback.</small>
            </div>
        `;
    }
}

window.generateStars = function generateStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

    let stars = '';
    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star"></i>';
    }
    if (hasHalfStar) {
        stars += '<i class="fas fa-star-half-alt"></i>';
    }
    for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star"></i>';
    }

    return stars;
}

window.escapeHtml = function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.formatSessionType = function formatSessionType(type) {
    const types = {
        'chat_session': 'Chat Session',
        'trade_session': 'Trade Session',
        'skill_sharing': 'Skill Sharing'
    };
    return types[type] || type;
}
</script>
@endpush

@push('styles')
<style>
/* Profile Header Styles */
.profile-header-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 2rem;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.profile-picture-container {
    position: relative;
    display: inline-block;
}

.profile-picture-wrapper {
    position: relative;
    display: inline-block;
    border-radius: 50%;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.profile-picture-wrapper:hover {
    transform: scale(1.05);
}

.profile-picture {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.3);
    transition: all 0.3s ease;
}

.profile-picture-placeholder {
    width: 120px;
    height: 120px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: rgba(255,255,255,0.8);
    border: 4px solid rgba(255,255,255,0.3);
    transition: all 0.3s ease;
}

.profile-picture-placeholder:hover {
    background: rgba(255,255,255,0.3);
    color: white;
}

.photo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    border-radius: 50%;
}

.profile-picture-wrapper:hover .photo-overlay {
    opacity: 1;
}

.photo-overlay i {
    color: white;
    font-size: 1.5rem;
}

.profile-info h1 {
    color: white;
    margin-bottom: 0.5rem;
}

.profile-info p {
    color: rgba(255,255,255,0.9);
    margin-bottom: 1rem;
}

.profile-badges .badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
}

/* Profile Card Styles */
.profile-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    margin-bottom: 1.5rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.profile-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.profile-card h3 {
    color: #495057;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
    margin-bottom: 1.5rem;
}

/* Profile Info List Layout */
.profile-info-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    min-width: 120px;
    text-align: left;
}

.info-separator {
    color: #6c757d;
    font-weight: 600;
    margin: 0 0.5rem;
    font-size: 0.9rem;
}

.info-value {
    color: #212529;
    font-size: 0.9rem;
    font-weight: 500;
    flex: 1;
}

/* Responsive adjustments for list layout */
@media (max-width: 768px) {
    .info-label {
        min-width: 100px;
        font-size: 0.85rem;
    }

    .info-value {
        font-size: 0.85rem;
    }
}

/* Action Buttons */
.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.btn-action {
    width: 100%;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    text-align: left;
}

.btn-action:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Skills Container */
.skills-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.skill-pill {
    display: inline-flex !important;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    margin: 0.25rem;
}

.skill-pill-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
}

.skill-pill-secondary {
    background: #f8f9fa;
    color: #495057;
    border-color: #dee2e6;
}

.skill-pill-acquired {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border-color: #28a745;
}

.skill-pill-registered {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border-color: #007bff;
}

.skill-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.skill-pill i {
    font-size: 0.8rem;
}

/* Ensure skill pills are always visible */
.skills-container .skill-pill {
    display: inline-flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-header-section {
        padding: 1.5rem;
        text-align: center;
    }

    .profile-picture-container {
        margin-bottom: 1rem;
    }

    .profile-info-grid {
        grid-template-columns: 1fr;
    }

    .action-buttons {
        flex-direction: column;
    }

    .btn-action {
        text-align: center;
    }
}

/* Modal Enhancements */
.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
    border-radius: 15px 15px 0 0;
    background: #f8f9fa;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    border-radius: 0 0 15px 15px;
    background: #f8f9fa;
}

.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Alert Styles */
.alert {
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.profile-header-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.profile-picture-container {
    flex-shrink: 0;
}

.profile-picture {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.profile-picture:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.profile-picture-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: 4px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: rgba(255, 255, 255, 0.8);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.profile-picture-placeholder:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}


.profile-badges .badge {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

.profile-info-grid {
    display: grid;
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    color: #212529;
    font-size: 1rem;
}

.skills-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
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
    background: #f8f9fa;
    color: #6c757d;
    border: 1px solid #dee2e6;
}

.skill-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}


.dashboard-card.border-danger {
    border: 2px solid #dc3545 !important;
}

@media (max-width: 768px) {
    .profile-header-card {
        padding: 1.5rem;
    }

    .profile-picture {
        width: 60px;
        height: 60px;
    }

    .profile-picture-placeholder {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }

    .d-flex.align-items-center.mb-2 {
        flex-direction: column;
        text-align: center;
    }

    .profile-picture-container {
        margin-bottom: 1rem;
        margin-right: 0 !important;
    }
}
</style>


<style>
.rating-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.rating-label {
    min-width: 120px;
    font-size: 0.9rem;
}

.stars {
    color: #ffc107;
    font-size: 1.1rem;
}

.rating-value {
    font-weight: 600;
    color: #495057;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.feedback-item {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef !important;
}

.feedback-text {
    font-style: italic;
    color: #495057;
}

.rating-breakdown .stars {
    font-size: 0.9rem;
}
</style>
@endpush

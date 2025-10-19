@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5">
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
                <div class="profile-header-section">
                    <div class="d-flex align-items-center mb-3">
                        <div class="profile-picture-container me-4">
                            <form id="photoUploadForm" method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <input type="file" id="photoInput" name="photo" accept="image/*" style="display: none;" onchange="uploadPhoto()">
                                <label for="photoInput" style="cursor: pointer; display: inline-block;">
                                    @if($user->photo && file_exists(storage_path('app/public/' . $user->photo)))
                                        <div class="profile-picture-wrapper">
                                            <img src="{{ asset('storage/' . $user->photo) }}" alt="Profile Photo" class="profile-picture">
                                            <div class="photo-overlay">
                                                <i class="fas fa-camera"></i>
                                            </div>
                                        </div>
                                    @else
                                        <div class="profile-picture-placeholder">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                    @endif
                                </label>
                            </form>
                        </div>
                        <div class="profile-info">
                            <h1 class="h2 fw-bold mb-1">{{ $user->name }}</h1>
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
        <div class="row">
            <!-- Personal Information -->
            <div class="col-lg-8 mb-4">
                <div class="profile-card">
                    <h3 class="h5 fw-bold mb-4">
                        <i class="fas fa-user me-2"></i>Personal Information
                    </h3>
                    <div class="profile-info-compact">
                        <div class="info-row">
                            <div class="info-group">
                                <label class="info-label">Full Name</label>
                                <div class="info-value">{{ $user->name }}</div>
                            </div>
                            <div class="info-group">
                                <label class="info-label">Username</label>
                                <div class="info-value">{{ $user->username }}</div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-group">
                                <label class="info-label">Email</label>
                                <div class="info-value">{{ $user->email }}</div>
                            </div>
                            <div class="info-group">
                                <label class="info-label">Gender</label>
                                <div class="info-value">{{ ucfirst($user->gender) }}</div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-group">
                                <label class="info-label">Birth Date</label>
                                <div class="info-value">{{ $user->bdate ? $user->bdate->format('M j, Y') : 'Not provided' }}</div>
                            </div>
                            <div class="info-group">
                                <label class="info-label">Age</label>
                                <div class="info-value">{{ $user->bdate ? $user->bdate->diffInYears(now()) . ' years old' : 'Not provided' }}</div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-group full-width">
                                <label class="info-label">Address</label>
                                <div class="info-value">{{ $user->address ?: 'Not provided' }}</div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-group">
                                <label class="info-label">Member Since</label>
                                <div class="info-value">{{ $user->created_at->format('M j, Y') }}</div>
                            </div>
                            <div class="info-group">
                                <label class="info-label">Skills Acquired</label>
                                <div class="info-value">{{ $acquiredSkills ? $acquiredSkills->count() : 0 }} skills</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="profile-card">
                    <h3 class="h5 fw-bold mb-4">
                        <i class="fas fa-cog me-2"></i>Account Settings
                    </h3>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary btn-action mb-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </button>
                        <button type="button" class="btn btn-success btn-action mb-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                        <button type="button" class="btn btn-warning btn-action mb-2" data-bs-toggle="modal" data-bs-target="#changeUsernameModal">
                            <i class="fas fa-user-edit me-2"></i>Change Username
                        </button>
                    </div>
                </div>
            </div>

            <!-- Skills Information -->
            <div class="col-lg-4 mb-4">
                <div class="profile-card">
                    <h3 class="h5 fw-bold mb-4">
                        <i class="fas fa-trophy me-2 text-warning"></i>Skill Lists
                        <small class="text-muted d-block" style="font-size: 0.8rem; font-weight: normal;">Acquired through trading</small>
                    </h3>
                    @if($acquiredSkills && $acquiredSkills->count() > 0)
                        <div class="skills-container">
                            @foreach($acquiredSkills as $skill)
                                <span class="skill-pill {{ $skill->skill_id == ($user->skill_id ?? null) ? 'skill-pill-primary' : 'skill-pill-secondary' }}">
                                    {{ $skill->name }}
                                    @if($skill->skill_id == ($user->skill_id ?? null))
                                        <i class="fas fa-star ms-1" title="Primary Skill"></i>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No skills acquired through trading yet. <a href="{{ route('trades.index') }}">Start trading to acquire new skills</a></p>
                    @endif
                </div>
            </div>
        </div>


        <!-- Danger Zone -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card border-danger">
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
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
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
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="{{ $user->username }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3">{{ $user->address }}</textarea>
                    </div>
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
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
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
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
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

<!-- Change Username Modal -->
<div class="modal fade" id="changeUsernameModal" tabindex="-1" aria-labelledby="changeUsernameModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeUsernameModalLabel">Change Username</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('patch')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_username" class="form-label">New Username</label>
                        <input type="text" class="form-control" id="new_username" name="username" value="{{ $user->username }}" required>
                        <div class="form-text">Choose a unique username that others can use to find you.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-user-edit me-2"></i>Change Username
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
    // Handle profile photo upload
    function uploadPhoto() {
        const fileInput = document.getElementById('photoInput');
        const file = fileInput.files[0];
        
        if (!file) return;
        
        // Validate file size (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            showAlert('File size must be less than 2MB', 'danger');
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showAlert('Please select a valid image file (JPEG, PNG, GIF, or WebP)', 'danger');
            return;
        }
        
        // Show loading state
        const form = document.getElementById('photoUploadForm');
        const originalSubmit = form.querySelector('button[type="submit"]');
        if (originalSubmit) {
            originalSubmit.disabled = true;
            originalSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
        }
        
        // Submit form
        form.submit();
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
});
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

/* Profile Info Grid - Compact Layout */
.profile-info-compact {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.info-row:last-child {
    border-bottom: none;
}

.info-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-group.full-width {
    grid-column: 1 / -1;
}

.info-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.85rem;
    margin-bottom: 0;
}

.info-value {
    color: #495057;
    font-size: 0.95rem;
    font-weight: 500;
}

/* Responsive adjustments for compact layout */
@media (max-width: 768px) {
    .info-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .info-group.full-width {
        grid-column: 1;
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
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 2px solid transparent;
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

.skill-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.skill-pill i {
    font-size: 0.8rem;
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
@endpush

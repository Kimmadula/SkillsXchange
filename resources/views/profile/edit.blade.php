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
                {{ session('status') === 'profile-updated' ? 'Profile updated successfully!' : session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="needs-validation" novalidate>
            @csrf
            @method('patch')

            <div class="row">
                <!-- Personal Information -->
                <div class="col-lg-6 mb-4">
                    <div class="dashboard-card">
                        <h3 class="h5 fw-bold text-gradient mb-4">
                            <i class="fas fa-user me-2"></i>Personal Information
                        </h3>
                        
                        <!-- Profile Photo -->
                        <div class="mb-4">
                            <label class="form-label">Profile Photo</label>
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

                        <!-- Name Fields -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="firstname" class="form-label">First Name *</label>
                                <input type="text" class="form-control @error('firstname') is-invalid @enderror" 
                                       id="firstname" name="firstname" value="{{ old('firstname', $user->firstname) }}" required>
                                @error('firstname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lastname" class="form-label">Last Name *</label>
                                <input type="text" class="form-control @error('lastname') is-invalid @enderror" 
                                       id="lastname" name="lastname" value="{{ old('lastname', $user->lastname) }}" required>
                                @error('lastname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="middlename" class="form-label">Middle Name</label>
                            <input type="text" class="form-control @error('middlename') is-invalid @enderror" 
                                   id="middlename" name="middlename" value="{{ old('middlename', $user->middlename) }}">
                            @error('middlename')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Gender *</label>
                                <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $user->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="bdate" class="form-label">Birth Date *</label>
                                <input type="date" class="form-control @error('bdate') is-invalid @enderror" 
                                       id="bdate" name="bdate" value="{{ old('bdate', $user->bdate?->format('Y-m-d')) }}" required>
                                @error('bdate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address *</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" name="address" rows="3" required>{{ old('address', $user->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
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

                        <!-- Skills Selection -->
                        <div class="mb-3">
                            <label class="form-label">Skills & Expertise *</label>
                            <div class="skills-selection-container">
                                <div class="selected-skills" id="selectedSkills">
                                    @foreach($user->skills as $skill)
                                        <span class="selected-skill" data-skill-id="{{ $skill->skill_id }}">
                                            {{ $skill->skill_name }}
                                            <button type="button" class="remove-skill" onclick="removeSkill({{ $skill->skill_id }})">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                                <div class="skills-search-container">
                                    <input type="text" class="form-control" id="skillSearch" placeholder="Search skills..." autocomplete="off">
                                    <div class="skills-dropdown" id="skillsDropdown" style="display: none;">
                                        <!-- Skills will be populated here -->
                                    </div>
                                </div>
                                <input type="hidden" name="selected_skills" id="selectedSkillsInput" value="{{ $user->skills->pluck('skill_id')->toJson() }}">
                            </div>
                            @error('selected_skills')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
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
                            @method('put')
                            
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

// Skills selection functionality
let selectedSkills = @json($user->skills->pluck('skill_id')->toArray());
let allSkills = @json($skills);

function updateSelectedSkillsInput() {
    document.getElementById('selectedSkillsInput').value = JSON.stringify(
        selectedSkills.map(skillId => ({ id: skillId }))
    );
}

function addSkill(skillId, skillName) {
    if (!selectedSkills.includes(skillId)) {
        selectedSkills.push(skillId);
        const selectedSkillsContainer = document.getElementById('selectedSkills');
        const skillElement = document.createElement('span');
        skillElement.className = 'selected-skill';
        skillElement.setAttribute('data-skill-id', skillId);
        skillElement.innerHTML = `
            ${skillName}
            <button type="button" class="remove-skill" onclick="removeSkill(${skillId})">
                <i class="fas fa-times"></i>
            </button>
        `;
        selectedSkillsContainer.appendChild(skillElement);
        updateSelectedSkillsInput();
    }
    hideSkillsDropdown();
}

function removeSkill(skillId) {
    selectedSkills = selectedSkills.filter(id => id !== skillId);
    const skillElement = document.querySelector(`[data-skill-id="${skillId}"]`);
    if (skillElement) {
        skillElement.remove();
    }
    updateSelectedSkillsInput();
}

function showSkillsDropdown() {
    document.getElementById('skillsDropdown').style.display = 'block';
}

function hideSkillsDropdown() {
    document.getElementById('skillsDropdown').style.display = 'none';
    document.getElementById('skillSearch').value = '';
}

function filterSkills(searchTerm) {
    const dropdown = document.getElementById('skillsDropdown');
    const filteredSkills = allSkills.filter(skill => 
        skill.skill_name.toLowerCase().includes(searchTerm.toLowerCase()) &&
        !selectedSkills.includes(skill.skill_id)
    );
    
    dropdown.innerHTML = filteredSkills.map(skill => 
        `<div class="skill-option" onclick="addSkill(${skill.skill_id}, '${skill.skill_name}')">
            ${skill.skill_name}
        </div>`
    ).join('');
}

// Event listeners
document.getElementById('skillSearch').addEventListener('focus', showSkillsDropdown);
document.getElementById('skillSearch').addEventListener('input', function(e) {
    filterSkills(e.target.value);
    showSkillsDropdown();
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.skills-selection-container')) {
        hideSkillsDropdown();
    }
});

// Initialize selected skills input
updateSelectedSkillsInput();
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

.skills-selection-container {
    position: relative;
}

.selected-skills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
    min-height: 40px;
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background: #f8f9fa;
}

.selected-skill {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.remove-skill {
    background: none;
    border: none;
    color: white;
    margin-left: 0.5rem;
    cursor: pointer;
    padding: 0;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.remove-skill:hover {
    background-color: rgba(255,255,255,0.2);
}

.skills-search-container {
    position: relative;
}

.skills-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 0.375rem 0.375rem;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
}

.skill-option {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s;
}

.skill-option:hover {
    background-color: #f8f9fa;
}

.skill-option:last-child {
    border-bottom: none;
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

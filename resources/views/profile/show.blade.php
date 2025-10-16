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
                <div class="profile-header-card">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="d-flex align-items-center mb-2">
                                <div class="profile-picture-container me-3">
                                    @if($user->photo && file_exists(storage_path('app/public/' . $user->photo)))
                                        <img src="{{ asset('storage/' . $user->photo) }}" alt="Profile Photo" class="profile-picture">
                                    @else
                                        <div class="profile-picture-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <h1 class="h2 fw-bold text-gradient mb-0">{{ $user->name }}</h1>
                                </div>
                            </div>
                            <p class="text-muted mb-3">{{ $user->email }}</p>
                            <div class="profile-badges">
                                <span class="badge bg-primary me-2">{{ ucfirst($user->role) }}</span>

                                @if($user->email_verified_at)
                                    <span class="badge bg-success me-2">
                                        <i class="fas fa-check-circle me-1"></i>Email Verified
                                    </span>
                                @else
                                    <span class="badge bg-warning me-2">
                                        <i class="fas fa-envelope me-1"></i>Email Not Verified
                                    </span>
                                @endif

                                <span class="badge bg-info">{{ ucfirst($user->plan) }} Plan</span>
                            </div>

                        </div>
                        <div class="col-md-3 text-center text-md-end">
                            <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="row">
            <!-- Personal Information -->
            <div class="col-lg-6 mb-4">
                <div class="dashboard-card">
                    <h3 class="h5 fw-bold text-gradient mb-4">
                        <i class="fas fa-user me-2"></i>Personal Information
                    </h3>
                    <div class="profile-info-grid">
                        <div class="info-item">
                            <label class="info-label">Full Name</label>
                            <div class="info-value">{{ $user->name }}</div>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Gender</label>
                            <div class="info-value">{{ ucfirst($user->gender) }}</div>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Birth Date</label>
                            <div class="info-value">{{ $user->bdate ? $user->bdate->format('F j, Y') : 'Not provided' }}</div>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Age</label>
                            <div class="info-value">{{ $user->bdate ? $user->bdate->age . ' years old' : 'Not provided' }}</div>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Address</label>
                            <div class="info-value">{{ $user->address ?: 'Not provided' }}</div>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Member Since</label>
                            <div class="info-value">{{ $user->created_at->format('F j, Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skills Information -->
            <div class="col-lg-6 mb-4">
                <div class="dashboard-card">
                    <h3 class="h5 fw-bold text-gradient mb-4">
                        <i class="fas fa-tools me-2"></i>Skills & Expertise
                    </h3>
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
                    @else
                        <p class="text-muted">No skills added yet. <a href="{{ route('profile.edit') }}">Add your skills</a></p>
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

@push('styles')
<style>
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

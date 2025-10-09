
@php
use Illuminate\Support\Facades\Storage;
@endphp

@extends('layouts.app')

@section('content')
<div class="admin-dashboard">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <span class="logo-text">SkillsXchange Admin</span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="nav-item active">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="{{ route('admin.skills.index') }}" class="nav-item">
                <i class="fas fa-graduation-cap"></i>
                <span>Skills</span>
            </a>
            <a href="{{ route('admin.exchanges.index') }}" class="nav-item">
                <i class="fas fa-exchange-alt"></i>
                <span>Exchanges</span>
            </a>
            <a href="{{ route('admin.reports.index') }}" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="{{ route('admin.messages.index') }}" class="nav-item">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
            </a>
            <a href="{{ route('admin.settings.index') }}" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <!-- Header -->
        <div class="admin-header">
            <div class="header-left">
                <h1 class="page-title">User Details</h1>
                <p class="page-subtitle">View detailed information about this user</p>
            </div>
            <div class="header-right">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Users
                </a>
            </div>
        </div>

        <!-- User Details Content -->
        <div class="dashboard-content">
            <div class="row">
                <!-- User Profile Card -->
                <div class="col-lg-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Profile Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                @if($user->photo && Storage::disk('public')->exists($user->photo))
                                    <img src="{{ Storage::disk('public')->url($user->photo) }}" 
                                         alt="User Photo" 
                                         class="user-profile-photo"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="user-profile-placeholder" style="display: none;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                @else
                                    <div class="user-profile-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="user-info">
                                <div class="info-item">
                                    <label class="info-label">Full Name</label>
                                    <div class="info-value">{{ $user->name }}</div>
                                </div>
                                
                                <div class="info-item">
                                    <label class="info-label">Email</label>
                                    <div class="info-value">{{ $user->email }}</div>
                                </div>
                                
                                <div class="info-item">
                                    <label class="info-label">Username</label>
                                    <div class="info-value">{{ $user->username }}</div>
                                </div>
                                
                                <div class="info-item">
                                    <label class="info-label">Role</label>
                                    <div class="info-value">
                                        <span class="badge badge-{{ $user->role === 'admin' ? 'danger' : 'primary' }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <label class="info-label">Status</label>
                                    <div class="info-value">
                                        <span class="badge badge-{{ $user->is_verified ? 'success' : 'warning' }}">
                                            {{ $user->is_verified ? 'Verified' : 'Pending' }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <label class="info-label">Plan</label>
                                    <div class="info-value">
                                        <span class="badge badge-info">{{ ucfirst($user->plan) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Details -->
                <div class="col-lg-8 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Personal Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">Gender</label>
                                        <div class="info-value">{{ $user->gender ? ucfirst($user->gender) : 'Not provided' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">Birth Date</label>
                                        <div class="info-value">{{ $user->bdate ? $user->bdate->format('F j, Y') : 'Not provided' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">Age</label>
                                        <div class="info-value">{{ $user->bdate ? $user->bdate->age . ' years old' : 'Not provided' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">Member Since</label>
                                        <div class="info-value">{{ $user->created_at->format('F j, Y') }}</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="info-item">
                                        <label class="info-label">Address</label>
                                        <div class="info-value">{{ $user->address ?: 'Not provided' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills Information -->
                <div class="col-12 mb-4">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Skills & Expertise</h3>
                        </div>
                        <div class="card-body">
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
                                <p class="text-muted">No skills assigned yet.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Account Actions -->
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Account Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="d-flex gap-2 flex-wrap">
                                @if(!$user->is_verified)
                                    <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check me-2"></i>Approve User
                                        </button>
                                    </form>
        @else
                                    <form method="POST" action="{{ route('admin.users.deny', $user) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-times me-2"></i>Revoke Verification
                                        </button>
                                    </form>
        @endif
                                
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Users
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.dashboard-styles')

<style>
.user-profile-photo {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #e5e7eb;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.user-profile-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: #f3f4f6;
    border: 4px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #9ca3af;
    margin: 0 auto;
}

.user-info {
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

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-success {
    background-color: #10b981;
    color: white;
}

.badge-warning {
    background-color: #f59e0b;
    color: white;
}

.badge-info {
    background-color: #3b82f6;
    color: white;
}

.badge-primary {
    background-color: #6366f1;
    color: white;
}

.badge-danger {
    background-color: #ef4444;
    color: white;
}
</style>
@endsection
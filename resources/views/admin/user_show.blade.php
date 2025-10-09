
@php
use Illuminate\Support\Facades\Storage;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="font-sans antialiased">
    <div class="admin-dashboard">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <!-- LOGO IS HERE 
                    <img src="{{ asset('logo.png') }}" alt="SkillsXchange Logo" class="admin-logo">
                    -->                    
                    <span class="logo-text">SkillsXchange Admin</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3h18v18H3zM9 9h6v6H9z" />
                    </svg>
                    <span>Overview</span>
                </a>
                <a href="{{ route('admin.users.index') }}" class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <span>Users</span>
                </a>
                <a href="{{ route('admin.skills.index') }}" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                    </svg>
                    <span>Skills</span>
                </a>
                <a href="{{ route('admin.exchanges.index') }}" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <path d="M16 2v4M8 2v4M3 10h18" />
                    </svg>
                    <span>Exchanges</span>
                </a>
                <a href="{{ route('admin.reports.index') }}" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 20V10M12 20V4M6 20v-6" />
                    </svg>
                    <span>Reports</span>
                </a>
                <a href="{{ route('admin.messages.index') }}" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                        <polyline points="22,6 12,13 2,6" />
                    </svg>
                    <span>Messages</span>
                </a>
                <a href="{{ route('admin.settings.index') }}" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                    </svg>
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
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7" />
                        </svg>
                        Back to Users
                    </a>
                </div>
            </div>

            <!-- User Details Content -->
            <div class="dashboard-content">
                <div class="user-details-card">
                    <div class="card-header">
                        <h3 class="card-title">User Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="user-profile-section">
                            <div class="user-avatar-large">
                                @if($user->photo && Storage::disk('public')->exists($user->photo))
                                    <img src="{{ Storage::disk('public')->url($user->photo) }}" 
                                         alt="User Photo" 
                                         class="user-photo-large"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="user-avatar-fallback-large" style="display: none;">
                                        {{ substr($user->firstname, 0, 1) }}{{ substr($user->lastname, 0, 1) }}
                                    </div>
                                @else
                                    <div class="user-avatar-fallback-large">
                                        {{ substr($user->firstname, 0, 1) }}{{ substr($user->lastname, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            
                            <div class="user-details-grid">
                                <div class="detail-item">
                                    <label class="detail-label">Full Name</label>
                                    <div class="detail-value">{{ $user->name }}</div>
                                </div>
                                
                                <div class="detail-item">
                                    <label class="detail-label">Email</label>
                                    <div class="detail-value">{{ $user->email }}</div>
                                </div>
                                
                                <div class="detail-item">
                                    <label class="detail-label">Username</label>
                                    <div class="detail-value">{{ $user->username }}</div>
                                </div>
                                
                                <div class="detail-item">
                                    <label class="detail-label">Role</label>
                                    <div class="detail-value">
                                        <span class="status-badge status-{{ $user->role === 'admin' ? 'admin' : 'user' }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <label class="detail-label">Status</label>
                                    <div class="detail-value">
                                        <span class="status-badge status-{{ $user->is_verified ? 'verified' : 'pending' }}">
                                            {{ $user->is_verified ? 'Verified' : 'Pending' }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <label class="detail-label">Plan</label>
                                    <div class="detail-value">
                                        <span class="status-badge status-plan">{{ ucfirst($user->plan) }}</span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <label class="detail-label">Gender</label>
                                    <div class="detail-value">{{ $user->gender ? ucfirst($user->gender) : 'Not provided' }}</div>
                                </div>
                                
                                <div class="detail-item">
                                    <label class="detail-label">Birth Date</label>
                                    <div class="detail-value">{{ $user->bdate ? $user->bdate->format('F j, Y') : 'Not provided' }}</div>
                                </div>
                                
                                <div class="detail-item">
                                    <label class="detail-label">Age</label>
                                    <div class="detail-value">{{ $user->bdate ? $user->bdate->age . ' years old' : 'Not provided' }}</div>
                                </div>
                                
                                <div class="detail-item">
                                    <label class="detail-label">Member Since</label>
                                    <div class="detail-value">{{ $user->created_at->format('F j, Y') }}</div>
                                </div>
                                
                                <div class="detail-item full-width">
                                    <label class="detail-label">Address</label>
                                    <div class="detail-value">{{ $user->address ?: 'Not provided' }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Skills Section -->
                        <div class="skills-section">
                            <h4 class="section-title">Skills & Expertise</h4>
                            @if($user->skills->count() > 0)
                                <div class="skills-list">
                                    @foreach($user->skills as $skill)
                                        <span class="skill-tag {{ $skill->skill_id == $user->skill_id ? 'skill-tag-primary' : 'skill-tag-secondary' }}">
                                            {{ $skill->skill_name }}
                                            @if($skill->skill_id == $user->skill_id)
                                                <svg class="skill-star" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" />
                                                </svg>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="no-skills">No skills assigned yet.</p>
                            @endif
                        </div>
                        
                        <!-- Actions Section -->
                        <div class="actions-section">
                            <h4 class="section-title">Account Actions</h4>
                            <div class="action-buttons">
                                @if(!$user->is_verified)
                                    <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-approve">
                                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20,6 9,17 4,12"></polyline>
                                            </svg>
                                            Approve User
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.deny', $user) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-deny">
                                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                            Revoke Verification
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .user-details-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .card-header {
        padding: 24px 32px 16px;
        border-bottom: 1px solid #e5e7eb;
    }

    .card-title {
        font-size: 20px;
        font-weight: 600;
        color: #111827;
        margin: 0;
    }

    .card-body {
        padding: 32px;
    }

    .user-profile-section {
        display: flex;
        gap: 32px;
        margin-bottom: 32px;
    }

    .user-avatar-large {
        flex-shrink: 0;
    }

    .user-photo-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #e5e7eb;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .user-avatar-fallback-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        font-weight: 600;
        border: 4px solid #e5e7eb;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .user-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        flex: 1;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .detail-item.full-width {
        grid-column: 1 / -1;
    }

    .detail-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-value {
        font-size: 16px;
        color: #111827;
        font-weight: 500;
    }

    .skills-section, .actions-section {
        margin-top: 32px;
        padding-top: 32px;
        border-top: 1px solid #e5e7eb;
    }

    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #111827;
        margin: 0 0 16px 0;
    }

    .skills-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .skill-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .skill-tag-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .skill-tag-secondary {
        background: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .skill-star {
        width: 14px;
        height: 14px;
        fill: currentColor;
    }

    .no-skills {
        color: #9ca3af;
        font-style: italic;
        margin: 0;
    }

    .action-buttons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-icon {
        width: 16px;
        height: 16px;
    }

    .btn-approve {
        background: #10b981;
        color: white;
    }

    .btn-approve:hover {
        background: #059669;
    }

    .btn-deny {
        background: #ef4444;
        color: white;
    }

    .btn-deny:hover {
        background: #dc2626;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-verified {
        background: #d1fae5;
        color: #065f46;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-admin {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-user {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-plan {
        background: #e0e7ff;
        color: #3730a3;
    }

    @media (max-width: 768px) {
        .user-profile-section {
            flex-direction: column;
            text-align: center;
        }

        .user-details-grid {
            grid-template-columns: 1fr;
        }

        .action-buttons {
            justify-content: center;
        }
    }
    </style>
</body>
</html>
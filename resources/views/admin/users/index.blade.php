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
<div class="admin-dashboard" x-data="{ sidebarOpen: false }">
        <!-- Sidebar -->
    <div class="admin-sidebar" :class="{ 'open': sidebarOpen }">
            <div class="sidebar-header">
                <div class="logo">
                <img src="{{ asset('logo.png') }}" alt="SkillsXchange Logo" class="admin-logo">
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
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    <span>Exchanges</span>
                </a>
                <a href="{{ route('admin.fee-settings.index') }}" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
                    </svg>
                    <span>Token Management</span>
                </a>
                <a href="{{ route('admin.reports.index') }}" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                        <line x1="12" y1="9" x2="12" y2="13" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                    <span>Reports</span>
                </a>
                <a href="{{ route('admin.messages.index') }}" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    <span>Messages</span>
                </a>
            <a href="{{ route('admin.settings.index') }}" class="nav-item with-icon">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
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
                <button class="mobile-nav-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle navigation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                    <h1 class="page-title">Users</h1>
                    <p class="page-subtitle">Manage all registered users</p>
    <!-- Backdrop for mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="sidebar-backdrop"></div>
                </div>
                <div class="header-right">
                <div class="notifications" x-data="{ notificationsOpen: false }">
                    <div class="notification-icon" @click="notificationsOpen = !notificationsOpen">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                            </svg>
                            <span class="notification-badge">{{ $notifications->count() }}</span>
                        </div>

                    <!-- Notification Dropdown -->
                    <div x-show="notificationsOpen" @click.away="notificationsOpen = false" x-transition class="notification-dropdown">
                            <div class="notification-header">
                                <h3 class="notification-title">Notifications</h3>
                                <span class="notification-count">{{ $notifications->count() }} new</span>
                            </div>
                            <div class="notification-list">
                                @forelse($notifications as $notification)
                                <a href="{{ $notification['url'] }}"
                                    class="notification-item notification-{{ $notification['type'] }}">
                                    <div class="notification-icon-small">
                                        @if($notification['icon'] === 'users')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                            <circle cx="9" cy="7" r="4" />
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                        </svg>
                                        @elseif($notification['icon'] === 'user-plus')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                            <circle cx="8.5" cy="7" r="4" />
                                            <line x1="20" y1="8" x2="20" y2="14" />
                                            <line x1="23" y1="11" x2="17" y2="11" />
                                        </svg>
                                        @elseif($notification['icon'] === 'exchange')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                            <line x1="16" y1="2" x2="16" y2="6" />
                                            <line x1="8" y1="2" x2="8" y2="6" />
                                            <line x1="3" y1="10" x2="21" y2="10" />
                                        </svg>
                                        @elseif($notification['icon'] === 'settings')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="3" />
                                            <path
                                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                                        </svg>
                                        @endif
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-item-title">{{ $notification['title'] }}</div>
                                        <div class="notification-item-message">{{ $notification['message'] }}</div>
                                        <div class="notification-item-time">{{
                                            $notification['created_at']->diffForHumans() }}</div>
                                    </div>
                                </a>
                                @empty
                                <div class="notification-empty">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                                    </svg>
                                    <p>No new notifications</p>
                                </div>
                                @endforelse
                            </div>
                            <div class="notification-footer">
                                <a href="#" class="notification-view-all">View all notifications</a>
                            </div>
                        </div>
                    </div>
                <div class="user-profile" x-data="{ profileOpen: false }">
                    <button @click="profileOpen = !profileOpen" class="user-profile-button">
                            <div class="user-avatar">{{ substr(auth()->user()->firstname, 0, 1) }}{{
                                substr(auth()->user()->lastname, 0, 1) }}</div>
                            <div class="user-info">
                                <span class="user-name">{{ auth()->user()->name }}</span>
                                <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <polyline points="6,9 12,15 18,9" />
                                </svg>
                            </div>
                        </button>

                    <!-- Dropdown Menu -->
                    <div x-show="profileOpen" @click.away="profileOpen = false" x-transition class="user-dropdown">
                            <a href="{{ route('admin.profile') }}" class="dropdown-item">
                                <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                        <polyline points="16,17 21,12 16,7" />
                                        <line x1="21" y1="12" x2="9" y2="12" />
                                    </svg>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Content -->
            <div class="dashboard-content">
                @if(session('success'))
                <div class="alert alert-success" style="position: relative; animation: none;">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-error" style="position: relative; animation: none;">
                    {{ session('error') }}
                </div>
                @endif
                <!-- User Statistics Cards -->
                <div class="user-stats-row">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">{{ $users->total() }}</div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon verified">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                <polyline points="22,4 12,14.01 9,11.01" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">{{ $users->where('is_verified', true)->count() }}</div>
                            <div class="stat-label">Verified Users</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12,6 12,12 16,14" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">{{ $users->where('is_verified', false)->count() }}</div>
                            <div class="stat-label">Pending Approval</div>
                        </div>
                        </div>
                    </div>

                <div class="users-table-card">
                    <div class="table-header">
                        <div class="table-title-section">
                            <h3 class="card-title">All Users</h3>
                            <p class="table-subtitle">Manage user accounts and approvals</p>
                        </div>
                        <div class="table-actions" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                            <input type="text" placeholder="Search users..." class="search-input" id="userSearchInput" style="flex: 1; min-width: 200px;">
                            <select id="statusFilter" class="search-input" style="width: auto; min-width: 140px;">
                                <option value="all">All Status</option>
                                <option value="verified">Verified</option>
                                <option value="unverified">Unverified</option>
                                <option value="new">New</option>
                            </select>
                            <select id="planFilter" class="search-input" style="width: auto; min-width: 120px;">
                                <option value="all">All Plans</option>
                                <option value="premium">Premium</option>
                                <option value="free">Free</option>
                            </select>
                            <select id="skillFilter" class="search-input" style="width: auto; min-width: 180px;">
                                <option value="all">All Skills</option>
                                @foreach($skills as $skill)
                                    <option value="{{ $skill->skill_id }}">{{ $skill->name }}</option>
                                @endforeach
                            </select>
                            <select id="tokenFilter" class="search-input" style="width: auto; min-width: 140px;">
                                <option value="all">All Tokens</option>
                                <option value="highest">Highest Tokens</option>
                                <option value="lowest">Lowest Tokens</option>
                            </select>
                            <select id="suspendedFilter" class="search-input" style="width: auto; min-width: 140px;">
                                <option value="all">All Users</option>
                                <option value="suspended">Suspended</option>
                                <option value="not_suspended">Not Suspended</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>NAME</th>
                                    <th>USERNAME</th>
                                    <th>EMAIL</th>
                                    <th>SKILL</th>
                                    <th>PLAN</th>
                                    <th>TOKENS</th>
                                    <th>STATUS</th>
                                    <th>STUDENT ID</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr data-plan="{{ strtolower($user->plan ?? 'free') }}"
                                    data-skill-ids="{{ $user->skills && $user->skills->count() > 0 ? $user->skills->pluck('skill_id')->implode(',') : '' }}"
                                    data-primary-skill-id="{{ optional($user->skill)->skill_id ?? '' }}"
                                    data-token-balance="{{ (int)($user->token_balance ?? 0) }}"
                                    data-is-suspended="{{ $user->isAccountRestricted() ? 'yes' : 'no' }}">
                                    <td>
                                        <div class="user-name-cell">
                                            <div class="user-name">{{ $user->name }}</div>
                                            <div class="user-role">{{ $user->role }}</div>
                                        </div>
                                    </td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->skills && $user->skills->count() > 0)
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <span>{{ optional($user->skill)->name ?? $user->skills->first()->name ?? '—' }}</span>
                                                @if($user->skills->count() > 1)
                                                    <small style="color: #6b7280; font-size: 11px;">+{{ $user->skills->count() - 1 }} more</small>
                                                @endif
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if(strtolower($user->plan ?? 'free') === 'premium')
                                            <span style="color: #f59e0b; font-weight: 600;">Premium</span>
                                        @else
                                            <span style="color: #6b7280;">Free</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format((int)($user->token_balance ?? 0)) }}</td>
                                    <td data-status="{{ $user->is_verified ? 'verified' : 'unverified' }}" data-created="{{ optional($user->created_at)->timestamp ?? 0 }}">
                                        <span
                                            class="status-badge status-{{ $user->is_verified ? 'verified' : 'pending' }}">
                                            {{ $user->is_verified ? 'Verified' : 'Pending' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="user-photo-container">
                                            @if($user->photo && Storage::disk('public')->exists($user->photo))
                                                <img src="{{ Storage::disk('public')->url($user->photo) }}"
                                                     alt="Student ID"
                                                     class="user-photo clickable-image-table"
                                                     onclick="openStudentIdModal('{{ Storage::disk('public')->url($user->photo) }}', '{{ $user->name }}')"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="user-avatar-fallback" style="display: none;">
                                                    No ID
                                                </div>
                                        @else
                                                <div class="user-avatar-fallback">
                                                    No ID
                                                </div>
                                        @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            @if($user->role !== 'admin')
                                            @if(!$user->is_verified)
                                                <button onclick="verifyUser({{ $user->id }}, true)"
                                                            class="btn btn-approve"
                                                            id="approve-btn-{{ $user->id }}">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <polyline points="20,6 9,17 4,12"></polyline>
                                                        </svg>
                                                        Approve
                                                </button>
                                                <button onclick="verifyUser({{ $user->id }}, false)"
                                                            class="btn btn-deny"
                                                            id="deny-btn-{{ $user->id }}">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                                        </svg>
                                                        Deny
                                                </button>
                                            @else
                                                @if($user->isAccountRestricted())
                                                    <button onclick="liftSuspension({{ $user->id }})"
                                                            class="btn btn-warning"
                                                            id="lift-btn-{{ $user->id }}">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M9 12l2 2 4-4"></path>
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                        </svg>
                                                        Lift Suspension
                                                    </button>
                                                @else
                                                    <button onclick="openSuspendModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}', '{{ addslashes($user->username) }}', '{{ $user->photo ? Storage::disk('public')->url($user->photo) : '' }}')"
                                                            class="btn btn-warning"
                                                            id="suspend-btn-{{ $user->id }}">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                                        </svg>
                                                        Suspend
                                                    </button>
                                                @endif
                                            @endif
                                            @endif
                                            <a href="{{ route('admin.user.show', $user->id) }}"
                                                class="btn btn-view">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="no-data">No users found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="table-pagination">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.dashboard-styles')
    <style>
        /* User Statistics Cards */
        .user-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            color: #3b82f6;
        }

        .stat-icon.verified {
            background: #d1fae5;
            color: #059669;
        }

        .stat-icon.pending {
            background: #fef3c7;
            color: #f59e0b;
        }

        .stat-icon svg {
            width: 24px;
            height: 24px;
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 14px;
            color: #64748b;
        }

        /* Table Title Section */
        .table-title-section {
            flex: 1;
        }

        .table-subtitle {
            font-size: 14px;
            color: #64748b;
            margin: 4px 0 0 0;
        }

        /* Table Updates */
        .users-table-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .search-input {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            color: #374151;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-input:hover {
            border-color: #9ca3af;
        }

        .table-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }

            .table-actions {
                width: 100%;
            }

            .search-input {
                width: 100% !important;
            }
        }

        .table-container {
            overflow-x: auto;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            text-align: left;
            padding: 16px 12px;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .users-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
        }

        .user-name-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .user-role {
            font-size: 12px;
            color: #6b7280;
            text-transform: lowercase;
        }

        .user-photo-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-photo {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e7eb;
            display: block;
            background: #f3f4f6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .user-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .user-avatar-fallback {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            border: 2px solid #e5e7eb;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .user-avatar-fallback:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .no-photo {
            color: #9ca3af;
            font-size: 12px;
            font-style: italic;
        }

        .user-name {
            font-weight: 500;
            color: #1f2937;
        }

        .user-skill {
            font-size: 12px;
            color: #6b7280;
        }

        .role-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .role-admin {
            background: #fef3c7;
            color: #92400e;
        }

        .role-user {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .status-verified {
            background: #059669;
            color: white;
        }

        .status-pending {
            background: #f59e0b;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 12px 24px;  /* CHANGED: More padding */
            border: none;
            border-radius: 8px;  /* CHANGED: Larger radius */
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;  /* CHANGED: Bolder */
            transition: all 0.2s ease;  /* NEW: Transitions */
            display: inline-flex;  /* NEW: Flex for icons */
            align-items: center;
            gap: 8px;  /* NEW: Gap for icons */
            text-decoration: none;
        }

        .btn-view {
            background: #3b82f6;
            color: white;
        }

        .btn-view:hover {
            background: #2563eb;
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-deny:hover {
            background: #dc2626;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-success {
            background: #10b981;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-revoke {
            background: #f59e0b;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-revoke:hover {
            background: #d97706;
        }

        .btn-approve {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn-view {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn:disabled:hover {
            background: inherit;
        }

        .no-data {
            text-align: center;
            color: #6b7280;
            font-style: italic;
            padding: 40px;
        }

        .table-pagination {
            margin-top: 24px;
            display: flex;
            justify-content: center;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .user-stats-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .table-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .search-input {
                width: 100%;
            }

            .table-container {
                overflow-x: auto;
            }
        }
        /* Success/Error Messages */
    .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        position: fixed;
        top: 20px;
        right: 20px;
            z-index: 1000;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease-out;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

    @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
    }
    </style>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <script>
    // CSRF Token for AJAX requests
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Show loading overlay
    function showLoading() {
        document.getElementById('loadingOverlay').style.display = 'flex';
    }

    // Hide loading overlay
    function hideLoading() {
        document.getElementById('loadingOverlay').style.display = 'none';
    }

    // Show alert message
    function showAlert(message, type = 'success') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;

        document.body.appendChild(alert);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }

    // Verify/Deny user function
    async function verifyUser(userId, isVerified) {
        const action = isVerified ? 'approve' : 'deny';
        const actionText = isVerified ? 'approve' : 'deny';

        if (!confirm(`Are you sure you want to ${actionText} this user?`)) {
            return;
        }

        showLoading();

        try {
            const response = await fetch(`/admin/users/${userId}/${action}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    is_verified: isVerified
                })
            });

            const data = await response.json();

            if (response.ok) {
                showAlert(data.message || `User ${actionText}d successfully!`, 'success');

                // Update the UI
                updateUserRow(userId, isVerified);
                    updateStats();
            } else {
                showAlert(data.message || `Failed to ${actionText} user`, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert(`Network error occurred while trying to ${actionText} user`, 'error');
        } finally {
            hideLoading();
        }
    }

    // Update user row in the table
    function updateUserRow(userId, isVerified) {
            const row = document.querySelector(`tr:has(button[id*="${userId}"])`);
        if (!row) return;

            // Update status badge
            const statusBadge = row.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = `status-badge status-${isVerified ? 'verified' : 'pending'}`;
                statusBadge.textContent = isVerified ? 'Verified' : 'Pending';
            }

            // Update action buttons
            const actionButtons = row.querySelector('.action-buttons');
            const viewButton = actionButtons.querySelector('.btn-view');

            // Clear existing buttons except view
            actionButtons.innerHTML = '';

        if (isVerified) {
                // Add revoke button
                const revokeBtn = document.createElement('button');
                revokeBtn.onclick = () => verifyUser(userId, false);
                revokeBtn.className = 'btn btn-revoke';
                revokeBtn.id = `revoke-btn-${userId}`;
                revokeBtn.innerHTML = `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    Revoke
                `;
                actionButtons.appendChild(revokeBtn);
            } else {
                // Add approve and deny buttons
                const approveBtn = document.createElement('button');
                approveBtn.onclick = () => verifyUser(userId, true);
                approveBtn.className = 'btn btn-approve';
                approveBtn.id = `approve-btn-${userId}`;
                approveBtn.innerHTML = `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12"></polyline>
                    </svg>
                    Approve
                `;

                const denyBtn = document.createElement('button');
                denyBtn.onclick = () => verifyUser(userId, false);
                denyBtn.className = 'btn btn-deny';
                denyBtn.id = `deny-btn-${userId}`;
                denyBtn.innerHTML = `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Deny
                `;

                actionButtons.appendChild(approveBtn);
                actionButtons.appendChild(denyBtn);
            }

            // Re-add view button
            actionButtons.appendChild(viewButton);
        }

        // Update statistics
        function updateStats() {
            // Reload the page to update stats (simple approach)
            // In a more sophisticated app, you'd update the stats via AJAX
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }

        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('userSearchInput');
            const statusFilter = document.getElementById('statusFilter');
            const planFilter = document.getElementById('planFilter');
            const skillFilter = document.getElementById('skillFilter');
            const tokenFilter = document.getElementById('tokenFilter');
            const suspendedFilter = document.getElementById('suspendedFilter');
            const rows = document.querySelectorAll('.users-table tbody tr');

            function applyUserFilters() {
                const q = (searchInput?.value || '').toLowerCase();
                const status = statusFilter?.value || 'all';
                const plan = planFilter?.value || 'all';
                const skill = skillFilter?.value || 'all';
                const token = tokenFilter?.value || 'all';
                const suspended = suspendedFilter?.value || 'all';
                const now = Date.now() / 1000;
                const newThreshold = now - (7 * 24 * 60 * 60); // last 7 days

                // Get all token balances for highest/lowest filtering
                const tokenBalances = Array.from(rows).map(row => {
                    const balance = parseInt(row.getAttribute('data-token-balance') || '0', 10);
                    return { row, balance };
                });

                // Determine highest and lowest token values
                const balances = tokenBalances.map(item => item.balance);
                const maxTokens = balances.length > 0 ? Math.max(...balances) : 0;
                const minTokens = balances.length > 0 ? Math.min(...balances) : 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const statusCell = row.querySelector('td[data-status]');
                    const rowStatus = statusCell ? statusCell.getAttribute('data-status') : '';
                    const created = statusCell ? parseInt(statusCell.getAttribute('data-created') || '0', 10) : 0;
                    const rowPlan = row.getAttribute('data-plan') || 'free';
                    const skillIds = row.getAttribute('data-skill-ids') || '';
                    const primarySkillId = row.getAttribute('data-primary-skill-id') || '';
                    const tokenBalance = parseInt(row.getAttribute('data-token-balance') || '0', 10);
                    const isSuspended = row.getAttribute('data-is-suspended') || 'no';

                    // Status filter
                    let matchesStatus = true;
                    if (status === 'verified') matchesStatus = rowStatus === 'verified';
                    else if (status === 'unverified') matchesStatus = rowStatus === 'unverified';
                    else if (status === 'new') matchesStatus = created > newThreshold;

                    // Plan filter
                    let matchesPlan = true;
                    if (plan === 'premium') matchesPlan = rowPlan === 'premium';
                    else if (plan === 'free') matchesPlan = rowPlan === 'free';

                    // Skill filter
                    let matchesSkill = true;
                    if (skill !== 'all') {
                        // Check if user has this skill (either as primary or in their skills list)
                        const skillIdList = skillIds ? skillIds.split(',').filter(id => id.trim() !== '') : [];
                        matchesSkill = primarySkillId === skill || skillIdList.includes(skill);
                    }

                    // Token filter
                    let matchesToken = true;
                    if (token === 'highest') {
                        matchesToken = tokenBalance === maxTokens && maxTokens > 0;
                    } else if (token === 'lowest') {
                        matchesToken = tokenBalance === minTokens;
                    }

                    // Suspended filter
                    let matchesSuspended = true;
                    if (suspended === 'suspended') {
                        matchesSuspended = isSuspended === 'yes';
                    } else if (suspended === 'not_suspended') {
                        matchesSuspended = isSuspended === 'no';
                    }

                    // Search filter
                    const matchesSearch = text.includes(q);

                    // Show row only if all filters match
                    row.style.display = (matchesStatus && matchesPlan && matchesSkill && matchesToken && matchesSuspended && matchesSearch) ? '' : 'none';
                });
            }

            if (searchInput) searchInput.addEventListener('input', applyUserFilters);
            if (statusFilter) statusFilter.addEventListener('change', applyUserFilters);
            if (planFilter) planFilter.addEventListener('change', applyUserFilters);
            if (skillFilter) skillFilter.addEventListener('change', applyUserFilters);
            if (tokenFilter) tokenFilter.addEventListener('change', applyUserFilters);
            if (suspendedFilter) suspendedFilter.addEventListener('change', applyUserFilters);
        });

        // Suspension modal functions
        function openSuspendModal(userId, userName, userEmail, userUsername, userPhoto) {
            document.getElementById('suspendUserId').value = userId;

            // Update user profile in modal
            const profileName = document.getElementById('suspend_profile_name');
            const profileEmail = document.getElementById('suspend_profile_email');
            const profileUsername = document.getElementById('suspend_profile_username');
            const profilePhoto = document.getElementById('suspend_profile_photo');
            const profilePhotoFallback = document.getElementById('suspend_profile_photo_fallback');

            if (profileName) profileName.textContent = userName;
            if (profileEmail) profileEmail.textContent = userEmail;
            if (profileUsername) profileUsername.textContent = '@' + userUsername;

            if (userPhoto && profilePhoto) {
                profilePhoto.src = userPhoto;
                profilePhoto.style.display = 'block';
                if (profilePhotoFallback) profilePhotoFallback.style.display = 'none';
            } else {
                if (profilePhoto) profilePhoto.style.display = 'none';
                if (profilePhotoFallback) profilePhotoFallback.style.display = 'flex';
            }

            const modal = document.getElementById('suspendModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeSuspendModal() {
            document.getElementById('suspendModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('suspendForm').reset();
            document.getElementById('durationGroup').style.display = 'none';
        }

        function submitSuspension() {
            const form = document.getElementById('suspendForm');
            const formData = new FormData(form);
            const userId = document.getElementById('suspendUserId').value; // FIXED: Correct userId retrieval

            // Added client-side validation
            const violationType = document.getElementById('violation_type').value;
            const reason = document.getElementById('reason').value;

            if (!violationType) {
                alert('Please select an action type.');
                return;
            }
            if (!reason) {
                alert('Please select a reason.');
                return;
            }
            if (violationType === 'suspension') {
                const duration = document.getElementById('suspension_duration').value;
                if (!duration) {
                    alert('Please select a suspension duration.');
                    return;
                }
            }

            fetch(`/admin/users/${userId}/suspend`, {
                method: 'PATCH', // FIXED: Correct HTTP method
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json' // Added Accept header
                },
                body: JSON.stringify({
                    violation_type: violationType,
                    suspension_duration: document.getElementById('suspension_duration').value,
                    reason: reason,
                    admin_notes: document.getElementById('admin_notes').value
                })
            })
            .then(response => {
                if (!response.ok) { // Improved error handling
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeSuspendModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while suspending the user. Please try again.');
            });
        }

        function liftSuspension(userId) {
            if (confirm('Are you sure you want to lift the suspension for this user?')) {
                fetch(`/admin/users/${userId}/lift-suspension`, {
                    method: 'PATCH', // FIXED: Correct HTTP method
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json' // Added Accept header
                    }
                })
                .then(response => {
                    if (!response.ok) { // Improved error handling
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while lifting the suspension. Please try again.');
                });
            }
        }
    </script>

    <!-- Suspension Modal -->
    <div class="modal-overlay" id="suspendModal" style="display: none;">
        <div class="modal-content" style="overflow: hidden;">
            <!-- Important Notice - Centered Box above title -->
            <div style="display: flex; justify-content: center; align-items: center; padding: 20px 24px 0 24px; width: 100%; box-sizing: border-box;">
                <div style="background: #fef3c7; color: #92400e; border: 1px solid #fbbf24; padding: 16px 24px; border-radius: 8px; text-align: center; display: inline-flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; max-width: 420px; width: fit-content; box-sizing: border-box;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 24px; height: 24px; flex-shrink: 0;">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <div style="text-align: center; width: 100%;">
                        <strong style="display: block; margin-bottom: 6px; font-size: 15px; font-weight: 600;">Important Notice</strong>
                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #78350f;">This action will restrict the user's access to the platform. Please ensure you have reviewed all relevant information before proceeding.</p>
                    </div>
                </div>
            </div>


            <div class="modal-body">
                <!-- User Profile Section -->
                <div style="display: flex; align-items: center; gap: 16px; padding: 16px; background: #f9fafb; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                    <div style="flex-shrink: 0;">
                        <div style="width: 64px; height: 64px; border-radius: 50%; overflow: hidden; background: #e5e7eb; display: flex; align-items: center; justify-content: center; border: 2px solid #d1d5db;">
                            <img id="suspend_profile_photo" src="" alt="User Photo" style="width: 100%; height: 100%; object-fit: cover; display: none;" onerror="this.style.display='none'; document.getElementById('suspend_profile_photo_fallback').style.display='flex';">
                            <div id="suspend_profile_photo_fallback" style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; font-size: 24px; font-weight: 600; color: #6b7280; background: #e5e7eb;">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div id="suspend_profile_name" style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 4px;">—</div>
                        <div id="suspend_profile_email" style="font-size: 14px; color: #6b7280; margin-bottom: 2px;">—</div>
                        <div id="suspend_profile_username" style="font-size: 13px; color: #9ca3af;">—</div>
                    </div>
                </div>

                <form id="suspendForm">
                    <input type="hidden" id="suspendUserId" name="user_id">

                    <div class="form-group">
                        <label for="violation_type">Action Type <span style="color: #ef4444;">*</span></label>
                        <select id="violation_type" name="violation_type" required onchange="toggleDuration()">
                            <option value="">Select action...</option>
                            <option value="suspension">Suspend</option>
                            <option value="permanent_ban">Permanent Ban</option>
                        </select>
                    </div>

                    <div class="form-group" id="durationGroup" style="display: none;">
                        <label for="suspension_duration">Suspension Duration <span style="color: #ef4444;">*</span></label>
                        <select id="suspension_duration" name="suspension_duration">
                            <option value="">Select duration...</option>
                            <option value="7_days">7 Days</option>
                            <option value="30_days">30 Days</option>
                            <option value="indefinite">Indefinite</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reason">Reason <span style="color: #ef4444;">*</span></label>
                        <select id="reason" name="reason" required>
                            <option value="">Select reason...</option>
                            <option value="Inappropriate behavior">Inappropriate behavior</option>
                            <option value="Spam or harassment">Spam or harassment</option>
                            <option value="Violation of terms of service">Violation of terms of service</option>
                            <option value="Fraudulent activity">Fraudulent activity</option>
                            <option value="Multiple account violations">Multiple account violations</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="admin_notes">Admin Notes (Optional)</label>
                        <textarea id="admin_notes" name="admin_notes" rows="3" placeholder="Additional details or context..."></textarea>
                        <small style="color: #6b7280; font-size: 12px; display: block; margin-top: 4px;">Internal notes visible only to admins</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeSuspendModal()">Cancel</button>
                <button type="button" class="btn-primary" onclick="submitSuspension()" style="background: #f59e0b; border-color: #f59e0b;">Confirm Suspension</button>
            </div>
        </div>
    </div>

    <script>
        function toggleDuration() {
            const violationType = document.getElementById('violation_type').value;
            const durationGroup = document.getElementById('durationGroup');

            if (violationType === 'suspension') {
                durationGroup.style.display = 'block';
                document.getElementById('suspension_duration').required = true;
            } else {
                durationGroup.style.display = 'none';
                document.getElementById('suspension_duration').required = false;
            }
        }
    </script>

    <style>
        /* Standard Admin Modal Styles */
        .modal-overlay {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-sizing: border-box;
        }

        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
        }

        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            color: #6b7280;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close svg {
            width: 20px;
            height: 20px;
        }

        .modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            overflow-x: hidden;
            flex: 1;
            box-sizing: border-box;
        }

        .modal-footer {
            padding: 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
            width: 100%;
            box-sizing: border-box;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            font-family: inherit;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea {
            resize: vertical;
        }

        /* Button Styles */
        .btn-secondary {
            padding: 10px 20px;
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        .btn-primary {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: #2563eb;
            border-color: #2563eb;
        }

        .clickable-image-table {
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .clickable-image-table:hover {
            transform: scale(1.1);
        }

        /* Student ID Modal */
        .student-id-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            animation: fadeIn 0.3s ease;
        }

        .student-id-modal-content {
            position: relative;
            margin: auto;
            margin-top: 5vh;
            max-width: 90%;
            max-height: 90vh;
            animation: zoomIn 0.3s ease;
        }

        .student-id-modal-image {
            width: 100%;
            height: auto;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .student-id-modal-header {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .student-id-modal-close {
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .student-id-modal-close:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes zoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>

    <!-- Student ID Modal -->
    <div id="studentIdModal" class="student-id-modal" onclick="if(event.target.id === 'studentIdModal') closeStudentIdModal()">
        <div class="student-id-modal-content">
            <div class="student-id-modal-header">
                <span class="student-id-modal-close" onclick="closeStudentIdModal()">&times;</span>
            </div>
            <img id="modalStudentIdImage" class="student-id-modal-image" src="" alt="Student ID">
            <p id="modalStudentName" style="color: white; text-align: center; margin-top: 10px; font-weight: 500;"></p>
        </div>
    </div>

    <script>
        function openStudentIdModal(imageUrl, studentName) {
            document.getElementById('modalStudentIdImage').src = imageUrl;
            document.getElementById('modalStudentName').textContent = 'Student ID - ' + studentName;
            document.getElementById('studentIdModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeStudentIdModal() {
            document.getElementById('studentIdModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeStudentIdModal();
            }
        });
    </script>
</body>

</html>



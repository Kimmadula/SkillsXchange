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
                <a href="{{ route('admin.settings.index') }}" class="nav-item">
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
                    <h1 class="page-title">Users</h1>
                    <p class="page-subtitle">Manage all registered users</p>
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
                        <div class="table-actions">
                            <input type="text" placeholder="Search users..." class="search-input">
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
                                    <th>STATUS</th>
                                    <th>PHOTO</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    <td>
                                        <div class="user-name-cell">
                                            <div class="user-name">{{ $user->name }}</div>
                                            <div class="user-role">{{ $user->role }}</div>
                                        </div>
                                    </td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ optional($user->skill)->name ?? '—' }}</td>
                                    <td>
                                        <span
                                            class="status-badge status-{{ $user->is_verified ? 'verified' : 'pending' }}">
                                            {{ $user->is_verified ? 'Verified' : 'Pending' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="user-photo-container">
                                            @if($user->photo && Storage::disk('public')->exists($user->photo))
                                                <img src="{{ Storage::disk('public')->url($user->photo) }}"
                                                     alt="User Photo"
                                                     class="user-photo"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="user-avatar-fallback" style="display: none;">
                                                    {{ substr($user->firstname, 0, 1) }}{{ substr($user->lastname, 0, 1) }}
                                                </div>
                                        @else
                                                <div class="user-avatar-fallback">
                                                    {{ substr($user->firstname, 0, 1) }}{{ substr($user->lastname, 0, 1) }}
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
                                                            class="btn btn-success"
                                                            id="lift-btn-{{ $user->id }}">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M9 12l2 2 4-4"></path>
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                        </svg>
                                                        Lift Suspension
                                                    </button>
                                                @else
                                                    <button onclick="openSuspendModal({{ $user->id }}, '{{ $user->name }}')"
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
        }

        .search-input {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            width: 300px;
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
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
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
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn-deny:hover {
            background: #dc2626;
        }

        .btn-revoke {
            background: #f59e0b;
            color: white;
            display: flex;
            align-items: center;
            gap: 4px;
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
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const rows = document.querySelectorAll('.users-table tbody tr');

        rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
        });

        // Suspension modal functions
        function openSuspendModal(userId, userName) {
            document.getElementById('suspendUserId').value = userId;
            document.getElementById('suspendUserName').textContent = userName;
            document.getElementById('suspendModal').style.display = 'block';
        }

        function closeSuspendModal() {
            document.getElementById('suspendModal').style.display = 'none';
            document.getElementById('suspendForm').reset();
        }

        function submitSuspension() {
            const form = document.getElementById('suspendForm');
            const formData = new FormData(form);
            const userId = formData.get('user_id');

            fetch(`/admin/users/${userId}/suspend`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    violation_type: formData.get('violation_type'),
                    suspension_duration: formData.get('suspension_duration'),
                    reason: formData.get('reason'),
                    admin_notes: formData.get('admin_notes')
                })
            })
            .then(response => response.json())
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
                alert('An error occurred while suspending the user.');
            });
        }

        function liftSuspension(userId) {
            if (confirm('Are you sure you want to lift the suspension for this user?')) {
                fetch(`/admin/users/${userId}/lift-suspension`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
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
                    alert('An error occurred while lifting the suspension.');
                });
            }
        }
    </script>

    <!-- Suspension Modal -->
    <div id="suspendModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Suspend User</h3>
                <span class="close" onclick="closeSuspendModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="suspendForm">
                    <input type="hidden" id="suspendUserId" name="user_id">

                    <div class="form-group">
                        <label>User: <span id="suspendUserName"></span></label>
                    </div>

                    <div class="form-group">
                        <label for="violation_type">Action Type:</label>
                        <select id="violation_type" name="violation_type" required onchange="toggleDuration()">
                            <option value="">Select action...</option>
                            <option value="suspension">Suspend</option>
                            <option value="permanent_ban">Permanent Ban</option>
                        </select>
                    </div>

                    <div class="form-group" id="durationGroup" style="display: none;">
                        <label for="suspension_duration">Suspension Duration:</label>
                        <select id="suspension_duration" name="suspension_duration">
                            <option value="7_days">7 Days</option>
                            <option value="30_days">30 Days</option>
                            <option value="indefinite">Indefinite</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reason">Reason:</label>
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
                        <label for="admin_notes">Admin Notes (Optional):</label>
                        <textarea id="admin_notes" name="admin_notes" rows="3" placeholder="Additional details..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSuspendModal()">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="submitSuspension()">Confirm Suspension</button>
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
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: none;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #495057;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn:hover {
            opacity: 0.9;
        }
    </style>
</body>

</html>

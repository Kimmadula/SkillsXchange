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
                    <path d="M3 3h18v18H3zM9 9h6v6H9z"/>
                </svg>
                <span>Overview</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Users</span>
            </a>
            <a href="{{ route('admin.skills.index') }}" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
                <span>Skills</span>
            </a>
            <a href="{{ route('admin.exchanges.index') }}" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Exchanges</span>
            </a>
            <a href="{{ route('admin.reports.index') }}" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span>Reports</span>
            </a>
            <a href="{{ route('admin.messages.index') }}" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <span>Messages</span>
            </a>
            <a href="{{ route('admin.settings.index') }}" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
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
                <h1 class="page-title">Exchanges</h1>
                <p class="page-subtitle">Manage skill exchanges and trades</p>
            </div>
            <div class="header-right">
                <div class="notifications" x-data="{ notificationsOpen: false }">
                    <div class="notification-icon" @click="notificationsOpen = !notificationsOpen">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
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
                            <a href="{{ $notification['url'] }}" class="notification-item notification-{{ $notification['type'] }}">
                                <div class="notification-icon-small">
                                    @if($notification['icon'] === 'users')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                        </svg>
                                    @elseif($notification['icon'] === 'user-plus')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                            <circle cx="8.5" cy="7" r="4"/>
                                            <line x1="20" y1="8" x2="20" y2="14"/>
                                            <line x1="23" y1="11" x2="17" y2="11"/>
                                        </svg>
                                    @elseif($notification['icon'] === 'exchange')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                    @elseif($notification['icon'] === 'settings')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="notification-content">
                                    <div class="notification-item-title">{{ $notification['title'] }}</div>
                                    <div class="notification-item-message">{{ $notification['message'] }}</div>
                                    <div class="notification-item-time">{{ $notification['created_at']->diffForHumans() }}</div>
                                </div>
                            </a>
                            @empty
                            <div class="notification-empty">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
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
                        <div class="user-avatar">{{ substr(auth()->user()->firstname, 0, 1) }}{{ substr(auth()->user()->lastname, 0, 1) }}</div>
                        <div class="user-info">
                            <span class="user-name">{{ auth()->user()->name }}</span>
                            <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 12,15 18,9"/>
                            </svg>
                        </div>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div x-show="profileOpen" @click.away="profileOpen = false" x-transition class="user-dropdown">
                        <a href="{{ route('admin.profile') }}" class="dropdown-item">
                            <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            Profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                    <polyline points="16,17 21,12 16,7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exchanges Content -->
        <div class="dashboard-content">
            <!-- Exchange Statistics Cards -->
            <div class="exchange-stats-row">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ $trades->total() }}</div>
                        <div class="stat-label">Total Exchanges</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon open">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22,4 12,14.01 9,11.01"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ $trades->where('status', 'open')->count() }}</div>
                        <div class="stat-label">Open</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon ongoing">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12,6 12,12 16,14"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ $trades->where('status', 'ongoing')->count() }}</div>
                        <div class="stat-label">Ongoing</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon closed">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <path d="M9 9l6 6"/>
                            <path d="M15 9l-6 6"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ $trades->where('status', 'closed')->count() }}</div>
                        <div class="stat-label">Closed</div>
                    </div>
                </div>
            </div>

            <div class="exchanges-table-card">
                <div class="table-header">
                    <div class="table-title-section">
                        <h3 class="card-title">All Exchanges</h3>
                        <p class="table-subtitle">Manage and monitor user skill exchanges</p>
                    </div>
                    <div class="table-actions">
                        <select class="status-filter">
                            <option value="">All Status</option>
                            <option value="open">Open</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="exchanges-table">
                        <thead>
                            <tr>
                                <th>USER</th>
                                <th>OFFERING</th>
                                <th>LOOKING FOR</th>
                                <th>STATUS</th>
                                <th>REQUESTS</th>
                                <th>CREATED</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trades as $trade)
                            <tr>
                                <td>
                                    <div class="user-info-cell">
                                        <div class="user-avatar-small">{{ substr($trade->user->firstname, 0, 1) }}{{ substr($trade->user->lastname, 0, 1) }}</div>
                                        <div class="user-details">
                                            <div class="user-name">{{ $trade->user->name }}</div>
                                            <div class="user-email">{{ $trade->user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="skill-badge offering">{{ $trade->offeringSkill->name }}</span>
                                </td>
                                <td>
                                    <span class="skill-badge looking">{{ $trade->lookingSkill->name }}</span>
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $trade->status }}">
                                        {{ ucfirst($trade->status) }}
                                    </span>
                                </td>
                                <td>{{ $trade->requests_count ?? 0 }} requests</td>
                                <td>
                                    <div class="date-time">
                                        <div class="date">{{ $trade->created_at->format('M d, Y') }}</div>
                                        <div class="time">{{ $trade->created_at->format('h:i A') }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="#" class="btn btn-view">View Details</a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="no-data">No exchanges found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="table-pagination">
                    {{ $trades->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.dashboard-styles')
<style>
/* Exchange Statistics Cards */
.exchange-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
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

.stat-icon.open {
    background: #d1fae5;
    color: #059669;
}

.stat-icon.ongoing {
    background: #fef3c7;
    color: #f59e0b;
}

.stat-icon.closed {
    background: #f3f4f6;
    color: #6b7280;
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
.exchanges-table-card {
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

.status-filter {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.table-container {
    overflow-x: auto;
}

.exchanges-table {
    width: 100%;
    border-collapse: collapse;
}

.exchanges-table th {
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

.exchanges-table td {
    padding: 12px;
    border-bottom: 1px solid #f3f4f6;
}

.user-info-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.user-email {
    font-size: 12px;
    color: #6b7280;
}

.user-avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.skill-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.skill-badge.offering {
    background: #dbeafe;
    color: #1e40af;
}

.skill-badge.looking {
    background: #d1fae5;
    color: #065f46;
}

.date-time {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.date {
    font-size: 14px;
    color: #1f2937;
}

.time {
    font-size: 12px;
    color: #6b7280;
}

.user-name {
    font-weight: 500;
    color: #1f2937;
}

.user-username {
    font-size: 12px;
    color: #6b7280;
}

.skill-info {
    display: flex;
    flex-direction: column;
}

.skill-name {
    font-weight: 500;
    color: #1f2937;
}

.skill-category {
    font-size: 12px;
    color: #6b7280;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.status-open {
    background: #059669;
    color: white;
}

.status-ongoing {
    background: #f59e0b;
    color: white;
}

.status-closed {
    background: #6b7280;
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
    padding: 8px 16px;
    font-size: 12px;
}

.btn-view:hover {
    background: #2563eb;
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
    .exchange-stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .exchange-stats-row {
        grid-template-columns: 1fr;
    }
    
    .table-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    
    .table-container {
        overflow-x: auto;
    }
}
</style>
</body>
</html>

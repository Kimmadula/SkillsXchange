<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Admin Fee Settings</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased">
<div class="admin-dashboard" x-data="{ sidebarOpen: false }" @keydown.window.escape="sidebarOpen = false" x-cloak>
    <!-- Sidebar -->
    <div class="admin-sidebar" :class="{ 'open': sidebarOpen }" @click.stop>
        <div class="sidebar-header">
            <div class="logo">
                <img src="{{ asset('logo.png') }}" alt="SkillsXchange Logo" class="admin-logo">
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
            <a href="{{ route('admin.exchanges.index') }}" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Exchanges</span>
            </a>
            <a href="{{ route('admin.fee-settings.index') }}" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
                </svg>
                <span>Token Management</span>
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
            <a href="{{ route('admin.settings.index') }}" class="nav-item with-icon">
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
                <button class="mobile-nav-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle navigation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <h1 class="page-title">Token Management</h1>
                <p class="page-subtitle">Manage token fees for trade requests and acceptance</p>
            </div>
            <div class="header-right">
                <button type="button" class="btn-primary" data-bs-toggle="modal" data-bs-target="#createFeeModal">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add New Fee Setting
                </button>
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
                                    @elseif($notification['icon'] === 'coins')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
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

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <!-- Fee Settings Content -->
        <div class="dashboard-content">
            @php
                $availableFeeTypes = $feeSettings->pluck('fee_type')->unique()->values()->toArray();
                if (!in_array('token_price', $availableFeeTypes, true)) {
                    $availableFeeTypes[] = 'token_price';
                }
            @endphp

            <!-- Fee Settings Statistics Cards -->
            <div class="fee-stats-row">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ $feeSettings->where('is_active', true)->sum('fee_amount') }}</div>
                        <div class="stat-label">Total Active Fees</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4"/>
                            <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"/>
                            <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ $feeSettings->where('is_active', true)->count() }}</div>
                        <div class="stat-label">Active Settings</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ $feeSettings->count() }}</div>
                        <div class="stat-label">Total Settings</div>
                    </div>
                </div>
            </div>

            <div class="fee-settings-table-card">
                <div class="table-header">
                    <div class="table-title-section">
                        <h3 class="card-title">Current Fee Settings</h3>
                        <p class="table-subtitle">Manage token fees for trade requests and acceptance</p>
                    </div>
                </div>

                <div class="table-container">
                    <table class="fee-settings-table">
                        <thead>
                            <tr>
                                <th>Fee Type</th>
                                <th>Amount (Tokens)</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($feeSettings as $feeSetting)
                                <tr>
                                    <td>
                                        <div class="fee-type">
                                            <span class="fee-badge">{{ ucwords(str_replace('_', ' ', $feeSetting->fee_type)) }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fee-amount">
                                            <span class="amount-value">{{ $feeSetting->fee_amount }}</span>
                                            <span class="amount-label">tokens</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($feeSetting->is_active)
                                            <span class="status-badge active">Active</span>
                                        @else
                                            <span class="status-badge inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="description">
                                            {{ $feeSetting->description ?: 'No description' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date">
                                            {{ $feeSetting->created_at->format('M d, Y') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-edit"
                                                    onclick="editFeeSetting({{ $feeSetting->id }}, '{{ $feeSetting->fee_type }}', {{ $feeSetting->fee_amount }}, {{ $feeSetting->is_active ? 'true' : 'false' }}, '{{ $feeSetting->description }}')">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn-delete"
                                                    onclick="deleteFeeSetting({{ $feeSetting->id }}, '{{ $feeSetting->fee_type }}')">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3,6 5,6 21,6"/>
                                                    <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <div class="empty-content">
                                            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M9 12l2 2 4-4"/>
                                                <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"/>
                                                <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"/>
                                            </svg>
                                            <h4>No fee settings found</h4>
                                            <p>Create your first fee setting to get started</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Backdrop for mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="sidebar-backdrop"></div>
</div>

@include('admin.dashboard-styles')
<style>
/* Fee Settings Statistics Cards */
.fee-stats-row {
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

/* Fee Settings Table */
.fee-settings-table-card {
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

.table-container {
    overflow-x: auto;
}

.fee-settings-table {
    width: 100%;
    border-collapse: collapse;
}

.fee-settings-table th {
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

.fee-settings-table td {
    padding: 12px;
    border-bottom: 1px solid #f3f4f6;
}

.fee-settings-table tr:hover {
    background: #f9fafb;
}

/* Fee-specific styles */
.fee-badge {
    background: #3b82f6;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.fee-amount {
    display: flex;
    align-items: baseline;
    gap: 4px;
}

.amount-value {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
}

.amount-label {
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

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.description {
    color: #6b7280;
    font-size: 14px;
}

.date {
    color: #6b7280;
    font-size: 14px;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-edit {
    background: #f59e0b;
    color: white;
    border: none;
    padding: 8px;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.btn-edit:hover {
    background: #d97706;
}

.btn-delete {
    background: #ef4444;
    color: white;
    border: none;
    padding: 8px;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.btn-delete:hover {
    background: #dc2626;
}

.btn-edit svg,
.btn-delete svg {
    width: 16px;
    height: 16px;
}

/* Button Styles */
.btn-primary {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.btn-icon {
    width: 16px;
    height: 16px;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
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
    overflow-y: auto;
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
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-body {
    padding: 24px;
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
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group small {
    display: block;
    margin-top: 4px;
    color: #6b7280;
    font-size: 12px;
}

/* Alert Styles */
.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.alert-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 48px 16px;
}

.empty-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

.empty-icon {
    width: 48px;
    height: 48px;
    color: #9ca3af;
}

.empty-content h4 {
    margin: 0;
    color: #374151;
    font-weight: 600;
}

.empty-content p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .admin-sidebar {
        width: 200px;
    }

    .admin-main {
        margin-left: 200px;
    }

    .fee-stats-row {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
}

@media (max-width: 1024px) {
    .admin-sidebar {
        width: 180px;
    }

    .admin-main {
        margin-left: 180px;
    }

    .fee-stats-row {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .stat-card {
        padding: 20px;
    }

    .stat-value {
        font-size: 28px;
    }

    .fee-settings-table-card {
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .admin-main {
        margin-left: 0;
        padding: 16px;
    }

    .admin-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }

    .header-right {
        width: 100%;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .fee-stats-row {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .stat-card {
        padding: 16px;
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
    }

    .stat-icon svg {
        width: 20px;
        height: 20px;
    }

    .stat-value {
        font-size: 24px;
    }

    .stat-label {
        font-size: 13px;
    }

    .fee-settings-table-card {
        padding: 16px;
    }

    .table-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }

    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .fee-settings-table th,
    .fee-settings-table td {
        padding: 10px 8px;
        font-size: 13px;
    }

    .fee-badge {
        font-size: 11px;
        padding: 4px 8px;
    }

    .amount-value {
        font-size: 16px;
    }

    .amount-label {
        font-size: 11px;
    }

    .status-badge {
        font-size: 11px;
        padding: 4px 8px;
    }

    .description {
        font-size: 13px;
    }

    .date {
        font-size: 13px;
    }

    .btn-edit,
    .btn-delete {
        padding: 6px;
    }

    .btn-edit svg,
    .btn-delete svg {
        width: 14px;
        height: 14px;
    }

    .btn-primary {
        padding: 10px 20px;
        font-size: 14px;
    }

    .modal-content {
        width: 95%;
        margin: 16px;
        max-width: none;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 20px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 10px;
        font-size: 14px;
    }

    .notifications {
        position: relative;
    }

    .notification-dropdown {
        position: absolute;
        right: 0;
        top: 100%;
        width: 280px;
        z-index: 1000;
    }

    .user-profile {
        position: relative;
    }

    .user-dropdown {
        position: absolute;
        right: 0;
        top: 100%;
        width: 200px;
        z-index: 1000;
    }
}

@media (max-width: 480px) {
    .admin-main {
        padding: 12px;
    }

    .page-title {
        font-size: 20px;
    }

    .page-subtitle {
        font-size: 12px;
    }

    .fee-stats-row {
        gap: 12px;
    }

    .stat-card {
        padding: 12px;
    }

    .stat-icon {
        width: 36px;
        height: 36px;
    }

    .stat-icon svg {
        width: 18px;
        height: 18px;
    }

    .stat-value {
        font-size: 20px;
    }

    .stat-label {
        font-size: 12px;
    }

    .fee-settings-table-card {
        padding: 12px;
    }

    .fee-settings-table th,
    .fee-settings-table td {
        padding: 8px 6px;
        font-size: 12px;
    }

    .fee-badge {
        font-size: 10px;
        padding: 3px 6px;
    }

    .amount-value {
        font-size: 14px;
    }

    .amount-label {
        font-size: 10px;
    }

    .status-badge {
        font-size: 10px;
        padding: 3px 6px;
    }

    .description {
        font-size: 12px;
    }

    .date {
        font-size: 12px;
    }

    .btn-edit,
    .btn-delete {
        padding: 5px;
    }

    .btn-edit svg,
    .btn-delete svg {
        width: 12px;
        height: 12px;
    }

    .btn-primary {
        padding: 8px 16px;
        font-size: 13px;
    }

    .modal-content {
        width: 98%;
        margin: 8px;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 16px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 8px;
        font-size: 13px;
    }

    .notification-dropdown {
        width: 250px;
    }

    .user-dropdown {
        width: 180px;
    }

    .header-right {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
    }

    .btn-primary {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- Create Fee Setting Modal -->
<div class="modal-overlay" id="createFeeModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create New Fee Setting</h3>
            <button type="button" class="modal-close" onclick="closeModal('createFeeModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form id="createFeeForm">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="create_fee_type">Fee Type</label>
                    <select id="create_fee_type_select">
                        @foreach($availableFeeTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                        <option value="__custom__">Custom...</option>
                    </select>
                    <input type="text" id="create_fee_type_custom" placeholder="Enter custom fee type" style="display:none; margin-top:8px;" />
                    <small>Select an existing type or choose Custom to enter a new one</small>
                </div>
                <div class="form-group">
                    <label for="create_fee_amount">Fee Amount (Tokens)</label>
                    <input type="number" id="create_fee_amount" name="fee_amount" min="0" max="1000" required value="1">
                </div>
                <div class="form-group">
                    <label for="create_is_active">Status</label>
                    <select id="create_is_active" name="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="create_description">Description</label>
                    <textarea id="create_description" name="description" rows="3" placeholder="Optional description"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('createFeeModal')">Cancel</button>
                <button type="submit" class="btn-primary">Create Fee Setting</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Fee Setting Modal -->
<div class="modal-overlay" id="editFeeModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Fee Setting</h3>
            <button type="button" class="modal-close" onclick="closeModal('editFeeModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form id="editFeeForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_fee_type">Fee Type</label>
                    <input type="text" id="edit_fee_type" name="fee_type" readonly>
                    <input type="hidden" id="edit_fee_id" name="fee_id">
                </div>
                <div class="form-group">
                    <label for="edit_fee_amount">Fee Amount (Tokens)</label>
                    <input type="number" id="edit_fee_amount" name="fee_amount" min="0" max="1000" required>
                </div>
                <div class="form-group">
                    <label for="edit_is_active">Status</label>
                    <select id="edit_is_active" name="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('editFeeModal')">Cancel</button>
                <button type="submit" class="btn-primary">Update Fee Setting</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal Functions
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Edit Fee Setting Function
    function editFeeSetting(id, type, amount, isActive, description) {
        document.getElementById('edit_fee_id').value = id;
        document.getElementById('edit_fee_type').value = type;
        document.getElementById('edit_fee_amount').value = amount;
        document.getElementById('edit_is_active').value = isActive ? '1' : '0';
        document.getElementById('edit_description').value = description;

        // Set the form action URL
        document.getElementById('editFeeForm').action = '/admin/fee-settings/' + id;

        openModal('editFeeModal');
    }

    // Delete Fee Setting Function
    function deleteFeeSetting(id, type) {
        if (confirm('Are you sure you want to delete the fee setting "' + type + '"? This action cannot be undone.')) {
            fetch('/admin/fee-settings/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the fee setting.');
            });
        }
    }

    // Handle Create Fee Form Submission
    // Toggle custom fee type field visibility
    (function() {
        const select = document.getElementById('create_fee_type_select');
        const custom = document.getElementById('create_fee_type_custom');
        if (select) {
            select.addEventListener('change', function() {
                if (this.value === '__custom__') {
                    custom.style.display = 'block';
                    custom.required = true;
                    custom.focus();
                } else {
                    custom.style.display = 'none';
                    custom.required = false;
                }
            });
        }
    })();

    document.getElementById('createFeeForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        // Resolve fee_type from dropdown/custom input
        const selectedType = document.getElementById('create_fee_type_select').value;
        const customType = document.getElementById('create_fee_type_custom').value.trim();
        data.fee_type = selectedType === '__custom__' ? customType : selectedType;

        if (!data.fee_type) {
            alert('Please select or enter a fee type.');
            return;
        }

        // Convert string values to proper types
        data.fee_amount = parseInt(data.fee_amount);
        data.is_active = data.is_active === '1' ? true : false;

        fetch('/admin/fee-settings', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                alert(result.message);
                closeModal('createFeeModal');
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the fee setting.');
        });
    });

    // Handle Edit Fee Form Submission
    document.getElementById('editFeeForm').addEventListener('submit', function(e) {
        // Let the form submit normally - no preventDefault
        // The form will submit to the server and reload the page
        console.log('Form submitted normally');
        console.log('Form action:', this.action);
        console.log('Form method:', this.method);

        // Log form data
        const formData = new FormData(this);
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
    });

    // Open create modal when button is clicked
    document.querySelector('[data-bs-target="#createFeeModal"]').addEventListener('click', function(e) {
        e.preventDefault();
        openModal('createFeeModal');
    });

    // Close modals when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Admin Reports</title>
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
            <a href="{{ route('admin.fee-settings.index') }}" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
                </svg>
                <span>Token Management</span>
            </a>
            <a href="{{ route('admin.reports.index') }}" class="nav-item active">
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
                <h1 class="page-title">Reports</h1>
                <p class="page-subtitle">Analytics and insights for your platform</p>
            </div>
            <div class="header-right">
                <div class="time-filter">
                    <select class="filter-select">
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
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

        <!-- Reports Content -->
        <div class="dashboard-content">
            <!-- Key Metrics Cards -->
            <div class="metrics-row">
                <div class="metric-card">
                    <div class="metric-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">{{ $metrics['totalUsers'] }}</div>
                        <div class="metric-label">Total Users</div>
                        <div class="metric-change">+{{ $metrics['usersThisMonth'] }} this month</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon trades">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                            <path d="M8 14h.01"/>
                            <path d="M12 14h.01"/>
                            <path d="M16 14h.01"/>
                            <path d="M8 18h.01"/>
                            <path d="M12 18h.01"/>
                            <path d="M16 18h.01"/>
                        </svg>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">{{ $metrics['totalTrades'] }}</div>
                        <div class="metric-label">Total Trades</div>
                        <div class="metric-change">+{{ $metrics['tradesThisMonth'] }} this month</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon active">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22,4 12,14.01 9,11.01"/>
                        </svg>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">{{ $metrics['activeTrades'] }}</div>
                        <div class="metric-label">Active Trades</div>
                        <div class="metric-status">{{ $metrics['ongoingTrades'] }} ongoing</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon messages">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">{{ $metrics['totalMessages'] }}</div>
                        <div class="metric-label">Total Messages</div>
                        <div class="metric-status">{{ $metrics['pendingRequests'] }} requests</div>
                    </div>
                </div>
            </div>

            <!-- Token Metrics Row -->
            <div class="metrics-row">
                <div class="metric-card">
                    <div class="metric-icon tokens">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
                        </svg>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">{{ number_format($tokenMetrics['totalTokensInCirculation']) }}</div>
                        <div class="metric-label">Tokens in Circulation</div>
                        <div class="metric-status">Across all users</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon revenue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">₱{{ number_format($tokenMetrics['totalRevenue']) }}</div>
                        <div class="metric-label">Total Revenue</div>
                        <div class="metric-change">₱{{ number_format($tokenMetrics['monthlyRevenue']) }} this month (tokens + premium)</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon purchases">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                        </svg>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">{{ $tokenMetrics['completedPurchases'] }}</div>
                        <div class="metric-label">Token Purchases</div>
                        <div class="metric-status">{{ $tokenMetrics['totalTokenTransactions'] }} total transactions</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon fees">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">{{ $tokenMetrics['totalFeeTransactions'] }}</div>
                        <div class="metric-label">Fee Transactions</div>
                        <div class="metric-status">{{ $tokenMetrics['totalFeesCollected'] }} tokens collected</div>
                    </div>
                </div>
            </div>

            <!-- Premium User Metrics Row -->
            <div class="metrics-row">
                <div class="metric-card">
                    <div class="metric-icon" style="background: #fef3c7; color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">{{ $premiumMetrics['totalPremiumUsers'] }}</div>
                        <div class="metric-label">Total Premium Users</div>
                        <div class="metric-status">{{ $premiumMetrics['activePremiumUsers'] }} active</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background: #d1fae5; color: #059669;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22,4 12,14.01 9,11.01"/>
                        </svg>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">{{ $premiumMetrics['activePremiumUsers'] }}</div>
                        <div class="metric-label">Active Premium Users</div>
                        <div class="metric-status">{{ $premiumMetrics['expiredPremiumUsers'] }} expired, {{ $premiumMetrics['premiumExpiringSoon'] }} expiring soon</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background: #e9d5ff; color: #7c3aed;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">₱{{ number_format($premiumMetrics['totalPremiumRevenue']) }}</div>
                        <div class="metric-label">Total Premium Revenue</div>
                        <div class="metric-change">₱{{ number_format($premiumMetrics['monthlyPremiumRevenue']) }} this month</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background: #fce7f3; color: #ec4899;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">{{ $premiumMetrics['premiumSubscriptionsThisMonth'] }}</div>
                        <div class="metric-label">Premium Subscriptions</div>
                        <div class="metric-status">{{ $premiumMetrics['premiumExpiringThisMonth'] }} expiring this month</div>
                    </div>
                </div>
            </div>

            <!-- Trend Charts -->
            <div class="charts-row">
                <div class="chart-card">
                    <h3 class="chart-title">User Registration Trends (Last 7 Days)</h3>
                    <div class="chart-container">
                        <div class="bar-chart">
                            @foreach($userTrends as $date => $count)
                            <div class="chart-bar">
                                <div class="bar" style="--bar-height: {{ $count > 0 ? round($count / max($userTrends) * 100, 1) : 5 }}%; height: var(--bar-height);"></div>
                                <div class="bar-label">{{ $date }}</div>
                                <div class="bar-value">{{ $count }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <h3 class="chart-title">Trade Creation Trends (Last 7 Days)</h3>
                    <div class="chart-container">
                        <div class="bar-chart">
                            @foreach($tradeTrends as $date => $count)
                            <div class="chart-bar">
                                <div class="bar trades" style="--bar-height: {{ $count > 0 ? round($count / max($tradeTrends) * 100, 1) : 5 }}%; height: var(--bar-height);"></div>
                                <div class="bar-label">{{ $date }}</div>
                                <div class="bar-value">{{ $count }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Token Analytics Charts -->
            <div class="charts-row">
                <div class="chart-card">
                    <h3 class="chart-title">Token Purchase Trends (Last 7 Days)</h3>
                    <div class="chart-container">
                        <div class="bar-chart">
                            @foreach($tokenTrends as $date => $count)
                            <div class="chart-bar">
                                <div class="bar tokens" style="--bar-height: {{ $count > 0 ? round($count / max($tokenTrends) * 100, 1) : 5 }}%; height: var(--bar-height);"></div>
                                <div class="bar-label">{{ $date }}</div>
                                <div class="bar-value">{{ $count }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <h3 class="chart-title">Revenue Trends (Last 7 Days)</h3>
                    <p class="chart-subtitle" style="font-size: 0.875rem; color: #6b7280; margin-top: -0.5rem; margin-bottom: 1rem;">Includes token purchases and premium subscriptions</p>
                    <div class="chart-container">
                        <div class="bar-chart">
                            @php
                                $maxRevenueTrends = !empty($revenueTrends) ? max($revenueTrends) : 1;
                            @endphp
                            @foreach($revenueTrends as $date => $amount)
                            <div class="chart-bar">
                                <div class="bar revenue" style="--bar-height: {{ $amount > 0 && $maxRevenueTrends > 0 ? round($amount / $maxRevenueTrends * 100, 1) : 5 }}%; height: var(--bar-height);"></div>
                                <div class="bar-label">{{ $date }}</div>
                                <div class="bar-value">₱{{ number_format($amount) }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fee Collection Chart -->
            <div class="charts-row">
                <div class="chart-card">
                    <h3 class="chart-title">Fee Collection Trends (Last 7 Days)</h3>
                    <div class="chart-container">
                        <div class="bar-chart">
                            @foreach($feeTrends as $date => $count)
                            <div class="chart-bar">
                                <div class="bar fees" style="--bar-height: {{ $count > 0 ? round($count / max($feeTrends) * 100, 1) : 5 }}%; height: var(--bar-height);"></div>
                                <div class="bar-label">{{ $date }}</div>
                                <div class="bar-value">{{ $count }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <h3 class="chart-title">Fee Transaction Summary</h3>
                    <div class="chart-container">
                        <div class="fee-summary">
                            @forelse($feeTransactionSummary as $fee)
                            <div class="fee-item">
                                <div class="fee-type">{{ ucfirst(str_replace('_', ' ', $fee->fee_type)) }}</div>
                                <div class="fee-stats">
                                    <span class="fee-count">{{ $fee->count }} transactions</span>
                                    <span class="fee-amount">{{ $fee->total_amount }} tokens</span>
                                </div>
                            </div>
                            @empty
                            <div class="no-data">No fee transactions yet</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Premium Analytics Charts -->
            <div class="charts-row">
                <div class="chart-card">
                    <h3 class="chart-title">Premium Subscription Trends (Last 7 Days)</h3>
                    <div class="chart-container">
                        <div class="bar-chart">
                            @php
                                $maxPremiumTrends = !empty($premiumTrends) ? max($premiumTrends) : 1;
                            @endphp
                            @foreach($premiumTrends as $date => $count)
                            <div class="chart-bar">
                                <div class="bar" style="background: linear-gradient(to top, #f59e0b, #fbbf24); --bar-height: {{ $count > 0 && $maxPremiumTrends > 0 ? round($count / $maxPremiumTrends * 100, 1) : 5 }}%; height: var(--bar-height);"></div>
                                <div class="bar-label">{{ $date }}</div>
                                <div class="bar-value">{{ $count }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <h3 class="chart-title">Premium Revenue Trends (Last 7 Days)</h3>
                    <div class="chart-container">
                        <div class="bar-chart">
                            @php
                                $maxPremiumRevenueTrends = !empty($premiumRevenueTrends) ? max($premiumRevenueTrends) : 1;
                            @endphp
                            @foreach($premiumRevenueTrends as $date => $amount)
                            <div class="chart-bar">
                                <div class="bar" style="background: linear-gradient(to top, #7c3aed, #a78bfa); --bar-height: {{ $amount > 0 && $maxPremiumRevenueTrends > 0 ? round($amount / $maxPremiumRevenueTrends * 100, 1) : 5 }}%; height: var(--bar-height);"></div>
                                <div class="bar-label">{{ $date }}</div>
                                <div class="bar-value">₱{{ number_format($amount) }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Skills Table -->
            <div class="skills-table-card">
                <div class="table-header">
                    <div class="table-title-section">
                        <h3 class="card-title">Top Skills by Usage</h3>
                    </div>
                    <div class="table-actions">
                        <button class="btn btn-export">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7,10 12,15 17,10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Export CSV
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="skills-table">
                        <thead>
                            <tr>
                                <th>SKILL</th>
                                <th>CATEGORY</th>
                                <th>USERS</th>
                                <th>OFFERING</th>
                                <th>LOOKING FOR</th>
                                <th>TOTAL USAGE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topSkills as $skill)
                            <tr>
                                <td>
                                    <div class="skill-name">{{ $skill->name }}</div>
                                </td>
                                <td>
                                    <span class="category-badge">{{ $skill->category }}</span>
                                </td>
                                <td>{{ $skill->users_count }}</td>
                                <td>{{ $skill->offering_count }}</td>
                                <td>{{ $skill->looking_count }}</td>
                                <td>{{ $skill->total_usage }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="no-data">No skills data available</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Token Analytics Tables -->
            <div class="charts-row">
                <!-- Top Token Purchasers -->
                <div class="chart-card">
                    <div class="table-header">
                        <h3 class="chart-title">Top Token Purchasers</h3>
                    </div>
                    <div class="table-container">
                        <table class="token-table">
                            <thead>
                                <tr>
                                    <th>USER</th>
                                    <th>EMAIL</th>
                                    <th>TOKENS PURCHASED</th>
                                    <th>TOTAL SPENT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topTokenPurchasers as $user)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $user->firstname }} {{ $user->lastname }}</div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ number_format($user->total_purchased) }}</td>
                                    <td>₱{{ number_format($user->total_spent) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="no-data">No token purchases yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Token Transactions -->
                <div class="chart-card">
                    <div class="table-header">
                        <h3 class="chart-title">Recent Token Transactions</h3>
                    </div>
                    <div class="table-container">
                        <table class="token-table">
                            <thead>
                                <tr>
                                    <th>USER</th>
                                    <th>QUANTITY</th>
                                    <th>AMOUNT</th>
                                    <th>STATUS</th>
                                    <th>DATE</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTokenTransactions as $transaction)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $transaction->user->name }}</div>
                                    </td>
                                    <td>{{ $transaction->quantity > 0 ? '+' : '' }}{{ $transaction->quantity }}</td>
                                    <td>₱{{ number_format($transaction->amount) }}</td>
                                    <td>
                                        <span class="status-badge {{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span>
                                    </td>
                                    <td>{{ $transaction->created_at->format('M d, Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="no-data">No token transactions yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Premium Users Analytics -->
            <div class="charts-row">
                <!-- Top Premium Users -->
                <div class="chart-card">
                    <div class="table-header">
                        <h3 class="chart-title">Top Premium Users</h3>
                    </div>
                    <div class="table-container">
                        <table class="token-table">
                            <thead>
                                <tr>
                                    <th>USER</th>
                                    <th>EMAIL</th>
                                    <th>SUBSCRIPTIONS</th>
                                    <th>TOTAL SPENT</th>
                                    <th>STATUS</th>
                                    <th>EXPIRES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topPremiumUsers as $user)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $user->firstname }} {{ $user->lastname }}</div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->subscription_count }}</td>
                                    <td>₱{{ number_format($user->total_spent) }}</td>
                                    <td>
                                        @if($user->plan === 'premium' && ($user->premium_expires_at === null || \Carbon\Carbon::parse($user->premium_expires_at)->isFuture()))
                                            <span class="status-badge completed">Active</span>
                                        @else
                                            <span class="status-badge failed">Expired</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->premium_expires_at)
                                            {{ \Carbon\Carbon::parse($user->premium_expires_at)->format('M d, Y') }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="no-data">No premium users yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Export Reports Section -->
            <div class="export-section">
                <h3 class="section-title">Export Reports</h3>
                <div class="export-cards">
                    <div class="export-card">
                        <h4 class="export-title">User Reports</h4>
                        <p class="export-description">Export user data and registration trends</p>
                        <div class="export-buttons">
                            <button class="btn btn-csv">CSV</button>
                            <button class="btn btn-pdf">PDF</button>
                        </div>
                    </div>

                    <div class="export-card">
                        <h4 class="export-title">Trade Reports</h4>
                        <p class="export-description">Export trade data and completion rates</p>
                        <div class="export-buttons">
                            <button class="btn btn-csv">CSV</button>
                            <button class="btn btn-pdf">PDF</button>
                        </div>
                    </div>

                    <div class="export-card">
                        <h4 class="export-title">Activity Reports</h4>
                        <p class="export-description">Export message and activity data</p>
                        <div class="export-buttons">
                            <button class="btn btn-csv">CSV</button>
                            <button class="btn btn-pdf">PDF</button>
                        </div>
                    </div>

                    <div class="export-card">
                        <h4 class="export-title">Token Analytics</h4>
                        <p class="export-description">Export token purchases and revenue data</p>
                        <div class="export-buttons">
                            <button class="btn btn-csv">CSV</button>
                            <button class="btn btn-pdf">PDF</button>
                        </div>
                    </div>

                    <div class="export-card">
                        <h4 class="export-title">Fee Reports</h4>
                        <p class="export-description">Export fee collection and transaction data</p>
                        <div class="export-buttons">
                            <button class="btn btn-csv">CSV</button>
                            <button class="btn btn-pdf">PDF</button>
                        </div>
                    </div>

                    <div class="export-card">
                        <h4 class="export-title">Financial Summary</h4>
                        <p class="export-description">Export comprehensive financial reports</p>
                        <div class="export-buttons">
                            <button class="btn btn-csv">CSV</button>
                            <button class="btn btn-pdf">PDF</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Backdrop for mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="sidebar-backdrop"></div>
</div>

@include('admin.dashboard-styles')
<style>
/* Key Metrics Cards */
.metrics-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 16px;
}

.metric-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #dbeafe;
    color: #3b82f6;
}

.metric-icon.trades {
    background: #d1fae5;
    color: #059669;
}

.metric-icon.active {
    background: #fef3c7;
    color: #f59e0b;
}

.metric-icon.messages {
    background: #e9d5ff;
    color: #7c3aed;
}

.metric-icon.tokens {
    background: #fef3c7;
    color: #f59e0b;
}

.metric-icon.revenue {
    background: #d1fae5;
    color: #059669;
}

.metric-icon.purchases {
    background: #dbeafe;
    color: #3b82f6;
}

.metric-icon.fees {
    background: #fce7f3;
    color: #ec4899;
}

.metric-icon svg {
    width: 24px;
    height: 24px;
}

.metric-content {
    flex: 1;
}

.metric-value {
    font-size: 32px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 4px;
}

.metric-label {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 4px;
}

.metric-change {
    font-size: 12px;
    color: #059669;
    font-weight: 500;
}

.metric-status {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
}

/* Header Updates */
.time-filter {
    margin-right: 16px;
}

.filter-select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    font-size: 14px;
    color: #374151;
}

.notifications {
    position: relative;
    margin-right: 16px;
}

.notification-icon {
    position: relative;
    padding: 8px;
    color: #6b7280;
}

.notification-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

/* Trend Charts */
.charts-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

.chart-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.chart-title {
    font-size: 16px;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 20px 0;
}

.chart-container {
    height: 200px;
}

.bar-chart {
    display: flex;
    align-items: end;
    justify-content: space-between;
    height: 100%;
    gap: 8px;
}

.chart-bar {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
}

.bar {
    width: 100%;
    background: #3b82f6;
    border-radius: 4px 4px 0 0;
    min-height: 4px;
    margin-bottom: 8px;
    transition: all 0.3s ease;
}

.bar.trades {
    background: #10b981;
}

.bar.tokens {
    background: #f59e0b;
}

.bar.revenue {
    background: #059669;
}

.bar.fees {
    background: #ec4899;
}

.bar-label {
    font-size: 10px;
    color: #6b7280;
    margin-bottom: 4px;
}

.bar-value {
    font-size: 12px;
    font-weight: 600;
    color: #1f2937;
}

/* Skills Table */
.skills-table-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 32px;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.table-title-section {
    flex: 1;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a202c;
    margin: 0;
}

.table-actions {
    display: flex;
    gap: 12px;
}

.btn-export {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}

.btn-export:hover {
    background: #2563eb;
}

.btn-export svg {
    width: 16px;
    height: 16px;
}

.table-container {
    overflow-x: auto;
}

.skills-table {
    width: 100%;
    border-collapse: collapse;
}

.skills-table th {
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

.skills-table td {
    padding: 16px 12px;
    border-bottom: 1px solid #f3f4f6;
}

.skill-name {
    font-weight: 500;
    color: #1f2937;
}

.category-badge {
    background: #dbeafe;
    color: #1e40af;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

/* Export Section */
.export-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 24px 0;
}

.export-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.export-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.export-title {
    font-size: 16px;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 8px 0;
}

.export-description {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 16px 0;
    line-height: 1.5;
}

.export-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.btn-csv {
    background: #10b981;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-csv:hover {
    background: #059669;
}

.btn-pdf {
    background: #ef4444;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-pdf:hover {
    background: #dc2626;
}

.no-data {
    text-align: center;
    color: #6b7280;
    font-style: italic;
    padding: 40px;
}

/* Token Tables */
.token-table {
    width: 100%;
    border-collapse: collapse;
}

.token-table th {
    text-align: left;
    padding: 12px 8px;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.token-table td {
    padding: 12px 8px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 13px;
}

.user-name {
    font-weight: 500;
    color: #1f2937;
}

.status-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-badge.completed {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.failed {
    background: #fee2e2;
    color: #991b1b;
}

/* Fee Summary */
.fee-summary {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.fee-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.fee-type {
    font-weight: 500;
    color: #1f2937;
}

.fee-stats {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
}

.fee-count {
    font-size: 12px;
    color: #6b7280;
}

.fee-amount {
    font-size: 14px;
    font-weight: 600;
    color: #059669;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .metrics-row {
        grid-template-columns: repeat(2, 1fr);
    }

    .charts-row {
        grid-template-columns: 1fr;
    }

    .export-cards {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .metrics-row {
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

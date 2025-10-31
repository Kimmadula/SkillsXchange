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
                <a href="{{ route('admin.users.index') }}" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <span>Users</span>
                </a>
                <a href="{{ route('admin.skills.index') }}" class="nav-item active">
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
                    <h1 class="page-title">Skills</h1>
                    <p class="page-subtitle">Manage skills and categories</p>
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

            <!-- Skills Content -->
            <div class="dashboard-content">
                <!-- Stats Cards -->
                @php(
                    $initialCategories = collect($skills)->pluck('category')->filter()->unique()
                )
                @php(
                    $initialUsersTotal = collect($skills)->sum(function($s){ return $s->users_count ?? 0; })
                )
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-label">Total Skills</div>
                        <div class="stat-value" id="skillsTotalCount">{{ $skills->count() }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Categories</div>
                        <div class="stat-value" id="skillsCategoryCount">{{ $initialCategories->count() }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Users Across Skills</div>
                        <div class="stat-value" id="skillsUsersTotal">{{ $initialUsersTotal }}</div>
                    </div>
                </div>

                @if(session('success'))
                <div class="success-message">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="error-message">
                    <div class="error-title">⚠️ Error:</div>
                    <p>{{ session('error') }}</p>
                </div>
                @endif

                @if($errors->any())
                <div class="error-message">
                    <div class="error-title">⚠️ Please fix the following errors:</div>
                    <ul>
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Add Skill -->
                <div class="add-skill-card">
                    <form class="add-skill-form" method="POST" action="{{ route('admin.skill.store') }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label" for="skillName">Name</label>
                            <input class="form-input" id="skillName" name="name" type="text" placeholder="e.g., Web Development" required>
                        </div>
                        <div class="form-group combo-select">
                            <label class="form-label" for="addCategorySelect">Category</label>
                            <select class="form-input" id="addCategorySelect" name="category" required>
                                <option value="">Select category</option>
                                @php($allCategories = \App\Models\Skill::query()->select('category')->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'))
                                @foreach($allCategories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                                <option value="__new__">+ Add new category...</option>
                            </select>
                        </div>

                        <button class="btn btn-primary" type="submit">Add Skill</button>
                    </form>
                </div>

                <!-- All Skills Section -->
                <div class="skills-table-card">
                    <div class="table-header">
                        <div class="table-title-section">
                            <h3 class="card-title">All Skills</h3>
                            <p class="table-subtitle">Manage existing skills and categories</p>
                        </div>
                        <div class="table-actions">
                            <div class="filters">
                                <input id="skillSearch" class="form-input" type="text" placeholder="Search skills..." list="skillSuggestions" autocomplete="off">
                                <datalist id="skillSuggestions"></datalist>
                                <input type="hidden" id="skillIdHidden" value="">
                                <select id="categoryFilter" class="form-input">
                                    <option value="">All categories</option>
                                    @php($__categories = collect($skills)->pluck('category')->filter()->unique()->sort())
                                    @foreach($__categories as $__cat)
                                        <option value="{{ $__cat }}">{{ $__cat }}</option>
                                    @endforeach
                                </select>
                                <select id="sortSelect" class="form-input">
                                    <option value="category_name">Sort: Category/Name</option>
                                    <option value="users_desc">Sort: Highest Users</option>
                                    <option value="users_asc">Sort: Lowest Users</option>
                                    <option value="name">Sort: Name (A-Z)</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <!-- Toast Notification -->
                    <div id="skill-toast" class="toast hidden" role="status" aria-live="polite"></div>

                    <div class="table-container">
                        <table class="skills-table">
                            <thead>
                                <tr>
                                    <th>NAME</th>
                                    <th>CATEGORY</th>
                                    <th>USERS COUNT</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="skillsTableBody">
                                @forelse($skills as $skill)
                                <tr data-id="{{ $skill->skill_id }}">
                                    <td>{{ $skill->name }}</td>
                                    <td>
                                        <span class="category-badge">{{ $skill->category }}</span>
                                    </td>
                                    <td>{{ $skill->users_count ?? 0 }} users</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-edit" data-action="edit" data-id="{{ $skill->skill_id }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn-delete" onclick="deleteSkill({{ $skill->skill_id }}, '{{ $skill->name }}')">
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
                                    <td colspan="4" class="no-data">No skills found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.dashboard-styles')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('skillsTableBody');
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const addCategorySelect = document.getElementById('addCategorySelect');
        const addSkillForm = document.querySelector('.add-skill-form');
        const searchInput = document.getElementById('skillSearch');
        const categorySelect = document.getElementById('categoryFilter');
        const skillIdInput = document.getElementById('skillIdHidden');
        const suggestions = document.getElementById('skillSuggestions');
        const sortSelect = document.getElementById('sortSelect');
        let currentSkills = [];
        let isEditing = false; // Track if we're currently editing

        function buildQuery() {
            const params = new URLSearchParams();
            if (searchInput && searchInput.value.trim() !== '') params.set('q', searchInput.value.trim());
            if (categorySelect && categorySelect.value !== '') params.set('category', categorySelect.value);
            if (sortSelect && sortSelect.value !== '') params.set('sort', sortSelect.value);
            if (skillIdInput && skillIdInput.value !== '') params.set('skill_id', skillIdInput.value);
            const qs = params.toString();
            return qs ? ('?' + qs) : '';
        }

        async function refreshSkills() {
            // Don't refresh if currently editing
            if (isEditing) {
                return;
            }
            try {
                const res = await fetch('{{ route('admin.api.skills') }}' + buildQuery(), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (!data.success) return;
                tbody.innerHTML = '';
                if (data.count === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="no-data">There are no matching records for your search</td></tr>';
                    // Update stats to zero state for current filter
                    const t = document.getElementById('skillsTotalCount');
                    const c = document.getElementById('skillsCategoryCount');
                    const u = document.getElementById('skillsUsersTotal');
                    if (t) t.textContent = '0';
                    if (c) c.textContent = '0';
                    if (u) u.textContent = '0';
                    return;
                }
                // Populate datalist suggestions with skill names
                if (suggestions) {
                    currentSkills = data.skills;
                    let html = '';
                    currentSkills.forEach(function(s) {
                        // datalist cannot carry IDs; we'll map on selection
                        html += `<option value="${s.name}"></option>`;
                    });
                    suggestions.innerHTML = html;
                }
                data.skills.forEach(function(s) {
                    const tr = document.createElement('tr');
                    tr.setAttribute('data-id', s.skill_id);
                    tr.innerHTML = `
                        <td>${s.name}</td>
                        <td><span class="category-badge">${s.category ?? ''}</span></td>
                        <td>${s.users_count ?? 0} users</td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn-edit" data-action="edit" data-id="${s.skill_id}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button type="button" class="btn-delete" onclick="deleteSkill(${s.skill_id}, '${s.name.replace(/'/g, "\\'")}')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3,6 5,6 21,6"/>
                                        <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                // Update stats from response
                (function(){
                    try {
                        const total = data.skills.length;
                        const categories = new Set();
                        let users = 0;
                        data.skills.forEach(function(s){
                            if (s.category) categories.add(s.category);
                            users += (s.users_count || 0);
                        });
                        const t = document.getElementById('skillsTotalCount');
                        const c = document.getElementById('skillsCategoryCount');
                        const u = document.getElementById('skillsUsersTotal');
                        if (t) t.textContent = String(total);
                        if (c) c.textContent = String(categories.size);
                        if (u) u.textContent = String(users);
                    } catch (_) {}
                })();
            } catch (e) {
                // silent
            }
        }

        // Expose for header Apply button
        window.refreshSkills = refreshSkills;

        // Wire up filter listeners with debounce for search
        let searchDebounce;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(refreshSkills, 300);
            });
            // Trigger on Enter
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    refreshSkills();
                }
            });
        }
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                // Reset selected skill when category changes
                if (skillIdInput) skillIdInput.value = '';
                refreshSkills();
            });
        }

        // When a suggestion is chosen or input matches a skill exactly, set hidden skill id
        function updateSkillIdFromInput() {
            const val = (searchInput?.value || '').trim().toLowerCase();
            if (!val) {
                if (skillIdInput) skillIdInput.value = '';
                return;
            }
            const match = currentSkills.find(s => (s.name || '').toLowerCase() === val);
            if (skillIdInput) skillIdInput.value = match ? String(match.skill_id) : '';
        }

        if (searchInput) {
            searchInput.addEventListener('change', function(){ updateSkillIdFromInput(); refreshSkills(); });
            searchInput.addEventListener('blur', function(){ updateSkillIdFromInput(); });
        }

        // Same dropdown acts as input via inline editor overlay
        if (addCategorySelect) {
            addCategorySelect.addEventListener('change', function() {
                if (addCategorySelect.value !== '__new__') return;
                const wrapper = addCategorySelect.closest('.combo-select') || addCategorySelect.parentElement;
                if (!wrapper) return;
                // Create inline input overlayed on top of the select
                const inline = document.createElement('input');
                inline.type = 'text';
                inline.className = 'form-input inline-combo-input';
                inline.placeholder = 'e.g., Technology';
                inline.setAttribute('aria-label', 'New Category');
                // Position absolutely over the select
                inline.style.position = 'absolute';
                inline.style.inset = 'auto 0 0 0';
                inline.style.height = addCategorySelect.offsetHeight + 'px';
                inline.style.zIndex = '3';
                wrapper.style.position = 'relative';
                wrapper.appendChild(inline);
                inline.focus();

                const finalize = (commit) => {
                    const value = (inline.value || '').trim();
                    inline.remove();
                    if (commit && value) {
                        // Insert new option before the "add new" option
                        const opt = document.createElement('option');
                        opt.value = value;
                        opt.textContent = value;
                        const addNewIndex = Array.from(addCategorySelect.options).findIndex(o => o.value === '__new__');
                        const insertBefore = addNewIndex >= 0 ? addCategorySelect.options[addNewIndex] : null;
                        addCategorySelect.add(opt, insertBefore);
                        addCategorySelect.value = value;
                    } else {
                        addCategorySelect.value = '';
                    }
                };

                inline.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        finalize(true);
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        finalize(false);
                    }
                });
                inline.addEventListener('blur', function() { finalize(inline.value.trim() !== ''); });
            });
        }

        // Block submit if category not selected or "add new" not completed
        if (addSkillForm && addCategorySelect) {
            addSkillForm.addEventListener('submit', function(e) {
                const val = addCategorySelect.value;
                if (!val || val === '__new__') {
                    e.preventDefault();
                    if (typeof showSkillToast === 'function') {
                        showSkillToast('Please select a category (or add a new one).', 'error');
                    }
                    addCategorySelect.focus();
                    return false;
                }
            });
        }
        if (sortSelect) {
            sortSelect.addEventListener('change', refreshSkills);
        }

        // Inline edit handler - check for edit button specifically
        tbody.addEventListener('click', function(e) {
            // Only handle if clicking the edit button or its SVG
            const btn = e.target.closest('button[data-action="edit"]');
            if (!btn) return;

            // Prevent any other handlers
            e.stopPropagation();

            const id = btn.getAttribute('data-id');
            const row = btn.closest('tr');
            const nameCell = row.children[0];
            const categoryCell = row.children[1];

            // Check if already in edit mode
            if (nameCell.querySelector('input')) {
                return; // Already editing, don't trigger again
            }

            // Set editing flag to prevent auto-refresh
            isEditing = true;

            const currentName = nameCell.textContent.trim();
            const currentCategory = categoryCell.querySelector('.category-badge') ? categoryCell.querySelector('.category-badge').textContent.trim() : categoryCell.textContent.trim();

            nameCell.innerHTML = `<input class="form-input" value="${currentName}">`;
            categoryCell.innerHTML = `<input class="form-input" value="${currentCategory}">`;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"></polyline></svg>';
            btn.dataset.action = 'save';
            btn.title = 'Save';
        });

        // Save handler - check for save button specifically
        tbody.addEventListener('click', async function(e) {
            // Only handle if clicking the save button or its SVG
            const btn = e.target.closest('button[data-action="save"]');
            if (!btn) return;

            // Prevent any other handlers
            e.stopPropagation();
            e.preventDefault();

            const row = btn.closest('tr');
            const id = row.getAttribute('data-id');

            // Get input values
            const nameInput = row.children[0].querySelector('input');
            const categoryInput = row.children[1].querySelector('input');

            if (!nameInput || !categoryInput) {
                return; // Inputs not found, something went wrong
            }

            const name = nameInput.value.trim();
            const category = categoryInput.value.trim();

            if (!name || !category) {
                alert('Name and category are required.');
                return;
            }

            try {
                // Disable button during save
                btn.disabled = true;

                const res = await fetch(`/admin/skills/${id}`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: new URLSearchParams({ _method: 'PUT', name, category })
                });

                // Reset editing flag before refresh
                isEditing = false;

                if (res.ok) {
                    const result = await res.json();
                    await refreshSkills();
                    if (typeof showSkillToast === 'function') {
                        showSkillToast('Skill updated successfully.', 'success');
                    }
                } else {
                    alert('Failed to update skill. Please try again.');
                    btn.disabled = false;
                    isEditing = true; // Keep editing mode on error
                }
            } catch (err) {
                console.error('Save error:', err);
                alert('An error occurred while saving. Please try again.');
                btn.disabled = false;
                isEditing = true; // Keep editing mode on error
            }
        });
    });

    // Toast helper (global)
    function showSkillToast(message, type = 'success') {
        const toast = document.getElementById('skill-toast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('hidden', 'toast-success', 'toast-error');
        toast.classList.add(type === 'success' ? 'toast-success' : 'toast-error', 'show');
        setTimeout(() => {
            toast.classList.remove('show');
            // Delay adding hidden to allow transition out
            setTimeout(() => toast.classList.add('hidden'), 300);
        }, 2500);
    }

    // Delete Skill Function
    function deleteSkill(id, name) {
        if (confirm('Are you sure you want to delete the skill "' + name + '"? This action cannot be undone.')) {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/admin/skills/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if (typeof showSkillToast === 'function') {
                        showSkillToast(result.message || 'Skill deleted successfully.', 'success');
                    }
                    // Refresh table after delete
                    if (typeof refreshSkills === 'function') {
                        refreshSkills();
                    } else {
                        location.reload();
                    }
                } else {
                    if (typeof showSkillToast === 'function') {
                        showSkillToast(result.message || 'Failed to delete skill', 'error');
                    } else {
                        alert('Error: ' + (result.message || 'Failed to delete skill'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof showSkillToast === 'function') {
                    showSkillToast('An error occurred while deleting the skill.', 'error');
                } else {
                    alert('An error occurred while deleting the skill.');
                }
                // Attempt to sync state
                if (typeof refreshSkills === 'function') {
                    refreshSkills();
                } else {
                    location.reload();
                }
            });
        }
    }
    </script>
    <style>
        /* Toast Notification */
        .toast {
            position: fixed;
            top: 88px; /* below admin header */
            right: 24px;
            background: #10b981; /* success default */
            color: white;
            padding: 10px 14px;
            border-radius: 8px;
            box-shadow: 0 10px 15px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05);
            z-index: 1000;
            opacity: 0;
            transform: translateY(-6px);
            transition: opacity 0.25s ease, transform 0.25s ease;
            font-size: 14px;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast.hidden { display: none; }
        .toast-success { background: #10b981; }
        .toast-error { background: #ef4444; }

        /* Combo select overlay input */
        .combo-select { position: relative; }
        .inline-combo-input {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
        }

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #eef2f7;
        }
        .stat-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
        .stat-value { font-size: 22px; font-weight: 700; color: #111827; margin-top: 6px; }
        @media (max-width: 768px) {
            .stats-cards { grid-template-columns: 1fr; }
        }
        /* Success and Error Messages */
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            border: 1px solid #a7f3d0;
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .error-title {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .error-message ul {
            margin: 0;
            padding-left: 20px;
        }

        /* Add Skill Card */
        .add-skill-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .add-skill-form {
            display: flex;
            gap: 16px;
            align-items: end;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }

        .form-input {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            color: #374151;
            background: white;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .error-text {
            color: #dc2626;
            font-size: 12px;
            font-weight: 500;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        /* Small button utility */
        .btn-sm {
            padding: 6px 10px;
            font-size: 12px;
            border-radius: 6px;
        }

        .btn-sm .btn-icon {
            width: 14px;
            height: 14px;
            margin-right: 6px;
        }

        /* Action Buttons - Matching Token Management Style */
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

        /* Skills Table Card */
        .skills-table-card {
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

        .table-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .table-actions .filters {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .table-actions .filters .form-input {
            height: 36px;
            padding: 6px 10px;
            min-width: 160px;
            line-height: 22px;
            box-sizing: border-box;
        }

        /* Normalize native select/input heights in header filters */
        .table-actions .filters select.form-input,
        .table-actions .filters input.form-input {
            height: 36px;
            line-height: 22px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        #skillSearch.form-input,
        #categoryFilter.form-input,
        #sortSelect.form-input {
            min-width: 220px;
            height: 36px; /* ensure same height as dropdowns */
        }

        .table-title-section {
            flex: 1;
        }

        .table-subtitle {
            font-size: 14px;
            color: #64748b;
            margin: 4px 0 0 0;
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
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
        }

        .category-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .no-data {
            text-align: center;
            color: #6b7280;
            font-style: italic;
            padding: 40px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .add-skill-form {
                flex-direction: column;
                align-items: stretch;
            }

            .table-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .table-actions {
                width: 100%;
                flex-wrap: wrap;
                gap: 10px;
            }

            .table-actions .filters {
                width: 100%;
                flex-wrap: wrap;
            }

            .table-actions .filters .form-input {
                flex: 1 1 180px;
            }

            .table-container {
                overflow-x: auto;
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
        }

        @media (max-width: 480px) {
            .btn-edit,
            .btn-delete {
                padding: 5px;
            }

            .btn-edit svg,
            .btn-delete svg {
                width: 12px;
                height: 12px;
            }
        }
    </style>
</body>

</html>

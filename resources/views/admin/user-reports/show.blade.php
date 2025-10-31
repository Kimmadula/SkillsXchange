<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Report Details</title>
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
                <img src="{{ asset('logo.png') }}" alt="SkillsXchange Logo" class="admin-logo">
                <span class="logo-text">SkillsXchange Admin</span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}" class="nav-item active">
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
                <h1 class="page-title">Report Details</h1>
                <p class="page-subtitle">Review and manage this user report</p>
            </div>
            <div class="header-right">
                <div class="user-profile">
                    <div class="user-avatar">{{ substr(auth()->user()->firstname, 0, 1) }}{{ substr(auth()->user()->lastname, 0, 1) }}</div>
                    <div class="user-info">
                        <span class="user-name">{{ auth()->user()->name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="dashboard-content">
    <!-- Report Details Header -->
    <div class="metric-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 8px 0;">Report #{{ $report->id }}</h2>
                    </div>
                </div>
            </div>

<!-- Report Information -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Reporter Information -->
    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px;">
        <h3 style="font-size: 14px; font-weight: 600; color: #64748b; margin: 0 0 16px 0; text-transform: uppercase; letter-spacing: 0.5px;">Reporter</h3>
        <div style="display: flex; align-items: center; gap: 12px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
            <div style="width: 40px; height: 40px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 16px;">
                {{ substr($report->reporter->firstname, 0, 1) }}{{ substr($report->reporter->lastname, 0, 1) }}
            </div>
            <div>
                <div style="font-weight: 600; color: #1a202c; font-size: 16px;">{{ $report->reporter->firstname }} {{ $report->reporter->lastname }}</div>
                <div style="color: #94a3b8; font-size: 13px;">ID: #{{ $report->reporter->id }}</div>
            </div>
        </div>
        <div style="margin-top: 16px; display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #64748b; font-size: 14px;">Email</span>
                <span style="color: #1a202c; font-size: 14px; font-weight: 500;">{{ $report->reporter->email }}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #64748b; font-size: 14px;">Member Since</span>
                <span style="color: #1a202c; font-size: 14px; font-weight: 500;">{{ $report->reporter->created_at->format('M d, Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Reported User Information -->
    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px;">
        <h3 style="font-size: 14px; font-weight: 600; color: #64748b; margin: 0 0 16px 0; text-transform: uppercase; letter-spacing: 0.5px;">Reported User</h3>
        <div style="display: flex; align-items: center; gap: 12px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
            <div style="width: 40px; height: 40px; background: #ef4444; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 16px;">
                {{ substr($report->reported->firstname, 0, 1) }}{{ substr($report->reported->lastname, 0, 1) }}
            </div>
            <div>
                <div style="font-weight: 600; color: #1a202c; font-size: 16px;">{{ $report->reported->firstname }} {{ $report->reported->lastname }}</div>
                <div style="color: #94a3b8; font-size: 13px;">ID: #{{ $report->reported->id }}</div>
            </div>
        </div>
        <div style="margin-top: 16px; display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #64748b; font-size: 14px;">Email</span>
                <span style="color: #1a202c; font-size: 14px; font-weight: 500;">{{ $report->reported->email }}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #64748b; font-size: 14px;">Member Since</span>
                <span style="color: #1a202c; font-size: 14px; font-weight: 500;">{{ $report->reported->created_at->format('M d, Y') }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Report Details -->
<div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px;">
    <h3 style="font-size: 14px; font-weight: 600; color: #64748b; margin: 0 0 20px 0; text-transform: uppercase; letter-spacing: 0.5px;">Report Details</h3>
    
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px;">
        <div>
            <div style="color: #94a3b8; font-size: 12px; margin-bottom: 6px;">Reason</div>
            <span style="background: #e0e7ff; color: #3730a3; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: 500; text-transform: capitalize; display: inline-block;">
                {{ $report->reason }}
            </span>
        </div>
        <div>
            <div style="color: #94a3b8; font-size: 12px; margin-bottom: 6px;">Status</div>
            @if($report->status === 'pending')
                <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: 500; display: inline-block;">
                    Pending
                </span>
            @elseif($report->status === 'under_review')
                <span style="background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: 500; display: inline-block;">
                    Under Review
                </span>
            @elseif($report->status === 'resolved')
                <span style="background: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: 500; display: inline-block;">
                    Resolved
                </span>
            @else
                <span style="background: #f3f4f6; color: #6b7280; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: 500; display: inline-block;">
                    Dismissed
                </span>
            @endif
        </div>
        <div>
            <div style="color: #94a3b8; font-size: 12px; margin-bottom: 6px;">Report Date</div>
            <div style="color: #1a202c; font-size: 14px; font-weight: 500;">{{ $report->created_at->format('M j, Y') }}</div>
            <div style="color: #94a3b8; font-size: 12px; margin-top: 2px;">{{ $report->created_at->format('g:i A') }}</div>
        </div>
        <div>
            <div style="color: #94a3b8; font-size: 12px; margin-bottom: 6px;">Last Updated</div>
            <div style="color: #1a202c; font-size: 14px; font-weight: 500;">{{ $report->updated_at->format('M j, Y') }}</div>
            <div style="color: #94a3b8; font-size: 12px; margin-top: 2px;">{{ $report->updated_at->format('g:i A') }}</div>
        </div>
    </div>

    <div style="border-top: 1px solid #f1f5f9; padding-top: 20px;">
        <div style="color: #94a3b8; font-size: 12px; margin-bottom: 8px;">Description</div>
        <div style="background: #f8fafc; border-radius: 6px; padding: 16px; white-space: pre-wrap; line-height: 1.6; color: #374151; font-size: 14px;">{{ $report->description }}</div>
    </div>
</div>

<!-- Admin Actions -->
<div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px;">
    <h3 style="font-size: 14px; font-weight: 600; color: #64748b; margin: 0 0 20px 0; text-transform: uppercase; letter-spacing: 0.5px;">Admin Actions</h3>
    <form method="POST" action="{{ route('admin.user-reports.update', $report) }}">
        @csrf
        @method('PATCH')
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label style="display: block; font-size: 13px; color: #64748b; margin-bottom: 8px; font-weight: 500;">Update Status</label>
                <select name="status" required style="width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; background: white; color: #1a202c;">
                    <option value="pending" {{ $report->status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="under_review" {{ $report->status === 'under_review' ? 'selected' : '' }}>Under Review</option>
                    <option value="resolved" {{ $report->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="dismissed" {{ $report->status === 'dismissed' ? 'selected' : '' }}>Dismissed</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; color: #64748b; margin-bottom: 8px; font-weight: 500;">Admin Notes</label>
                <textarea name="admin_notes" placeholder="Add internal notes..." 
                          style="width: 70%; min-height: 50px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 6px; resize: vertical; font-size: 14px; font-family: inherit;">{{ old('admin_notes', $report->admin_notes) }}</textarea>
            </div>
        </div>
        <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 16px; border-top: 1px solid #f1f5f9;">
            <a href="{{ route('admin.dashboard') }}" 
               style="background: white; border: 1px solid #e2e8f0; padding: 10px 20px; border-radius: 6px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 500;">
                Cancel
            </a>
            <button type="submit" 
                    style="background: #3b82f6; color: white; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
                Save Changes
            </button>
        </div>
    </form>
</div>
    </div>
</div>

@include('admin.dashboard-styles')
</body>
</html>



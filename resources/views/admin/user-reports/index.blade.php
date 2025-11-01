<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Admin User Reports</title>
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
                    <path d="M3 3h18v18H3zM9 9h6v6H9z"/>
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
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
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
                <h1 class="page-title">User Reports</h1>
                <p class="page-subtitle">Manage and review user reports</p>
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
    <!-- User Reports Header -->
    <div class="metric-card">
        <!-- Search and Filters -->
        <div class="flex flex-col gap-4 mb-6 bg-white p-5 rounded-lg border border-slate-200 relative z-10">
        <div style="flex: 1; min-width: 300px;">
                <input type="text" id="reportSearch" placeholder="Search reports by reporter, reported user, or reason..."
                       style="width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;"
                       onkeyup="filterReports()">
            </div>
            <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                <select id="statusFilter" onchange="filterReports()"
                        style="padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: white;">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                    <option value="resolved">Resolved</option>
                    <option value="dismissed">Dismissed</option>
                </select>
                <select id="reasonFilter" onchange="filterReports()"
                        style="padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: white;">
                    <option value="">All Reasons</option>
                    <option value="harassment">Harassment</option>
                    <option value="spam">Spam</option>
                    <option value="inappropriate">Inappropriate</option>
                    <option value="fraud">Fraud</option>
                    <option value="safety">Safety</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>

        <!-- Reports Table -->
        <div style="overflow-x: auto; background: white; border: 1px solid #e2e8f0; border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <th style="text-align: left; padding: 16px; font-weight: 600; color: #374151;">ID</th>
                        <th style="text-align: left; padding: 16px; font-weight: 600; color: #374151;">Reporter</th>
                        <th style="text-align: left; padding: 16px; font-weight: 600; color: #374151;">Reported</th>
                        <th style="text-align: left; padding: 16px; font-weight: 600; color: #374151;">Reason</th>
                        <th style="text-align: left; padding: 16px; font-weight: 600; color: #374151;">Status</th>
                        <th style="text-align: left; padding: 16px; font-weight: 600; color: #374151;">Date</th>
                        <th style="text-align: left; padding: 16px; font-weight: 600; color: #374151;">Actions</th>
                    </tr>
                </thead>
                <tbody id="reportsTableBody">
                    @foreach($reports as $report)
                    <tr class="report-row" data-report-id="{{ $report->id }}" data-status="{{ $report->status }}" data-reason="{{ $report->reason }}">
                        <td style="padding: 16px; border-bottom: 1px solid #f3f4f6; font-weight: 500; color: #1a202c;">
                            #{{ $report->id }}
                        </td>
                        <td style="padding: 16px; border-bottom: 1px solid #f3f4f6;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 32px; height: 32px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">
                                    {{ substr($report->reporter->firstname, 0, 1) }}{{ substr($report->reporter->lastname, 0, 1) }}
                                </div>
                                <div>
                                    <div style="font-weight: 500; color: #1a202c;">{{ $report->reporter->firstname }} {{ $report->reporter->lastname }}</div>
                                    <div style="font-size: 12px; color: #64748b;">{{ $report->reporter->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 16px; border-bottom: 1px solid #f3f4f6;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 32px; height: 32px; background: #ef4444; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">
                                    {{ substr($report->reported->firstname, 0, 1) }}{{ substr($report->reported->lastname, 0, 1) }}
                                </div>
                                <div>
                                    <div style="font-weight: 500; color: #1a202c;">{{ $report->reported->firstname }} {{ $report->reported->lastname }}</div>
                                    <div style="font-size: 12px; color: #64748b;">{{ $report->reported->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 16px; border-bottom: 1px solid #f3f4f6;">
                            <span style="background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; text-transform: capitalize;">
                                {{ $report->reason }}
                            </span>
                        </td>
                        <td style="padding: 16px; border-bottom: 1px solid #f3f4f6;">
                            @if($report->status === 'pending')
                                <span style="background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                    ⏳ Pending
                                </span>
                            @elseif($report->status === 'under_review')
                                <span style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                    🔍 Under Review
                                </span>
                            @elseif($report->status === 'resolved')
                                <span style="background: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                    ✅ Resolved
                                </span>
                            @else
                                <span style="background: #f3f4f6; color: #6b7280; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                    ❌ Dismissed
                                </span>
                            @endif
                        </td>
                        <td style="padding: 16px; border-bottom: 1px solid #f3f4f6; color: #64748b; font-size: 14px;">
                            {{ $report->created_at->format('M d, Y H:i') }}
                        </td>
                        <td style="padding: 16px; border-bottom: 1px solid #f3f4f6;">
                            <a href="{{ route('admin.user-reports.show', $report) }}"
                               style="background: #3b82f6; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                👁 View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div style="margin-top: 24px; display: flex; justify-content: center;">
            {{ $reports->links() }}
        </div>
    </div>
</div>

<script>
// Filter reports function
function filterReports() {
    const searchTerm = document.getElementById('reportSearch').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const reasonFilter = document.getElementById('reasonFilter').value;

    const rows = document.querySelectorAll('.report-row');

    rows.forEach(row => {
        const reportText = row.textContent.toLowerCase();
        const reportStatus = row.getAttribute('data-status');
        const reportReason = row.getAttribute('data-reason');

        const matchesSearch = reportText.includes(searchTerm);
        const matchesStatus = !statusFilter || reportStatus === statusFilter;
        const matchesReason = !reasonFilter || reportReason === reasonFilter;

        if (matchesSearch && matchesStatus && matchesReason) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Refresh reports function
function refreshReports() {
    window.location.reload();
}

// Export reports function
function exportReports() {
    alert('Export functionality coming soon!');
}
</script>

@include('admin.dashboard-styles')
</body>
</html>



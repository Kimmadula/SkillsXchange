@extends('admin.dashboard')

@section('content')
<div class="dashboard-content">
    <!-- User Reports Header -->
    <div class="metric-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h2 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 8px 0;">User Reports</h2>
                <p style="color: #64748b; margin: 0;">Manage and review user reports</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button onclick="refreshReports()" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 6px; color: #64748b; cursor: pointer; font-size: 14px;">
                    🔄 Refresh
                </button>
                <button onclick="exportReports()" style="background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                    📊 Export
                </button>
            </div>
        </div>

        <!-- Search and Filters -->
        <div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <input type="text" id="reportSearch" placeholder="Search reports by reporter, reported user, or reason..." 
                       style="width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;"
                       onkeyup="filterReports()">
            </div>
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
@endsection



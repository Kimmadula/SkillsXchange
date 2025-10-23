@extends('admin.dashboard')

@section('content')
<div class="dashboard-content">
    <!-- Report Details Header -->
    <div class="metric-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h2 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 8px 0;">Report #{{ $report->id }}</h2>
                <p style="color: #64748b; margin: 0;">Review and manage this user report</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="{{ route('admin.user-reports.index') }}" 
                   style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 6px; color: #64748b; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                    ← Back to Reports
                </a>
            </div>
        </div>

        <!-- Report Information -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 32px;">
            <!-- Reporter Information -->
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
                <h3 style="font-size: 16px; font-weight: 600; color: #1a202c; margin: 0 0 16px 0;">Reporter Information</h3>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <div style="width: 48px; height: 48px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 18px;">
                        {{ substr($report->reporter->firstname, 0, 1) }}{{ substr($report->reporter->lastname, 0, 1) }}
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #1a202c; font-size: 18px;">{{ $report->reporter->firstname }} {{ $report->reporter->lastname }}</div>
                        <div style="color: #64748b; font-size: 14px;">{{ $report->reporter->email }}</div>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div>
                        <label style="font-size: 12px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">User ID</label>
                        <div style="font-weight: 500; color: #1a202c; margin-top: 4px;">#{{ $report->reporter->id }}</div>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Member Since</label>
                        <div style="font-weight: 500; color: #1a202c; margin-top: 4px;">{{ $report->reporter->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>

            <!-- Reported User Information -->
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
                <h3 style="font-size: 16px; font-weight: 600; color: #1a202c; margin: 0 0 16px 0;">Reported User</h3>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <div style="width: 48px; height: 48px; background: #ef4444; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 18px;">
                        {{ substr($report->reported->firstname, 0, 1) }}{{ substr($report->reported->lastname, 0, 1) }}
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #1a202c; font-size: 18px;">{{ $report->reported->firstname }} {{ $report->reported->lastname }}</div>
                        <div style="color: #64748b; font-size: 14px;">{{ $report->reported->email }}</div>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div>
                        <label style="font-size: 12px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">User ID</label>
                        <div style="font-weight: 500; color: #1a202c; margin-top: 4px;">#{{ $report->reported->id }}</div>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Member Since</label>
                        <div style="font-weight: 500; color: #1a202c; margin-top: 4px;">{{ $report->reported->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Details -->
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; margin-bottom: 24px;">
            <h3 style="font-size: 18px; font-weight: 600; color: #1a202c; margin: 0 0 20px 0;">Report Details</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
                <div>
                    <label style="font-size: 12px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Reason</label>
                    <div style="margin-top: 8px;">
                        <span style="background: #e0e7ff; color: #3730a3; padding: 6px 12px; border-radius: 6px; font-size: 14px; font-weight: 500; text-transform: capitalize;">
                            {{ $report->reason }}
                        </span>
                    </div>
                </div>
			<div>
                    <label style="font-size: 12px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Current Status</label>
                    <div style="margin-top: 8px;">
                        @if($report->status === 'pending')
                            <span style="background: #fef3c7; color: #92400e; padding: 6px 12px; border-radius: 6px; font-size: 14px; font-weight: 500;">
                                ⏳ Pending
                            </span>
                        @elseif($report->status === 'under_review')
                            <span style="background: #dbeafe; color: #1e40af; padding: 6px 12px; border-radius: 6px; font-size: 14px; font-weight: 500;">
                                🔍 Under Review
                            </span>
                        @elseif($report->status === 'resolved')
                            <span style="background: #d1fae5; color: #065f46; padding: 6px 12px; border-radius: 6px; font-size: 14px; font-weight: 500;">
                                ✅ Resolved
                            </span>
                        @else
                            <span style="background: #f3f4f6; color: #6b7280; padding: 6px 12px; border-radius: 6px; font-size: 14px; font-weight: 500;">
                                ❌ Dismissed
                            </span>
                        @endif
                    </div>
			</div>
			<div>
                    <label style="font-size: 12px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Report Date</label>
                    <div style="font-weight: 500; color: #1a202c; margin-top: 8px;">{{ $report->created_at->format('F j, Y \a\t g:i A') }}</div>
			</div>
			<div>
                    <label style="font-size: 12px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Last Updated</label>
                    <div style="font-weight: 500; color: #1a202c; margin-top: 8px;">{{ $report->updated_at->format('F j, Y \a\t g:i A') }}</div>
                </div>
			</div>
			<div>
                <label style="font-size: 12px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Description</label>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; white-space: pre-wrap; line-height: 1.6; color: #374151;">
                    {{ $report->description }}
			</div>
			</div>
		</div>

        <!-- Admin Actions -->
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px;">
            <h3 style="font-size: 18px; font-weight: 600; color: #1a202c; margin: 0 0 20px 0;">Admin Actions</h3>
		<form method="POST" action="{{ route('admin.user-reports.update', $report) }}">
			@csrf
			@method('PATCH')
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
				<div>
                        <label style="display: block; font-size: 14px; color: #374151; margin-bottom: 8px; font-weight: 500;">Update Status</label>
                        <select name="status" required style="width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: white;">
                            <option value="pending" {{ $report->status === 'pending' ? 'selected' : '' }}>⏳ Pending</option>
                            <option value="under_review" {{ $report->status === 'under_review' ? 'selected' : '' }}>🔍 Under Review</option>
                            <option value="resolved" {{ $report->status === 'resolved' ? 'selected' : '' }}>✅ Resolved</option>
                            <option value="dismissed" {{ $report->status === 'dismissed' ? 'selected' : '' }}>❌ Dismissed</option>
					</select>
				</div>
				<div>
                        <label style="display: block; font-size: 14px; color: #374151; margin-bottom: 8px; font-weight: 500;">Admin Notes (optional)</label>
                        <textarea name="admin_notes" placeholder="Add internal notes about this report..." 
                                  style="width: 100%; min-height: 120px; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; resize: vertical; font-size: 14px;">{{ old('admin_notes', $report->admin_notes) }}</textarea>
				</div>
			</div>
                <div style="margin-top: 24px; display: flex; gap: 12px;">
                    <a href="{{ route('admin.user-reports.index') }}" 
                       style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 20px; border-radius: 8px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 500;">
                        Cancel
                    </a>
                    <button type="submit" 
                            style="background: #3b82f6; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500;">
                        Save Changes
                    </button>
			</div>
		</form>
        </div>
	</div>
</div>
@endsection



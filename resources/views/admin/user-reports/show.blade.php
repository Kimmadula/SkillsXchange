@extends('layouts.app')

@section('content')
<div class="container mx-auto" style="max-width: 900px;">
	<h1 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 16px;">Report #{{ $report->id }}</h1>

	<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
			<div>
				<div style="font-size: 0.85rem; color: #6b7280;">Reporter</div>
				<div style="font-weight: 600;">{{ $report->reporter->firstname }} {{ $report->reporter->lastname }}</div>
			</div>
			<div>
				<div style="font-size: 0.85rem; color: #6b7280;">Reported User</div>
				<div style="font-weight: 600;">{{ $report->reported->firstname }} {{ $report->reported->lastname }}</div>
			</div>
			<div>
				<div style="font-size: 0.85rem; color: #6b7280;">Reason</div>
				<div style="text-transform: capitalize;">{{ $report->reason }}</div>
			</div>
			<div>
				<div style="font-size: 0.85rem; color: #6b7280;">Status</div>
				<div style="text-transform: capitalize;">{{ $report->status }}</div>
			</div>
			<div style="grid-column: 1 / -1;">
				<div style="font-size: 0.85rem; color: #6b7280;">Description</div>
				<div style="white-space: pre-wrap;">{{ $report->description }}</div>
			</div>
		</div>

		<form method="POST" action="{{ route('admin.user-reports.update', $report) }}">
			@csrf
			@method('PATCH')
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
				<div>
					<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Update Status</label>
					<select name="status" required style="width: 100%; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
						<option value="pending" {{ $report->status === 'pending' ? 'selected' : '' }}>Pending</option>
						<option value="under_review" {{ $report->status === 'under_review' ? 'selected' : '' }}>Under Review</option>
						<option value="resolved" {{ $report->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
						<option value="dismissed" {{ $report->status === 'dismissed' ? 'selected' : '' }}>Dismissed</option>
					</select>
				</div>
				<div>
					<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Admin Notes (optional)</label>
					<textarea name="admin_notes" style="width: 100%; min-height: 100px; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 6px; resize: vertical;">{{ old('admin_notes', $report->admin_notes) }}</textarea>
				</div>
			</div>
			<div style="margin-top: 12px; display: flex; gap: 8px;">
				<a href="{{ route('admin.user-reports.index') }}" style="background: #e5e7eb; color: #111827; padding: 8px 12px; border-radius: 6px; text-decoration: none;">Back</a>
				<button type="submit" style="background: #2563eb; color: white; padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer;">Save</button>
			</div>
		</form>
	</div>
</div>
@endsection



@extends('layouts.app')

@section('content')
<div class="container mx-auto" style="max-width: 1100px;">
	<h1 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 16px;">User Reports</h1>

	<div style="overflow-x: auto; background: white; border: 1px solid #e5e7eb; border-radius: 8px;">
		<table style="width: 100%; border-collapse: collapse;">
			<thead>
				<tr style="background: #f9fafb;">
					<th style="text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb;">ID</th>
					<th style="text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb;">Reporter</th>
					<th style="text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb;">Reported</th>
					<th style="text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb;">Reason</th>
					<th style="text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb;">Status</th>
					<th style="text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb;">Date</th>
					<th style="text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb;">Actions</th>
				</tr>
			</thead>
			<tbody>
				@foreach($reports as $report)
				<tr>
					<td style="padding: 10px; border-bottom: 1px solid #f3f4f6;">#{{ $report->id }}</td>
					<td style="padding: 10px; border-bottom: 1px solid #f3f4f6;">{{ $report->reporter->firstname }} {{ $report->reporter->lastname }}</td>
					<td style="padding: 10px; border-bottom: 1px solid #f3f4f6;">{{ $report->reported->firstname }} {{ $report->reported->lastname }}</td>
					<td style="padding: 10px; border-bottom: 1px solid #f3f4f6; text-transform: capitalize;">{{ $report->reason }}</td>
					<td style="padding: 10px; border-bottom: 1px solid #f3f4f6; text-transform: capitalize;">{{ $report->status }}</td>
					<td style="padding: 10px; border-bottom: 1px solid #f3f4f6;">{{ $report->created_at->format('M d, Y H:i') }}</td>
					<td style="padding: 10px; border-bottom: 1px solid #f3f4f6;"><a href="{{ route('admin.user-reports.show', $report) }}" style="color: #2563eb;">View</a></td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	<div style="margin-top: 12px;">
		{{ $reports->links() }}
	</div>
</div>
@endsection



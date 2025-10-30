@extends('layouts.app')

@section('content')
<div style="padding:16px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <h1 style="font-size:1.25rem; margin:0;">My Trades</h1>
        <a href="{{ route('dashboard') }}" style="padding:8px 12px; background:#6b7280; color:#fff; text-decoration:none; border-radius:6px;">← Back to Dashboard</a>
    </div>
    @if(session('success'))
        <div style="background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; padding:10px 12px; border-radius:6px; margin-bottom:12px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca; padding:10px 12px; border-radius:6px; margin-bottom:12px;">{{ session('error') }}</div>
    @endif

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse;">
            <thead style="background:#f9fafb;">
                <tr>
                    <th style="text-align:left; padding:12px; border-bottom:1px solid #e5e7eb;">Offering</th>
                    <th style="text-align:left; padding:12px; border-bottom:1px solid #e5e7eb;">Looking For</th>
                    <th style="text-align:left; padding:12px; border-bottom:1px solid #e5e7eb;">Session</th>
                    <th style="text-align:left; padding:12px; border-bottom:1px solid #e5e7eb;">Status</th>
                    <th style="text-align:right; padding:12px; border-bottom:1px solid #e5e7eb;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trades as $trade)
                <tr>
                    <td style="padding:12px; border-bottom:1px solid #f3f4f6;">{{ $trade->offeringSkill->name ?? '—' }}</td>
                    <td style="padding:12px; border-bottom:1px solid #f3f4f6;">{{ $trade->lookingSkill->name ?? '—' }}</td>
                    <td style="padding:12px; border-bottom:1px solid #f3f4f6; text-transform:capitalize;">{{ $trade->session_type }}</td>
                    <td style="padding:12px; border-bottom:1px solid #f3f4f6;">
                        @if($trade->status === 'open')
                            <span style="padding:4px 8px; background:#dcfce7; color:#166534; border-radius:4px; font-size:0.875rem; font-weight:500;">Active</span>
                        @elseif($trade->status === 'ongoing')
                            <span style="padding:4px 8px; background:#dbeafe; color:#1e40af; border-radius:4px; font-size:0.875rem; font-weight:500;">Matched</span>
                        @elseif($trade->status === 'closed')
                            <span style="padding:4px 8px; background:#f3f4f6; color:#4b5563; border-radius:4px; font-size:0.875rem; font-weight:500;">Closed</span>
                        @else
                            <span style="padding:4px 8px; background:#f3f4f6; color:#4b5563; border-radius:4px; font-size:0.875rem; font-weight:500; text-transform:capitalize;">{{ $trade->status }}</span>
                        @endif
                    </td>
                    <td style="padding:12px; border-bottom:1px solid #f3f4f6; text-align:right;">
                        @if($trade->status !== 'ongoing')
                            <a href="{{ route('trades.edit', $trade) }}" style="padding:6px 10px; background:#3b82f6; color:#fff; text-decoration:none; border-radius:6px; margin-right:6px;">Edit</a>
                            <form action="{{ route('trades.destroy', $trade) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this post?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="padding:6px 10px; background:#ef4444; color:#fff; border:none; border-radius:6px;">Delete</button>
                            </form>
                        @else
                            <span style="padding:4px 8px; background:#f3f4f6; color:#6b7280; border-radius:4px; font-size:0.875rem;">Locked</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:16px; text-align:center; color:#6b7280;">You haven't posted any trades yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:12px;">
        {{ $trades->links() }}
    </div>
    <div style="margin-top:16px;">
        <a href="{{ route('trades.create') }}" style="padding:8px 12px; background:#1e40af; color:#fff; text-decoration:none; border-radius:6px; font-weight:600;">+ New Post</a>
    </div>
</div>
@endsection



@extends('layouts.app')

@section('content')
<main style="padding:32px; max-width:900px; margin:0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h1 style="font-size:1.5rem; margin:0;">Notifications</h1>
        @php
            $unreadCount = App\Http\Controllers\TradeController::getUnreadNotificationCount(Auth::id());
        @endphp
        @if($unreadCount > 0)
            <span style="background:#ef4444; color:white; padding:4px 12px; border-radius:12px; font-size:0.875rem; font-weight:600;">
                {{ $unreadCount }} unread
            </span>
        @endif
    </div>

    @if(session('success'))
        <div style="background:#def7ec; color:#03543f; padding:12px; border-radius:8px; margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display:grid; gap:12px;">
        <!-- Announcements Quick Card -->
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; border-radius:9999px; background:#EFF6FF; display:flex; align-items:center; justify-content:center; color:#2563EB;">📣</div>
                <div>
                    <div style="font-weight:600; color:#1f2937;">Announcements</div>
                    <div style="font-size:0.9rem; color:#6b7280;">
                        @if(($announcementsCount ?? 0) > 0)
                            {{ $announcementsCount }} unread announcement{{ $announcementsCount > 1 ? 's' : '' }}
                        @else
                            You're all caught up
                        @endif
                    </div>
                </div>
            </div>
            <a href="{{ route('dashboard') }}" style="padding:8px 12px; background:#2563eb; color:#fff; text-decoration:none; border-radius:6px; font-size:0.875rem;">View</a>
        </div>
        <!-- Trade Notifications -->
        @forelse($notifications as $n)
            <div style="background:#fff; border:1px solid {{ $n->read ? '#e5e7eb' : '#ef4444' }}; border-radius:8px; padding:16px; {{ $n->read ? '' : 'border-left:4px solid #ef4444;' }}">
                <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:8px;">
                    <div style="flex:1;">
                        @if($n->type === 'trade_request')
                            <div style="font-weight:600; color:#1f2937; margin-bottom:4px;">
                                🔔 New Trade Request
                            </div>
                            <div style="color:#6b7280; font-size:0.9rem; margin-bottom:8px;">
                                @php $name = $n->data['requester_name'] ?? 'Unknown User'; @endphp
                                <strong>{{ $name }}</strong> wants to trade with you
                            </div>
                            @if(isset($n->data['offering_skill']) && isset($n->data['looking_skill']))
                                <div style="background:#f3f4f6; padding:8px; border-radius:6px; margin-bottom:8px;">
                                    <div style="font-size:0.875rem; color:#374151;">
                                        <strong>Trade:</strong> {{ $n->data['offering_skill'] }} → {{ $n->data['looking_skill'] }}
                                    </div>
                                </div>
                            @endif
                            @if(!empty($n->data['message']))
                                <div style="background:#fef3c7; padding:8px; border-radius:6px; margin-bottom:8px;">
                                    <div style="font-size:0.875rem; color:#92400e;">
                                        <strong>Message:</strong> "{{ $n->data['message'] }}"
                                    </div>
                                </div>
                            @endif
                            <div style="display:flex; gap:8px; margin-top:12px;">
                                <a href="{{ route('trades.requests') }}" style="padding:6px 12px; background:#2563eb; color:#fff; text-decoration:none; border-radius:4px; font-size:0.875rem;">
                                    View Request
                                </a>
                            </div>
                        @elseif($n->type === 'trade_response')
                            <div style="font-weight:600; color:#1f2937; margin-bottom:4px;">
                                @if(isset($n->data['status']) && $n->data['status'] === 'accepted')
                                    ✅ Trade Accepted
                                @else
                                    ❌ Trade Declined
                                @endif
                            </div>
                            <div style="color:#6b7280; font-size:0.9rem; margin-bottom:8px;">
                                @php $owner = $n->data['trade_owner_name'] ?? 'Unknown User'; @endphp
                                <strong>{{ $owner }}</strong>
                                @if(isset($n->data['status']) && $n->data['status'] === 'accepted')
                                    accepted your trade request
                                @else
                                    declined your trade request
                                @endif
                            </div>
                            @if(isset($n->data['offering_skill']) && isset($n->data['looking_skill']))
                                <div style="background:#f3f4f6; padding:8px; border-radius:6px; margin-bottom:8px;">
                                    <div style="font-size:0.875rem; color:#374151;">
                                        <strong>Trade:</strong> {{ $n->data['offering_skill'] }} → {{ $n->data['looking_skill'] }}
                                    </div>
                                </div>
                            @endif
                            <div style="display:flex; gap:8px; margin-top:12px;">
                                @if(isset($n->data['status']) && $n->data['status'] === 'accepted')
                                    <a href="{{ route('trades.ongoing') }}" style="padding:6px 12px; background:#10b981; color:#fff; text-decoration:none; border-radius:4px; font-size:0.875rem;">
                                        View Ongoing Trade
                                    </a>
                                @else
                                    <a href="{{ route('trades.matches') }}" style="padding:6px 12px; background:#6b7280; color:#fff; text-decoration:none; border-radius:4px; font-size:0.875rem;">
                                        Find Other Trades
                                    </a>
                                @endif
                            </div>
                        @elseif($n->type === 'match_found')
                            <div style="font-weight:600; color:#1f2937; margin-bottom:4px;">
                                🎯 New Match Found
                            </div>
                            <div style="color:#6b7280; font-size:0.9rem; margin-bottom:8px;">
                                @php
                                    $partner = $n->data['partner_username'] ?? ($n->data['partner_name'] ?? 'a user');
                                    $score = isset($n->data['compatibility_score']) ? (int)$n->data['compatibility_score'] : null;
                                @endphp
                                @if($score !== null)
                                    A {{ $score }}% compatible trade with <strong>{{ $partner }}</strong>.
                                @else
                                    A compatible trade with <strong>{{ $partner }}</strong>.
                                @endif
                            </div>
                            @if(!empty($n->data['offering_skill']) && !empty($n->data['looking_skill']))
                                <div style="background:#f3f4f6; padding:8px; border-radius:6px; margin-bottom:8px;">
                                    <div style="font-size:0.875rem; color:#374151;">
                                        <strong>Trade:</strong> {{ $n->data['offering_skill'] }} → {{ $n->data['looking_skill'] }}
                                    </div>
                                </div>
                            @endif
                            <div style="display:flex; gap:8px; margin-top:12px;">
                                <button type="button" onclick="showMatchDetails({{ json_encode($n->data) }})" style="padding:6px 12px; background:#2563eb; color:#fff; border:none; border-radius:4px; font-size:0.875rem; cursor:pointer;">
                                    View Details
                                </button>
                                <a href="{{ route('trades.matches') }}" style="padding:6px 12px; background:#10b981; color:#fff; text-decoration:none; border-radius:4px; font-size:0.875rem;">
                                    View All Matches
                                </a>
                            </div>
                        @else
                            <div style="font-weight:600; color:#1f2937; margin-bottom:4px;">
                                🔔 Notification
                            </div>
                            <div style="color:#6b7280; font-size:0.9rem;">
                                You have a new update. Please check your requests, matches, or ongoing trades.
                            </div>
                        @endif
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:end; gap:8px;">
                        <span style="font-size:0.8rem; color:#6b7280;">
                            {{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}
                        </span>
                        @if(!$n->read)
                            <form method="POST" action="{{ route('trades.mark-read', $n->id) }}" style="margin:0;">
                                @csrf
                                <button type="submit" style="padding:4px 8px; background:#6b7280; color:#fff; border:none; border-radius:4px; font-size:0.75rem; cursor:pointer;">
                                    Mark Read
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align:center; padding:32px; color:#6b7280;">
                <div style="font-size:3rem; margin-bottom:16px;">🔔</div>
                <div style="font-size:1.1rem; margin-bottom:8px;">No notifications yet</div>
                <div style="font-size:0.9rem;">You'll see notifications here when someone requests a trade or responds to your requests.</div>
            </div>
        @endforelse
    </div>
</main>

@endsection


@push('scripts')
<script>
    function showMatchDetails(data) {
        // Build a simple modal dynamically
        const overlay = document.createElement('div');
        overlay.style.position = 'fixed';
        overlay.style.inset = '0';
        overlay.style.background = 'rgba(0,0,0,0.5)';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.zIndex = '2000';

        const card = document.createElement('div');
        card.style.background = '#fff';
        card.style.borderRadius = '12px';
        card.style.width = '90%';
        card.style.maxWidth = '520px';
        card.style.padding = '20px';
        card.style.boxShadow = '0 10px 25px rgba(0,0,0,0.1)';

        const title = document.createElement('div');
        title.style.fontWeight = '700';
        title.style.fontSize = '1.1rem';
        title.style.marginBottom = '8px';
        title.textContent = 'Match Details';

        const body = document.createElement('div');
        body.style.color = '#374151';
        body.style.fontSize = '0.95rem';

        const line = (label, value) => `<div style="margin:6px 0;"><strong>${label}:</strong> ${value || '—'}</div>`;
        const days = Array.isArray(data.preferred_days) ? data.preferred_days.join(', ') : (data.preferred_days || '—');

        const stars = (avg) => {
            avg = parseFloat(avg || 0);
            const full = Math.floor(avg);
            const half = (avg - full) >= 0.5 ? 1 : 0;
            const empty = 5 - full - half;
            return `${'★'.repeat(full)}${half?'<span style="opacity:0.5">★</span>':''}${'☆'.repeat(empty)}`;
        };

        body.innerHTML = `
            ${line('Partner', (data.partner_username || data.partner_name || '—'))}
            ${line('Trade', (data.offering_skill || '—') + ' → ' + (data.looking_skill || '—'))}
            ${line('Compatibility', (data.compatibility_score != null ? data.compatibility_score + '%': '—'))}
            <div style="margin:6px 0;"><strong>Rating:</strong> <span style="color:#F59E0B; font-size:0.9rem;">${stars(data.partner_rating_avg)}</span> (${data.partner_rating_count || 0})</div>
            ${line('Session Type', data.session_type)}
            ${line('Location', data.location || 'Any location')}
            ${line('Time', (data.available_from || '—') + ' - ' + (data.available_to || '—'))}
            ${line('Days', days)}
            ${line('Dates', (data.start_date || '—') + ' to ' + (data.end_date || '—'))}
        `;

        const footer = document.createElement('div');
        footer.style.display = 'flex';
        footer.style.justifyContent = 'flex-end';
        footer.style.gap = '8px';
        footer.style.marginTop = '14px';

        const closeBtn = document.createElement('button');
        closeBtn.textContent = 'Close';
        closeBtn.style.padding = '8px 12px';
        closeBtn.style.border = 'none';
        closeBtn.style.background = '#6b7280';
        closeBtn.style.color = '#fff';
        closeBtn.style.borderRadius = '6px';
        closeBtn.style.cursor = 'pointer';
        closeBtn.onclick = () => document.body.removeChild(overlay);

        const toMatches = document.createElement('a');
        toMatches.textContent = 'Go to Matches';
        toMatches.href = `{{ route('trades.matches') }}`;
        toMatches.style.padding = '8px 12px';
        toMatches.style.background = '#2563eb';
        toMatches.style.color = '#fff';
        toMatches.style.borderRadius = '6px';
        toMatches.style.textDecoration = 'none';

        footer.appendChild(closeBtn);
        footer.appendChild(toMatches);

        card.appendChild(title);
        card.appendChild(body);
        card.appendChild(footer);
        overlay.appendChild(card);

        overlay.addEventListener('click', function(e){
            if (e.target === overlay) document.body.removeChild(overlay);
        });

        document.body.appendChild(overlay);
    }
</script>
@endpush


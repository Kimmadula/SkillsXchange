@extends('layouts.app')

@section('content')
<main style="padding:32px; max-width:1100px; margin:0 auto; display:grid; gap:24px;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1 style="font-size:1.5rem; margin:0;">Trade Requests</h1>
        <a href="{{ route('dashboard') }}" style="padding:8px 12px; background:#6b7280; color:#fff; text-decoration:none; border-radius:6px; font-size:0.875rem;">
            ← Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div style="background:#def7ec; color:#03543f; padding:10px 12px; border-radius:6px; margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background:#fde8e8; color:#9b1c1c; padding:10px 12px; border-radius:6px; margin-bottom:16px;">
            {{ session('error') }}
        </div>
    @endif

    <section>
        <h2 style="font-size:1.1rem; margin-bottom:8px;">Incoming Requests</h2>
        <div style="display:grid; gap:10px;">
        @forelse($incoming as $r)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px;">
                <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:12px;">
                    <div style="flex:1;">
                        <div style="font-weight:600; margin-bottom:4px;">
                            Request from: {{ $r->requester->use_username ? ($r->requester->username ?? 'User') : (($r->requester->firstname ?? '') . ' ' . ($r->requester->lastname ?? '')) }}
                        </div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-bottom:4px;">
                            Trade: {{ optional($r->trade->offeringSkill)->name }} → {{ optional($r->trade->lookingSkill)->name }}
                        </div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-bottom:8px;">
                            Status: <span style="font-weight:600; color:{{ $r->status === 'pending' ? '#f59e0b' : ($r->status === 'accepted' ? '#10b981' : '#ef4444') }}">{{ strtoupper($r->status) }}</span>
                        </div>
                        @if($r->status === 'pending')
                            <div style="margin:6px 0 10px 0;">
                                @php
                                    $reqRating = \DB::table('session_ratings')
                                        ->selectRaw('AVG(overall_rating) as avg_rating, COUNT(*) as total_ratings')
                                        ->where('rated_user_id', $r->requester_id)
                                        ->first();
                                    $reqAvg = $reqRating ? round((float)($reqRating->avg_rating ?? 0), 2) : 0;
                                    $reqCount = $reqRating ? (int)($reqRating->total_ratings ?? 0) : 0;
                                @endphp
                                <button type="button" onclick="showRequestDetails({{ $r->id }}, {{ json_encode([
                                    'requester' => $r->requester->use_username ? $r->requester->username : ($r->requester->firstname.' '.$r->requester->lastname),
                                    'offering' => optional($r->trade->offeringSkill)->name,
                                    'looking' => optional($r->trade->lookingSkill)->name,
                                    'requester_rating_avg' => $reqAvg,
                                    'requester_rating_count' => $reqCount,
                                    'session_type' => $r->trade->session_type,
                                    'location' => $r->trade->location,
                                    'available_from' => $r->trade->available_from,
                                    'available_to' => $r->trade->available_to,
                                    'preferred_days' => $r->trade->preferred_days,
                                    'start_date' => $r->trade->start_date,
                                    'end_date' => $r->trade->end_date,
                                    'message' => $r->message,
                                ]) }})"
                                        style="padding:6px 12px; background:#2563eb; color:#fff; border:none; border-radius:4px; cursor:pointer;">
                                    View Details
                                </button>
                            </div>
                        @endif
                        @if($r->message)
                            <div style="background:#f9fafb; padding:8px; border-radius:4px; font-size:0.9rem;">
                                <strong>Message:</strong> {{ $r->message }}
                            </div>
                        @endif
                    </div>
                    @if($r->status === 'pending')
                        @php
                            $acceptanceFee = \App\Models\TradeFeeSetting::getFeeAmount('trade_acceptance');
                            $userBalance = auth()->user()->token_balance ?? 0;
                        @endphp
                        <div style="margin-left:16px;">
                            @if($acceptanceFee > 0 && \App\Models\TradeFeeSetting::isFeeActive('trade_acceptance'))
                                <div style="margin-bottom:8px; padding:6px 10px; background:#fef3c7; color:#92400e; border-radius:4px; font-size:0.8rem; border:1px solid #f59e0b;">
                                    <div style="font-weight:600;">Acceptance Fee: {{ $acceptanceFee }} token{{ $acceptanceFee > 1 ? 's' : '' }}</div>
                                    <div style="font-size:0.75rem;">Your balance: {{ $userBalance }} tokens</div>
                                    <div style="font-size:0.7rem; color:#92400e; margin-top:2px;">
                                        <i>Both users will be charged when you accept</i>
                                    </div>
                                </div>
                            @endif
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <form method="POST" action="{{ route('trades.respond', $r->id) }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit"
                                            style="padding:6px 12px; background:#10b981; color:#fff; border:none; border-radius:4px; cursor:pointer; {{ $acceptanceFee > 0 && $userBalance < $acceptanceFee ? 'opacity:50; cursor:not-allowed;' : '' }}"
                                            {{ $acceptanceFee > 0 && $userBalance < $acceptanceFee ? 'disabled' : '' }}>
                                        Accept
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('trades.respond', $r->id) }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" style="padding:6px 12px; background:#ef4444; color:#fff; border:none; border-radius:4px; cursor:pointer;">Decline</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div style="color:#6b7280; text-align:center; padding:16px;">No incoming requests.</div>
        @endforelse
        </div>
    </section>

    <section>
        <h2 style="font-size:1.1rem; margin-bottom:8px;">Outgoing Requests</h2>
        <div style="display:grid; gap:10px;">
        @forelse($outgoing as $r)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px;">
                <div style="flex:1;">
                    <div style="font-weight:600; margin-bottom:4px;">
                        Request to: {{ $r->trade->user->use_username ? $r->trade->user->username : ($r->trade->user->firstname.' '.$r->trade->user->lastname) }}
                    </div>
                    <div style="color:#6b7280; font-size:0.9rem; margin-bottom:4px;">
                        Trade: {{ optional($r->trade->offeringSkill)->name }} → {{ optional($r->trade->lookingSkill)->name }}
                    </div>
                    <div style="color:#6b7280; font-size:0.9rem; margin-bottom:8px;">
                        Status: <span style="font-weight:600; color:{{ $r->status === 'pending' ? '#f59e0b' : ($r->status === 'accepted' ? '#10b981' : '#ef4444') }}">{{ strtoupper($r->status) }}</span>
                    </div>
                    @if($r->message)
                        <div style="background:#f9fafb; padding:8px; border-radius:4px; font-size:0.9rem;">
                            <strong>Your message:</strong> {{ $r->message }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div style="color:#6b7280; text-align:center; padding:16px;">No outgoing requests.</div>
        @endforelse
        </div>
    </section>
</main>
@endsection


@push('scripts')
<script>
    function showRequestDetails(id, data) {
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
        card.style.maxWidth = '560px';
        card.style.padding = '20px';
        card.style.boxShadow = '0 10px 25px rgba(0,0,0,0.1)';

        const title = document.createElement('div');
        title.style.fontWeight = '700';
        title.style.fontSize = '1.1rem';
        title.style.marginBottom = '8px';
        title.textContent = 'Request Details';

        const body = document.createElement('div');
        body.style.color = '#374151';
        body.style.fontSize = '0.95rem';

        const line = (label, value) => `<div style=\"margin:6px 0;\"><strong>${label}:</strong> ${value || '—'}</div>`;
        const days = Array.isArray(data.preferred_days) ? data.preferred_days.join(', ') : (data.preferred_days || '—');

        const stars = (avg) => {
            avg = parseFloat(avg || 0);
            const full = Math.floor(avg);
            const half = (avg - full) >= 0.5 ? 1 : 0;
            const empty = 5 - full - half;
            return `${'★'.repeat(full)}${half?'<span style=\\"opacity:0.5\\">★</span>':''}${'☆'.repeat(empty)}`;
        };

        body.innerHTML = `
            ${line('Requester', data.requester)}
            ${line('Trade', (data.offering || '—') + ' → ' + (data.looking || '—'))}
            <div style=\"margin:6px 0;\"><strong>Requester Rating:</strong> <span style=\"color:#F59E0B; font-size:0.9rem;\">${stars(data.requester_rating_avg)}</span> (${data.requester_rating_count || 0})</div>
            ${line('Session Type', data.session_type)}
            ${line('Location', data.location || 'Any location')}
            ${line('Time', (data.available_from || '—') + ' - ' + (data.available_to || '—'))}
            ${line('Days', days)}
            ${line('Dates', (data.start_date || '—') + ' to ' + (data.end_date || '—'))}
            ${data.message ? `<div style=\"margin:10px 0; background:#f9fafb; padding:8px; border-radius:6px;\"><strong>Message:</strong> ${data.message}</div>` : ''}
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

        footer.appendChild(closeBtn);
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


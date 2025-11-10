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
                            <strong>Their Trade:</strong> {{ optional(optional($r->other_trade)->offeringSkill)->name ?? '—' }} → {{ optional(optional($r->other_trade)->lookingSkill)->name ?? '—' }}
                        </div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-bottom:6px;">
                            <strong>Your Trade:</strong> {{ optional(optional($r->viewer_trade)->offeringSkill)->name ?? optional($r->trade->offeringSkill)->name }} → {{ optional(optional($r->viewer_trade)->lookingSkill)->name ?? optional($r->trade->lookingSkill)->name }}
                        </div>
                        @if(isset($r->trade->compatibility_score))
                            <div style="display:flex; align-items:center; gap:8px; margin:4px 0 8px 0;">
                                <span style="background:{{ ($r->trade->is_compatible ?? false) ? '#10b981' : '#9ca3af' }}; color:#fff; padding:2px 8px; border-radius:999px; font-size:0.8rem; font-weight:700;">
                                    {{ $r->trade->compatibility_score }}% Match
                                </span>
                                <span style="font-size:0.8rem; color:#6b7280;">{{ ($r->trade->is_compatible ?? false) ? 'Compatible' : 'Not compatible' }}</span>
                            </div>
                        @endif
                        <div style="color:#6b7280; font-size:0.9rem; margin-bottom:8px;">
                            Status: <span style="font-weight:600; color:{{ $r->status === 'pending' ? '#f59e0b' : ($r->status === 'accepted' ? '#10b981' : '#ef4444') }}">{{ strtoupper($r->status) }}</span>
                        </div>
                        @if(isset($r->trade->insights) && is_array($r->trade->insights) && count($r->trade->insights) > 0)
                            <div style="margin-top:12px;">
                                <button type="button"
                                        onclick="toggleMatchDetails({{ $r->id }})"
                                        style="padding:6px 12px; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:6px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; font-weight:600; color:#111827; font-size:0.875rem; transition:background 0.2s;"
                                        onmouseover="this.style.background='#e5e7eb'"
                                        onmouseout="this.style.background='#f3f4f6'"
                                        id="match-toggle-{{ $r->id }}">
                                    <span>🔍 View Details</span>
                                    <span id="match-icon-{{ $r->id }}" style="font-size:0.75rem;">▼</span>
                                </button>
                                <div id="match-details-{{ $r->id }}" style="display:none; margin-top:12px; padding:12px; background:#f9fafb; border-radius:8px; border:1px solid #e5e7eb;">
                                    <div style="display:grid; gap:12px;">
                                        @foreach($r->trade->insights as $ins)
                                            @php
                                                $status = $ins['status'] ?? 'neutral';
                                                $borderColor = '#D1D5DB';
                                                $bgColor = '#ffffff';
                                                if ($status === 'good') {
                                                    $borderColor = '#86EFAC';
                                                    $bgColor = '#F0FDF4';
                                                } elseif ($status === 'bad') {
                                                    $borderColor = '#FCA5A5';
                                                    $bgColor = '#FEF2F2';
                                                } else {
                                                    $borderColor = '#E5E7EB';
                                                    $bgColor = '#FAFAFA';
                                                }
                                            @endphp
                                            <div style="background:{{ $bgColor }}; border:2px solid {{ $borderColor }}; border-radius:8px; padding:12px;">
                                                <div style="font-weight:600; color:#374151; margin-bottom:8px; font-size:0.9rem;">
                                                    {{ $ins['label'] ?? 'Item' }}
                                                    @if(isset($ins['match_detail']))
                                                        <span style="margin-left:8px; font-size:0.8rem; font-weight:500; color:{{ $status === 'good' ? '#166534' : ($status === 'bad' ? '#991B1B' : '#6b7280') }};">
                                                            ({{ $ins['match_detail'] }})
                                                        </span>
                                                    @endif
                                                </div>
                                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:0.85rem;">
                                                    <div>
                                                        <div style="font-weight:600; color:#6b7280; margin-bottom:4px; font-size:0.75rem; text-transform:uppercase;">Their Post</div>
                                                        <div style="color:#374151; padding:6px; background:#fff; border-radius:4px; border:1px solid #e5e7eb;">
                                                            {{ $ins['their_value'] ?? 'Not specified' }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div style="font-weight:600; color:#6b7280; margin-bottom:4px; font-size:0.75rem; text-transform:uppercase;">Your Post</div>
                                                        <div style="color:#374151; padding:6px; background:#fff; border-radius:4px; border:1px solid #e5e7eb;">
                                                            {{ $ins['your_value'] ?? 'Not specified' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
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
                                $user = auth()->user();
                                $isPremium = $user->isPremium();
                                $acceptanceFee = \App\Models\TradeFeeSetting::getFeeAmount('trade_acceptance');
                                $userBalance = $user->token_balance ?? 0;
                            @endphp
                        <div style="margin-left:16px;">
                            @if($isPremium)
                                <div style="margin-bottom:8px; padding:6px 10px; background:#fef3c7; color:#92400e; border-radius:4px; font-size:0.8rem; border:1px solid #f59e0b;">
                                    <div style="font-weight:600;">✨ Premium Member - No acceptance fee required!</div>
                                    <div style="font-size:0.7rem; color:#92400e; margin-top:2px;">
                                        <i>You can accept unlimited requests as a Premium member</i>
                                    </div>
                                </div>
                            @elseif($acceptanceFee > 0 && \App\Models\TradeFeeSetting::isFeeActive('trade_acceptance'))
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
                                            style="padding:6px 12px; background:#10b981; color:#fff; border:none; border-radius:4px; cursor:pointer; {{ !$isPremium && $acceptanceFee > 0 && \App\Models\TradeFeeSetting::isFeeActive('trade_acceptance') && $userBalance < $acceptanceFee ? 'opacity:50; cursor:not-allowed;' : '' }}"
                                            {{ !$isPremium && $acceptanceFee > 0 && \App\Models\TradeFeeSetting::isFeeActive('trade_acceptance') && $userBalance < $acceptanceFee ? 'disabled' : '' }}>
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
                        <strong>Their Trade:</strong> {{ optional(optional($r->other_trade)->offeringSkill)->name ?? optional($r->trade->offeringSkill)->name }} → {{ optional(optional($r->other_trade)->lookingSkill)->name ?? optional($r->trade->lookingSkill)->name }}
                    </div>
                    <div style="color:#6b7280; font-size:0.9rem; margin-bottom:6px;">
                        <strong>Your Trade:</strong> {{ optional(optional($r->viewer_trade)->offeringSkill)->name ?? '—' }} → {{ optional(optional($r->viewer_trade)->lookingSkill)->name ?? '—' }}
                    </div>
                    @if(isset($r->trade->compatibility_score))
                        <div style="display:flex; align-items:center; gap:8px; margin:4px 0 8px 0;">
                            <span style="background:{{ ($r->trade->is_compatible ?? false) ? '#10b981' : '#9ca3af' }}; color:#fff; padding:2px 8px; border-radius:999px; font-size:0.8rem; font-weight:700;">
                                {{ $r->trade->compatibility_score }}% Match
                            </span>
                            <span style="font-size:0.8rem; color:#6b7280;">{{ ($r->trade->is_compatible ?? false) ? 'Compatible' : 'Not compatible' }}</span>
                        </div>
                    @endif
                    <div style="color:#6b7280; font-size:0.9rem; margin-bottom:8px;">
                        Status: <span style="font-weight:600; color:{{ $r->status === 'pending' ? '#f59e0b' : ($r->status === 'accepted' ? '#10b981' : '#ef4444') }}">{{ strtoupper($r->status) }}</span>
                    </div>
                    @if(isset($r->trade->insights) && is_array($r->trade->insights) && count($r->trade->insights) > 0)
                        <div style="margin-top:12px;">
                            <button type="button"
                                    onclick="toggleMatchDetailsOutgoing({{ $r->id }})"
                                    style="padding:6px 12px; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:6px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; font-weight:600; color:#111827; font-size:0.875rem; transition:background 0.2s;"
                                    onmouseover="this.style.background='#e5e7eb'"
                                    onmouseout="this.style.background='#f3f4f6'"
                                    id="match-toggle-outgoing-{{ $r->id }}">
                                <span>🔍 View Details</span>
                                <span id="match-icon-outgoing-{{ $r->id }}" style="font-size:0.75rem;">▼</span>
                            </button>
                            <div id="match-details-outgoing-{{ $r->id }}" style="display:none; margin-top:12px; padding:12px; background:#f9fafb; border-radius:8px; border:1px solid #e5e7eb;">
                                <div style="display:grid; gap:12px;">
                                    @foreach($r->trade->insights as $ins)
                                        @php
                                            $status = $ins['status'] ?? 'neutral';
                                            $borderColor = '#D1D5DB';
                                            $bgColor = '#ffffff';
                                            if ($status === 'good') {
                                                $borderColor = '#86EFAC';
                                                $bgColor = '#F0FDF4';
                                            } elseif ($status === 'bad') {
                                                $borderColor = '#FCA5A5';
                                                $bgColor = '#FEF2F2';
                                            } else {
                                                $borderColor = '#E5E7EB';
                                                $bgColor = '#FAFAFA';
                                            }
                                        @endphp
                                        <div style="background:{{ $bgColor }}; border:2px solid {{ $borderColor }}; border-radius:8px; padding:12px;">
                                            <div style="font-weight:600; color:#374151; margin-bottom:8px; font-size:0.9rem;">
                                                {{ $ins['label'] ?? 'Item' }}
                                                @if(isset($ins['match_detail']))
                                                    <span style="margin-left:8px; font-size:0.8rem; font-weight:500; color:{{ $status === 'good' ? '#166534' : ($status === 'bad' ? '#991B1B' : '#6b7280') }};">
                                                        ({{ $ins['match_detail'] }})
                                                    </span>
                                                @endif
                                            </div>
                                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:0.85rem;">
                                                <div>
                                                    <div style="font-weight:600; color:#6b7280; margin-bottom:4px; font-size:0.75rem; text-transform:uppercase;">Their Post</div>
                                                    <div style="color:#374151; padding:6px; background:#fff; border-radius:4px; border:1px solid #e5e7eb;">
                                                        {{ $ins['their_value'] ?? 'Not specified' }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div style="font-weight:600; color:#6b7280; margin-bottom:4px; font-size:0.75rem; text-transform:uppercase;">Your Post</div>
                                                    <div style="color:#374151; padding:6px; background:#fff; border-radius:4px; border:1px solid #e5e7eb;">
                                                        {{ $ins['your_value'] ?? 'Not specified' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
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
    function toggleMatchDetails(id) {
        const details = document.getElementById('match-details-' + id);
        const icon = document.getElementById('match-icon-' + id);
        if (details.style.display === 'none') {
            details.style.display = 'block';
            icon.textContent = '▲';
        } else {
            details.style.display = 'none';
            icon.textContent = '▼';
        }
    }

    function toggleMatchDetailsOutgoing(id) {
        const details = document.getElementById('match-details-outgoing-' + id);
        const icon = document.getElementById('match-icon-outgoing-' + id);
        if (details.style.display === 'none') {
            details.style.display = 'block';
            icon.textContent = '▲';
        } else {
            details.style.display = 'none';
            icon.textContent = '▼';
        }
    }

</script>
@endpush


@extends('layouts.app')

@section('content')
<main style="padding:32px; max-width:1100px; margin:0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <h1 style="font-size:1.5rem; margin:0;">Matching Trades</h1>
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            @if(isset($userOpenTrades) && $userOpenTrades->count() > 1)
                <div style="display:flex; align-items:center; gap:8px;">
                    <label for="trade-filter" style="font-size:0.875rem; color:#6b7280; font-weight:500;">Filter by trade:</label>
                    <select id="trade-filter"
                            onchange="filterByTrade(this.value)"
                            style="padding:6px 12px; border:1px solid #d1d5db; border-radius:6px; background:#fff; font-size:0.875rem; cursor:pointer; min-width:200px;">
                        <option value="all">All My Trades</option>
                        @foreach($userOpenTrades as $userTrade)
                            <option value="{{ $userTrade->id }}">
                                {{ optional($userTrade->offeringSkill)->name ?? 'Unknown' }} → {{ optional($userTrade->lookingSkill)->name ?? 'Unknown' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <a href="{{ route('dashboard') }}" style="padding:8px 12px; background:#6b7280; color:#fff; text-decoration:none; border-radius:6px; font-size:0.875rem;">
                ← Back to Dashboard
            </a>
        </div>
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

    @if(isset($noSkill) && $noSkill)
        <div style="background:#fef3c7; color:#92400e; padding:20px; border-radius:8px; text-align:center; margin-bottom:16px;">
            <div style="font-size:1.2rem; margin-bottom:8px;">📝 Register a Skill First</div>
            <div style="margin-bottom:16px;">You need to register a skill in your profile before you can see available trades.</div>
            <a href="{{ route('profile.edit') }}" style="display:inline-block; padding:10px 20px; background:#2563eb; color:#fff; text-decoration:none; border-radius:6px; font-weight:600;">
                Update Profile
            </a>
        </div>
    @endif

    @if(isset($noTradePosted) && $noTradePosted)
        <div style="background:#e0e7ff; color:#3730a3; padding:20px; border-radius:8px; text-align:center; margin-bottom:16px;">
            <div style="font-size:1.2rem; margin-bottom:8px;">📣 Post a Trade First</div>
            <div style="margin-bottom:16px;">You need to post your own trade before you can view and request matches.</div>
            <a href="{{ route('trades.create') }}" style="display:inline-block; padding:10px 20px; background:#2563eb; color:#fff; text-decoration:none; border-radius:6px; font-weight:600;">
                Post a Trade
            </a>
        </div>
    @endif

    <div style="display:grid; gap:12px;" id="matches-container">
        @forelse($trades as $t)
            <div class="match-item"
                 data-matching-trade-id="{{ $t->matching_user_trade_id ?? '' }}"
                 style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px;">
                <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:12px;">
                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:1.1rem; margin-bottom:8px;">{{ $t->use_username ? ($t->user->username ?? 'User') : (($t->user->firstname ?? '') . ' ' . ($t->user->lastname ?? '')) }}</div>

                        {{-- Skills Exchange - More Readable Format --}}
                        <div style="background:#f3f4f6; border-radius:6px; padding:10px; margin-bottom:8px;">
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="font-weight:600; color:#6b7280; font-size:0.85rem; min-width:80px;">Offering:</span>
                                    <span style="font-weight:600; color:#111827; font-size:0.95rem;">{{ optional($t->offeringSkill)->name ?? 'Not specified' }}</span>
                                </div>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="font-weight:600; color:#6b7280; font-size:0.85rem; min-width:80px;">Looking for:</span>
                                    <span style="font-weight:600; color:#111827; font-size:0.95rem;">{{ optional($t->lookingSkill)->name ?? 'Not specified' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Ratings --}}
                        @php
                            $avg = (float)($t->partner_rating_avg ?? 0);
                            $count = (int)($t->partner_rating_count ?? 0);
                        @endphp
                        @if($count > 0)
                            @php
                                $full = (int) floor($avg);
                                $half = ($avg - $full) >= 0.5 ? 1 : 0;
                                $empty = 5 - $full - $half;
                            @endphp
                            <div style="color:#6b7280; font-size:0.85rem; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                                <span>
                                    @for($i=0;$i<$full;$i++)
                                        <span style="color:#F59E0B; font-size:0.9rem;">★</span>
                                    @endfor
                                    @if($half)
                                        <span style="color:#F59E0B; opacity:0.5; font-size:0.9rem;">★</span>
                                    @endif
                                    @for($i=0;$i<$empty;$i++)
                                        <span style="color:#D1D5DB; font-size:0.9rem;">☆</span>
                                    @endfor
                                </span>
                                <span>({{ $count }})</span>
                            </div>
                        @else
                            <div style="color:#9ca3af; font-size:0.85rem; margin-bottom:8px; font-style:italic;">
                                No ratings yet
                            </div>
                        @endif
                        @if(isset($t->insights) && is_array($t->insights) && count($t->insights) > 0)
                            <div style="margin-top:16px;">
                                <button type="button"
                                        onclick="toggleMatchDetails({{ $t->id }})"
                                        style="padding:6px 12px; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:6px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; font-weight:600; color:#111827; font-size:0.875rem; transition:background 0.2s;"
                                        onmouseover="this.style.background='#e5e7eb'"
                                        onmouseout="this.style.background='#f3f4f6'"
                                        id="match-toggle-{{ $t->id }}">
                                    <span>🔍 Why This Match</span>
                                    <span id="match-icon-{{ $t->id }}" style="font-size:0.75rem;">▼</span>
                                </button>
                                <div id="match-details-{{ $t->id }}" style="display:none; margin-top:12px; padding:12px; background:#f9fafb; border-radius:8px; border:1px solid #e5e7eb;">
                                    <div style="display:grid; gap:12px;">
                                        @foreach($t->insights as $ins)
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
                    </div>
                    <div style="text-align:right; margin-left:16px;">
                        <div style="background:{{ $t->is_compatible ? '#10b981' : '#9ca3af' }}; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:600;">
                            {{ $t->compatibility_score ?? 0 }}% Match
                        </div>
                        <div style="font-size:0.8rem; color:#6b7280; margin-top:4px;">
                            {{ $t->is_compatible ? 'Compatible' : 'Not compatible' }}
                        </div>
                    </div>
                </div>

                @if($t->is_compatible)
                    @php
                        $user = auth()->user();
                        $isPremium = $user->isPremium();
                        $requestFee = \App\Models\TradeFeeSetting::getFeeAmount('trade_request');
                        $userBalance = $user->token_balance ?? 0;
                        // Only check token balance if user is NOT premium
                        $hasInsufficientTokens = !$isPremium && $requestFee > 0 && \App\Models\TradeFeeSetting::isFeeActive('trade_request') && $userBalance < $requestFee;
                        $isDisabled = ($t->has_incoming_request_from_user ?? false) || ($t->has_outgoing_request_to_user ?? false) || $hasInsufficientTokens;
                    @endphp
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        @if(($t->has_incoming_request_from_user ?? false))
                            <div style="padding:6px 12px; background:#fef3c7; color:#92400e; border-radius:6px; font-size:0.8rem; border:1px solid #f59e0b;">
                                They already sent you a request. Review it in Requests.
                                <a href="{{ route('trades.requests') }}" style="margin-left:8px; color:#1e40af; text-decoration:underline;">Open requests</a>
                            </div>
                        @endif
                        @if(($t->has_outgoing_request_to_user ?? false))
                            <div style="padding:6px 12px; background:#e0e7ff; color:#3730a3; border-radius:6px; font-size:0.8rem; border:1px solid #6366f1;">
                                You already requested this user. Pending response.
                            </div>
                        @endif
                        @if($isPremium)
                            <div style="padding:6px 12px; background:#dcfce7; color:#166534; border-radius:6px; font-size:0.8rem; border:1px solid #86efac;">
                                <div style="font-weight:600;">⭐ Premium - Unlimited Requests</div>
                            </div>
                        @elseif($requestFee > 0 && \App\Models\TradeFeeSetting::isFeeActive('trade_request'))
                            <div style="padding:6px 12px; background:#dbeafe; color:#1e40af; border-radius:6px; font-size:0.8rem; border:1px solid #3b82f6;">
                                <div style="font-weight:600;">Fee: {{ $requestFee }} token{{ $requestFee > 1 ? 's' : '' }} (when accepted)</div>
                                <div style="font-size:0.75rem;">Balance: {{ $userBalance }} tokens</div>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('trades.request', $t->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit"
                                    style="padding:8px 16px; background:#2563eb; color:#fff; border:none; border-radius:6px; cursor:pointer; white-space:nowrap; {{ $isDisabled ? 'opacity:50; cursor:not-allowed;' : '' }}"
                                    {{ $isDisabled ? 'disabled' : '' }}>
                                {{ ($t->has_outgoing_request_to_user ?? false) ? 'Requested' : 'Request Trade' }}
                            </button>
                        </form>
                    </div>
                @else
                    {{-- Not rendered: list is already filtered to compatible only --}}
                @endif
            </div>
        @empty
            @if(!isset($noTradePosted) || !$noTradePosted)
                <div style="color:#6b7280; text-align:center; padding:32px;">
                    <div style="font-size:1.2rem; margin-bottom:8px;">No matches found</div>
                    <div>Try posting a trade or check back later for new opportunities.</div>
                </div>
            @endif
        @endforelse
    </div>
</main>

<script>
function toggleMatchDetails(tradeId) {
    const details = document.getElementById('match-details-' + tradeId);
    const icon = document.getElementById('match-icon-' + tradeId);

    if (details.style.display === 'none') {
        details.style.display = 'block';
        icon.textContent = '▲';
    } else {
        details.style.display = 'none';
        icon.textContent = '▼';
    }
}

function filterByTrade(tradeId) {
    const matchItems = document.querySelectorAll('.match-item');
    const filterValue = tradeId === 'all' ? '' : tradeId;

    let visibleCount = 0;

    matchItems.forEach(item => {
        const matchingTradeId = item.getAttribute('data-matching-trade-id');

        if (filterValue === '' || matchingTradeId === filterValue) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    // Show/hide empty state message
    const emptyState = document.getElementById('empty-filter-state');
    if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    } else if (visibleCount === 0) {
        // Create empty state if it doesn't exist
        const container = document.getElementById('matches-container');
        const emptyDiv = document.createElement('div');
        emptyDiv.id = 'empty-filter-state';
        emptyDiv.style.cssText = 'color:#6b7280; text-align:center; padding:32px; grid-column:1/-1;';
        emptyDiv.innerHTML = '<div style="font-size:1.1rem; margin-bottom:8px;">No matches found for this trade</div><div>Try selecting a different trade or check back later.</div>';
        container.appendChild(emptyDiv);
    }
}
</script>
@endsection



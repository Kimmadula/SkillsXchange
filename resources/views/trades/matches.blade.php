@extends('layouts.app')

@section('content')
<main style="padding:32px; max-width:1100px; margin:0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h1 style="font-size:1.5rem; margin:0;">Matching Trades</h1>
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

    <div style="display:grid; gap:12px;">
        @forelse($trades as $t)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px;">
                <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:12px;">
                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:1.1rem;">{{ $t->use_username ? ($t->user->username ?? 'User') : (($t->user->firstname ?? '') . ' ' . ($t->user->lastname ?? '')) }}</div>
                        <div style="color:#374151; margin-top:4px;">
                            <strong>Offering:</strong> {{ optional($t->offeringSkill)->name }}
                            <span style="margin:0 8px;">→</span>
                            <strong>Looking for:</strong> {{ optional($t->lookingSkill)->name }}
                        </div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">
                            📍 {{ $t->location ?: 'Any location' }} • {{ strtoupper($t->session_type) }} • {{ ucfirst($t->user->gender ?? 'Not specified') }}
                        </div>
                        @php
                            $avg = (float)($t->partner_rating_avg ?? 0);
                            $count = (int)($t->partner_rating_count ?? 0);
                            $full = (int) floor($avg);
                            $half = ($avg - $full) >= 0.5 ? 1 : 0;
                            $empty = 5 - $full - $half;
                        @endphp
                        <div style="color:#6b7280; font-size:0.85rem; margin-top:4px; display:flex; align-items:center; gap:6px;">
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
                        @if($t->start_date && $t->end_date)
                            <div style="color:#6b7280; font-size:0.9rem;">
                                📅 {{ \Carbon\Carbon::parse($t->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($t->end_date)->format('M d, Y') }}
                            </div>
                        @endif
                        @if($t->available_from && $t->available_to)
                            <div style="color:#6b7280; font-size:0.9rem;">
                                🕒 {{ $t->available_from }} - {{ $t->available_to }}
                            </div>
                        @endif
                        @if($t->preferred_days && count($t->preferred_days) > 0)
                            <div style="color:#6b7280; font-size:0.9rem;">
                                📅 {{ implode(', ', $t->preferred_days) }}
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
                        $requestFee = \App\Models\TradeFeeSetting::getFeeAmount('trade_request');
                        $userBalance = auth()->user()->token_balance ?? 0;
                    @endphp
                    <div style="display:flex; gap:8px; align-items:center;">
                        @if($requestFee > 0 && \App\Models\TradeFeeSetting::isFeeActive('trade_request'))
                            <div style="padding:6px 12px; background:#dbeafe; color:#1e40af; border-radius:6px; font-size:0.8rem; border:1px solid #3b82f6;">
                                <div style="font-weight:600;">Fee: {{ $requestFee }} token{{ $requestFee > 1 ? 's' : '' }} (when accepted)</div>
                                <div style="font-size:0.75rem;">Balance: {{ $userBalance }} tokens</div>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('trades.request', $t->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit"
                                    style="padding:8px 16px; background:#2563eb; color:#fff; border:none; border-radius:6px; cursor:pointer; white-space:nowrap; {{ $requestFee > 0 && $userBalance < $requestFee ? 'opacity:50; cursor:not-allowed;' : '' }}"
                                    {{ $requestFee > 0 && $userBalance < $requestFee ? 'disabled' : '' }}>
                                Request Trade
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
@endsection



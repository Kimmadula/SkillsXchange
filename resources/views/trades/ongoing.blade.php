@extends('layouts.app')

@section('content')
<main style="padding:32px; max-width:1100px; margin:0 auto;">
    <h1 style="font-size:1.5rem; margin-bottom:1rem;">Ongoing Trades</h1>
    
    @if(session('success'))
        <div style="background:#def7ec; color:#03543f; padding:10px 12px; border-radius:6px; margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif




    <div style="display:grid; gap:12px;">
        @forelse($ongoing as $t)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px;">
                <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:8px;">
                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:1.1rem; margin-bottom:4px;">
                            {{ $t->use_username ? $t->user->username : ($t->user->firstname.' '.$t->user->lastname) }}
                        </div>
                        <div style="color:#374151; margin-bottom:4px;">
                            <strong>Offering:</strong> {{ optional($t->offeringSkill)->name }} 
                            <span style="color:#6b7280;">→</span> 
                            <strong>Looking for:</strong> {{ optional($t->lookingSkill)->name }}
                        </div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-bottom:4px;">
                            📅 {{ $t->start_date }} to {{ $t->end_date ?? 'open' }}
                        </div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-bottom:4px;">
                            🕒 {{ $t->start_time }} - {{ $t->end_time }}
                        </div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-bottom:4px;">
                            📍 {{ $t->location ?: 'Any location' }}
                        </div>
                        @if($t->preferred_days)
                            <div style="color:#6b7280; font-size:0.9rem; margin-bottom:4px;">
                                📆 Days: {{ implode(', ', $t->preferred_days) }}
                            </div>
                        @endif
                    </div>
                    <div style="margin-left:16px; display:flex; flex-direction:column; gap:8px; align-items:end;">
                        @if($t->user_id == Auth::id())
                            <span style="background:#10b981; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.75rem; font-weight:600;">
                                Your Trade
                            </span>
                        @else
                            <span style="background:#2563eb; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.75rem; font-weight:600;">
                                Participating
                            </span>
                        @endif
                        
                        <!-- Chat Button -->
                        @if(auth()->user()->role !== 'admin')
                        <a href="{{ route('chat.show', $t->id) }}" 
                           style="background:#8b5cf6; color:#fff; padding:6px 12px; border:none; border-radius:6px; font-size:0.75rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:4px; transition:background 0.2s; text-decoration:none;" 
                           onmouseover="this.style.background='#7c3aed'" 
                           onmouseout="this.style.background='#8b5cf6'">
                            💬 Chat
                        </a>
                        @endif
                    </div>
                </div>
                
                @if($t->session_type)
                    <div style="background:#f3f4f6; padding:8px; border-radius:6px; margin-top:8px;">
                        <div style="font-size:0.875rem; color:#374151;">
                            <strong>Session Type:</strong> {{ strtoupper($t->session_type) }}
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div style="text-align:center; padding:32px; color:#6b7280;">
                <div style="font-size:3rem; margin-bottom:16px;">🤝</div>
                <div style="font-size:1.1rem; margin-bottom:8px;">No ongoing trades</div>
                <div style="font-size:0.9rem;">You'll see your active trades here once you accept or have your trade requests accepted.</div>
                <div style="margin-top:16px;">
                    <a href="/trades/matches" style="background:#2563eb; color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; display:inline-block;">
                        Find Trades to Request
                    </a>
                </div>
            </div>
        @endforelse
    </div>
</main>

<script>
function openChat(tradeId, partnerName) {
    // Redirect to chat page
    window.location.href = `/chat/${tradeId}`;
}
</script>
@endsection



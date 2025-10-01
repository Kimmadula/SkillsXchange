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
        @forelse($notifications as $n)
            <div style="background:#fff; border:1px solid {{ $n->read ? '#e5e7eb' : '#ef4444' }}; border-radius:8px; padding:16px; {{ $n->read ? '' : 'border-left:4px solid #ef4444;' }}">
                <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:8px;">
                    <div style="flex:1;">
                        @if($n->type === 'trade_request')
                            <div style="font-weight:600; color:#1f2937; margin-bottom:4px;">
                                🔔 New Trade Request
                            </div>
                            <div style="color:#6b7280; font-size:0.9rem; margin-bottom:8px;">
                                <strong>{{ $n->data['requester_name'] ?? 'Unknown User' }}</strong> wants to trade with you
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
                                <strong>{{ $n->data['trade_owner_name'] ?? 'Unknown User' }}</strong> 
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
                        @else
                            <div style="font-weight:600; color:#1f2937; margin-bottom:4px;">
                                {{ ucfirst(str_replace('_',' ', $n->type)) }}
                            </div>
                            <div style="color:#6b7280; font-size:0.9rem;">
                                @if(is_array($n->data))
                                    @foreach($n->data as $key => $value)
                                        <div><strong>{{ $key }}:</strong> {{ is_string($value) ? $value : json_encode($value) }}</div>
                                    @endforeach
                                @else
                                    {{ json_encode($n->data) }}
                                @endif
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



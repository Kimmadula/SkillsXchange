@extends('layouts.app')

@section('content')
<main style="padding:32px; max-width:1100px; margin:0 auto; display:grid; gap:24px;">
    <h1 style="font-size:1.5rem;">Trade Requests</h1>

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
                            Request from: {{ $r->requester->use_username ? $r->requester->username : ($r->requester->firstname.' '.$r->requester->lastname) }}
                        </div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-bottom:4px;">
                            Trade: {{ optional($r->trade->offeringSkill)->name }} → {{ optional($r->trade->lookingSkill)->name }}
                        </div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-bottom:8px;">
                            Status: <span style="font-weight:600; color:{{ $r->status === 'pending' ? '#f59e0b' : ($r->status === 'accepted' ? '#10b981' : '#ef4444') }}">{{ strtoupper($r->status) }}</span>
                        </div>
                        @if($r->message)
                            <div style="background:#f9fafb; padding:8px; border-radius:4px; font-size:0.9rem;">
                                <strong>Message:</strong> {{ $r->message }}
                            </div>
                        @endif
                    </div>
                    @if($r->status === 'pending')
                        <div style="display:flex; gap:8px; margin-left:16px;">
                            <form method="POST" action="{{ route('trades.respond', $r->id) }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="action" value="accept">
                                <button type="submit" style="padding:6px 12px; background:#10b981; color:#fff; border:none; border-radius:4px; cursor:pointer;">Accept</button>
                            </form>
                            <form method="POST" action="{{ route('trades.respond', $r->id) }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="action" value="decline">
                                <button type="submit" style="padding:6px 12px; background:#ef4444; color:#fff; border:none; border-radius:4px; cursor:pointer;">Decline</button>
                            </form>
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



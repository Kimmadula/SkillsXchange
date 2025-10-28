@extends('layouts.app')

@section('content')
<main style="padding:32px; max-width:900px; margin:0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h1 style="font-size:1.5rem; margin:0;">Announcements</h1>
        <a href="{{ route('dashboard') }}" style="padding:8px 12px; background:#6b7280; color:#fff; text-decoration:none; border-radius:6px; font-size:0.875rem;">
            ← Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div style="background:#def7ec; color:#03543f; padding:12px; border-radius:8px; margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display:grid; gap:12px;">
        @forelse($announcements as $announcement)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px;">
                <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:8px;">
                    <div style="flex:1;">
                        <div style="font-weight:600; color:#1f2937; margin-bottom:4px;">
                            📣 {{ $announcement->title }}
                        </div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-bottom:8px;">
                            {{ $announcement->content }}
                        </div>
                        @if($announcement->audience)
                            <div style="background:#f3f4f6; padding:6px 8px; border-radius:4px; margin-bottom:8px; display:inline-block;">
                                <span style="font-size:0.8rem; color:#374151;">
                                    <strong>Audience:</strong> {{ ucfirst($announcement->audience) }}
                                </span>
                            </div>
                        @endif
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:end; gap:8px;">
                        <span style="font-size:0.8rem; color:#6b7280;">
                            {{ \Carbon\Carbon::parse($announcement->created_at)->diffForHumans() }}
                        </span>
                        @if(!$announcement->isReadBy(Auth::user()))
                            <form method="POST" action="{{ route('announcements.mark-read', $announcement->id) }}" style="margin:0;">
                                @csrf
                                <button type="submit" style="padding:4px 8px; background:#2563eb; color:#fff; border:none; border-radius:4px; font-size:0.75rem; cursor:pointer;">
                                    Mark as Read
                                </button>
                            </form>
                        @else
                            <span style="padding:4px 8px; background:#10b981; color:#fff; border-radius:4px; font-size:0.75rem;">
                                ✓ Read
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align:center; padding:32px; color:#6b7280;">
                <div style="font-size:3rem; margin-bottom:16px;">📣</div>
                <div style="font-size:1.1rem; margin-bottom:8px;">No announcements yet</div>
                <div style="font-size:0.9rem;">You'll see important announcements here from the SkillsXchange team.</div>
            </div>
        @endforelse
    </div>

    @if($announcements->hasPages())
        <div style="margin-top:24px; display:flex; justify-content:center;">
            {{ $announcements->links() }}
        </div>
    @endif
</main>
@endsection

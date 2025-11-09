<header class="session-header">
    <div class="header-left">
        <a href="{{ route('trades.ongoing') }}" class="back-btn">← Back</a>
        <div class="session-info">
            <h1>💛 Active Trade Session</h1>
            <div class="session-meta">Trading: {{ $trade->offeringSkill->name ?? 'Unknown' }} for {{ $trade->lookingSkill->name ?? 'Unknown' }}</div>
        </div>
    </div>
    <div class="header-actions">
        <button class="icon-btn" id="video-call-btn" onclick="openVideoChat()" title="Video Call">📹</button>
        <button class="icon-btn" title="Tasks">☑️<span class="badge">{{ $myTasks->count() + $partnerTasks->count() }}</span></button>
        <button class="icon-btn" title="Settings">⚙️</button>
    </div>
</header>


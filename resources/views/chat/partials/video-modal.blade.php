<!-- Video Chat Modal -->
<div id="video-chat-modal" class="video-chat-modal">
    <div class="video-chat-container">
        <button class="close-video" id="close-video-btn" title="Close Video Chat" aria-label="Close video chat">×</button>

        <div class="video-status" id="video-status" role="status" aria-live="polite">Initializing video chat...</div>
        <div class="call-timer" id="call-timer" style="display: none;" aria-label="Call duration">00:00</div>

        <div class="video-grid" id="video-grid" role="region" aria-label="Video call participants">
            <div class="video-item local" id="local-video-item" role="region" aria-label="Your video">
                <video id="local-video" autoplay muted playsinline aria-label="Your video feed"></video>
                <div class="connection-status" id="local-status" aria-label="Your connection status">Local</div>
                <div class="video-overlay">
                    <div class="user-name" aria-label="Your name">{{ Auth::user()->firstname }} {{ Auth::user()->lastname }}</div>
                    <div class="video-controls-overlay">
                        <button class="control-btn" id="local-maximize-btn" onclick="maximizeVideo('local')"
                            title="Maximize your video" aria-label="Maximize your video">⛶</button>
                    </div>
                </div>
            </div>
            <div class="video-item remote" id="remote-video-item" role="region" aria-label="Partner's video">
                <video id="remote-video" autoplay playsinline aria-label="Partner's video feed"></video>
                <div class="connection-status" id="remote-status" aria-label="Partner's connection status">Waiting...</div>
                <div class="video-overlay">
                    <div class="user-name" id="remote-user-name" aria-label="Partner's name">{{ $partner->firstname ?? 'Partner' }} {{
                        $partner->lastname ?? '' }}</div>
                    <div class="video-controls-overlay">
                        <button class="control-btn" id="remote-maximize-btn" onclick="maximizeVideo('remote')"
                            title="Maximize partner's video" aria-label="Maximize partner's video">⛶</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="video-controls" role="toolbar" aria-label="Video call controls">
            <div id="presence-status" role="status" aria-live="polite"
                style="color: #6b7280; font-size: 0.875rem; margin: 0 8px; display: flex; align-items: center;">🔴
                Partner is offline</div>
            <button id="start-call-btn" class="video-btn primary" title="Start Call" aria-label="Start video call">📞</button>
            <button id="end-call-btn" class="video-btn danger" style="display: none;" title="End Call" aria-label="End video call">📞</button>
            <button id="toggle-audio-btn" class="video-btn success" style="display: none;"
                title="Mute/Unmute" aria-label="Toggle microphone">🎤</button>
            <button id="toggle-video-btn" class="video-btn success" style="display: none;"
                title="Turn Video On/Off" aria-label="Toggle camera">📹</button>
            <button id="mirror-video-btn" class="video-btn secondary" style="display: none;"
                title="Mirror Video" aria-label="Mirror video display">🪞</button>
            <button id="screen-share-btn" class="video-btn secondary" style="display: none;"
                title="Share Screen" aria-label="Share your screen">🖥️</button>
            <button id="maximize-btn" class="video-btn maximize" style="display: none;" title="Maximize" aria-label="Maximize video display">⛶</button>
            <button id="chat-toggle-btn" class="video-btn secondary" style="display: none;"
                title="Toggle Chat" aria-label="Toggle chat panel">💬</button>
        </div>
    </div>
</div>
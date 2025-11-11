<div id="video-chat-modal" class="video-chat-modal">
        <div class="video-chat-container">
            <div class="video-header">
                <div class="header-info">
                    <div class="call-timer" id="call-timer" style="display: none;">00:00</div>
                    <div class="video-status" id="video-status">Initializing video chat...</div>
                </div>
                <button class="close-video" id="close-video-btn" title="Close Video Chat">x</button>
            </div>

            <div class="video-grid" id="video-grid">
                <div class="video-item local" id="local-video-item">
                    <video id="local-video" autoplay muted playsinline></video>
                    <div class="connection-status connected" id="local-status">Local</div>
                    <div class="video-overlay">
                    <div class="user-name" aria-label="Your name">{{ Auth::user()->firstname }} {{ Auth::user()->lastname }}</div>
                        <div class="video-controls-overlay">
                            <button class="control-btn" onclick="maximizeVideo('local')" title="Maximize">⛶</button>
                        </div>
                    </div>
                </div>
                <div class="video-item remote" id="remote-video-item">
                    <video id="remote-video" autoplay playsinline></video>
                    <div class="connection-status connecting" id="remote-status">Waiting...</div>
                    <div class="video-overlay">
                        <div class="user-name" id="remote-user-name" aria-label="Partner's name">{{ $partner->firstname ?? 'Partner' }} {{
                            $partner->lastname ?? '' }}</div>
                            <div class="video-controls-overlay">
                            <button class="control-btn" onclick="maximizeVideo('remote')" title="Maximize">⛶</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="video-controls">
                <div class="controls-center">
                    <button id="toggle-audio-btn" class="video-btn success" style="display: none;" data-tooltip="Mute/Unmute">🎤</button>
                    <button id="start-call-btn" class="video-btn primary" data-tooltip="Start Call">📞</button>
                    <button id="end-call-btn" class="video-btn danger" style="display: none;" data-tooltip="End Call">📞</button>
                    <button id="toggle-video-btn" class="video-btn success" style="display: none;" data-tooltip="Turn Video On/Off">📹</button>
                </div>

                <div class="controls-right">
                    <button id="screen-share-btn" class="video-btn secondary" style="display: none;" data-tooltip="Share Screen">🖥️</button>
                    <button id="chat-toggle-btn" class="video-btn secondary" style="display: none;" data-tooltip="Toggle Chat">💬</button>
                    <button id="maximize-btn" class="video-btn maximize" style="display: none;" data-tooltip="Maximize">⛶</button>
                </div>
            </div>
        </div>
    </div>
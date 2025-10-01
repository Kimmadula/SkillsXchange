/**
 * Enhanced Video Call UI Component
 * Modern, responsive video call interface with real-time status updates
 */

class EnhancedVideoCallUI {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.options = {
            showLocalVideo: true,
            showRemoteVideo: true,
            enableControls: true,
            enableStatus: true,
            enableTimer: true,
            enableParticipantInfo: true,
            ...options
        };
        
        this.state = {
            isConnected: false,
            isMuted: false,
            isVideoOff: false,
            isScreenSharing: false,
            callDuration: 0,
            connectionQuality: 'excellent',
            participants: []
        };
        
        this.timer = null;
        this.statusUpdateInterval = null;
        
        this.init();
    }
    
    init() {
        this.createUI();
        this.bindEvents();
        this.startStatusUpdates();
    }
    
    createUI() {
        this.container.innerHTML = `
            <div class="video-call-container">
                <!-- Call Header -->
                <div class="call-header">
                    <div class="call-info">
                        <div class="call-title">
                            <i class="call-icon">📞</i>
                            <span class="call-text">Video Call</span>
                        </div>
                        <div class="call-status" id="call-status">
                            <div class="status-indicator connecting"></div>
                            <span class="status-text">Connecting...</span>
                        </div>
                    </div>
                    <div class="call-timer" id="call-timer">00:00</div>
                    <div class="call-actions">
                        <button class="action-btn minimize-btn" id="minimizeBtn" title="Minimize">
                            <i>➖</i>
                        </button>
                        <button class="action-btn close-btn" id="closeBtn" title="Close">
                            <i>✕</i>
                        </button>
                    </div>
                </div>
                
                <!-- Video Grid -->
                <div class="video-grid" id="video-grid">
                    <!-- Remote Video (Main) -->
                    <div class="video-wrapper remote-video-wrapper" id="remote-video-wrapper">
                        <video id="remote-video" autoplay playsinline></video>
                        <div class="video-overlay">
                            <div class="participant-info" id="participant-info">
                                <div class="participant-name">Connecting...</div>
                                <div class="connection-quality" id="connection-quality">
                                    <div class="quality-indicator excellent"></div>
                                    <span>Excellent</span>
                                </div>
                            </div>
                            <div class="video-controls-overlay">
                                <button class="control-btn" id="remote-mute-btn" title="Remote Audio">
                                    <i>🔇</i>
                                </button>
                                <button class="control-btn" id="remote-video-btn" title="Remote Video">
                                    <i>📹</i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Local Video (Picture-in-Picture) -->
                    <div class="video-wrapper local-video-wrapper" id="local-video-wrapper">
                        <video id="local-video" autoplay muted playsinline></video>
                        <div class="video-overlay">
                            <div class="local-info">
                                <span class="local-label">You</span>
                                <div class="local-status">
                                    <div class="status-dot" id="local-audio-status"></div>
                                    <div class="status-dot" id="local-video-status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Call Controls -->
                <div class="call-controls" id="call-controls">
                    <div class="control-group">
                        <button class="control-btn mute-btn" id="muteBtn" title="Mute/Unmute">
                            <i class="mute-icon">🎤</i>
                            <span class="control-label">Mute</span>
                        </button>
                        <button class="control-btn video-btn" id="videoBtn" title="Turn Video On/Off">
                            <i class="video-icon">📹</i>
                            <span class="control-label">Video</span>
                        </button>
                        <button class="control-btn screen-btn" id="screenBtn" title="Share Screen">
                            <i class="screen-icon">🖥️</i>
                            <span class="control-label">Screen</span>
                        </button>
                    </div>
                    
                    <div class="control-group center">
                        <button class="control-btn end-call-btn" id="endCallBtn" title="End Call">
                            <i class="end-icon">📞</i>
                            <span class="control-label">End Call</span>
                        </button>
                    </div>
                    
                    <div class="control-group">
                        <button class="control-btn settings-btn" id="settingsBtn" title="Settings">
                            <i class="settings-icon">⚙️</i>
                            <span class="control-label">Settings</span>
                        </button>
                        <button class="control-btn participants-btn" id="participantsBtn" title="Participants">
                            <i class="participants-icon">👥</i>
                            <span class="control-label">Participants</span>
                        </button>
                    </div>
                </div>
                
                <!-- Status Bar -->
                <div class="status-bar" id="status-bar">
                    <div class="status-item">
                        <i class="status-icon">📡</i>
                        <span id="connection-status">Connecting...</span>
                    </div>
                    <div class="status-item">
                        <i class="status-icon">🔊</i>
                        <span id="audio-status">Audio: On</span>
                    </div>
                    <div class="status-item">
                        <i class="status-icon">📹</i>
                        <span id="video-status">Video: On</span>
                    </div>
                </div>
                
                <!-- Incoming Call Modal -->
                <div class="incoming-call-modal" id="incoming-call-modal">
                    <div class="modal-content">
                        <div class="caller-info">
                            <div class="caller-avatar">
                                <i>👤</i>
                            </div>
                            <div class="caller-details">
                                <h3 id="caller-name">Incoming Call</h3>
                                <p id="caller-status">is calling you...</p>
                            </div>
                        </div>
                        <div class="call-actions">
                            <button class="action-btn answer-btn" id="answerBtn">
                                <i>📞</i>
                                <span>Answer</span>
                            </button>
                            <button class="action-btn decline-btn" id="declineBtn">
                                <i>📴</i>
                                <span>Decline</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Modal -->
                <div class="settings-modal" id="settings-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Call Settings</h3>
                            <button class="close-btn" id="closeSettingsBtn">✕</button>
                        </div>
                        <div class="modal-body">
                            <div class="setting-group">
                                <label>Audio Input</label>
                                <select id="audioInput">
                                    <option value="default">Default Microphone</option>
                                </select>
                            </div>
                            <div class="setting-group">
                                <label>Video Input</label>
                                <select id="videoInput">
                                    <option value="default">Default Camera</option>
                                </select>
                            </div>
                            <div class="setting-group">
                                <label>Audio Output</label>
                                <select id="audioOutput">
                                    <option value="default">Default Speaker</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.addStyles();
    }
    
    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .video-call-container {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                flex-direction: column;
                z-index: 1000;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .call-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px 30px;
                background: rgba(0, 0, 0, 0.3);
                backdrop-filter: blur(10px);
                color: white;
            }
            
            .call-info {
                display: flex;
                align-items: center;
                gap: 20px;
            }
            
            .call-title {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 18px;
                font-weight: 600;
            }
            
            .call-icon {
                font-size: 20px;
            }
            
            .call-status {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .status-indicator {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                animation: pulse 2s infinite;
            }
            
            .status-indicator.connecting {
                background: #ffa500;
            }
            
            .status-indicator.connected {
                background: #4caf50;
            }
            
            .status-indicator.failed {
                background: #f44336;
            }
            
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.5; }
                100% { opacity: 1; }
            }
            
            .call-timer {
                font-size: 16px;
                font-weight: 500;
                font-family: 'Courier New', monospace;
            }
            
            .call-actions {
                display: flex;
                gap: 10px;
            }
            
            .action-btn {
                width: 40px;
                height: 40px;
                border: none;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.2);
                color: white;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }
            
            .action-btn:hover {
                background: rgba(255, 255, 255, 0.3);
                transform: scale(1.1);
            }
            
            .video-grid {
                flex: 1;
                display: grid;
                grid-template-columns: 1fr;
                position: relative;
                overflow: hidden;
            }
            
            .video-wrapper {
                position: relative;
                background: #000;
                overflow: hidden;
            }
            
            .remote-video-wrapper {
                width: 100%;
                height: 100%;
            }
            
            .local-video-wrapper {
                position: absolute;
                top: 20px;
                right: 20px;
                width: 200px;
                height: 150px;
                border-radius: 12px;
                border: 3px solid rgba(255, 255, 255, 0.3);
                z-index: 10;
                transition: all 0.3s ease;
            }
            
            .local-video-wrapper:hover {
                transform: scale(1.05);
                border-color: rgba(255, 255, 255, 0.6);
            }
            
            .video-wrapper video {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .video-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                padding: 20px;
            }
            
            .participant-info {
                color: white;
            }
            
            .participant-name {
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 5px;
            }
            
            .connection-quality {
                display: flex;
                align-items: center;
                gap: 5px;
                font-size: 14px;
            }
            
            .quality-indicator {
                width: 8px;
                height: 8px;
                border-radius: 50%;
            }
            
            .quality-indicator.excellent {
                background: #4caf50;
            }
            
            .quality-indicator.good {
                background: #ff9800;
            }
            
            .quality-indicator.poor {
                background: #f44336;
            }
            
            .video-controls-overlay {
                display: flex;
                gap: 10px;
                align-self: flex-end;
            }
            
            .control-btn {
                width: 40px;
                height: 40px;
                border: none;
                border-radius: 50%;
                background: rgba(0, 0, 0, 0.5);
                color: white;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }
            
            .control-btn:hover {
                background: rgba(0, 0, 0, 0.7);
                transform: scale(1.1);
            }
            
            .local-info {
                display: flex;
                align-items: center;
                gap: 10px;
                color: white;
            }
            
            .local-label {
                font-size: 14px;
                font-weight: 500;
            }
            
            .local-status {
                display: flex;
                gap: 5px;
            }
            
            .status-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #4caf50;
            }
            
            .status-dot.muted {
                background: #f44336;
            }
            
            .status-dot.video-off {
                background: #ff9800;
            }
            
            .call-controls {
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px 30px;
                background: rgba(0, 0, 0, 0.3);
                backdrop-filter: blur(10px);
                gap: 20px;
            }
            
            .control-group {
                display: flex;
                gap: 15px;
            }
            
            .control-group.center {
                flex: 1;
                justify-content: center;
            }
            
            .control-btn {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                padding: 15px 20px;
                border: none;
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.1);
                color: white;
                cursor: pointer;
                transition: all 0.3s ease;
                min-width: 80px;
            }
            
            .control-btn:hover {
                background: rgba(255, 255, 255, 0.2);
                transform: translateY(-2px);
            }
            
            .control-btn.active {
                background: rgba(255, 255, 255, 0.3);
            }
            
            .control-btn.muted {
                background: rgba(244, 67, 54, 0.3);
            }
            
            .control-btn.video-off {
                background: rgba(255, 152, 0, 0.3);
            }
            
            .end-call-btn {
                background: rgba(244, 67, 54, 0.8);
                width: 60px;
                height: 60px;
                border-radius: 50%;
            }
            
            .end-call-btn:hover {
                background: rgba(244, 67, 54, 1);
                transform: scale(1.1);
            }
            
            .control-icon {
                font-size: 20px;
            }
            
            .control-label {
                font-size: 12px;
                font-weight: 500;
            }
            
            .status-bar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 30px;
                background: rgba(0, 0, 0, 0.2);
                color: white;
                font-size: 14px;
            }
            
            .status-item {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .status-icon {
                font-size: 16px;
            }
            
            .incoming-call-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.8);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 2000;
            }
            
            .incoming-call-modal.show {
                display: flex;
            }
            
            .modal-content {
                background: white;
                border-radius: 20px;
                padding: 40px;
                text-align: center;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                animation: slideIn 0.3s ease;
            }
            
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-50px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .caller-info {
                margin-bottom: 30px;
            }
            
            .caller-avatar {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                font-size: 40px;
            }
            
            .caller-details h3 {
                margin: 0 0 10px;
                color: #333;
                font-size: 24px;
            }
            
            .caller-details p {
                margin: 0;
                color: #666;
                font-size: 16px;
            }
            
            .call-actions {
                display: flex;
                gap: 20px;
                justify-content: center;
            }
            
            .action-btn {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                padding: 20px 30px;
                border: none;
                border-radius: 15px;
                cursor: pointer;
                transition: all 0.3s ease;
                min-width: 120px;
            }
            
            .answer-btn {
                background: #4caf50;
                color: white;
            }
            
            .answer-btn:hover {
                background: #45a049;
                transform: scale(1.05);
            }
            
            .decline-btn {
                background: #f44336;
                color: white;
            }
            
            .decline-btn:hover {
                background: #da190b;
                transform: scale(1.05);
            }
            
            .action-btn i {
                font-size: 24px;
            }
            
            .action-btn span {
                font-size: 14px;
                font-weight: 500;
            }
            
            .settings-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.8);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 2000;
            }
            
            .settings-modal.show {
                display: flex;
            }
            
            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }
            
            .modal-header h3 {
                margin: 0;
                color: #333;
            }
            
            .setting-group {
                margin-bottom: 20px;
            }
            
            .setting-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: #333;
            }
            
            .setting-group select {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 14px;
            }
            
            /* Responsive Design */
            @media (max-width: 768px) {
                .call-header {
                    padding: 15px 20px;
                }
                
                .call-controls {
                    padding: 15px 20px;
                    flex-wrap: wrap;
                }
                
                .control-group {
                    gap: 10px;
                }
                
                .control-btn {
                    min-width: 60px;
                    padding: 10px 15px;
                }
                
                .local-video-wrapper {
                    width: 150px;
                    height: 112px;
                    top: 15px;
                    right: 15px;
                }
                
                .status-bar {
                    padding: 8px 20px;
                    font-size: 12px;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    bindEvents() {
        // Control buttons
        document.getElementById('muteBtn').addEventListener('click', () => this.toggleMute());
        document.getElementById('videoBtn').addEventListener('click', () => this.toggleVideo());
        document.getElementById('screenBtn').addEventListener('click', () => this.toggleScreenShare());
        document.getElementById('endCallBtn').addEventListener('click', () => this.endCall());
        document.getElementById('settingsBtn').addEventListener('click', () => this.showSettings());
        document.getElementById('participantsBtn').addEventListener('click', () => this.showParticipants());
        
        // Incoming call buttons
        document.getElementById('answerBtn').addEventListener('click', () => this.answerCall());
        document.getElementById('declineBtn').addEventListener('click', () => this.declineCall());
        
        // Modal close buttons
        document.getElementById('closeSettingsBtn').addEventListener('click', () => this.hideSettings());
        document.getElementById('closeBtn').addEventListener('click', () => this.closeCall());
        document.getElementById('minimizeBtn').addEventListener('click', () => this.minimizeCall());
    }
    
    startStatusUpdates() {
        this.statusUpdateInterval = setInterval(() => {
            this.updateConnectionStatus();
            this.updateCallDuration();
        }, 1000);
    }
    
    updateConnectionStatus() {
        const statusElement = document.getElementById('connection-status');
        const statusIndicator = document.querySelector('.status-indicator');
        const statusText = document.querySelector('.status-text');
        
        if (this.state.isConnected) {
            statusElement.textContent = 'Connected';
            statusIndicator.className = 'status-indicator connected';
            statusText.textContent = 'Connected';
        } else {
            statusElement.textContent = 'Connecting...';
            statusIndicator.className = 'status-indicator connecting';
            statusText.textContent = 'Connecting...';
        }
    }
    
    updateCallDuration() {
        if (this.state.isConnected && this.timer) {
            this.state.callDuration++;
            const minutes = Math.floor(this.state.callDuration / 60);
            const seconds = this.state.callDuration % 60;
            const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('call-timer').textContent = timeString;
        }
    }
    
    // Public methods
    showIncomingCall(callerName) {
        document.getElementById('caller-name').textContent = callerName;
        document.getElementById('incoming-call-modal').classList.add('show');
    }
    
    hideIncomingCall() {
        document.getElementById('incoming-call-modal').classList.remove('show');
    }
    
    setLocalStream(stream) {
        const localVideo = document.getElementById('local-video');
        localVideo.srcObject = stream;
        this.updateLocalStatus();
    }
    
    setRemoteStream(stream) {
        const remoteVideo = document.getElementById('remote-video');
        remoteVideo.srcObject = stream;
        this.updateParticipantInfo('Connected');
    }
    
    updateParticipantInfo(name) {
        document.querySelector('.participant-name').textContent = name;
    }
    
    updateConnectionQuality(quality) {
        const qualityElement = document.getElementById('connection-quality');
        const indicator = qualityElement.querySelector('.quality-indicator');
        const text = qualityElement.querySelector('span');
        
        indicator.className = `quality-indicator ${quality}`;
        text.textContent = quality.charAt(0).toUpperCase() + quality.slice(1);
    }
    
    updateLocalStatus() {
        const audioStatus = document.getElementById('local-audio-status');
        const videoStatus = document.getElementById('local-video-status');
        
        audioStatus.className = `status-dot ${this.state.isMuted ? 'muted' : ''}`;
        videoStatus.className = `status-dot ${this.state.isVideoOff ? 'video-off' : ''}`;
        
        // Update control buttons
        const muteBtn = document.getElementById('muteBtn');
        const videoBtn = document.getElementById('videoBtn');
        
        muteBtn.classList.toggle('muted', this.state.isMuted);
        videoBtn.classList.toggle('video-off', this.state.isVideoOff);
        
        // Update status bar
        document.getElementById('audio-status').textContent = `Audio: ${this.state.isMuted ? 'Off' : 'On'}`;
        document.getElementById('video-status').textContent = `Video: ${this.state.isVideoOff ? 'Off' : 'On'}`;
    }
    
    toggleMute() {
        this.state.isMuted = !this.state.isMuted;
        this.updateLocalStatus();
        this.onMuteToggle?.(this.state.isMuted);
    }
    
    toggleVideo() {
        this.state.isVideoOff = !this.state.isVideoOff;
        this.updateLocalStatus();
        this.onVideoToggle?.(this.state.isVideoOff);
    }
    
    toggleScreenShare() {
        this.state.isScreenSharing = !this.state.isScreenSharing;
        const screenBtn = document.getElementById('screenBtn');
        screenBtn.classList.toggle('active', this.state.isScreenSharing);
        this.onScreenShareToggle?.(this.state.isScreenSharing);
    }
    
    showSettings() {
        document.getElementById('settings-modal').classList.add('show');
    }
    
    hideSettings() {
        document.getElementById('settings-modal').classList.remove('show');
    }
    
    showParticipants() {
        // Implementation for participants modal
        console.log('Show participants');
    }
    
    answerCall() {
        this.hideIncomingCall();
        this.onAnswerCall?.();
    }
    
    declineCall() {
        this.hideIncomingCall();
        this.onDeclineCall?.();
    }
    
    endCall() {
        this.onEndCall?.();
    }
    
    closeCall() {
        this.onCloseCall?.();
    }
    
    minimizeCall() {
        this.onMinimizeCall?.();
    }
    
    setConnected(connected) {
        this.state.isConnected = connected;
        this.updateConnectionStatus();
        
        if (connected) {
            this.startCallTimer();
        } else {
            this.stopCallTimer();
        }
    }
    
    startCallTimer() {
        this.state.callDuration = 0;
        this.timer = setInterval(() => {
            this.state.callDuration++;
            this.updateCallDuration();
        }, 1000);
    }
    
    stopCallTimer() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
    
    destroy() {
        this.stopCallTimer();
        if (this.statusUpdateInterval) {
            clearInterval(this.statusUpdateInterval);
        }
        this.container.innerHTML = '';
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EnhancedVideoCallUI;
} else {
    window.EnhancedVideoCallUI = EnhancedVideoCallUI;
}

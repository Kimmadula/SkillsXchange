@extends('layouts.chat')

@section('content')
<script>
    // Initialize global variables for the chat session
    window.currentUserId = parseInt('{{ auth()->id() }}');
    window.tradeId = parseInt('{{ $trade->id }}');
    window.authUserId = parseInt('{{ Auth::id() }}');
    window.partnerId = parseInt('{{ $partner->id }}');
    window.partnerName = '{{ addslashes(($partner->firstname ?? "Unknown") . " " . ($partner->lastname ?? "User")) }}';
    window.initialMessageCount = parseInt('{{ $messages->count() }}');
    
    // Firebase Video Call Integration
    let firebaseVideoCall = null;
    let videoCallListenersInitialized = false;
    let videoCallState = {
        isActive: false,
        isConnected: false,
        isInitiator: false,
        callId: null,
        partnerId: null,
        localStream: null,
        remoteStream: null,
        peerConnection: null,
        startTime: null,
        timer: null,
        isProcessingCall: false,
        lastCallTime: 0,
        callCooldown: 2000 // 2 seconds between calls
    };
    
    // Listen for user presence events
    function initializePresenceListeners() {
        if (typeof window.Echo !== 'undefined') {
            try {
                window.Echo.channel('trade-{{ $trade->id }}')
                    .listen('user-joined', function(data) {
                        console.log('User joined:', data);
                        if (typeof handleUserJoined === 'function') {
                            handleUserJoined(data);
                        }
                    });

                window.Echo.channel('trade-{{ $trade->id }}')
                    .listen('user-left', function(data) {
                        console.log('User left:', data);
                        if (typeof handleUserLeft === 'function') {
                            handleUserLeft(data);
                        }
                    });
            } catch (error) {
                console.error('Error setting up presence listeners:', error);
            }
        } else {
            console.error('Laravel Echo not available. Make sure Pusher is properly configured.');
        }
    }
    
    // Define updateCallStatus function before Firebase initialization
    function updateCallStatus(status) {
        const statusElement = document.getElementById('video-status');
        if (statusElement) {
            statusElement.textContent = status;
            
            // Add visual indicators based on status
            statusElement.className = 'call-status';
            
            switch (status.toLowerCase()) {
                case 'calling...':
                    statusElement.className += ' calling';
                    statusElement.innerHTML = '📞 ' + status;
                    break;
                case 'answering...':
                    statusElement.className += ' answering';
                    statusElement.innerHTML = '📱 ' + status;
                    break;
                case 'waiting for answer...':
                    statusElement.className += ' waiting';
                    statusElement.innerHTML = '⏳ ' + status;
                    break;
                case 'connected':
                case 'video connected':
                case 'connection established':
                    statusElement.className += ' connected';
                    statusElement.innerHTML = '✅ ' + status;
                    break;
                case 'connection failed':
                    statusElement.className += ' failed';
                    statusElement.innerHTML = '❌ ' + status;
                    break;
                case 'connection lost':
                    statusElement.className += ' lost';
                    statusElement.innerHTML = '⚠️ ' + status;
                    break;
                case 'call ended':
                    statusElement.className += ' ended';
                    statusElement.innerHTML = '📴 ' + status;
                    break;
                default:
                    statusElement.innerHTML = '🔄 ' + status;
            }
        }
        console.log('Call status:', status);
    }

    function initializeVideoCallListeners() {
        if (videoCallListenersInitialized) {
            console.log('⚠️ Video call listeners already initialized, skipping...');
            return;
        }
        
        console.log('🔧 Setting up Firebase video call listeners...');
        videoCallListenersInitialized = true;
        
        try {
            // Initialize Firebase video call integration
            if (typeof FirebaseVideoIntegration !== 'undefined') {
                firebaseVideoCall = new FirebaseVideoIntegration({
                    userId: {{ auth()->id() }},
                    tradeId: {{ $trade->id }},
                    partnerId: {{ $partner->id }},
                    onCallReceived: async (call) => {
                        console.log('📞 Incoming call received via Firebase:', call);
                        
                        // Show notification for incoming call
                        if (window.notificationService) {
                            console.log('📞 Showing notification for incoming call from:', call.fromUserId);
                            window.notificationService.showIncomingCallNotification(
                                'Partner',
                                call.fromUserId,
                                {{ $trade->id }}
                            );
                        }
                        
                        await handleVideoCallOffer(call);
                    },
                    onCallAnswered: (remoteStream) => {
                        console.log('📞 Call answered via Firebase');
                        handleVideoCallAnswer({ answer: null, remoteStream: remoteStream });
                    },
                    onCallEnded: () => {
                        console.log('📞 Call ended via Firebase');
                        handleVideoCallEnd({});
                    },
                    onConnectionStateChange: (state) => {
                        console.log('📞 Connection state changed:', state);
                        updateCallStatus(state);
                    },
                    onError: (error) => {
                        console.error('❌ Firebase video call error:', error);
                        updateCallStatus('Error: ' + error.message);
                    },
                    onLog: (message, type) => {
                        console.log(`[FirebaseVideoCall] ${message}`);
                    },
                    onStatusUpdate: (status) => {
                        updateCallStatus(status);
                    }
                });
                
                // Initialize Firebase
                firebaseVideoCall.initialize().then(success => {
                    if (success) {
                        console.log('✅ Firebase video call integration initialized successfully');
                    } else {
                        console.error('❌ Failed to initialize Firebase video call integration');
                        // Fallback to HTTP polling
                        startVideoCallPolling();
                    }
                });
            } else {
                console.error('❌ FirebaseVideoIntegration not available, falling back to HTTP polling');
                startVideoCallPolling();
            }
        } catch (error) {
            console.error('❌ Error setting up Firebase video call listeners:', error);
            console.warn('⚠️ Switching to HTTP polling fallback');
            startVideoCallPolling();
        }
            
        console.log('✅ Video call listeners initialized successfully');
        videoCallListenersInitialized = true;
        
        // Also initialize presence listeners
        initializePresenceListeners();
    }

    // Initialize Firebase video call listeners when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Wait a bit for Firebase to load
        setTimeout(() => {
            initializeVideoCallListeners();
        }, 1000);
    });
</script>
<style>
    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }

    @keyframes flash {
        0% {
            background-color: rgba(59, 130, 246, 0.3);
        }

        50% {
            background-color: rgba(16, 185, 129, 0.5);
        }

        100% {
            background-color: rgba(59, 130, 246, 0.3);
        }
    }

    .flash-effect {
        animation: flash 0.5s ease-in-out;
    }

    /* Ensure all message bubbles have proper text wrapping */
    #chat-messages>div>div {
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
    }

    #chat-messages>div>div>div:first-child {
        word-break: break-word !important;
        line-height: 1.4 !important;
    }

    /* Video Chat Styles */
    .video-chat-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.95);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }

    .video-chat-container {
        background: #1a1a1a;
        border-radius: 12px;
        padding: 0;
        max-width: 95vw;
        width: 95vw;
        max-height: 95vh;
        height: 95vh;
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .video-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        flex: 1;
        padding: 10px;
        min-height: 0;
    }

    .video-grid.maximized {
        grid-template-columns: 1fr;
    }

    .video-item {
        position: relative;
        background: #000;
        border-radius: 8px;
        overflow: hidden;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    .video-item.maximized {
        grid-column: 1 / -1;
        grid-row: 1 / -1;
    }

    .video-item video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        flex: 1;
    }

    /* Default state for local video - no mirroring by default */
    #local-video {
        transform: scaleX(1);
    }

    .video-item.remote {
        border: 2px solid #3b82f6;
    }

    .video-item.local {
        border: 2px solid #10b981;
    }

    .video-item.local.minimized {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 200px;
        height: 150px;
        z-index: 10;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .video-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 12px;
        padding: 20px;
        background: rgba(0, 0, 0, 0.8);
        border-top: 1px solid #333;
    }

    .video-btn {
        padding: 12px;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 48px;
        min-height: 48px;
        font-size: 18px;
    }

    .video-btn.primary {
        background: #3b82f6;
        color: white;
    }

    .video-btn.primary:hover {
        background: #2563eb;
        transform: scale(1.05);
    }

    .video-btn.danger {
        background: #ef4444;
        color: white;
    }

    .video-btn.danger:hover {
        background: #dc2626;
        transform: scale(1.05);
    }

    .video-btn.success {
        background: #10b981;
        color: white;
    }

    .video-btn.success:hover {
        background: #059669;
        transform: scale(1.05);
    }

    .video-btn.secondary {
        background: #6b7280;
        color: white;
    }

    .video-btn.secondary:hover {
        background: #4b5563;
        transform: scale(1.05);
    }

    .video-btn.muted {
        background: #ef4444 !important;
    }

    .video-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none !important;
    }

    .video-btn.maximize {
        background: #8b5cf6;
        color: white;
    }

    .video-btn.maximize:hover {
        background: #7c3aed;
        transform: scale(1.05);
    }

    .video-status {
        text-align: center;
        padding: 15px;
        font-weight: 600;
        color: #e5e7eb;
        background: rgba(0, 0, 0, 0.5);
        border-bottom: 1px solid #333;
    }

    .call-timer {
        text-align: center;
        font-size: 1.1rem;
        font-weight: 600;
        color: #3b82f6;
        padding: 10px;
        background: rgba(0, 0, 0, 0.3);
        border-bottom: 1px solid #333;
    }

    .close-video {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        font-size: 1.2rem;
        z-index: 1000;
        transition: all 0.2s;
    }

    .close-video:hover {
        background: rgba(239, 68, 68, 0.8);
        transform: scale(1.1);
    }

    .connection-status {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        backdrop-filter: blur(10px);
        z-index: 10;
    }

    .connection-status.connected {
        background: rgba(16, 185, 129, 0.9);
        color: white;
    }

    .connection-status.connecting {
        background: rgba(245, 158, 11, 0.9);
        color: white;
    }

    .connection-status.disconnected {
        background: rgba(239, 68, 68, 0.9);
        color: white;
    }

    .video-overlay {
        position: absolute;
        bottom: 10px;
        left: 10px;
        right: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 10;
    }

    .user-name {
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
        backdrop-filter: blur(10px);
    }

    .video-controls-overlay {
        display: flex;
        gap: 8px;
    }

    .control-btn {
        background: rgba(0, 0, 0, 0.7);
        color: white;
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: all 0.2s;
        backdrop-filter: blur(10px);
    }

    .control-btn:hover {
        background: rgba(0, 0, 0, 0.9);
        transform: scale(1.1);
    }

    .control-btn.active {
        background: rgba(239, 68, 68, 0.9);
    }

    /* Emoji button hover effect */
    #emoji-button:hover {
        background-color: #f3f4f6 !important;
    }

    #emoji-button:active {
        background-color: #e5e7eb !important;
    }

    /* Message styles */
    .message-container {
        margin-bottom: 16px;
        display: flex;
    }

    .message-container[data-sender="{{ Auth::id() }}"] {
        justify-content: flex-end;
    }

    .message-container:not([data-sender="{{ Auth::id() }}"]) {
        justify-content: flex-start;
    }

    .message-bubble {
        max-width: 70%;
        padding: 12px;
        border-radius: 12px;
        position: relative;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .message-bubble[data-sender="{{ Auth::id() }}"] {
        background: #3b82f6;
        color: white;
    }

    .message-bubble:not([data-sender="{{ Auth::id() }}"]) {
        background: #e5e7eb;
        color: #374151;
    }

    .message-content {
        margin-bottom: 4px;
        word-break: break-word;
        line-height: 1.4;
    }

    .message-time {
        font-size: 0.75rem;
        opacity: 0.8;
    }

    /* Dynamic Status Styles */
    .call-status {
        font-size: 18px;
        font-weight: 600;
        text-align: center;
        padding: 10px 20px;
        border-radius: 25px;
        transition: all 0.3s ease;
        animation: pulse 2s infinite;
    }

    .call-status.calling {
        background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
        color: white;
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
    }

    .call-status.answering {
        background: linear-gradient(45deg, #4ecdc4, #6dd5ed);
        color: white;
        box-shadow: 0 4px 15px rgba(78, 205, 196, 0.4);
    }

    .call-status.waiting {
        background: linear-gradient(45deg, #ffd93d, #ffed4e);
        color: #333;
        box-shadow: 0 4px 15px rgba(255, 217, 61, 0.4);
    }

    .call-status.connected {
        background: linear-gradient(45deg, #51cf66, #69db7c);
        color: white;
        box-shadow: 0 4px 15px rgba(81, 207, 102, 0.4);
        animation: none;
    }

    .call-status.failed {
        background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
        color: white;
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
    }

    .call-status.lost {
        background: linear-gradient(45deg, #ffa726, #ffb74d);
        color: white;
        box-shadow: 0 4px 15px rgba(255, 167, 38, 0.4);
    }

    .call-status.ended {
        background: linear-gradient(45deg, #78909c, #90a4ae);
        color: white;
        box-shadow: 0 4px 15px rgba(120, 144, 156, 0.4);
        animation: none;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }

        100% {
            transform: scale(1);
        }
    }

    .call-timer {
        font-size: 24px;
        font-weight: 700;
        color: #51cf66;
        text-align: center;
        margin: 10px 0;
        font-family: 'Courier New', monospace;
    }
</style>

<!-- Video Chat Modal -->
<div id="video-chat-modal" class="video-chat-modal">
    <div class="video-chat-container">
        <button class="close-video" id="close-video-btn">×</button>

        <div class="video-status" id="video-status">Initializing video chat...</div>
        <div class="call-timer" id="call-timer" style="display: none;">00:00</div>

        <div class="video-grid" id="video-grid">
            <div class="video-item local" id="local-video-item">
                <video id="local-video" autoplay muted playsinline></video>
                <div class="connection-status" id="local-status">Local</div>
                <div class="video-overlay">
                    <div class="user-name">{{ Auth::user()->firstname }} {{ Auth::user()->lastname }}</div>
                    <div class="video-controls-overlay">
                        <button class="control-btn" id="local-maximize-btn" onclick="maximizeVideo('local')"
                            title="Maximize">⛶</button>
                    </div>
                </div>
            </div>
            <div class="video-item remote" id="remote-video-item">
                <video id="remote-video" autoplay playsinline></video>
                <div class="connection-status" id="remote-status">Waiting...</div>
                <div class="video-overlay">
                    <div class="user-name" id="remote-user-name">{{ $partner->firstname ?? 'Partner' }} {{
                        $partner->lastname ?? '' }}</div>
                    <div class="video-controls-overlay">
                        <button class="control-btn" id="remote-maximize-btn" onclick="maximizeVideo('remote')"
                            title="Maximize">⛶</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="video-controls">
            <button id="auto-call-toggle" class="video-btn secondary" title="Toggle Auto-call"
                style="background: #10b981;">🔗 Auto-call ON</button>
            <div id="presence-status"
                style="color: #6b7280; font-size: 0.875rem; margin: 0 8px; display: flex; align-items: center;">🔴
                Partner is offline</div>
            <button id="start-call-btn" class="video-btn primary" title="Start Call">📞</button>
            <button id="end-call-btn" class="video-btn danger" style="display: none;" title="End Call">📞</button>
            <button id="toggle-audio-btn" class="video-btn success" style="display: none;"
                title="Mute/Unmute">🎤</button>
            <button id="toggle-video-btn" class="video-btn success" style="display: none;"
                title="Turn Video On/Off">📹</button>
            <button id="mirror-video-btn" class="video-btn secondary" style="display: none;"
                title="Mirror Video">🪞</button>
            <button id="screen-share-btn" class="video-btn secondary" style="display: none;"
                title="Share Screen">🖥️</button>
            <button id="maximize-btn" class="video-btn maximize" style="display: none;" title="Maximize">⛶</button>
            <button id="chat-toggle-btn" class="video-btn secondary" style="display: none;"
                title="Toggle Chat">💬</button>
        </div>
    </div>
</div>

<div style="height: 100vh; display: flex; flex-direction: column;">
    <!-- Header -->
    <div
        style="background: #1e40af; color: white; padding: 16px; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-size: 1.5rem; font-weight: bold;">SkillsXchange</div>
        <a href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            style="color: #ef4444; text-decoration: none;">Logout</a>
    </div>

    <!-- Active Trade Session Banner -->
    <div style="background: #1e40af; color: white; padding: 12px 16px; text-align: center;">
        <div style="font-size: 1.2rem; font-weight: bold; margin-bottom: 4px;">
            💛 Active Trade Session
        </div>
        <div style="font-size: 0.9rem;">
            Trading: {{ $trade->offeringSkill->name ?? 'Unknown' }} for {{ $trade->lookingSkill->name ?? 'Unknown' }}
        </div>
    </div>

    <!-- Main Content -->
    <div style="flex: 1; display: flex; overflow: hidden;">
        <!-- Session Chat (Left Panel) -->
        <div style="flex: 1; display: flex; flex-direction: column; border-right: 1px solid #e5e7eb;">
            <!-- Chat Header -->
            <div
                style="background: #1e40af; color: white; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span>💬</span>
                    <span>Session Chat</span>
                    <span id="new-message-indicator"
                        style="display: none; background: #ef4444; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; animation: pulse 2s infinite;">NEW</span>
                    <div id="connection-status" style="margin-left: 12px; font-size: 0.7rem;">
                        <span id="status-indicator"
                            style="display:inline-block; width:6px; height:6px; border-radius:50%; background:#10b981; margin-right:4px;"></span>
                        <span id="status-text">Connecting...</span>
                    </div>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button id="video-call-btn" onclick="window.openVideoChat()"
                        style="background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem;">📷</button>

                    <!-- Ensure openVideoChat is defined immediately -->
                    <script>
                        // Initialize camera function
                        function initializeCamera() {
                            console.log('📹 Initializing camera...');
                            
                            // Check if getUserMedia is supported
                            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                                console.error('❌ Camera not supported');
                                alert('Camera is not supported in this browser. Please use a modern browser.');
                                return;
                            }
                            
                            // Request camera access
                            navigator.mediaDevices.getUserMedia({ 
                                video: { 
                                    width: { ideal: 1280 },
                                    height: { ideal: 720 },
                                    facingMode: 'user'
                                }, 
                                audio: true 
                            })
                            .then(function(stream) {
                                console.log('✅ Camera access granted');
                                
                                // Find local video element
                                const localVideo = document.getElementById('local-video');
                                if (localVideo) {
                                    localVideo.srcObject = stream;
                                    localVideo.style.display = 'block';
                                    localVideo.play();
                                    console.log('✅ Local video stream started');
                                } else {
                                    console.error('❌ Local video element not found');
                                }
                                
                                // Store stream globally for later use
                                window.localStream = stream;
                            })
                            .catch(function(error) {
                                console.error('❌ Camera access denied:', error);
                                alert('Camera access is required for video calls. Please allow camera access and try again.');
                            });
                        }
                        
                        // Define a basic startVideoCall function immediately
                        window.startVideoCall = async function() {
                            console.log('🚀 Starting video call (immediate version)...');
                            
                            // Check if we have a local stream
                            if (window.localStream) {
                                console.log('✅ Local stream available, proceeding with call');
                                
                                // Update UI to show calling state
                                const modal = document.getElementById('video-chat-modal');
                                if (modal) {
                                    const statusElement = document.getElementById('video-status');
                                    if (statusElement) {
                                        statusElement.textContent = 'Starting call...';
                                    }
                                    
                                    // Show call controls
                                    const startBtn = document.getElementById('start-call-btn');
                                    const endBtn = document.getElementById('end-call-btn');
                                    if (startBtn) startBtn.style.display = 'none';
                                    if (endBtn) endBtn.style.display = 'inline-block';
                                }
                                
                                // Start WebRTC call using Firebase signaling
                                if (window.webrtcSignaling) {
                                    // For now, use a dummy partner ID - you'll need to get the actual partner ID
                                    const partnerId = window.partnerId || 'partner_' + Math.random().toString(36).substr(2, 9);
                                    const success = await window.webrtcSignaling.startCall(partnerId);
                                    
                                    if (success) {
                                        console.log('✅ WebRTC call started successfully');
                                        
                                        // Listen for answer
                                        window.webrtcSignaling.listenForAnswer();
                                        
                                        // Update status
                                        const statusElement = document.getElementById('video-status');
                                        if (statusElement) {
                                            statusElement.textContent = 'Call in progress...';
                                        }
                                    } else {
                                        console.error('❌ Failed to start WebRTC call');
                                        alert('Failed to start video call. Please try again.');
                                    }
                                } else {
                                    console.error('❌ WebRTC signaling not available');
                                    alert('Video call service not available. Please refresh the page.');
                                }
                            } else {
                                console.error('❌ No local stream available');
                                alert('Please allow camera access first.');
                            }
                        };
                        
                        // Define closeVideoChat function immediately
                        window.closeVideoChat = function() {
                            console.log('🛑 Closing video chat...');
                            const modal = document.getElementById('video-chat-modal');
                            if (modal) {
                                modal.style.display = 'none';
                            }
                            if (typeof window.endVideoCall === 'function') {
                                window.endVideoCall();
                            }
                        };
                        
                        // Define endVideoCall function immediately
                        window.endVideoCall = function() {
                            console.log('🛑 Ending video call...');
                            
                            // End WebRTC call
                            if (window.webrtcSignaling) {
                                window.webrtcSignaling.endCall();
                            }
                            
                            // Stop local stream
                            if (window.localStream) {
                                window.localStream.getTracks().forEach(track => track.stop());
                                window.localStream = null;
                            }
                            
                            // Clear remote video
                            const remoteVideo = document.getElementById('remote-video');
                            if (remoteVideo) {
                                remoteVideo.srcObject = null;
                                remoteVideo.style.display = 'none';
                            }
                            
                            // Reset UI
                            const startBtn = document.getElementById('start-call-btn');
                            const endBtn = document.getElementById('end-call-btn');
                            if (startBtn) startBtn.style.display = 'inline-block';
                            if (endBtn) endBtn.style.display = 'none';
                            
                            const statusElement = document.getElementById('video-status');
                            if (statusElement) {
                                statusElement.textContent = 'Call ended';
                            }
                            
                            console.log('✅ Video call ended');
                        };
                        
                        // Firebase WebRTC Signaling Class
                        class FirebaseWebRTCSignaling {
                            constructor() {
                                this.database = null;
                                this.callId = null;
                                this.partnerId = null;
                                this.isInitiator = false;
                                this.peerConnection = null;
                                this.localStream = null;
                                this.remoteStream = null;
                                
                                // ICE candidate buffering
                                this.iceCandidateBuffer = [];
                                this.remoteDescriptionSet = false;
                                
                                this.initFirebase();
                            }
                            
                            initFirebase() {
                                try {
                                    // Check for Firebase v9 compat first
                                    if (window.firebaseDatabase) {
                                        this.database = window.firebaseDatabase;
                                        console.log('✅ Firebase database initialized from global reference (v9 compat)');
                                    } else if (typeof firebase !== 'undefined' && firebase.database) {
                                        this.database = firebase.database();
                                        console.log('✅ Firebase database initialized for WebRTC signaling (v9 compat)');
                                    } else {
                                        console.error('❌ Firebase not available for WebRTC signaling');
                                        console.log('🔍 Available globals:', Object.keys(window).filter(key => key.includes('firebase')));
                                    }
                                } catch (error) {
                                    console.error('❌ Error initializing Firebase:', error);
                                }
                            }
                            
                            // Process buffered ICE candidates
                            async processBufferedIceCandidates() {
                                console.log(`🔄 Processing ${this.iceCandidateBuffer.length} buffered ICE candidates...`);
                                
                                while (this.iceCandidateBuffer.length > 0) {
                                    const candidateData = this.iceCandidateBuffer.shift();
                                    
                                    try {
                                        await this.peerConnection.addIceCandidate(candidateData);
                                        console.log('✅ Buffered ICE candidate processed successfully');
                                    } catch (error) {
                                        console.error('❌ Error processing buffered ICE candidate:', error);
                                    }
                                }
                                
                                console.log('✅ All buffered ICE candidates processed');
                            }
                            
                            async startCall(partnerId) {
                                console.log('🚀 Starting WebRTC call with partner:', partnerId);
                                
                                this.partnerId = partnerId;
                                this.callId = 'call_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                                this.isInitiator = true;
                                
                                // Reset buffering state
                                this.iceCandidateBuffer = [];
                                this.remoteDescriptionSet = false;
                                
                                try {
                                    // Get local stream
                                    this.localStream = await navigator.mediaDevices.getUserMedia({
                                        video: true,
                                        audio: true
                                    });
                                    
                                    // Set up peer connection
                                    await this.setupPeerConnection();
                                    
                                    // Create offer
                                    const offer = await this.peerConnection.createOffer();
                                    await this.peerConnection.setLocalDescription(offer);
                                    
                                    // Send offer to Firebase
                                    await this.sendOffer(offer);
                                    
                                    console.log('✅ WebRTC call initiated successfully');
                                    return true;
                                    
                                } catch (error) {
                                    console.error('❌ Error starting WebRTC call:', error);
                                    return false;
                                }
                            }
                            
                            async answerCall(callId) {
                                console.log('📞 Answering WebRTC call:', callId);
                                
                                this.callId = callId;
                                this.isInitiator = false;
                                
                                // Reset buffering state
                                this.iceCandidateBuffer = [];
                                this.remoteDescriptionSet = false;
                                
                                try {
                                    // Get local stream
                                    this.localStream = await navigator.mediaDevices.getUserMedia({
                                        video: true,
                                        audio: true
                                    });
                                    
                                    // Set up peer connection
                                    await this.setupPeerConnection();
                                    
                                    // Listen for offer
                                    this.listenForOffer();
                                    
                                    console.log('✅ WebRTC call answering setup complete');
                                    return true;
                                    
                                } catch (error) {
                                    console.error('❌ Error answering WebRTC call:', error);
                                    return false;
                                }
                            }
                            
                            async setupPeerConnection() {
                                console.log('🔧 Setting up peer connection...');
                                
                                const configuration = {
                                    iceServers: [
                                        { urls: 'stun:stun.l.google.com:19302' },
                                        { urls: 'stun:stun1.l.google.com:19302' }
                                    ]
                                };
                                
                                this.peerConnection = new RTCPeerConnection(configuration);
                                
                                // Add local stream
                                if (this.localStream) {
                                    this.localStream.getTracks().forEach(track => {
                                        this.peerConnection.addTrack(track, this.localStream);
                                    });
                                }
                                
                                // Handle remote stream
                                this.peerConnection.ontrack = (event) => {
                                    console.log('📹 Remote stream received');
                                    this.remoteStream = event.streams[0];
                                    this.displayRemoteStream();
                                };
                                
                                // Handle ICE candidates
                                this.peerConnection.onicecandidate = (event) => {
                                    if (event.candidate) {
                                        console.log('🧊 Sending ICE candidate');
                                        this.sendIceCandidate(event.candidate);
                                    }
                                };
                                
                                // Listen for ICE candidates
                                this.listenForIceCandidates();
                                
                                console.log('✅ Peer connection setup complete');
                            }
                            
                            async sendOffer(offer) {
                                console.log('📤 Sending offer to Firebase...');
                                
                                if (!this.database) {
                                    console.error('❌ Firebase database not available');
                                    return;
                                }
                                
                                try {
                                    // Use Firebase v9 compat syntax
                                    await this.database.ref(`calls/${this.callId}/offer`).set({
                                        type: 'offer',
                                        sdp: offer.sdp,
                                        timestamp: Date.now(),
                                        from: window.currentUserId || 'unknown'
                                    });
                                    
                                    console.log('✅ Offer sent to Firebase');
                                } catch (error) {
                                    console.error('❌ Error sending offer:', error);
                                }
                            }
                            
                            async sendAnswer(answer) {
                                console.log('📤 Sending answer to Firebase...');
                                
                                if (!this.database) {
                                    console.error('❌ Firebase database not available');
                                    return;
                                }
                                
                                try {
                                    // Use Firebase v9 compat syntax
                                    await this.database.ref(`calls/${this.callId}/answer`).set({
                                        type: 'answer',
                                        sdp: answer.sdp,
                                        timestamp: Date.now(),
                                        from: window.currentUserId || 'unknown'
                                    });
                                    
                                    console.log('✅ Answer sent to Firebase');
                                } catch (error) {
                                    console.error('❌ Error sending answer:', error);
                                }
                            }
                            
                            async sendIceCandidate(candidate) {
                                console.log('🧊 Sending ICE candidate to Firebase...');
                                
                                if (!this.database) {
                                    console.error('❌ Firebase database not available');
                                    return;
                                }
                                
                                try {
                                    // Use Firebase v9 compat syntax
                                    await this.database.ref(`calls/${this.callId}/candidates`).push({
                                        candidate: candidate.candidate,
                                        sdpMLineIndex: candidate.sdpMLineIndex,
                                        sdpMid: candidate.sdpMid,
                                        timestamp: Date.now(),
                                        from: window.currentUserId || 'unknown'
                                    });
                                    
                                    console.log('✅ ICE candidate sent to Firebase');
                                } catch (error) {
                                    console.error('❌ Error sending ICE candidate:', error);
                                }
                            }
                            
                            listenForOffer() {
                                console.log('👂 Listening for offer...');
                                
                                if (!this.database) {
                                    console.error('❌ Firebase database not available');
                                    return;
                                }
                                
                                // Use Firebase v9 compat syntax
                                this.database.ref(`calls/${this.callId}/offer`).on('value', async (snapshot) => {
                                    const offerData = snapshot.val();
                                    if (offerData && offerData.sdp) {
                                        console.log('📥 Received offer from Firebase');
                                        
                                        try {
                                            await this.peerConnection.setRemoteDescription(offerData);
                                            
                                            // Mark remote description as set
                                            this.remoteDescriptionSet = true;
                                            console.log('✅ Remote description set, processing buffered ICE candidates...');
                                            
                                            // Process any buffered ICE candidates
                                            await this.processBufferedIceCandidates();
                                            
                                            // Create answer
                                            const answer = await this.peerConnection.createAnswer();
                                            await this.peerConnection.setLocalDescription(answer);
                                            
                                            // Send answer
                                            await this.sendAnswer(answer);
                                            
                                            console.log('✅ Answer created and sent');
                                        } catch (error) {
                                            console.error('❌ Error handling offer:', error);
                                        }
                                    }
                                });
                            }
                            
                            listenForAnswer() {
                                console.log('👂 Listening for answer...');
                                
                                if (!this.database) {
                                    console.error('❌ Firebase database not available');
                                    return;
                                }
                                
                                this.database.ref(`calls/${this.callId}/answer`).on('value', async (snapshot) => {
                                    const answerData = snapshot.val();
                                    if (answerData && answerData.sdp && answerData.from !== window.currentUserId) {
                                        console.log('📥 Received answer from Firebase from user:', answerData.from);
                                        
                                        // Only process if we're the initiator and haven't set remote description yet
                                        if (this.isInitiator && !this.remoteDescriptionSet) {
                                            try {
                                                await this.peerConnection.setRemoteDescription(answerData);
                                                
                                                // Mark remote description as set
                                                this.remoteDescriptionSet = true;
                                                console.log('✅ Remote description set, processing buffered ICE candidates...');
                                                
                                                // Process any buffered ICE candidates
                                                await this.processBufferedIceCandidates();
                                                
                                                console.log('✅ Answer processed successfully');
                                            } catch (error) {
                                                console.error('❌ Error handling answer:', error);
                                            }
                                        } else if (!this.isInitiator) {
                                            console.log('📞 Ignoring answer - we are the answerer, not the caller');
                                        } else if (this.remoteDescriptionSet) {
                                            console.log('📞 Ignoring answer - remote description already set');
                                        }
                                    } else if (answerData && answerData.from === window.currentUserId) {
                                        console.log('📞 Ignoring own answer');
                                    }
                                });
                            }
                            
                            listenForIceCandidates() {
                                console.log('👂 Listening for ICE candidates...');
                                
                                if (!this.database) {
                                    console.error('❌ Firebase database not available');
                                    return;
                                }
                                
                                this.database.ref(`calls/${this.callId}/candidates`).on('child_added', async (snapshot) => {
                                    const candidateData = snapshot.val();
                                    if (candidateData && candidateData.candidate && candidateData.from !== window.currentUserId) {
                                        console.log('🧊 Received ICE candidate from Firebase from user:', candidateData.from);
                                        
                                        // Check if remote description is set
                                        if (this.remoteDescriptionSet) {
                                            // Remote description is set, add candidate immediately
                                            try {
                                                await this.peerConnection.addIceCandidate(candidateData);
                                                console.log('✅ ICE candidate processed immediately');
                                            } catch (error) {
                                                console.error('❌ Error handling ICE candidate:', error);
                                            }
                                        } else {
                                            // Remote description not set yet, buffer the candidate
                                            this.iceCandidateBuffer.push(candidateData);
                                            console.log(`🔄 ICE candidate buffered (${this.iceCandidateBuffer.length} total buffered)`);
                                        }
                                    } else if (candidateData && candidateData.from === window.currentUserId) {
                                        console.log('🧊 Ignoring own ICE candidate');
                                    }
                                });
                            }
                            
                            displayRemoteStream() {
                                const remoteVideo = document.getElementById('remote-video');
                                if (remoteVideo && this.remoteStream) {
                                    remoteVideo.srcObject = this.remoteStream;
                                    remoteVideo.style.display = 'block';
                                    console.log('✅ Remote video stream displayed');
                                }
                            }
                            
                            endCall() {
                                console.log('🛑 Ending WebRTC call...');
                                
                                if (this.peerConnection) {
                                    this.peerConnection.close();
                                    this.peerConnection = null;
                                }
                                
                                if (this.localStream) {
                                    this.localStream.getTracks().forEach(track => track.stop());
                                    this.localStream = null;
                                }
                                
                                this.remoteStream = null;
                                
                                // Clean up Firebase listeners
                                if (this.database && this.callId) {
                                    this.database.ref(`calls/${this.callId}`).off();
                                }
                                
                                // Reset call state
                                this.callId = null;
                                this.partnerId = null;
                                this.isInitiator = false;
                                this.iceCandidateBuffer = [];
                                this.remoteDescriptionSet = false;
                                
                                console.log('✅ WebRTC call ended');
                            }
                        }
                        
                        // Initialize WebRTC signaling
                        window.webrtcSignaling = new FirebaseWebRTCSignaling();
                        
                        // Auto-setup callee to listen for offers
                        function setupCalleeForIncomingCalls() {
                        console.log('🔧 Setting up callee for incoming calls...');
                        
                        // Listen for any new offers in the calls collection
                        if (window.webrtcSignaling && window.webrtcSignaling.database) {
                            // Listen for new calls being created
                            window.webrtcSignaling.database.ref('calls').on('child_added', (snapshot) => {
                                const callId = snapshot.key;
                                const callData = snapshot.val();
                                
                                // Check if this call has an offer but no answer yet AND it's not from us
                                if (callData.offer && !callData.answer && callData.offer.from !== window.currentUserId) {
                                    console.log('📞 Incoming call detected from user:', callData.offer.from, 'callId:', callId);
                                    
                                    // Enhanced call state management
                                    const now = Date.now();
                                    // Only block if we're processing a call or too soon after last call
                                    if (videoCallState.isProcessingCall || 
                                        (now - videoCallState.lastCallTime) < videoCallState.callCooldown) {
                                        console.log('📞 Call already in progress or too soon, ignoring incoming call');
                                        return;
                                    }
                                    
                                    // Set processing flag to prevent duplicate calls
                                    videoCallState.isProcessingCall = true;
                                    videoCallState.lastCallTime = now;
                                    
                                    console.log('📞 Auto-answering incoming call from user:', callData.offer.from);
                                    
                                    // Auto-answer the call
                                    window.webrtcSignaling.answerCall(callId).then(success => {
                                        if (success) {
                                            console.log('✅ Successfully answered incoming call');
                                            
                                            // Show video chat modal for callee
                                            const modal = document.getElementById('video-chat-modal');
                                            if (modal) {
                                                modal.style.display = 'block';
                                                console.log('📹 Video chat modal opened for callee');
                                            }
                                        } else {
                                            console.error('❌ Failed to answer incoming call');
                                        }
                                        
                                        // Reset processing flag
                                        videoCallState.isProcessingCall = false;
                                    }).catch(error => {
                                        console.error('❌ Error answering call:', error);
                                        videoCallState.isProcessingCall = false;
                                    });
                                } else if (callData.offer && callData.offer.from === window.currentUserId) {
                                    console.log('📞 Ignoring own call offer:', callId);
                                }
                            });
                            
                            console.log('✅ Callee setup complete - listening for incoming calls');
                        } else {
                            console.error('❌ WebRTC signaling not available for callee setup');
                        }
                    }
                        
                        // Set up callee after a short delay to ensure Firebase is ready
                        setTimeout(setupCalleeForIncomingCalls, 2000);
                        
                        // Fallback definition to ensure function is always available
                        if (typeof window.openVideoChat !== 'function') {
                            console.log('🔧 Creating fallback openVideoChat function...');
                            window.openVideoChat = function() {
                                console.log('🎥 Opening video chat (fallback)...');
                                const modal = document.getElementById('video-chat-modal');
                                if (modal) {
                                    modal.style.display = 'flex';
                                    
                                    // Initialize camera immediately
                                    initializeCamera();
                                    
                                    // Try to call startVideoCall if it exists
                                    if (typeof window.startVideoCall === 'function') {
                                        window.startVideoCall();
                                    } else {
                                        console.log('startVideoCall not available yet, will be called when ready');
                                        // Set up a retry mechanism
                                        const retryInterval = setInterval(() => {
                                            if (typeof window.startVideoCall === 'function') {
                                                clearInterval(retryInterval);
                                                window.startVideoCall();
                                            }
                                        }, 100);
                                        
                                        // Stop retrying after 5 seconds
                                        setTimeout(() => clearInterval(retryInterval), 5000);
                                    }
                                } else {
                                    console.error('Video chat modal not found');
                                    alert('Video chat is not available. Please refresh the page.');
                                }
                            };
                            console.log('✅ Fallback openVideoChat function created:', typeof window.openVideoChat);
                        }
                        
                        // Function to verify all video functions are available
                        function verifyVideoFunctions() {
                            console.log('🔍 Verifying video functions...');
                            const functions = ['openVideoChat', 'closeVideoChat', 'startVideoCall', 'endVideoCall'];
                            let allAvailable = true;
                            
                            functions.forEach(funcName => {
                                if (typeof window[funcName] === 'function') {
                                    console.log(`✅ ${funcName} is available`);
                                } else {
                                    console.error(`❌ ${funcName} is NOT available`);
                                    allAvailable = false;
                                }
                            });
                            
                            if (allAvailable) {
                                console.log('🎉 All video functions are available!');
                            } else {
                                console.warn('⚠️ Some video functions are missing');
                            }
                            
                            return allAvailable;
                        }
                        
                        // Add event listener as backup to onclick
                        document.addEventListener('DOMContentLoaded', function() {
                            const videoCallBtn = document.getElementById('video-call-btn');
                            if (videoCallBtn) {
                                videoCallBtn.addEventListener('click', function() {
                                    console.log('🎥 Video call button clicked via event listener');
                                    if (typeof window.openVideoChat === 'function') {
                                        window.openVideoChat();
                                    } else {
                                        console.error('openVideoChat function still not available');
                                        alert('Video chat function not available. Please refresh the page.');
                                    }
                                });
                                console.log('✅ Video call button event listener added');
                            }
                            
                            // Add event listener for start call button
                            const startCallBtn = document.getElementById('start-call-btn');
                            if (startCallBtn) {
                                startCallBtn.addEventListener('click', function() {
                                    console.log('📞 Start call button clicked via event listener');
                                    if (typeof window.startVideoCall === 'function') {
                                        window.startVideoCall();
                                    } else {
                                        console.error('startVideoCall function not available');
                                        alert('Start video call function not available. Please refresh the page.');
                                    }
                                });
                                console.log('✅ Start call button event listener added');
                            }
                            
                            // Add event listeners for other video call buttons
                            const endCallBtn = document.getElementById('end-call-btn');
                            if (endCallBtn) {
                                endCallBtn.addEventListener('click', function() {
                                    console.log('📞 End call button clicked');
                                    if (typeof window.endVideoCall === 'function') {
                                        window.endVideoCall();
                                    } else {
                                        console.error('endVideoCall function not available');
                                    }
                                });
                            }
                            
                            const toggleAudioBtn = document.getElementById('toggle-audio-btn');
                            if (toggleAudioBtn) {
                                toggleAudioBtn.addEventListener('click', function() {
                                    console.log('🎤 Toggle audio button clicked');
                                    if (typeof window.toggleAudio === 'function') {
                                        window.toggleAudio();
                                    } else {
                                        console.error('toggleAudio function not available');
                                    }
                                });
                            }
                            
                            const toggleVideoBtn = document.getElementById('toggle-video-btn');
                            if (toggleVideoBtn) {
                                toggleVideoBtn.addEventListener('click', function() {
                                    console.log('📹 Toggle video button clicked');
                                    if (typeof window.toggleVideo === 'function') {
                                        window.toggleVideo();
                                    } else {
                                        console.error('toggleVideo function not available');
                                    }
                                });
                            }
                            
                            const mirrorVideoBtn = document.getElementById('mirror-video-btn');
                            if (mirrorVideoBtn) {
                                mirrorVideoBtn.addEventListener('click', function() {
                                    console.log('🪞 Mirror video button clicked');
                                    if (typeof window.toggleMirror === 'function') {
                                        window.toggleMirror();
                                    } else {
                                        console.error('toggleMirror function not available');
                                    }
                                });
                            }
                            
                            // Add event listener for close video button
                            const closeVideoBtn = document.getElementById('close-video-btn');
                            if (closeVideoBtn) {
                                closeVideoBtn.addEventListener('click', function() {
                                    console.log('❌ Close video button clicked');
                                    if (typeof window.closeVideoChat === 'function') {
                                        window.closeVideoChat();
                                    } else {
                                        console.error('closeVideoChat function not available');
                                        // Fallback: just hide the modal
                                        const modal = document.getElementById('video-chat-modal');
                                        if (modal) {
                                            modal.style.display = 'none';
                                            console.log('✅ Modal closed via fallback');
                                        }
                                    }
                                });
                                console.log('✅ Close video button event listener added');
                            }
                            
                            // Add event listeners for remaining buttons
                            const autoCallToggle = document.getElementById('auto-call-toggle');
                            if (autoCallToggle) {
                                autoCallToggle.addEventListener('click', function() {
                                    console.log('🔗 Auto-call toggle clicked');
                                    if (typeof window.toggleAutoCall === 'function') {
                                        window.toggleAutoCall();
                                    } else {
                                        console.error('toggleAutoCall function not available');
                                    }
                                });
                            }
                            
                            const screenShareBtn = document.getElementById('screen-share-btn');
                            if (screenShareBtn) {
                                screenShareBtn.addEventListener('click', function() {
                                    console.log('🖥️ Screen share button clicked');
                                    if (typeof window.toggleScreenShare === 'function') {
                                        window.toggleScreenShare();
                                    } else {
                                        console.error('toggleScreenShare function not available');
                                    }
                                });
                            }
                            
                            const maximizeBtn = document.getElementById('maximize-btn');
                            if (maximizeBtn) {
                                maximizeBtn.addEventListener('click', function() {
                                    console.log('⛶ Maximize button clicked');
                                    if (typeof window.toggleMaximize === 'function') {
                                        window.toggleMaximize();
                                    } else {
                                        console.error('toggleMaximize function not available');
                                    }
                                });
                            }
                            
                            const chatToggleBtn = document.getElementById('chat-toggle-btn');
                            if (chatToggleBtn) {
                                chatToggleBtn.addEventListener('click', function() {
                                    console.log('💬 Chat toggle button clicked');
                                    if (typeof window.toggleChat === 'function') {
                                        window.toggleChat();
                                    } else {
                                        console.error('toggleChat function not available');
                                    }
                                });
                            }
                            
                            // Verify functions are available after DOM is loaded
                            setTimeout(() => {
                                verifyVideoFunctions();
                            }, 1000);
                        });
                    </script>
                    <button
                        style="background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem;">🎤</button>
                    <button
                        style="background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem;">⚠️</button>
                </div>
            </div>

            <!-- Chat Messages -->
            <div id="chat-messages" style="flex: 1; padding: 16px; overflow-y: auto; background: #f9fafb;">
                @foreach($messages as $message)
                <div class="message-container" data-sender="{{ $message->sender_id }}" data-auth="{{ Auth::id() }}">
                    <div class="message-bubble" data-sender="{{ $message->sender_id }}" data-auth="{{ Auth::id() }}">
                        <div class="message-content">{{ $message->message }}</div>
                        <div class="message-time">{{ $message->created_at->format('g:i A') }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Message Input -->
            <div style="padding: 16px; background: white; border-top: 1px solid #e5e7eb;">
                <form id="message-form" style="display: flex; gap: 8px;">
                    <div style="flex: 1; position: relative;">
                        <input type="text" id="message-input" placeholder="Type your message here..."
                            style="width: 100%; padding: 12px 40px 12px 12px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                        <button type="button" id="emoji-button"
                            style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 18px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;"
                            title="Add emoji">😊</button>
                    </div>
                    <div style="display: flex; gap: 4px; align-items: center;">
                        <input type="file" id="image-upload" accept="image/*" style="display: none;"
                            onchange="handleImageUpload(event)">
                        <input type="file" id="video-upload" accept="video/*" style="display: none;"
                            onchange="handleVideoUpload(event)">
                        <button type="button" onclick="document.getElementById('image-upload').click()"
                            style="padding: 8px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer; font-size: 16px;"
                            title="Send Image">📷</button>
                        <button type="button" onclick="document.getElementById('video-upload').click()"
                            style="padding: 8px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer; font-size: 16px;"
                            title="Send Video">🎥</button>
                        <button type="submit" id="send-button"
                            style="background: #1e40af; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Send</button>
                    </div>
                </form>

            </div>
        </div>

        <!-- Session Tasks (Right Sidebar) -->
        <div
            style="width: 350px; background: white; border-left: 1px solid #e5e7eb; display: flex; flex-direction: column;">
            <!-- Sidebar Header -->
            <div
                style="background: #f3f4f6; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px;">
                <span>☑️</span>
                <span style="font-weight: 600;">Session Tasks</span>
            </div>

            <!-- Tasks Content -->
            <div style="flex: 1; padding: 16px; overflow-y: auto;">
                <!-- Your Tasks -->
                <div style="margin-bottom: 24px;">
                    <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 12px; color: #374151;">Your Tasks</h3>
                    <div id="my-tasks">
                        @forelse($myTasks as $task)
                        <div class="task-item" data-task-id="{{ $task->id }}"
                            style="margin-bottom: 12px; padding: 12px; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                    <input type="checkbox" {{ $task->completed ? 'checked' : '' }}
                                    onchange="toggleTask({{ $task->id }})"
                                    style="width: 16px; height: 16px;">
                                    <span style="font-weight: 500; {{ $task->completed ? 'text-decoration: line-through; color: #6b7280;' : '' }}">{{ $task->title }}</span>
                                    
                                    <!-- Task Status Badge -->
                                    @if($task->current_status)
                                    <span class="badge" style="background: 
                                        @switch($task->current_status)
                                            @case('assigned') #6b7280 @break
                                            @case('in_progress') #f59e0b @break
                                            @case('submitted') #3b82f6 @break
                                            @case('completed') #10b981 @break
                                            @default #6b7280
                                        @endswitch; color: white; font-size: 0.75rem;">
                                        {{ ucfirst(str_replace('_', ' ', $task->current_status)) }}
                                    </span>
                                    @endif

                                    <!-- File Submission Required -->
                                    @if($task->requires_submission)
                                    <span class="badge" style="background: #8b5cf6; color: white; font-size: 0.75rem;">
                                        <i class="fas fa-paperclip" style="font-size: 0.7rem;"></i> Submission Required
                                    </span>
                                    @endif
                                </div>

                                <!-- Task Actions - Always visible Edit/Delete for creators -->
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    @if($task->created_by === Auth::id())
                                        <!-- Edit Button -->
                                        <button onclick="editTask({{ $task->id }})" title="Edit Task"
                                                style="background: #3b82f6; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <!-- Delete Button -->
                                        <button onclick="deleteTask({{ $task->id }})" title="Delete Task"
                                                style="background: #ef4444; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    @endif
                                    
                                    <!-- Dropdown for additional actions -->
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" 
                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                style="padding: 4px 8px; font-size: 0.75rem;">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" onclick="viewTaskDetails({{ $task->id }})">
                                                <i class="fas fa-eye me-2"></i>View Details
                                            </a></li>
                                            
                                            @if($task->created_by === Auth::id())
                                                <!-- Task Creator Actions -->
                                                @if($task->current_status === 'submitted')
                                                <li><a class="dropdown-item" href="#" onclick="reviewTaskSubmission({{ $task->id }})">
                                                    <i class="fas fa-clipboard-check me-2"></i>Review Submission
                                                </a></li>
                                                @endif
                                            @else
                                                <!-- Task Assignee Actions -->
                                                @if($task->requires_submission && in_array($task->current_status, ['assigned', 'in_progress']))
                                                <li><a class="dropdown-item" href="#" onclick="submitTaskWork({{ $task->id }})">
                                                    <i class="fas fa-upload me-2"></i>Submit Work
                                                </a></li>
                                                @endif
                                                @if($task->current_status === 'assigned')
                                                <li><a class="dropdown-item" href="#" onclick="startTask({{ $task->id }})">
                                                    <i class="fas fa-play me-2"></i>Start Task
                                                </a></li>
                                                @endif
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            @if($task->description)
                            <div style="font-size: 0.875rem; color: #6b7280; margin-left: 24px; margin-bottom: 8px;">{{ $task->description }}</div>
                            @endif

                            <!-- Task Details -->
                            <div style="margin-left: 24px; font-size: 0.75rem; color: #9ca3af;">
                                @if($task->priority)
                                <span style="margin-right: 12px;">
                                    <i class="fas fa-flag" style="color: 
                                        @switch($task->priority)
                                            @case('high') #ef4444 @break
                                            @case('medium') #f59e0b @break
                                            @case('low') #10b981 @break
                                            @default #6b7280
                                        @endswitch;"></i>
                                    {{ ucfirst($task->priority) }} Priority
                                </span>
                                @endif
                                
                                @if($task->due_date)
                                <span style="margin-right: 12px;">
                                    <i class="fas fa-calendar"></i>
                                    Due: {{ \Carbon\Carbon::parse($task->due_date)->format('M j, Y') }}
                                </span>
                                @endif

                                @if($task->allowed_file_types)
                                <span>
                                    <i class="fas fa-file"></i>
                                    Accepts: {{ implode(', ', $task->allowed_file_types) }}
                                </span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div style="color: #6b7280; font-size: 0.875rem; text-align: center; padding: 16px;">No tasks
                            assigned to you</div>
                        @endforelse
                    </div>

                    <!-- Your Progress -->
                    <div style="margin-top: 16px;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 0.875rem; color: #6b7280;">Progress</span>
                            <span id="my-progress-text" style="font-size: 0.875rem; font-weight: 600;"
                                data-progress="{{ round($myProgress) }}">{{ round($myProgress) }}%</span>
                        </div>
                        <div style="background: #e5e7eb; border-radius: 4px; height: 8px; overflow: hidden;">
                            <div id="my-progress-bar"
                                style="background: #10b981; height: 100%; transition: width 0.3s ease;"
                                data-progress="{{ $myProgress }}"></div>
                        </div>
                    </div>
                </div>

                <!-- Partner's Tasks -->
                <div style="margin-bottom: 24px;">
                    <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 12px; color: #374151;">{{
                        $partner->firstname }}'s Tasks</h3>
                    <div id="partner-tasks">
                        @forelse($partnerTasks as $task)
                        <div class="task-item" data-task-id="{{ $task->id }}"
                            style="margin-bottom: 12px; padding: 12px; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                    <input type="checkbox" {{ $task->completed ? 'checked' : '' }} disabled style="width: 16px; height: 16px;">
                                    <span style="font-weight: 500; {{ $task->completed ? 'text-decoration: line-through; color: #6b7280;' : '' }}">{{ $task->title }}</span>

                                    <!-- Task Status Badge -->
                                    @if($task->current_status)
                                    <span class="badge" style="background: 
                                        @switch($task->current_status)
                                            @case('assigned') #6b7280 @break
                                            @case('in_progress') #f59e0b @break
                                            @case('submitted') #3b82f6 @break
                                            @case('completed') #10b981 @break
                                            @default #6b7280
                                        @endswitch; color: white; font-size: 0.75rem;">
                                        {{ ucfirst(str_replace('_', ' ', $task->current_status)) }}
                                    </span>
                                    @endif

                                    <!-- File Submission Required -->
                                    @if($task->requires_submission)
                                    <span class="badge" style="background: #8b5cf6; color: white; font-size: 0.75rem;">
                                        <i class="fas fa-paperclip" style="font-size: 0.7rem;"></i> Submission Required
                                    </span>
                                    @endif

                                    <!-- Verification Status Badge (Legacy) -->
                                    @if($task->completed)
                                    @if($task->verified)
                                    <span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 500;">✓ Verified</span>
                                    @else
                                    <span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 500;">⏳ Pending Verification</span>
                                    @endif
                                    @endif
                                </div>

                                <!-- Task Actions - Edit/Delete for creators -->
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    @if($task->created_by === Auth::id())
                                        <!-- Edit Button -->
                                        <button onclick="editTask({{ $task->id }})" title="Edit Task"
                                                style="background: #3b82f6; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <!-- Delete Button -->
                                        <button onclick="deleteTask({{ $task->id }})" title="Delete Task"
                                                style="background: #ef4444; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    @endif
                                    
                                    <!-- Dropdown for additional actions -->
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" 
                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                style="padding: 4px 8px; font-size: 0.75rem;">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" onclick="viewTaskDetails({{ $task->id }})">
                                                <i class="fas fa-eye me-2"></i>View Details
                                            </a></li>
                                            
                                            @if($task->created_by === Auth::id())
                                                <!-- Task Creator Actions -->
                                                @if($task->current_status === 'submitted')
                                                <li><a class="dropdown-item" href="#" onclick="reviewTaskSubmission({{ $task->id }})">
                                                    <i class="fas fa-clipboard-check me-2"></i>Review Submission
                                                </a></li>
                                                @endif
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @if($task->description)
                            <div style="font-size: 0.875rem; color: #6b7280; margin-left: 24px;">{{ $task->description
                                }}</div>
                            @endif

                            <!-- Submission Review Actions (Only for Task Creator) -->
                            @if($task->current_status === 'submitted' && $task->created_by == auth()->id())
                            <div style="margin-top: 8px; margin-left: 24px;">
                                <button onclick="reviewTaskSubmission({{ $task->id }})"
                                    style="background: #3b82f6; color: white; padding: 4px 12px; border: none; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                    <i class="fas fa-eye"></i> Review Submission
                                </button>
                            </div>
                            @endif
                        </div>
                        @empty
                        <div style="color: #6b7280; font-size: 0.875rem; text-align: center; padding: 16px;">No tasks
                            assigned to {{ $partner->firstname }}</div>
                        @endforelse
                    </div>

                    <!-- Partner's Progress -->
                    <div style="margin-top: 16px;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 0.875rem; color: #6b7280;">Progress</span>
                            <span id="partner-progress-text" style="font-size: 0.875rem; font-weight: 600;"
                                data-progress="{{ round($partnerProgress) }}">{{ round($partnerProgress) }}%</span>
                        </div>
                        <div style="background: #e5e7eb; border-radius: 4px; height: 8px; overflow: hidden;">
                            <div id="partner-progress-bar"
                                style="background: #3b82f6; height: 100%; transition: width 0.3s ease;"
                                data-progress="{{ $partnerProgress }}"></div>
                        </div>
                    </div>
                </div>

                <!-- Skill Learning Status -->
                <div
                    style="margin-bottom: 24px; padding: 16px; background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px;">
                    <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 12px; color: #0c4a6e;">🎓 Skill
                        Learning Progress</h3>
                    <div id="skill-learning-status">
                        <div style="text-align: center; color: #6b7280; font-size: 0.875rem;">
                            Loading skill learning status...
                        </div>
                    </div>
                    <div id="complete-session-section" style="margin-top: 16px; display: none;">
                        <button onclick="completeSession()" id="complete-session-btn"
                            style="width: 100%; background: #10b981; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            ✅ Complete Session & Learn Skills
                        </button>
                    </div>
                </div>

                <!-- Add Task Button -->
                <button onclick="showAddTaskModal()"
                    style="width: 100%; background: #1e40af; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    + Add Task
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div
        style="background: #f3f4f6; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e5e7eb;">
        <div style="font-size: 0.875rem; color: #6b7280;">
            <div>Session started: {{ \Carbon\Carbon::parse($trade->start_date)->format('M d, Y') }} at {{
                \Carbon\Carbon::parse($trade->start_date)->format('g:i A') }}</div>
            <div>Current time: <span id="current-time">{{ now()->format('g:i A') }}</span> • Duration: <span
                    id="session-duration">0 minutes</span></div>
            <div>Status: <span id="session-status" style="color: #10b981; font-weight: 600;">🟢 Active</span> • Tasks:
                <span id="task-count">0</span>
            </div>
        </div>
        <button onclick="endSession()"
            style="background: #ef4444; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">End
            Session</button>
    </div>
</div>

<!-- Add Task Modal -->
<div id="add-task-modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;"
    onclick="handleModalClick(event)">
    <div style="background: white; padding: 24px; border-radius: 8px; width: 500px; max-width: 90%; max-height: 90%; overflow-y: auto;"
        onclick="event.stopPropagation()">
        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 16px;">Add Task</h3>
        
        <!-- Task Assignment (Read-only, only to partner) -->
        <div style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 4px; font-weight: 500;">Assign Task To</label>
            <input type="text" value="{{ $partner->firstname }} {{ $partner->lastname }}" readonly
                style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; background: #f9fafb; color: #6b7280;">
            <input type="hidden" id="task-assignee" value="{{ $partner->id }}">
            <small style="color: #6b7280; font-size: 0.875rem;">Tasks can only be assigned to your trade partner</small>
        </div>

        <form id="add-task-form">
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 4px; font-weight: 500;">Task Title</label>
                <input type="text" id="task-title" required
                    style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 4px; font-weight: 500;">Description (Optional)</label>
                <textarea id="task-description" rows="3"
                    style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; resize: vertical;"></textarea>
            </div>

            <!-- File Submission Requirements -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                    <input type="checkbox" id="requires-submission" style="margin-right: 8px;">
                    Require File Submission
                </label>
                
                <div id="submission-options" style="display: none; margin-left: 20px; padding: 12px; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.875rem;">Required File Types:</label>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">
                        <label style="display: flex; align-items: center; font-size: 0.875rem;">
                            <input type="checkbox" name="file-types" value="images" style="margin-right: 6px;">
                            📸 Images (JPG, PNG, GIF)
                        </label>
                        <label style="display: flex; align-items: center; font-size: 0.875rem;">
                            <input type="checkbox" name="file-types" value="videos" style="margin-right: 6px;">
                            🎥 Videos (MP4, MOV, AVI)
                        </label>
                        <label style="display: flex; align-items: center; font-size: 0.875rem;">
                            <input type="checkbox" name="file-types" value="pdf" style="margin-right: 6px;">
                            📄 PDF Documents
                        </label>
                        <label style="display: flex; align-items: center; font-size: 0.875rem;">
                            <input type="checkbox" name="file-types" value="docx" style="margin-right: 6px;">
                            📝 Word Documents
                        </label>
                        <label style="display: flex; align-items: center; font-size: 0.875rem;">
                            <input type="checkbox" name="file-types" value="excel" style="margin-right: 6px;">
                            📊 Excel Files
                        </label>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 0.875rem;">Submission Instructions:</label>
                        <textarea id="submission-instructions" rows="2" placeholder="Provide specific instructions for what should be submitted..."
                            style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem; resize: vertical;"></textarea>
                    </div>
                </div>
            </div>

            <!-- Priority and Due Date -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; margin-bottom: 4px; font-weight: 500;">Priority</label>
                    <select id="task-priority" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 4px; font-weight: 500;">Due Date (Optional)</label>
                    <input type="date" id="task-due-date" min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                        style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                </div>
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" onclick="hideAddTaskModal()"
                    style="padding: 8px 16px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                <button type="submit"
                    style="padding: 8px 16px; background: #1e40af; color: white; border: none; border-radius: 4px; cursor: pointer;">Add Task</button>
            </div>
        </form>
    </div>
</div>

<!-- Task Edit Modal -->
<div id="edit-task-modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;"
    onclick="handleEditTaskModalClick(event)">
    <div style="background: white; padding: 24px; border-radius: 8px; width: 500px; max-width: 90%; max-height: 90%; overflow-y: auto;"
        onclick="event.stopPropagation()">
        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 16px;">Edit Task</h3>
        
        <form id="edit-task-form">
            <input type="hidden" id="edit-task-id">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 4px; font-weight: 500;">Task Title</label>
                <input type="text" id="edit-task-title" required
                    style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 4px; font-weight: 500;">Description (Optional)</label>
                <textarea id="edit-task-description" rows="3"
                    style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; resize: vertical;"></textarea>
            </div>

            <!-- File Submission Requirements -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                    <input type="checkbox" id="edit-requires-submission" style="margin-right: 8px;">
                    Require File Submission
                </label>
                
                <div id="edit-submission-options" style="display: none; margin-left: 20px; padding: 12px; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.875rem;">Required File Types:</label>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">
                        <label style="display: flex; align-items: center; font-size: 0.875rem;">
                            <input type="checkbox" name="edit-file-types" value="image" style="margin-right: 6px;">
                            📸 Images (JPG, PNG, GIF)
                        </label>
                        <label style="display: flex; align-items: center; font-size: 0.875rem;">
                            <input type="checkbox" name="edit-file-types" value="video" style="margin-right: 6px;">
                            🎥 Videos (MP4, MOV, AVI)
                        </label>
                        <label style="display: flex; align-items: center; font-size: 0.875rem;">
                            <input type="checkbox" name="edit-file-types" value="pdf" style="margin-right: 6px;">
                            📄 PDF Documents
                        </label>
                        <label style="display: flex; align-items: center; font-size: 0.875rem;">
                            <input type="checkbox" name="edit-file-types" value="word" style="margin-right: 6px;">
                            📝 Word Documents
                        </label>
                        <label style="display: flex; align-items: center; font-size: 0.875rem;">
                            <input type="checkbox" name="edit-file-types" value="excel" style="margin-right: 6px;">
                            📊 Excel Files
                        </label>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 0.875rem;">Submission Instructions:</label>
                        <textarea id="edit-submission-instructions" rows="2" placeholder="Provide specific instructions for what should be submitted..."
                            style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem; resize: vertical;"></textarea>
                    </div>
                </div>
            </div>

            <!-- Priority and Due Date -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; margin-bottom: 4px; font-weight: 500;">Priority</label>
                    <select id="edit-task-priority" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 4px; font-weight: 500;">Due Date (Optional)</label>
                    <input type="date" id="edit-task-due-date" min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                        style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                </div>
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" onclick="hideEditTaskModal()"
                    style="padding: 8px 16px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                <button type="submit"
                    style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">Update Task</button>
            </div>
        </form>
    </div>
</div>

<!-- Task Submission Review Modal -->
<div id="submission-review-modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;"
    onclick="handleSubmissionReviewModalClick(event)">
    <div style="background: white; border-radius: 8px; padding: 24px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto;"
        onclick="event.stopPropagation()">
        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 16px; color: #374151;">
            Review Task Submission
        </h3>
        
        <div id="submission-content">
            <!-- Submission details will be loaded here -->
        </div>

        <form id="submission-evaluation-form" style="margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 20px;">
            <input type="hidden" id="evaluation-task-id" name="task_id">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 8px;">
                    Score (0-100%)
                </label>
                <input type="number" id="evaluation-score" name="score_percentage" min="0" max="100" 
                       style="width: 100px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem;"
                       placeholder="85">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 8px;">
                    Status
                </label>
                <select id="evaluation-status" name="status" 
                        style="width: 200px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem;">
                    <option value="pass">Pass</option>
                    <option value="needs_improvement">Needs Improvement</option>
                    <option value="fail">Fail</option>
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 8px;">
                    Feedback
                </label>
                <textarea id="evaluation-feedback" name="feedback"
                    style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem; resize: vertical; min-height: 80px;"
                    placeholder="Provide feedback on the submitted work..."></textarea>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 8px;">
                    Improvement Notes (Optional)
                </label>
                <textarea id="evaluation-improvement-notes" name="improvement_notes"
                    style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem; resize: vertical; min-height: 60px;"
                    placeholder="Specific suggestions for improvement..."></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="hideSubmissionReviewModal()"
                    style="padding: 8px 16px; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                    Cancel
                </button>
                <button type="submit"
                    style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                    Submit Evaluation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden logout form -->
<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>

<script>
    // Laravel Echo is already initialized in bootstrap.js
// We'll use it to listen for events

        // Debug information
        console.log('=== CHAT DEBUG INFO ===');
        console.log('Trade ID:', window.tradeId);
        console.log('User ID:', window.authUserId);
        console.log('Laravel Echo available:', !!window.Echo);
        console.log('Pusher available:', !!window.Pusher);
        console.log('Current URL:', window.location.href);
        console.log('Base URL:', window.location.origin);
        console.log('Generated message URL:', window.location.origin + '/chat/{{ $trade->id }}/message');

// Listen for events using Laravel Echo
if (window.Echo) {
    console.log('Initializing Pusher connection for trade {{ $trade->id }}');
    console.log('Pusher configuration:', {
        key: window.PUSHER_APP_KEY,
        cluster: window.PUSHER_APP_CLUSTER,
        encrypted: true
    });
    console.log('Echo available:', !!window.Echo);
    console.log('✅ Using Pusher for video call signaling (WebSocket fallback disabled)');
    
    // Connection status monitoring
    window.Echo.connector.pusher.connection.bind('connected', function() {
        console.log('✅ Pusher connected successfully');
        updateConnectionStatus('connected');
    });
    
    window.Echo.connector.pusher.connection.bind('disconnected', function() {
        console.log('❌ Pusher disconnected');
        updateConnectionStatus('disconnected');
    });
    
    window.Echo.connector.pusher.connection.bind('error', function(error) {
        console.error('🚨 Pusher connection error:', error);
        updateConnectionStatus('error');
    });
    
    // Additional debugging
    window.Echo.connector.pusher.connection.bind('connecting', function() {
        console.log('🔄 Pusher connecting...');
        updateConnectionStatus('connecting');
    });

    // Listen for new messages
    window.Echo.channel('trade-{{ $trade->id }}')
        .listen('new-message', function(data) {
            console.log('Received new message event:', data);
            // Only add if it's not from the current user (to avoid duplicates)
            if (data.message.sender_id !== window.authUserId) {
                addMessageToChat(data.message, data.sender_name, data.timestamp, false);
            } else {
                // For our own messages, just update the timestamp if needed
                const existingMessage = document.querySelector(`[data-confirmed="true"]`);
                if (existingMessage) {
                    const timestampElement = existingMessage.querySelector('div[style*="font-size: 0.75rem"]');
                    if (timestampElement) {
                        timestampElement.textContent = data.timestamp;
                    }
                }
            }
        });

    // Listen for task updates
    window.Echo.channel('trade-{{ $trade->id }}')
        .listen('task-updated', function(data) {
            console.log('Received task update event:', data);
            updateTask(data.task);
            updateProgress();
        });

    // Video call functionality - Messenger style
    let videoCallState = {
        isActive: false,
        isInitiator: false,
        isConnected: false,
        callId: null,
        partnerId: null,
        localStream: null,
        remoteStream: null,
        peerConnection: null,
        startTime: null,
        timer: null
    };

    // Make openVideoChat globally accessible
    console.log('🔧 Defining openVideoChat function...');
    try {
        window.openVideoChat = function() {
            console.log('🎥 Opening video chat...');
            const modal = document.getElementById('video-chat-modal');
            if (modal) {
                modal.style.display = 'flex';
                
                // Initialize camera immediately
                if (typeof initializeCamera === 'function') {
                    initializeCamera();
                } else {
                    console.log('initializeCamera not available, using fallback');
                    // Fallback camera initialization
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                            .then(stream => {
                                const localVideo = document.getElementById('local-video');
                                if (localVideo) {
                                    localVideo.srcObject = stream;
                                    localVideo.style.display = 'block';
                                    localVideo.play();
                                }
                                window.localStream = stream;
                            })
                            .catch(error => console.error('Camera error:', error));
                    }
                }
                
                // Automatically start the call like Messenger
                if (typeof window.startVideoCall === 'function') {
                    window.startVideoCall();
                } else {
                    console.log('startVideoCall not available yet');
                }
            } else {
                console.error('Video chat modal not found');
                alert('Video chat is not available. Please refresh the page.');
            }
    };
        console.log('✅ openVideoChat function defined:', typeof window.openVideoChat);
    } catch (error) {
        console.error('❌ Error defining openVideoChat:', error);
    }
    
    // Make closeVideoChat globally accessible
    try {
        window.closeVideoChat = function() {
        console.log('🛑 Closing video chat...');
        const modal = document.getElementById('video-chat-modal');
        if (modal) {
            modal.style.display = 'none';
        }
            if (typeof window.endVideoCall === 'function') {
                window.endVideoCall();
            }
        };
        console.log('✅ closeVideoChat function defined:', typeof window.closeVideoChat);
    } catch (error) {
        console.error('❌ Error defining closeVideoChat:', error);
    }
    
    // Make startVideoCall globally accessible (full version)
    window.startVideoCallFull = async function() {
        console.log('🚀 Starting video call with Firebase (full version)...');
        
        try {
            // Get partner ID
            const partnerId = getPartnerId();
            if (!partnerId) {
                alert('No partner found for this trade.');
                return;
            }
            
            // Check if Firebase video call is available
            if (!firebaseVideoCall) {
                console.error('❌ Firebase video call not initialized');
                alert('Video call service not available. Please refresh the page.');
                return;
            }
            
            // Update UI to show calling state
            updateCallStatus('Initializing...');
            updateCallTimer('00:00');
            
            // Start the call using Firebase
            const success = await firebaseVideoCall.startCall(partnerId);
            
            if (success) {
                // Setup local video display
                const localVideo = document.getElementById('local-video');
                if (localVideo && firebaseVideoCall.localStream) {
                    localVideo.srcObject = firebaseVideoCall.localStream;
                    localVideo.style.display = 'block';
                }
                
                videoCallState.isActive = true;
                videoCallState.isInitiator = true;
                videoCallState.partnerId = partnerId;
                videoCallState.callId = firebaseVideoCall.callId;
                
                startCallTimer();
                
                // Set a timeout to show "waiting" status
                setTimeout(() => {
                    if (videoCallState.isActive && !videoCallState.isConnected) {
                        updateCallStatus('Waiting for answer...');
                    }
                }, 5000);
                
                console.log('✅ Video call initiated successfully with Firebase');
            } else {
                throw new Error('Failed to start video call with Firebase');
            }
            
        } catch (error) {
            console.error('❌ Error starting video call:', error);
            alert('Failed to start video call: ' + error.message);
            endVideoCall();
        }
    }
    
    // Peer connection creation is now handled by Firebase integration
    
    async function fetchTurnCredentials() {
        try {
            console.log('🔄 Fetching TURN credentials...');
            const apiKey = '511852cda421697270ed9af8b089038b39a7';
            const response = await fetch(`https://skillxchange.metered.live/api/v1/turn/credentials?apiKey=${apiKey}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const iceServers = await response.json();
            console.log('✅ TURN credentials fetched:', iceServers.length, 'servers');
            return iceServers;
            
        } catch (error) {
            console.error('❌ Error fetching TURN credentials:', error);
            // Fallback servers
            return [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' },
                { urls: 'stun:stun.relay.metered.ca:80' }
            ];
        }
    }
    
    // Video call signaling is now handled by Firebase integration
    // No need for HTTP-based signaling functions
    
    // ICE candidate signaling is now handled by Firebase integration
    
    // Make endVideoCall globally accessible
    window.endVideoCall = function() {
        console.log('🛑 Ending video call...');
        
        // Use Firebase to end the call
        if (firebaseVideoCall) {
            firebaseVideoCall.endCall();
        }
        
        // Clear video elements
        const localVideo = document.getElementById('local-video');
        const remoteVideo = document.getElementById('remote-video');
        if (localVideo) localVideo.srcObject = null;
        if (remoteVideo) remoteVideo.srcObject = null;
        
        // Stop timer
        if (videoCallState.timer) {
            clearInterval(videoCallState.timer);
            videoCallState.timer = null;
        }
        
        // Reset state
        videoCallState.isActive = false;
        videoCallState.isInitiator = false;
        videoCallState.isConnected = false;
        videoCallState.callId = null;
        videoCallState.partnerId = null;
        videoCallState.startTime = null;
        videoCallState.localStream = null;
        videoCallState.remoteStream = null;
        videoCallState.isProcessingCall = false;
        
        updateCallStatus('Call ended');
    }
    
    function getPartnerId() {
        const tradeOwnerId = {{ $trade->user_id }};
        const currentUserId = {{ auth()->id() }};
        
        if (currentUserId === tradeOwnerId) {
            // Current user is the trade owner, get the requester
            const acceptedRequest = {!! json_encode($trade->requests()->where('status', 'accepted')->first() ?: null) !!};
            return acceptedRequest ? acceptedRequest.requester_id : null;
        } else {
            // Current user is the requester, get the trade owner
            return tradeOwnerId;
        }
    }
    
    function generateCallId() {
        return 'call_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    
    function updateCallTimer(time) {
        const timerElement = document.getElementById('call-timer');
        if (timerElement) {
            timerElement.textContent = time;
        }
    }
    
    function startCallTimer() {
        videoCallState.startTime = Date.now();
        videoCallState.timer = setInterval(() => {
            const elapsed = Date.now() - videoCallState.startTime;
            const minutes = Math.floor(elapsed / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);
            updateCallTimer(String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0'));
        }, 1000);
    }

    // Listen for video call events via Pusher
    console.log('🔗 Setting up video call event listeners for trade {{ $trade->id }}');
    console.log('🔗 Current user ID: {{ auth()->id() }}');
    console.log('🔗 Echo available:', typeof window.Echo !== 'undefined');
    console.log('🔗 Pusher available:', typeof window.Pusher !== 'undefined');
    
    // Check Echo connection status
    if (window.Echo) {
        console.log('🔗 Echo connection state:', window.Echo.connector.pusher.connection.state);
        
        // Listen for connection events
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('✅ Pusher connected for video calls');
        });
        
        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            console.log('❌ Pusher disconnected');
        });
        
        window.Echo.connector.pusher.connection.bind('error', (error) => {
            console.error('❌ Pusher connection error:', error);
        });
    }

    // Handle incoming offer
    async function handleVideoCallOffer(data) {
        console.log('📞 Handling video call offer:', data);
        
        try {
            // Use Firebase to answer the call
            if (firebaseVideoCall) {
                const success = await firebaseVideoCall.answerCall(data.offer);
                
                if (success) {
                    // Setup local video display
                    const localVideo = document.getElementById('local-video');
                    if (localVideo && firebaseVideoCall.localStream) {
                        localVideo.srcObject = firebaseVideoCall.localStream;
                        localVideo.style.display = 'block';
                    }
                    
                    videoCallState.isActive = true;
                    videoCallState.isInitiator = false;
                    videoCallState.partnerId = data.fromUserId;
                    videoCallState.callId = data.callId;
                    
                    startCallTimer();
                    
                    console.log('✅ Video call answered successfully with Firebase');
                } else {
                    throw new Error('Failed to answer call with Firebase');
                }
            } else {
                throw new Error('Firebase video call not available');
            }
            
        } catch (error) {
            console.error('Error handling offer:', error);
            alert('Failed to answer call: ' + error.message);
            endVideoCall();
        }
    }

    // Handle incoming answer
    async function handleVideoCallAnswer(data) {
        console.log('📞 Handling video call answer:', data);
        
        try {
            // If we have a remote stream from Firebase, display it
            if (data.remoteStream) {
                const remoteVideo = document.getElementById('remote-video');
                if (remoteVideo) {
                    remoteVideo.srcObject = data.remoteStream;
                    remoteVideo.style.display = 'block';
                }
                videoCallState.remoteStream = data.remoteStream;
            }
            
            updateCallStatus('Connected');
            videoCallState.isConnected = true;
            
        } catch (error) {
            console.error('Error handling answer:', error);
        }
    }

    // Handle ICE candidate
    async function handleIceCandidate(data) {
        console.log('📞 Handling ICE candidate:', data);
        
        try {
            await videoCallState.peerConnection.addIceCandidate(data.candidate);
            
        } catch (error) {
            console.error('Error handling ICE candidate:', error);
        }
    }

    // Handle call end
    function handleVideoCallEnd(data) {
        console.log('📞 Video call ended:', data);
        endVideoCall();
    }
    
    // Test function to verify event listening (can be called from browser console)
    window.testVideoCallEvents = function() {
        console.log('🧪 Testing video call event listening...');
        console.log('🧪 Trade ID: {{ $trade->id }}');
        console.log('🧪 User ID: {{ auth()->id() }}');
        console.log('🧪 Echo available:', typeof window.Echo !== 'undefined');
        console.log('🧪 Pusher available:', typeof window.Pusher !== 'undefined');
        
        if (window.Echo) {
            console.log('🧪 Echo connection state:', window.Echo.connector.pusher.connection.state);
            console.log('🧪 Pusher connection state:', window.Echo.connector.pusher.connection.state);
        }
        
        // Test if we can access the private channel
        try {
            const channel = window.Echo.private('trade.{{ $trade->id }}');
            console.log('🧪 Private channel created successfully');
            console.log('🧪 Channel name: trade.{{ $trade->id }}');
        } catch (error) {
            console.error('🧪 Error creating private channel:', error);
        }
        
        return {
            tradeId: {{ $trade->id }},
            userId: {{ auth()->id() }},
            echoAvailable: typeof window.Echo !== 'undefined',
            pusherAvailable: typeof window.Pusher !== 'undefined',
            connectionState: window.Echo && window.Echo.connector && window.Echo.connector.pusher && window.Echo.connector.pusher.connection ? window.Echo.connector.pusher.connection.state : 'unknown'
        };
    };

    updateConnectionStatus('error');
}

// Function to show video call errors to the user
function showVideoCallError(message) {
    console.error('📞 Video Call Error:', message);
    
    // Show a user-friendly error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
    errorDiv.innerHTML = `
        <div class="flex">
            <div class="py-1">
                <svg class="fill-current h-6 w-6 text-red-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/>
                </svg>
            </div>
            <div>
                <p class="font-bold">Video Call Unavailable</p>
                <p class="text-sm">${message}</p>
            </div>
        </div>
    `;
    
    // Insert the error message at the top of the chat container
    const chatContainer = document.querySelector('.chat-container') || document.querySelector('.bg-white');
    if (chatContainer) {
        chatContainer.insertBefore(errorDiv, chatContainer.firstChild);
    }
}

// HTTP polling fallback for video calls when Pusher fails
let videoCallPollingInterval = null;
let lastPollTime = Date.now();

function startVideoCallPolling() {
    console.log('🔄 Starting HTTP polling fallback for video calls...');
    
    if (videoCallPollingInterval) {
        clearInterval(videoCallPollingInterval);
    }
    
    videoCallPollingInterval = setInterval(async () => {
        try {
            const response = await fetch(`/chat/{{ $trade->id }}/video-call/poll?since=${lastPollTime}`);
            const data = await response.json();
            
            if (data.success && data.messages && data.messages.length > 0) {
                console.log('📞 Polling received messages:', data.messages);
                
                for (const message of data.messages) {
                    if (message.type === 'video-call-offer' && message.toUserId === {{ auth()->id() }}) {
                        console.log('📞 Processing video call offer via polling:', message);
                        await handleVideoCallOffer(message);
                    } else if (message.type === 'video-call-answer' && message.toUserId === {{ auth()->id() }}) {
                        console.log('📞 Processing video call answer via polling:', message);
                        await handleVideoCallAnswer(message);
                    } else if (message.type === 'video-call-ice-candidate' && message.toUserId === {{ auth()->id() }}) {
                        console.log('📞 Processing ICE candidate via polling:', message);
                        await handleIceCandidate(message);
                    } else if (message.type === 'video-call-end' && message.fromUserId !== {{ auth()->id() }}) {
                        console.log('📞 Processing video call end via polling:', message);
                        handleVideoCallEnd(message);
                    }
                }
                
                lastPollTime = Date.now();
            }
        } catch (error) {
            console.error('❌ Error polling for video call messages:', error);
        }
    }, 5000); // Poll every 5 seconds
    
    // Show a notification that we're using polling fallback
    const fallbackDiv = document.createElement('div');
    fallbackDiv.className = 'bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4';
    fallbackDiv.innerHTML = `
        <div class="flex">
            <div class="py-1">
                <svg class="fill-current h-6 w-6 text-yellow-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"/>
                </svg>
            </div>
            <div>
                <p class="font-bold">Using Fallback Mode</p>
                <p class="text-sm">Video calls are working via HTTP polling. Real-time features may be slightly delayed.</p>
            </div>
        </div>
    `;
    
    const chatContainer = document.querySelector('.chat-container') || document.querySelector('.bg-white');
    if (chatContainer) {
        chatContainer.insertBefore(fallbackDiv, chatContainer.firstChild);
    }
}

function stopVideoCallPolling() {
    if (videoCallPollingInterval) {
        clearInterval(videoCallPollingInterval);
        videoCallPollingInterval = null;
        console.log('🛑 Stopped HTTP polling for video calls');
    }
}

// WebSocket fallback removed - using Pusher for all video call signaling

// WebSocket and HTTP polling removed - using Pusher for all video call signaling

// Connection status update function
function updateConnectionStatus(status) {
    const indicator = document.getElementById('status-indicator');
    const text = document.getElementById('status-text');
    
    if (!indicator || !text) return;
    
    switch(status) {
        case 'connected':
            indicator.style.background = '#10b981';
            text.textContent = 'Connected';
            break;
        case 'disconnected':
            indicator.style.background = '#f59e0b';
            text.textContent = 'Disconnected';
            break;
        case 'error':
            indicator.style.background = '#ef4444';
            text.textContent = 'Connection Error';
            break;
        default:
            indicator.style.background = '#6b7280';
            text.textContent = 'Connecting...';
    }
}

// Message handling with debounce
let isSending = false;
document.getElementById('message-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (message && !isSending) {
        isSending = true;
        sendMessage(message);
        input.value = '';
        
        // Prevent rapid sending (reduced from 1000ms to 300ms for better responsiveness)
        setTimeout(() => {
            isSending = false;
        }, 300);
    }
});

function sendMessage(message) {
    console.log('📤 Sending message:', message);
    
    // Show loading state
    const sendButton = document.getElementById('send-button');
    const originalText = sendButton.textContent;
    sendButton.textContent = 'Sending...';
    sendButton.disabled = true;
    sendButton.style.background = '#6b7280';
    
    // Add message to UI immediately (optimistic update)
    const tempId = 'temp_' + Date.now();
    addMessageToChat(message, '{{ Auth::user()->firstname }} {{ Auth::user()->lastname }}', new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}), true, tempId);
    
    // Generate absolute URL for production compatibility
    const baseUrl = window.location.origin;
    const url = baseUrl + '/chat/{{ $trade->id }}/message';
    console.log('📡 Sending to URL:', url);
    console.log('📡 CSRF Token:', '{{ csrf_token() }}');
    console.log('📡 Base URL:', baseUrl);
    
    // Check if URL is valid
    if (!url || url.includes('undefined') || !url.includes('/chat/')) {
        console.error('❌ Invalid URL generated:', url);
        showError('Invalid chat URL. Please refresh the page.');
        return;
    }
    
    // Add credentials for CORS
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ message: message }),
        credentials: 'same-origin' // Important for CORS
    })
    .then(response => {
        console.log('📨 Response status:', response.status);
        console.log('📨 Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('📨 Response data:', data);
        
        // Reset button state
        sendButton.textContent = originalText;
        sendButton.disabled = false;
        sendButton.style.background = '#1e40af';
        
        if (data.success) {
            console.log('✅ Message sent successfully');
            // Update the temporary message with the real one and mark it as confirmed
            updateMessageInChat(tempId, data.message);
            // Mark this message as confirmed to prevent duplicate Echo events
            const messageElement = document.querySelector(`[data-temp-id="${tempId}"]`);
            if (messageElement) {
                messageElement.setAttribute('data-confirmed', 'true');
                messageElement.removeAttribute('data-temp-id');
            }
        } else {
            console.error('❌ Message send failed:', data.error);
            // Remove the temporary message if it failed
            removeMessageFromChat(tempId);
            showError('Failed to send message: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('🚨 Fetch error:', error);
        console.error('🚨 Error type:', error.name);
        console.error('🚨 Error message:', error.message);
        
        // Reset button state
        sendButton.textContent = originalText;
        sendButton.disabled = false;
        sendButton.style.background = '#1e40af';
        
        // Remove the temporary message if it failed
        removeMessageFromChat(tempId);
        
        // Show specific error messages based on error type
        if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
            showError('Network error: Unable to connect to server. Please check your internet connection and try again.');
        } else if (error.name === 'TypeError' && error.message.includes('NetworkError')) {
            showError('Network error: Please check your internet connection.');
        } else if (error.message.includes('CORS')) {
            showError('CORS error: Cross-origin request blocked. Please refresh the page.');
        } else if (error.message.includes('HTTP error')) {
            showError('Server error: ' + error.message);
        } else {
            showError('Failed to send message: ' + error.message);
        }
    });
}

function addMessageToChat(message, senderName, timestamp, isOwn, tempId = null) {
    // Check for duplicate messages to prevent double display
    if (isOwn) {
        const messageText = typeof message === 'string' ? message : message.message;
        const existingMessages = document.querySelectorAll('#chat-messages > div');
        const lastMessage = existingMessages[existingMessages.length - 1];
        
        if (lastMessage && lastMessage.querySelector('div[style*="background: #3b82f6"]')) {
            const lastMessageText = lastMessage.querySelector('div[style*="margin-bottom: 4px"]').textContent;
            if (lastMessageText === messageText) {
                console.log('Duplicate message detected, skipping...');
                return lastMessage;
            }
        }
    }
    
    const chatMessages = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.style.marginBottom = '16px';
    messageDiv.style.display = 'flex';
    messageDiv.style.justifyContent = isOwn ? 'flex-end' : 'flex-start';
    
    if (tempId) {
        messageDiv.setAttribute('data-temp-id', tempId);
    }
    
    // Handle both string messages and message objects
    const messageText = typeof message === 'string' ? message : message.message;
    const messageTime = timestamp || new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    // Check if message contains image or video
    let messageContent = '';
    if (messageText.includes('[IMAGE:') && messageText.includes(']')) {
        const fileName = messageText.match(/\[IMAGE:(.+?)\]/)[1];
        messageContent = `
            <div style="max-width: 70%; ${isOwn ? 'background: #3b82f6; color: white;' : 'background: #e5e7eb; color: #374151;'} padding: 12px; border-radius: 12px; position: relative; word-wrap: break-word; overflow-wrap: break-word;">
                <div style="margin-bottom: 8px;">
                    <img src="${window.tempImageData || '#'}" alt="${fileName}" class="chat-image" onerror="this.style.display='none'">
                </div>
                <div style="font-size: 0.75rem; opacity: 0.8;">${fileName}</div>
                <div style="font-size: 0.75rem; opacity: 0.8; margin-top: 4px;">${messageTime}</div>
            </div>
        `;
    } else if (messageText.includes('[VIDEO:') && messageText.includes(']')) {
        const fileName = messageText.match(/\[VIDEO:(.+?)\]/)[1];
        messageContent = `
            <div style="max-width: 70%; ${isOwn ? 'background: #3b82f6; color: white;' : 'background: #e5e7eb; color: #374151;'} padding: 12px; border-radius: 12px; position: relative; word-wrap: break-word; overflow-wrap: break-word;">
                <div style="margin-bottom: 8px;">
                    <video controls style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                        <source src="${window.tempVideoData || '#'}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <div style="font-size: 0.75rem; opacity: 0.8;">${fileName}</div>
                <div style="font-size: 0.75rem; opacity: 0.8; margin-top: 4px;">${messageTime}</div>
            </div>
        `;
    } else {
        messageContent = `
            <div style="max-width: 70%; ${isOwn ? 'background: #3b82f6; color: white;' : 'background: #e5e7eb; color: #374151;'} padding: 12px; border-radius: 12px; position: relative; word-wrap: break-word; overflow-wrap: break-word;">
                <div style="margin-bottom: 4px; word-break: break-word; line-height: 1.4;">${messageText}</div>
                <div style="font-size: 0.75rem; opacity: 0.8;">${messageTime}</div>
            </div>
        `;
    }
    
    messageDiv.innerHTML = messageContent;
    
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Flash effect for new messages (only for incoming messages, not your own)
    if (!isOwn) {
        console.log('🆕 New message added dynamically:', messageText);
        flashChatArea();
    }
    
    return messageDiv;
}

// Add flash effect function
function flashChatArea() {
    const chatMessages = document.getElementById('chat-messages');
    
    // Create flash overlay
    const flashOverlay = document.createElement('div');
    flashOverlay.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(59, 130, 246, 0.3), rgba(16, 185, 129, 0.3));
        border-radius: 8px;
        pointer-events: none;
        z-index: 10;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    // Position the overlay relative to chat messages
    chatMessages.style.position = 'relative';
    chatMessages.appendChild(flashOverlay);
    
    // Trigger flash animation
    setTimeout(() => {
        flashOverlay.style.opacity = '1';
    }, 50);
    
    setTimeout(() => {
        flashOverlay.style.opacity = '0';
    }, 150);
    
    // Remove overlay after animation
    setTimeout(() => {
        if (flashOverlay.parentNode) {
            flashOverlay.parentNode.removeChild(flashOverlay);
        }
    }, 500);
    
    // Show new message indicator
    showNewMessageIndicator();
}

// Show new message indicator
function showNewMessageIndicator() {
    const indicator = document.getElementById('new-message-indicator');
    if (indicator) {
        indicator.style.display = 'inline-block';
        
        // Hide after 3 seconds
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 3000);
    }
}

// Show error message function
function showError(message) {
    console.error('Error:', message);
    
    // Create error notification
    const errorDiv = document.createElement('div');
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ef4444;
        color: white;
        padding: 12px 16px;
        border-radius: 6px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        max-width: 300px;
        word-wrap: break-word;
    `;
    errorDiv.innerHTML = `
        <div style="display: flex; align-items: center; gap: 8px;">
            <span>⚠️</span>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; margin-left: auto;">×</button>
        </div>
    `;
    
    document.body.appendChild(errorDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 5000);
}

// Show success message function
function showSuccess(message) {
    console.log('Success:', message);
    
    // Create success notification
    const successDiv = document.createElement('div');
    successDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 12px 16px;
        border-radius: 6px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        max-width: 300px;
        word-wrap: break-word;
    `;
    
    successDiv.innerHTML = `
        <div style="display: flex; align-items: center; gap: 8px;">
            <span>✅</span>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; margin-left: auto;">×</button>
        </div>
    `;
    
    document.body.appendChild(successDiv);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (successDiv.parentNode) {
            successDiv.remove();
        }
    }, 3000);
}

// Remove message from chat function
function removeMessageFromChat(tempId) {
    const messageElement = document.querySelector(`[data-temp-id="${tempId}"]`);
    if (messageElement) {
        messageElement.remove();
    }
}

function updateMessageInChat(tempId, messageData) {
    const messageDiv = document.querySelector(`[data-temp-id="${tempId}"]`);
    if (messageDiv) {
        // Update with real message data
        messageDiv.removeAttribute('data-temp-id');
        messageDiv.setAttribute('data-message-id', messageData.id);
        
        const messageText = messageData.message;
        const messageTime = new Date(messageData.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        messageDiv.innerHTML = `
            <div style="max-width: 70%; background: #3b82f6; color: white; padding: 12px; border-radius: 12px; position: relative; word-wrap: break-word; overflow-wrap: break-word;">
                <div style="margin-bottom: 4px; word-break: break-word; line-height: 1.4;">${messageText}</div>
                <div style="font-size: 0.75rem; opacity: 0.8;">${messageTime}</div>
            </div>
        `;
    }
}

function removeMessageFromChat(tempId) {
    const messageDiv = document.querySelector(`[data-temp-id="${tempId}"]`);
    if (messageDiv) {
        messageDiv.remove();
    }
}

// Task handling
function toggleTask(taskId) {
    fetch(`/chat/task/${taskId}/toggle`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTask(data.task);
            updateProgress();
            // Refresh skill learning status
            loadSkillLearningStatus();
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateTask(task) {
    const taskElement = document.querySelector(`[data-task-id="${task.id}"]`);
    if (taskElement) {
        const checkbox = taskElement.querySelector('input[type="checkbox"]');
        const title = taskElement.querySelector('span');
        
        checkbox.checked = task.completed;
        if (task.completed) {
            title.style.textDecoration = 'line-through';
            title.style.color = '#6b7280';
        } else {
            title.style.textDecoration = 'none';
            title.style.color = '';
        }
    }
}

function updateProgress() {
    // Recalculate progress without reloading
    const myTasks = document.querySelectorAll('#my-tasks .task-item');
    const myCompletedTasks = document.querySelectorAll('#my-tasks .task-item input[type="checkbox"]:checked');
    const myProgress = myTasks.length > 0 ? (myCompletedTasks.length / myTasks.length) * 100 : 0;
    
    const partnerTasks = document.querySelectorAll('#partner-tasks .task-item');
    const partnerCompletedTasks = document.querySelectorAll('#partner-tasks .task-item input[type="checkbox"]:checked');
    const partnerProgress = partnerTasks.length > 0 ? (partnerCompletedTasks.length / partnerTasks.length) * 100 : 0;
    
    // Update progress bars
    const myProgressBar = document.querySelector('#my-tasks + div div[style*="background: #10b981"]');
    const partnerProgressBar = document.querySelector('#partner-tasks + div div[style*="background: #3b82f6"]');
    
    if (myProgressBar) {
        myProgressBar.style.width = myProgress + '%';
        myProgressBar.parentElement.previousElementSibling.querySelector('span:last-child').textContent = Math.round(myProgress) + '%';
    }
    
    if (partnerProgressBar) {
        partnerProgressBar.style.width = partnerProgress + '%';
        partnerProgressBar.parentElement.previousElementSibling.querySelector('span:last-child').textContent = Math.round(partnerProgress) + '%';
    }
    
    // Update task count in session info
    updateTaskCount();
}

function updateTaskCount() {
    const myTasks = document.querySelectorAll('#my-tasks .task-item').length;
    const partnerTasks = document.querySelectorAll('#partner-tasks .task-item').length;
    const totalTasks = myTasks + partnerTasks;
    
    const taskCountElement = document.getElementById('task-count');
    if (taskCountElement) {
        taskCountElement.textContent = totalTasks;
        
        // Update color based on task count
        if (totalTasks === 0) {
            taskCountElement.style.color = '#ef4444'; // Red for no tasks
        } else if (totalTasks < 3) {
            taskCountElement.style.color = '#f59e0b'; // Orange for few tasks
        } else {
            taskCountElement.style.color = '#10b981'; // Green for good task count
        }
    }
}

// Modal handling
function showAddTaskModal() {
    const modal = document.getElementById('add-task-modal');
    modal.style.display = 'flex';
    // Clear form when opening
    document.getElementById('add-task-form').reset();
}

function hideAddTaskModal() {
    const modal = document.getElementById('add-task-modal');
    modal.style.display = 'none';
    // Clear form when closing
    document.getElementById('add-task-form').reset();
}

function handleModalClick(event) {
    // Close modal when clicking outside the content area
    if (event.target.id === 'add-task-modal') {
        hideAddTaskModal();
    }
}

// Submission review modal functions
function reviewTaskSubmission(taskId) {
    // Fetch task submission details
    fetch(`/tasks/${taskId}/submission-details`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSubmissionReviewModal(taskId, data.task, data.submission);
        } else {
            showError('Failed to load submission details: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to load submission details. Please try again.');
    });
}

function showSubmissionReviewModal(taskId, task, submission) {
    const modal = document.getElementById('submission-review-modal');
    const contentDiv = document.getElementById('submission-content');
    
    // Set task ID for evaluation form
    document.getElementById('evaluation-task-id').value = taskId;
    
    // Build submission content HTML
    let submissionHtml = `
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
            <h4 style="margin: 0 0 8px 0; color: #374151;">Task: ${task.title}</h4>
            <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">${task.description || 'No description provided'}</p>
        </div>
        
        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
            <h5 style="margin: 0 0 12px 0; color: #374151;">Submission Details</h5>
            
            <div style="margin-bottom: 12px;">
                <strong>Submitted by:</strong> ${submission.submitter_name}
            </div>
            
            <div style="margin-bottom: 12px;">
                <strong>Submitted on:</strong> ${new Date(submission.created_at).toLocaleString()}
            </div>
            
            ${submission.submission_notes ? `
                <div style="margin-bottom: 12px;">
                    <strong>Notes:</strong>
                    <div style="background: #f3f4f6; padding: 8px; border-radius: 4px; margin-top: 4px;">
                        ${submission.submission_notes}
                    </div>
                </div>
            ` : ''}
            
            ${submission.file_paths && submission.file_paths.length > 0 ? `
                <div>
                    <strong>Submitted Files:</strong>
                    <div style="margin-top: 8px;">
                        ${submission.file_paths.map((filePath, index) => `
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <i class="fas fa-file" style="color: #6b7280;"></i>
                                <a href="/submissions/${submission.id}/files/${index}" target="_blank" 
                                   style="color: #3b82f6; text-decoration: none;">
                                    ${filePath.split('/').pop()}
                                </a>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : '<div style="color: #6b7280; font-style: italic;">No files submitted</div>'}
        </div>
    `;
    
    contentDiv.innerHTML = submissionHtml;
    modal.style.display = 'flex';
    
    // Clear evaluation form
    document.getElementById('submission-evaluation-form').reset();
    document.getElementById('evaluation-task-id').value = taskId;
}

function hideSubmissionReviewModal() {
    const modal = document.getElementById('submission-review-modal');
    modal.style.display = 'none';
    document.getElementById('submission-evaluation-form').reset();
}

function handleSubmissionReviewModalClick(event) {
    if (event.target.id === 'submission-review-modal') {
        hideSubmissionReviewModal();
    }
}

// Handle submission requirements toggle
document.getElementById('requires-submission').addEventListener('change', function() {
    const submissionOptions = document.getElementById('submission-options');
    if (this.checked) {
        submissionOptions.style.display = 'block';
    } else {
        submissionOptions.style.display = 'none';
    }
});

// Handle edit modal submission requirements toggle
document.getElementById('edit-requires-submission').addEventListener('change', function() {
    const submissionOptions = document.getElementById('edit-submission-options');
    if (this.checked) {
        submissionOptions.style.display = 'block';
    } else {
        submissionOptions.style.display = 'none';
    }
});

// Edit task form handler
document.getElementById('edit-task-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const taskId = document.getElementById('edit-task-id').value;
    const title = document.getElementById('edit-task-title').value;
    const description = document.getElementById('edit-task-description').value;
    const priority = document.getElementById('edit-task-priority').value;
    const dueDate = document.getElementById('edit-task-due-date').value;
    const requiresSubmission = document.getElementById('edit-requires-submission').checked;
    
    // Debug logging
    console.log('Due date from input:', dueDate);
    console.log('Due date input element:', document.getElementById('edit-task-due-date'));
    const submissionInstructions = document.getElementById('edit-submission-instructions').value;
    
    // Get selected file types
    const fileTypeCheckboxes = document.querySelectorAll('input[name="edit-file-types"]:checked');
    const selectedFileTypes = Array.from(fileTypeCheckboxes).map(cb => cb.value);
    
    // Validation
    if (requiresSubmission && selectedFileTypes.length === 0) {
        showError('Please select at least one file type when requiring submission.');
        return;
    }
    
    // Create form data for Laravel web routes
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}');
    formData.append('title', title);
    formData.append('description', description);
    formData.append('priority', priority);
    // Only append due_date if it has a value
    if (dueDate && dueDate.trim() !== '') {
        formData.append('due_date', dueDate);
    }
    formData.append('requires_submission', requiresSubmission ? '1' : '0');
    formData.append('submission_instructions', submissionInstructions);
    
    // Add file types
    selectedFileTypes.forEach(type => {
        formData.append('allowed_file_types[]', type);
    });
    
    // Add X-CSRF-TOKEN header as well for extra security
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    console.log('Updating task:', taskId, 'with data:', Object.fromEntries(formData));
    console.log('CSRF Token:', csrfToken);
    console.log('User ID:', window.authUserId);
    console.log('Form data entries:');
    for (let [key, value] of formData.entries()) {
        console.log(key, ':', value);
    }
    
    // Check if user is authenticated
    if (!window.authUserId) {
        showError('You must be logged in to update tasks. Please refresh the page and try again.');
        return;
    }
    
    fetch(`/tasks/${taskId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData,
        credentials: 'same-origin' // Ensure cookies are sent
    })
    .then(response => {
        console.log('Response status:', response.status, 'URL:', response.url);
        
        if (response.status === 429) {
            showError('Too many requests. Please slow down and try again in a moment.');
            return;
        }
        
        if (response.status === 302) {
            console.log('Redirect detected, checking location header');
            const location = response.headers.get('Location');
            console.log('Redirect location:', location);
            showError('Session expired. Please refresh the page and try again.');
            return;
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data && data.success) {
            hideEditTaskModal();
            showSuccess('Task updated successfully!');
            // Refresh the page to show updated task
            location.reload();
        } else if (data) {
            console.log('Error details:', data);
            if (data.errors) {
                console.log('Validation errors:', data.errors);
                showError('Validation failed: ' + JSON.stringify(data.errors));
            } else {
                showError('Failed to update task: ' + (data.error || 'Unknown error'));
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (error.message && error.message.includes('429')) {
            showError('Too many requests. Please slow down and try again in a moment.');
        } else {
            showError('Failed to update task. Please try again.');
        }
    });
});

document.getElementById('add-task-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const title = document.getElementById('task-title').value;
    const description = document.getElementById('task-description').value;
    const assignedTo = document.getElementById('task-assignee').value;
    const priority = document.getElementById('task-priority').value;
    const dueDate = document.getElementById('task-due-date').value;
    const requiresSubmission = document.getElementById('requires-submission').checked;
    const submissionInstructions = document.getElementById('submission-instructions').value;
    
    // Get selected file types
    const fileTypeCheckboxes = document.querySelectorAll('input[name="file-types"]:checked');
    const selectedFileTypes = Array.from(fileTypeCheckboxes).map(cb => cb.value);
    
    // Validate file types if submission is required
    if (requiresSubmission && selectedFileTypes.length === 0) {
        showError('Please select at least one file type when requiring submission.');
        return;
    }
    
    fetch('{{ route("chat.create-task", $trade->id) }}'.replace('http://', 'https://'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            title: title,
            description: description,
            assigned_to: assignedTo,
            priority: priority,
            due_date: dueDate,
            requires_submission: requiresSubmission,
            allowed_file_types: selectedFileTypes,
            submission_instructions: submissionInstructions
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideAddTaskModal();
            addTaskToUI(data.task);
            updateTaskCount(); // Update task count after adding task
            // Clear form
            clearTaskForm();
        } else {
            showError('Failed to create task: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to create task. Please try again.');
    });
});

function clearTaskForm() {
    document.getElementById('task-title').value = '';
    document.getElementById('task-description').value = '';
    document.getElementById('task-priority').value = 'medium';
    document.getElementById('task-due-date').value = '';
    document.getElementById('requires-submission').checked = false;
    document.getElementById('submission-instructions').value = '';
    document.getElementById('submission-options').style.display = 'none';
    
    // Clear file type checkboxes
    const fileTypeCheckboxes = document.querySelectorAll('input[name="file-types"]');
    fileTypeCheckboxes.forEach(cb => cb.checked = false);
}

// Task CRUD Operations
function viewTaskDetails(taskId) {
    // Redirect to task details page
    window.open(`/tasks/${taskId}`, '_blank');
}

function editTask(taskId) {
    // Fetch task details and show edit modal
    fetch(`/tasks/${taskId}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showEditTaskModal(data.task);
        } else {
            showError('Failed to load task details: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to load task details. Please try again.');
    });
}

function showEditTaskModal(task) {
    const modal = document.getElementById('edit-task-modal');
    
    // Populate form fields
    document.getElementById('edit-task-id').value = task.id;
    document.getElementById('edit-task-title').value = task.title;
    document.getElementById('edit-task-description').value = task.description || '';
    document.getElementById('edit-task-priority').value = task.priority || 'medium';
    // Format due_date for date input (YYYY-MM-DD)
    let dueDateValue = '';
    if (task.due_date) {
        // Handle different date formats
        if (task.due_date.match(/^\d{4}-\d{2}-\d{2}$/)) {
            // Already in YYYY-MM-DD format
            dueDateValue = task.due_date;
        } else if (task.due_date.includes('T')) {
            // ISO datetime format (2025-10-11T00:00:00.000000Z)
            dueDateValue = task.due_date.split('T')[0];
        } else {
            // Other datetime formats, try to extract date part
            dueDateValue = task.due_date.split(' ')[0];
        }
    }
    document.getElementById('edit-task-due-date').value = dueDateValue;
    
    // Debug log to see what we're setting
    console.log('Setting due date value:', dueDateValue, 'from task.due_date:', task.due_date);
    
    // Handle submission requirements
    const requiresSubmission = task.requires_submission;
    document.getElementById('edit-requires-submission').checked = requiresSubmission;
    const submissionOptions = document.getElementById('edit-submission-options');
    submissionOptions.style.display = requiresSubmission ? 'block' : 'none';
    
    // Set file types
    const fileTypeCheckboxes = document.querySelectorAll('input[name="edit-file-types"]');
    fileTypeCheckboxes.forEach(cb => {
        cb.checked = task.allowed_file_types && task.allowed_file_types.includes(cb.value);
    });
    
    // Set submission instructions
    document.getElementById('edit-submission-instructions').value = task.submission_instructions || '';
    
    modal.style.display = 'flex';
}

function hideEditTaskModal() {
    const modal = document.getElementById('edit-task-modal');
    modal.style.display = 'none';
    document.getElementById('edit-task-form').reset();
}

function handleEditTaskModalClick(event) {
    if (event.target.id === 'edit-task-modal') {
        hideEditTaskModal();
    }
}

function deleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
        fetch(`/tasks/${taskId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.error || `HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showSuccess('Task deleted successfully!');
                
                // Remove all instances of the task from UI immediately
                const taskElements = document.querySelectorAll(`[data-task-id="${taskId}"]`);
                taskElements.forEach(element => {
                    element.style.transition = 'opacity 0.3s ease';
                    element.style.opacity = '0';
                    setTimeout(() => {
                        element.remove();
                    }, 300);
                });
                
                // Check if containers are empty after deletion
                setTimeout(() => {
                    checkEmptyTaskContainers();
                }, 350);
                
                updateTaskCount();
            } else {
                showError('Failed to delete task: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Delete task error:', error);
            showError('Failed to delete task: ' + error.message);
        });
    }
}

function checkEmptyTaskContainers() {
    const myTasksContainer = document.getElementById('my-tasks');
    const partnerTasksContainer = document.getElementById('partner-tasks');
    
    // Check My Tasks container
    if (myTasksContainer && myTasksContainer.children.length === 0) {
        myTasksContainer.innerHTML = '<div style="color: #6b7280; font-size: 0.875rem; text-align: center; padding: 16px;">No tasks assigned to you</div>';
    }
    
    // Check Partner Tasks container
    if (partnerTasksContainer && partnerTasksContainer.children.length === 0) {
        const partnerName = '{{ $partner->firstname ?? "Partner" }}';
        partnerTasksContainer.innerHTML = `<div style="color: #6b7280; font-size: 0.875rem; text-align: center; padding: 16px;">No tasks assigned to ${partnerName}</div>`;
    }
}

function startTask(taskId) {
    fetch(`/tasks/${taskId}/start`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Task started successfully!');
            // Refresh the page to show updated task status
            location.reload();
        } else {
            showError('Failed to start task: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to start task. Please try again.');
    });
}

function submitTaskWork(taskId) {
    // Create and show file submission modal
    showTaskSubmissionModal(taskId);
}

function showTaskSubmissionModal(taskId) {
    // Create modal HTML
    const modalHtml = `
        <div id="task-submission-modal" style="display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
            <div style="background: white; padding: 24px; border-radius: 8px; width: 500px; max-width: 90%; max-height: 90%; overflow-y: auto;">
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 16px;">Submit Task Work</h3>
                
                <form id="task-submission-form" enctype="multipart/form-data">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 500;">Upload Files</label>
                        <input type="file" id="task-files" name="files[]" multiple 
                               style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;"
                               accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx">
                        <small style="color: #6b7280;">Select files according to task requirements</small>
                    </div>
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 500;">Submission Notes</label>
                        <textarea id="submission-notes" rows="3" 
                                  style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; resize: vertical;"
                                  placeholder="Add any notes about your submission..."></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <button type="button" onclick="hideTaskSubmissionModal()" 
                                style="padding: 8px 16px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                        <button type="submit" 
                                style="padding: 8px 16px; background: #1e40af; color: white; border: none; border-radius: 4px; cursor: pointer;">Submit Work</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Add form submit handler
    document.getElementById('task-submission-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        const files = document.getElementById('task-files').files;
        const notes = document.getElementById('submission-notes').value;
        
        // Add files to form data
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        formData.append('submission_notes', notes);
        
        fetch(`/tasks/${taskId}/submit`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                hideTaskSubmissionModal();
                showSuccess('Work submitted successfully!');
                // Refresh task status
                location.reload();
            } else {
                showError('Failed to submit work: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to submit work. Please try again.');
        });
    });
}

function hideTaskSubmissionModal() {
    const modal = document.getElementById('task-submission-modal');
    if (modal) {
        modal.remove();
    }
}

function showSuccess(message) {
    // Create and show success toast
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        background: #10b981; color: white; padding: 12px 20px;
        border-radius: 6px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function showError(message) {
    // Create and show error toast
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        background: #ef4444; color: white; padding: 12px 20px;
        border-radius: 6px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// Submission evaluation form handler
document.getElementById('submission-evaluation-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const taskId = document.getElementById('evaluation-task-id').value;
    const scorePercentage = document.getElementById('evaluation-score').value;
    const status = document.getElementById('evaluation-status').value;
    const feedback = document.getElementById('evaluation-feedback').value;
    const improvementNotes = document.getElementById('evaluation-improvement-notes').value;
    
    // Validation
    if (!scorePercentage || scorePercentage < 0 || scorePercentage > 100) {
        showError('Please enter a valid score between 0 and 100.');
        return;
    }
    
    if (!feedback.trim()) {
        showError('Please provide feedback for the submission.');
        return;
    }
    
    fetch(`/tasks/${taskId}/evaluation`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            score_percentage: parseInt(scorePercentage),
            status: status,
            feedback: feedback,
            improvement_notes: improvementNotes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideSubmissionReviewModal();
            showSuccess('Task evaluation submitted successfully!');
            
            // Refresh the page to show updated task status
            location.reload();
        } else {
            showError('Failed to submit evaluation: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to submit evaluation. Please try again.');
    });
});

function addTaskToUI(task) {
    // Determine which container to add the task to based on who it's assigned to
    const isAssignedToMe = task.assigned_to == window.authUserId;
    const isCreatedByMe = task.created_by == window.authUserId;
    const container = isAssignedToMe ? document.getElementById('my-tasks') : document.getElementById('partner-tasks');
    
    const taskDiv = document.createElement('div');
    taskDiv.className = 'task-item';
    taskDiv.setAttribute('data-task-id', task.id);
    taskDiv.style.cssText = 'margin-bottom: 12px; padding: 12px; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb;';
    
    const checkboxHtml = isAssignedToMe 
        ? `<input type="checkbox" ${task.completed ? 'checked' : ''} onchange="toggleTask(${task.id})" style="width: 16px; height: 16px;">`
        : `<input type="checkbox" disabled style="width: 16px; height: 16px;">`;
    
    // Status badge
    let statusBadge = '';
    if (task.current_status) {
        const statusColors = {
            'assigned': '#6b7280',
            'in_progress': '#f59e0b',
            'submitted': '#3b82f6',
            'completed': '#10b981'
        };
        const statusColor = statusColors[task.current_status] || '#6b7280';
        const statusText = task.current_status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        statusBadge = `<span style="background: ${statusColor}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem;">${statusText}</span>`;
    }
    
    // File submission badge
    let submissionBadge = '';
    if (task.requires_submission) {
        submissionBadge = '<span style="background: #8b5cf6; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem;"><i class="fas fa-paperclip" style="font-size: 0.7rem;"></i> Submission Required</span>';
    }
    
    // Edit/Delete buttons for creators
    let actionButtons = '';
    if (isCreatedByMe) {
        actionButtons = `
            <button onclick="editTask(${task.id})" title="Edit Task"
                    style="background: #3b82f6; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 4px; margin-right: 4px;">
                <i class="fas fa-edit"></i> Edit
            </button>
            <button onclick="deleteTask(${task.id})" title="Delete Task"
                    style="background: #ef4444; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                <i class="fas fa-trash"></i> Delete
            </button>
        `;
    }
    
    taskDiv.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
            <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                ${checkboxHtml}
                <span style="font-weight: 500;">${task.title}</span>
                ${statusBadge}
                ${submissionBadge}
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                ${actionButtons}
            </div>
        </div>
        ${task.description ? `<div style="font-size: 0.875rem; color: #6b7280; margin-left: 24px;">${task.description}</div>` : ''}
    `;
    
    // Remove the "No tasks" message if it exists
    const noTasksMessage = container.querySelector('div[style*="text-align: center"]');
    if (noTasksMessage) {
        noTasksMessage.remove();
    }
    
    container.appendChild(taskDiv);
}

function updateTaskInUI(task) {
    const taskElement = document.querySelector(`[data-task-id="${task.id}"]`);
    if (!taskElement) return;
    
    // Update the task content with verification status
    const titleSpan = taskElement.querySelector('span[style*="font-weight: 500"]');
    const checkbox = taskElement.querySelector('input[type="checkbox"]');
    
    // Update checkbox state
    checkbox.checked = task.completed;
    
    // Update title styling
    if (task.completed) {
        titleSpan.style.textDecoration = 'line-through';
        titleSpan.style.color = '#6b7280';
    } else {
        titleSpan.style.textDecoration = 'none';
        titleSpan.style.color = '';
    }
    
    // Remove existing verification badge and actions
    const existingBadge = taskElement.querySelector('span[style*="background: #10b981"], span[style*="background: #f59e0b"]');
    const existingActions = taskElement.querySelector('div[style*="margin-top: 8px; margin-left: 24px"]');
    const existingNotes = taskElement.querySelector('div[style*="color: #059669"]');
    
    if (existingBadge) existingBadge.remove();
    if (existingActions) existingActions.remove();
    if (existingNotes) existingNotes.remove();
    
    // Add verification badge if completed
    if (task.completed) {
        const badge = document.createElement('span');
        if (task.verified) {
            badge.style.cssText = 'background: #10b981; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 500;';
            badge.textContent = '✓ Verified';
        } else {
            badge.style.cssText = 'background: #f59e0b; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 500;';
            badge.textContent = '⏳ Pending Verification';
        }
        
        const titleContainer = taskElement.querySelector('div[style*="display: flex; align-items: center"]');
        titleContainer.appendChild(badge);
    }
    
    // Add verification notes if verified and has notes
    if (task.verified && task.verification_notes) {
        const notesDiv = document.createElement('div');
        notesDiv.style.cssText = 'font-size: 0.875rem; color: #059669; margin-left: 24px; margin-top: 4px; font-style: italic;';
        notesDiv.innerHTML = `<strong>Verification:</strong> ${task.verification_notes}`;
        taskElement.appendChild(notesDiv);
    }
    
    // Add submission review action if submitted and user is creator
    if (task.current_status === 'submitted' && task.created_by == window.authUserId) {
        const actionsDiv = document.createElement('div');
        actionsDiv.style.cssText = 'margin-top: 8px; margin-left: 24px;';
        actionsDiv.innerHTML = `
            <button onclick="reviewTaskSubmission(${task.id})"
                    style="background: #3b82f6; color: white; padding: 4px 12px; border: none; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                <i class="fas fa-eye"></i> Review Submission
            </button>
        `;
        taskElement.appendChild(actionsDiv);
    }
}

// Skill Learning Functions
async function loadSkillLearningStatus() {
    try {
        // Ensure HTTPS URL
        const url = '{{ route("chat.skill-learning-status", $trade->id) }}';
        const httpsUrl = url.replace('http://', 'https://');
        const response = await fetch(httpsUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        if (!response.ok) {
            throw new Error('Failed to load skill learning status');
        }

        const data = await response.json();
        if (data.success) {
            updateSkillLearningStatusUI(data.summary);
        } else {
            throw new Error(data.error || 'Unknown error');
        }
    } catch (error) {
        console.error('Error loading skill learning status:', error);
        document.getElementById('skill-learning-status').innerHTML = `
            <div style="text-align: center; color: #ef4444; font-size: 0.875rem;">
                Error loading skill learning status
            </div>
        `;
    }
}

function updateSkillLearningStatusUI(summary) {
    const statusContainer = document.getElementById('skill-learning-status');
    const completeSection = document.getElementById('complete-session-section');
    
    if (!summary.ready_for_processing) {
        statusContainer.innerHTML = `
            <div style="text-align: center; color: #6b7280; font-size: 0.875rem;">
                ${summary.message || 'Session not ready for completion'}
            </div>
        `;
        completeSection.style.display = 'none';
        return;
    }

    const tradeOwner = summary.trade_owner;
    const requester = summary.requester;
    
    let statusHTML = `
        <div style="space-y: 12px;">
            <div style="padding: 12px; background: white; border-radius: 6px; border: 1px solid #e5e7eb;">
                <div style="font-weight: 600; color: #374151; margin-bottom: 8px;">
                    ${tradeOwner.user.firstname} ${tradeOwner.user.lastname}
                </div>
                <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 4px;">
                    Learning: <strong>${tradeOwner.skill_to_learn.name}</strong>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="flex: 1; background: #e5e7eb; border-radius: 4px; height: 6px;">
                        <div style="background: ${tradeOwner.completion_rate >= 100 ? '#10b981' : '#f59e0b'}; height: 100%; width: ${Math.min(tradeOwner.completion_rate, 100)}%; border-radius: 4px; transition: width 0.3s ease;"></div>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 600; color: ${tradeOwner.completion_rate >= 100 ? '#10b981' : '#f59e0b'};">
                        ${tradeOwner.completion_rate}%
                    </span>
                </div>
                <div style="font-size: 0.75rem; color: ${tradeOwner.will_receive_skill ? '#10b981' : '#6b7280'}; margin-top: 4px;">
                    ${tradeOwner.will_receive_skill ? '✅ Will receive skill' : '❌ Will not receive skill'}
                </div>
            </div>
            
            <div style="padding: 12px; background: white; border-radius: 6px; border: 1px solid #e5e7eb;">
                <div style="font-weight: 600; color: #374151; margin-bottom: 8px;">
                    ${requester.user.firstname} ${requester.user.lastname}
                </div>
                <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 4px;">
                    Learning: <strong>${requester.skill_to_learn.name}</strong>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="flex: 1; background: #e5e7eb; border-radius: 4px; height: 6px;">
                        <div style="background: ${requester.completion_rate >= 100 ? '#10b981' : '#f59e0b'}; height: 100%; width: ${Math.min(requester.completion_rate, 100)}%; border-radius: 4px; transition: width 0.3s ease;"></div>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 600; color: ${requester.completion_rate >= 100 ? '#10b981' : '#f59e0b'};">
                        ${requester.completion_rate}%
                    </span>
                </div>
                <div style="font-size: 0.75rem; color: ${requester.will_receive_skill ? '#10b981' : '#6b7280'}; margin-top: 4px;">
                    ${requester.will_receive_skill ? '✅ Will receive skill' : '❌ Will not receive skill'}
                </div>
            </div>
        </div>
    `;
    
    statusContainer.innerHTML = statusHTML;
    
    // Show complete session button if both users have 100% completion or if any user has 100%
    const canComplete = tradeOwner.completion_rate >= 100 || requester.completion_rate >= 100;
    completeSection.style.display = canComplete ? 'block' : 'none';
    
    if (canComplete) {
        const completeBtn = document.getElementById('complete-session-btn');
        if (tradeOwner.completion_rate >= 100 && requester.completion_rate >= 100) {
            completeBtn.textContent = '✅ Complete Session & Learn Skills (Both Ready)';
            completeBtn.style.background = '#10b981';
        } else if (tradeOwner.completion_rate >= 100) {
            completeBtn.textContent = '✅ Complete Session (You\'re Ready)';
            completeBtn.style.background = '#f59e0b';
        } else {
            completeBtn.textContent = '✅ Complete Session (Partner Ready)';
            completeBtn.style.background = '#f59e0b';
        }
    }
}

async function completeSession() {
    const completeBtn = document.getElementById('complete-session-btn');
    const originalText = completeBtn.textContent;
    
    try {
        completeBtn.disabled = true;
        completeBtn.textContent = 'Processing...';
        
        const response = await fetch('{{ route("chat.complete-session", $trade->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            showSuccess('Session completed successfully! Skills have been added to profiles.');
            
            // Update the skill learning status
            await loadSkillLearningStatus();
            
            // Update session status
            document.getElementById('session-status').textContent = '✅ Completed';
            document.getElementById('session-status').style.color = '#10b981';
            
            // Hide the complete session button
            document.getElementById('complete-session-section').style.display = 'none';
            
            // Show skill learning results
            if (data.skill_learning_results && data.skill_learning_results.messages) {
                data.skill_learning_results.messages.forEach(message => {
                    showSuccess(message);
                });
            }
        } else {
            showError(data.error || 'Failed to complete session');
            completeBtn.disabled = false;
            completeBtn.textContent = originalText;
        }
    } catch (error) {
        console.error('Error completing session:', error);
        showError('Failed to complete session. Please try again.');
        completeBtn.disabled = false;
        completeBtn.textContent = originalText;
    }
}

// Real-time clock and session duration timer
let sessionStart = new Date('{{ $trade->start_date }}');
let currentTimeElement = document.getElementById('current-time');
let sessionDurationElement = document.getElementById('session-duration');

// Update current time every second
let timeInterval = setInterval(function() {
    const now = new Date();
    currentTimeElement.textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    // Calculate session duration
    const diff = Math.floor((now - sessionStart) / 60000);
    if (diff < 0) {
        sessionDurationElement.textContent = 'Not started yet';
    } else if (diff < 60) {
        sessionDurationElement.textContent = diff + ' minutes';
    } else {
        const hours = Math.floor(diff / 60);
        const minutes = diff % 60;
        sessionDurationElement.textContent = hours + 'h ' + minutes + 'm';
    }
}, 1000);

// Keep track of the last message count
let lastMessageCount = window.initialMessageCount;

// Smart message polling - only if Laravel Echo is not working
let messagePollingInterval = null;
let pollingFrequency = 10000; // Start with 10 seconds
let lastActivity = Date.now();
let consecutiveEmptyPolls = 0;

if (!window.Echo) {
    console.log('🔄 Laravel Echo not available, starting smart message polling...');
    startSmartMessagePolling();
    
    // Track user activity to optimize polling
    ['click', 'keypress', 'mousemove', 'scroll'].forEach(event => {
        document.addEventListener(event, () => {
            lastActivity = Date.now();
            
            // If user becomes active and polling is slow, speed it up
            if (pollingFrequency > 8000) {
                pollingFrequency = 8000;
                startSmartMessagePolling();
                console.log('🚀 User active - speeding up message polling to 8s');
            }
        }, { passive: true });
    });
}

function startSmartMessagePolling() {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    
    messagePollingInterval = setInterval(() => {
        checkForNewMessages();
    }, pollingFrequency);
}

function adjustPollingFrequency() {
    const timeSinceActivity = Date.now() - lastActivity;
    
    // If no activity for 30 seconds, slow down polling
    if (timeSinceActivity > 30000) {
        pollingFrequency = Math.min(20000, pollingFrequency + 2000); // Max 20 seconds
    } else {
        pollingFrequency = Math.max(8000, pollingFrequency - 1000); // Min 8 seconds
    }
    
    // If too many empty polls, slow down
    if (consecutiveEmptyPolls > 5) {
        pollingFrequency = Math.min(20000, pollingFrequency + 2000); // Max 20 seconds
    }
    
    // Restart polling with new frequency
    startSmartMessagePolling();
}

function checkForNewMessages() {
    fetch('/chat/{{ $trade->id }}/messages')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count > lastMessageCount) {
                // Get only the new messages
                const newMessages = data.messages.slice(lastMessageCount);
                lastMessageCount = data.count;
                
                // Reset activity tracking
                lastActivity = Date.now();
                consecutiveEmptyPolls = 0;

                // Add only new messages to chat
                newMessages.forEach(msg => {
                    addMessageToChat(
                        msg,
                        msg.sender.firstname + ' ' + msg.sender.lastname,
                        new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                        msg.sender_id === window.authUserId
                    );
                });
                
                console.log(`📨 Received ${newMessages.length} new messages`);
            } else {
                // No new messages
                consecutiveEmptyPolls++;
                
                // Adjust polling frequency every 10 polls
                if (consecutiveEmptyPolls % 10 === 0) {
                    adjustPollingFrequency();
                    console.log(`🔄 Adjusted polling frequency to ${pollingFrequency}ms after ${consecutiveEmptyPolls} empty polls`);
                }
            }
        })
        .catch(error => {
            console.error("Error checking for new messages:", error);
            consecutiveEmptyPolls++;
            
            // Handle rate limiting specifically
            if (error.message && error.message.includes('429')) {
                console.log('🔄 Rate limited - slowing down message polling');
                pollingFrequency = Math.min(30000, pollingFrequency + 5000); // Slow down significantly
                startSmartMessagePolling();
                return;
            }
            
            // Slow down on other errors
            if (consecutiveEmptyPolls % 5 === 0) {
                adjustPollingFrequency();
            }
        });
}

function endSession() {
    // Check if there are any tasks before ending session
    const myTasks = document.querySelectorAll('#my-tasks .task-item').length;
    const partnerTasks = document.querySelectorAll('#partner-tasks .task-item').length;
    const totalTasks = myTasks + partnerTasks;
    
    if (totalTasks === 0) {
        const proceed = confirm('No tasks have been added to this session. Are you sure you want to end the session without any tasks?\n\nIt is recommended to add at least one task to track progress.');
        if (!proceed) {
            return;
        }
    } else {
        const proceed = confirm(`Session has ${totalTasks} task(s). Are you sure you want to end this session?`);
        if (!proceed) {
            return;
        }
    }
    
    // End the session
    if (confirm('Are you sure you want to end this session? This action cannot be undone.')) {
        // Here you could add an API call to mark the session as ended
        // For now, we'll just redirect
        window.location.href = '{{ route("trades.ongoing") }}';
    }
}

// ===== VIDEO CHAT FUNCTIONALITY =====

// WebRTC variables
let localStream = null;
let remoteStream = null;
let peerConnection = null;
let isCallActive = false;
let callStartTime = null;
let callTimer = null;
let isAudioMuted = false;
let isVideoOff = false;
let currentCallId = null;
let isInitiator = false;
let otherUserId = null;
let pendingOffer = null; // Store the offer data for notification handling

// Video chat modal functions - removed duplicates, using the ones defined earlier in the file

function resetVideoChat() {
    // Reset UI
    if (isAutoCallEnabled) {
        document.getElementById('video-status').textContent = 'Camera and microphone ready. Auto-connecting when partner joins...';
    } else {
        document.getElementById('video-status').textContent = 'Initializing video chat...';
    }
    document.getElementById('call-timer').style.display = 'none';
    document.getElementById('start-call-btn').style.display = 'flex';
    document.getElementById('end-call-btn').style.display = 'none';
    document.getElementById('toggle-audio-btn').style.display = 'none';
    document.getElementById('toggle-video-btn').style.display = 'none';
    document.getElementById('mirror-video-btn').style.display = 'none';
    document.getElementById('screen-share-btn').style.display = 'none';
    document.getElementById('maximize-btn').style.display = 'none';
    document.getElementById('chat-toggle-btn').style.display = 'none';
    
    // Reset maximize state
    const videoGrid = document.getElementById('video-grid');
    const localVideoItem = document.getElementById('local-video-item');
    const remoteVideoItem = document.getElementById('remote-video-item');
    
    videoGrid.classList.remove('maximized');
    localVideoItem.classList.remove('maximized', 'minimized');
    remoteVideoItem.classList.remove('maximized', 'minimized');
    isMaximized = false;
    maximizedVideo = null;
    
    // Stop screen sharing if active
    if (isScreenSharing) {
        stopScreenShare();
    }
    
    // Reset status indicators
    document.getElementById('local-status').textContent = 'Local';
    document.getElementById('remote-status').textContent = 'Waiting...';
    document.getElementById('remote-status').className = 'connection-status';
    
    // Stop all tracks
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }
    
    // Clear video elements
    document.getElementById('local-video').srcObject = null;
}

// Missing video control functions
let isMaximized = false;
let maximizedVideo = null;

function maximizeVideo(videoType) {
    console.log('⛶ Maximizing video:', videoType);
    
    const videoGrid = document.getElementById('video-grid');
    const localVideoItem = document.getElementById('local-video-item');
    const remoteVideoItem = document.getElementById('remote-video-item');
    
    if (isMaximized && maximizedVideo === videoType) {
        // Restore normal view
        videoGrid.classList.remove('maximized');
        localVideoItem.classList.remove('maximized', 'minimized');
        remoteVideoItem.classList.remove('maximized', 'minimized');
        isMaximized = false;
        maximizedVideo = null;
        console.log('✅ Video restored to normal view');
    } else {
        // Maximize selected video
        videoGrid.classList.add('maximized');
        
        if (videoType === 'local') {
            localVideoItem.classList.add('maximized');
            remoteVideoItem.classList.add('minimized');
        } else {
            remoteVideoItem.classList.add('maximized');
            localVideoItem.classList.add('minimized');
        }
        
        isMaximized = true;
        maximizedVideo = videoType;
        console.log('✅ Video maximized:', videoType);
    }
}

function toggleMaximize() {
    console.log('⛶ Toggle maximize clicked');
    // Default to maximizing remote video
    maximizeVideo(maximizedVideo === 'remote' ? 'local' : 'remote');
}

function toggleAutoCall() {
    console.log('🔗 Toggle auto-call clicked');
    isAutoCallEnabled = !isAutoCallEnabled;
    const toggle = document.getElementById('auto-call-toggle');
    if (toggle) {
        toggle.textContent = isAutoCallEnabled ? '🔗' : '⛓️‍💥';
        toggle.title = isAutoCallEnabled ? 'Auto-call enabled' : 'Auto-call disabled';
    }
    console.log('Auto-call:', isAutoCallEnabled ? 'enabled' : 'disabled');
}

function toggleScreenShare() {
    console.log('🖥️ Toggle screen share clicked');
    if (isScreenSharing) {
        stopScreenShare();
    } else {
        startScreenShare();
    }
}

function startScreenShare() {
    console.log('🖥️ Starting screen share...');
    if (navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
        navigator.mediaDevices.getDisplayMedia({ video: true, audio: true })
            .then(stream => {
                const localVideo = document.getElementById('local-video');
                if (localVideo) {
                    localVideo.srcObject = stream;
                }
                
                // Replace video track in peer connection if active
                if (window.peerConnection && localStream) {
                    const videoTrack = stream.getVideoTracks()[0];
                    const sender = window.peerConnection.getSenders().find(s => 
                        s.track && s.track.kind === 'video'
                    );
                    if (sender) {
                        sender.replaceTrack(videoTrack);
                    }
                }
                
                isScreenSharing = true;
                const btn = document.getElementById('screen-share-btn');
                if (btn) {
                    btn.textContent = '🖥️';
                    btn.title = 'Stop screen share';
                }
                
                // Handle screen share end
                stream.getVideoTracks()[0].addEventListener('ended', () => {
                    stopScreenShare();
                });
                
                console.log('✅ Screen sharing started');
            })
            .catch(error => {
                console.error('❌ Screen share error:', error);
            });
    } else {
        console.error('❌ Screen sharing not supported');
    }
}

function stopScreenShare() {
    console.log('🖥️ Stopping screen share...');
    
    // Get camera stream back
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then(stream => {
                const localVideo = document.getElementById('local-video');
                if (localVideo) {
                    localVideo.srcObject = stream;
                }
                
                // Replace video track in peer connection if active
                if (window.peerConnection) {
                    const videoTrack = stream.getVideoTracks()[0];
                    const sender = window.peerConnection.getSenders().find(s => 
                        s.track && s.track.kind === 'video'
                    );
                    if (sender) {
                        sender.replaceTrack(videoTrack);
                    }
                }
                
                window.localStream = stream;
                console.log('✅ Camera restored');
            })
            .catch(error => {
                console.error('❌ Camera restore error:', error);
            });
    }
    
    isScreenSharing = false;
    const btn = document.getElementById('screen-share-btn');
    if (btn) {
        btn.textContent = '📱';
        btn.title = 'Share screen';
    }
    
    console.log('✅ Screen sharing stopped');
}

function toggleChat() {
    console.log('💬 Toggle chat clicked');
    const chatContainer = document.querySelector('.chat-container');
    const videoContainer = document.querySelector('.video-container');
    
    if (chatContainer && videoContainer) {
        if (chatContainer.style.display === 'none') {
            chatContainer.style.display = 'flex';
            videoContainer.style.width = '70%';
        } else {
            chatContainer.style.display = 'none';
            videoContainer.style.width = '100%';
        }
    }
}

// Global variables
let isScreenSharing = false;
let isAutoCallEnabled = true;

// Load video call fixes
(function() {
    console.log('🔧 Loading video call fixes...');
    
    // Add CSS for maximized video states
    const style = document.createElement('style');
    style.textContent = `
        .video-grid.maximized {
            position: relative;
        }
        
        .video-item.maximized {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 10;
        }
        
        .video-item.minimized {
            position: absolute !important;
            top: 10px !important;
            right: 10px !important;
            width: 150px !important;
            height: 100px !important;
            z-index: 11;
            border: 2px solid #fff;
            border-radius: 8px;
        }
        
        .video-item.minimized video {
            border-radius: 6px;
        }
    `;
    document.head.appendChild(style);
    
    console.log('✅ Video call fixes loaded successfully');
})();

async function initializeVideoChat() {
    try {
        // Check if media devices are supported
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Media devices not supported');
        }
        
        console.log('Requesting camera and microphone access...');
        
        // Request camera and microphone access with better constraints
        localStream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 1280, max: 1920 },
                height: { ideal: 720, max: 1080 },
                frameRate: { ideal: 30, max: 60 }
            },
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
            }
        });
        
        // Display local video
        const localVideo = document.getElementById('local-video');
        if (localVideo) {
            console.log('Setting up local video with stream:', localStream);
            console.log('Local stream tracks:', localStream.getTracks());
            localVideo.srcObject = localStream;
            localVideo.muted = true; // Mute local video to prevent echo
            localVideo.autoplay = true;
            localVideo.playsInline = true;
            localVideo.play().then(() => {
                console.log('Local video started playing');
            }).catch(e => {
                console.log('Local video play error:', e);
                // Try to play again after a short delay
                setTimeout(() => {
                    localVideo.play().catch(err => console.log('Retry play error:', err));
                }, 1000);
            });
        }
        document.getElementById('local-status').textContent = 'Ready';
        document.getElementById('local-status').className = 'connection-status connected';
        
        // Update status
        document.getElementById('video-status').textContent = 'Camera and microphone ready. Auto-connecting when partner joins...';
        
        // Show start call button
        document.getElementById('start-call-btn').disabled = false;
        
    } catch (error) {
        console.error('Error accessing media devices:', error);
        let errorMessage = 'Error: Could not access camera or microphone. ';
        
        if (error.name === 'NotAllowedError') {
            errorMessage += 'Please allow camera and microphone access and refresh the page.';
        } else if (error.name === 'NotFoundError') {
            errorMessage += 'No camera or microphone found. Please check your devices.';
        } else if (error.name === 'NotSupportedError') {
            errorMessage += 'Your browser does not support video calls.';
        } else {
            errorMessage += 'Please check permissions and try again.';
        }
        
        document.getElementById('video-status').textContent = errorMessage;
        document.getElementById('start-call-btn').disabled = true;
    }
}

// Duplicate startVideoCall function removed - using the one defined earlier

// Metered API Configuration
const METERED_API_KEY = '511852cda421697270ed9af8b089038b39a7';
const METERED_API_URL = 'https://skillsxchange.metered.live/api/v1/turn/credentials';

// Fetch TURN server credentials from Metered API
async function fetchTurnCredentials() {
    try {
        console.log('🔄 Fetching TURN server credentials...');
        const response = await fetch(`${METERED_API_URL}?apiKey=${METERED_API_KEY}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const iceServers = await response.json();
        console.log('✅ TURN credentials fetched successfully:', iceServers);
        return iceServers;
    } catch (error) {
        console.error('❌ Error fetching TURN credentials:', error);
        
        // Fallback to basic STUN servers with Metered TURN servers
        return [
            // Google STUN servers (fallback)
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
            // Metered STUN server
            { urls: 'stun:stun.relay.metered.ca:80' },
            // Metered TURN servers with credentials
            {
                urls: 'turn:asia.relay.metered.ca:80',
                username: '0582eeabe15281e17e922394',
                credential: 'g7fjNoaIyTpLnkaf'
            },
            {
                urls: 'turn:asia.relay.metered.ca:80?transport=tcp',
                username: '0582eeabe15281e17e922394',
                credential: 'g7fjNoaIyTpLnkaf'
            },
            {
                urls: 'turn:asia.relay.metered.ca:443',
                username: '0582eeabe15281e17e922394',
                credential: 'g7fjNoaIyTpLnkaf'
            },
            {
                urls: 'turns:asia.relay.metered.ca:443?transport=tcp',
                username: '0582eeabe15281e17e922394',
                credential: 'g7fjNoaIyTpLnkaf'
            }
        ];
    }
}

async function initializePeerConnection() {
    // Fetch fresh TURN server credentials
    const iceServers = await fetchTurnCredentials();
    
    // Create RTCPeerConnection with dynamic TURN server configuration
    const configuration = {
        iceServers: iceServers,
        iceCandidatePoolSize: 10,
        bundlePolicy: 'max-bundle',
        rtcpMuxPolicy: 'require',
        iceTransportPolicy: 'all'
    };
    
    peerConnection = new RTCPeerConnection(configuration);
    
    // Add local stream tracks to peer connection
    localStream.getTracks().forEach(track => {
        peerConnection.addTrack(track, localStream);
    });
    
    // Handle incoming tracks
    peerConnection.ontrack = (event) => {
        console.log('Received remote stream');
        console.log('Remote stream tracks:', event.streams[0].getTracks());
        remoteStream = event.streams[0];
        const remoteVideo = document.getElementById('remote-video');
        if (remoteVideo) {
            remoteVideo.srcObject = remoteStream;
            remoteVideo.autoplay = true;
            remoteVideo.playsInline = true;
            remoteVideo.muted = false; // Allow audio for remote video
            remoteVideo.play().then(() => {
                console.log('Remote video started playing');
            }).catch(e => {
                console.log('Remote video play error:', e);
                // Try to play again after a short delay
                setTimeout(() => {
                    remoteVideo.play().catch(err => console.log('Retry play error:', err));
                }, 1000);
            });
        }
        document.getElementById('remote-status').textContent = 'Connected';
        document.getElementById('remote-status').className = 'connection-status connected';
        document.getElementById('video-status').textContent = 'Call connected! You can now see and hear each other.';
    };
    
    // Handle ICE candidates
    peerConnection.onicecandidate = async (event) => {
        if (event.candidate) {
            console.log('ICE candidate generated:', event.candidate.type, event.candidate.protocol, event.candidate.address);
            // Only send ICE candidates if we have a valid call setup
            if (currentCallId && otherUserId && firebaseVideoCall) {
                // Send ICE candidate via Firebase in background without blocking
                firebaseVideoCall.sendIceCandidate(event.candidate).catch(error => {
                    console.warn('ICE candidate send failed (non-critical):', error);
                });
            } else {
                console.warn('Skipping ICE candidate - call not properly initialized', {
                    currentCallId: currentCallId,
                    otherUserId: otherUserId
                });
            }
        } else {
            console.log('ICE gathering completed');
        }
    };
    
    // Handle connection state changes
    peerConnection.onconnectionstatechange = () => {
        console.log('Connection state:', peerConnection.connectionState);
        if (peerConnection.connectionState === 'connected') {
            document.getElementById('remote-status').textContent = 'Connected';
            document.getElementById('remote-status').className = 'connection-status connected';
            document.getElementById('video-status').textContent = 'Call connected! You can now see and hear each other.';
        } else if (peerConnection.connectionState === 'disconnected') {
            document.getElementById('remote-status').textContent = 'Disconnected';
            document.getElementById('remote-status').className = 'connection-status disconnected';
            document.getElementById('video-status').textContent = 'Connection lost. Attempting to reconnect...';
        } else if (peerConnection.connectionState === 'failed') {
            document.getElementById('remote-status').textContent = 'Connection Failed';
            document.getElementById('remote-status').className = 'connection-status disconnected';
            document.getElementById('video-status').textContent = 'Connection failed. Please try again.';
            // Auto-end call after failure
            setTimeout(() => {
                if (isCallActive) {
                    endVideoCall();
                }
            }, 3000);
        } else if (peerConnection.connectionState === 'connecting') {
            document.getElementById('video-status').textContent = 'Connecting...';
        }
    };
    
    // Handle ICE gathering state changes
    peerConnection.onicegatheringstatechange = () => {
        console.log('ICE gathering state:', peerConnection.iceGatheringState);
        if (peerConnection.iceGatheringState === 'complete') {
            console.log('ICE gathering completed');
        }
    };
    
    // Handle ICE connection state changes
    peerConnection.oniceconnectionstatechange = () => {
        console.log('ICE connection state:', peerConnection.iceConnectionState);
        if (peerConnection.iceConnectionState === 'failed') {
            console.error('ICE connection failed');
            document.getElementById('video-status').textContent = 'Connection failed. Please check your network and try again.';
            // Try to restart ICE gathering
            peerConnection.restartIce();
        } else if (peerConnection.iceConnectionState === 'connected') {
            console.log('ICE connection established');
            document.getElementById('video-status').textContent = 'Call connected! You can now see and hear each other.';
            // Ensure videos are playing
            const localVideo = document.getElementById('local-video');
            const remoteVideo = document.getElementById('remote-video');
            if (localVideo && localVideo.srcObject) {
                localVideo.play().catch(e => console.log('Local video play error:', e));
            }
            if (remoteVideo && remoteVideo.srcObject) {
                remoteVideo.play().catch(e => console.log('Remote video play error:', e));
            }
        } else if (peerConnection.iceConnectionState === 'checking') {
            document.getElementById('video-status').textContent = 'Connecting... Please wait.';
        } else if (peerConnection.iceConnectionState === 'disconnected') {
            document.getElementById('video-status').textContent = 'Connection lost. Attempting to reconnect...';
        }
    };
    
    // Handle ICE gathering state changes
    peerConnection.onicegatheringstatechange = () => {
        console.log('ICE gathering state:', peerConnection.iceGatheringState);
        if (peerConnection.iceGatheringState === 'complete') {
            console.log('ICE gathering completed');
        }
    };
}

// All signaling functions are now handled by Firebase integration

// ICE candidate signaling is now handled by Firebase integration

// Duplicate endVideoCall and cleanupVideoCall functions removed - using the ones defined earlier

// Duplicate handleVideoCallOffer function removed - using the one defined earlier

// Duplicate handleIncomingCall function removed - using the one defined earlier

// Duplicate handleVideoCallAnswer function removed - using the one defined earlier

// All remaining duplicate functions removed - using the ones defined earlier

   // All duplicate functions removed - using the ones defined earlier

// All remaining duplicate functions removed - script section cleaned up


</script>
@endsection
/**
 * Improved Video Call Implementation
 * Enhanced error handling and debugging for WebRTC connections
 */

class VideoCallManager {
    constructor() {
        this.localStream = null;
        this.remoteStream = null;
        this.peerConnection = null;
        this.isInitiator = false;
        this.isCallActive = false;
        this.currentCallId = null;
        this.otherUserId = null;
        this.callStartTime = null;
        this.callTimer = null;
        this.connectionTimeout = null;
        this.retryCount = 0;
        this.maxRetries = 3;
        
        // Metered API Configuration
        this.meteredApiKey = '511852cda421697270ed9af8b089038b39a7';
        this.meteredApiUrl = 'https://skillxchange.metered.live/api/v1/turn/credentials';
        
        // Enhanced STUN/TURN configuration with Metered credentials
        this.iceServers = [
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
        
        this.init();
    }
    
    init() {
        this.log('VideoCallManager initialized', 'info');
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Listen for Pusher events
        if (window.Echo) {
            window.Echo.channel('trade-' + window.tradeId)
                .listen('video-call-offer', (data) => this.handleVideoCallOffer(data))
                .listen('video-call-answer', (data) => this.handleVideoCallAnswer(data))
                .listen('video-call-ice-candidate', (data) => this.handleVideoCallIceCandidate(data))
                .listen('video-call-end', (data) => this.handleVideoCallEnd(data));
        }
    }
    
    async initializeMedia() {
        try {
            this.log('Requesting media access...', 'info');
            
            // Request media with enhanced constraints
            this.localStream = await navigator.mediaDevices.getUserMedia({
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
            
            this.log('Media access granted', 'success');
            return true;
            
        } catch (error) {
            this.log(`Media access failed: ${error.message}`, 'error');
            this.showError('Camera/microphone access denied. Please check permissions and try again.');
            return false;
        }
    }
    
    async fetchTurnCredentials() {
        try {
            this.log('Fetching TURN server credentials...', 'info');
            const response = await fetch(`${this.meteredApiUrl}?apiKey=${this.meteredApiKey}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const iceServers = await response.json();
            this.log('TURN credentials fetched successfully', 'success');
            return iceServers;
        } catch (error) {
            this.log(`Error fetching TURN credentials: ${error.message}`, 'error');
            return this.iceServers; // Return fallback servers
        }
    }

    async initializePeerConnection() {
        try {
            this.log('Initializing peer connection...', 'info');
            
            // Fetch fresh TURN server credentials
            const iceServers = await this.fetchTurnCredentials();
            
            const configuration = {
                iceServers: iceServers,
                iceCandidatePoolSize: 10,
                bundlePolicy: 'max-bundle',
                rtcpMuxPolicy: 'require'
            };
            
            this.peerConnection = new RTCPeerConnection(configuration);
            
            // Add local stream tracks
            if (this.localStream) {
                this.localStream.getTracks().forEach(track => {
                    this.peerConnection.addTrack(track, this.localStream);
                });
            }
            
            // Enhanced event handlers
            this.setupPeerConnectionHandlers();
            
            this.log('Peer connection initialized', 'success');
            return true;
            
        } catch (error) {
            this.log(`Peer connection initialization failed: ${error.message}`, 'error');
            this.showError('Failed to initialize video call. Please try again.');
            return false;
        }
    }
    
    setupPeerConnectionHandlers() {
        // Handle incoming tracks
        this.peerConnection.ontrack = (event) => {
            this.log('Received remote stream', 'success');
            this.remoteStream = event.streams[0];
            this.updateRemoteVideo();
            this.updateConnectionStatus('connected');
        };
        
        // Handle ICE candidates with enhanced logging
        this.peerConnection.onicecandidate = async (event) => {
            if (event.candidate) {
                this.log(`ICE candidate: ${event.candidate.type} ${event.candidate.protocol}`, 'info');
                await this.sendIceCandidate(event.candidate);
            } else {
                this.log('ICE gathering completed', 'success');
            }
        };
        
        // Handle connection state changes
        this.peerConnection.onconnectionstatechange = () => {
            const state = this.peerConnection.connectionState;
            this.log(`Connection state: ${state}`, 'info');
            
            switch (state) {
                case 'connected':
                    this.updateConnectionStatus('connected');
                    this.clearConnectionTimeout();
                    break;
                case 'disconnected':
                    this.updateConnectionStatus('disconnected');
                    break;
                case 'failed':
                    this.handleConnectionFailure();
                    break;
                case 'closed':
                    this.cleanup();
                    break;
            }
        };
        
        // Handle ICE connection state changes
        this.peerConnection.oniceconnectionstatechange = () => {
            const state = this.peerConnection.iceConnectionState;
            this.log(`ICE connection state: ${state}`, 'info');
            
            if (state === 'failed') {
                this.handleConnectionFailure();
            }
        };
        
        // Handle data channel (for future features)
        this.peerConnection.ondatachannel = (event) => {
            const channel = event.channel;
            this.log('Data channel received', 'info');
            
            channel.onopen = () => {
                this.log('Data channel opened', 'success');
            };
            
            channel.onmessage = (event) => {
                this.log(`Data channel message: ${event.data}`, 'info');
            };
        };
    }
    
    async startCall() {
        try {
            this.log('Starting video call...', 'info');
            
            // Initialize media
            const mediaReady = await this.initializeMedia();
            if (!mediaReady) return false;
            
            // Initialize peer connection
            const peerReady = await this.initializePeerConnection();
            if (!peerReady) return false;
            
            // Generate unique call ID
            this.currentCallId = 'call_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            this.isInitiator = true;
            this.otherUserId = window.partnerId;
            
            if (!this.otherUserId) {
                this.log('Partner ID not available', 'error');
                this.showError('Unable to determine partner. Please refresh the page.');
                return false;
            }
            
            // Create and send offer
            const offer = await this.peerConnection.createOffer({
                offerToReceiveAudio: true,
                offerToReceiveVideo: true
            });
            
            await this.peerConnection.setLocalDescription(offer);
            await this.sendVideoCallOffer(offer);
            
            // Update UI
            this.updateCallUI('calling');
            this.startCallTimer();
            this.setConnectionTimeout();
            
            this.isCallActive = true;
            this.log('Call initiated successfully', 'success');
            return true;
            
        } catch (error) {
            this.log(`Error starting call: ${error.message}`, 'error');
            this.showError('Failed to start call. Please try again.');
            return false;
        }
    }
    
    async handleVideoCallOffer(data) {
        try {
            this.log(`Handling call offer from: ${data.fromUserName}`, 'info');
            
            // Set up for incoming call
            this.currentCallId = data.callId;
            this.otherUserId = data.fromUserId;
            this.isInitiator = false;
            
            // Show incoming call notification
            const acceptCall = confirm(`${data.fromUserName} is calling you. Do you want to accept?`);
            
            if (acceptCall) {
                // Initialize media and peer connection
                const mediaReady = await this.initializeMedia();
                if (!mediaReady) return;
                
                const peerReady = await this.initializePeerConnection();
                if (!peerReady) return;
                
                // Set remote description
                await this.peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer));
                
                // Create and send answer
                const answer = await this.peerConnection.createAnswer();
                await this.peerConnection.setLocalDescription(answer);
                await this.sendVideoCallAnswer(answer);
                
                // Update UI
                this.updateCallUI('connected');
                this.startCallTimer();
                
                this.isCallActive = true;
                this.log('Call accepted successfully', 'success');
            } else {
                this.log('Call declined', 'info');
            }
            
        } catch (error) {
            this.log(`Error handling call offer: ${error.message}`, 'error');
            this.showError('Failed to accept call. Please try again.');
        }
    }
    
    async handleVideoCallAnswer(data) {
        try {
            this.log('Handling call answer', 'info');
            await this.peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer));
            this.log('Call answer processed', 'success');
        } catch (error) {
            this.log(`Error handling call answer: ${error.message}`, 'error');
        }
    }
    
    async handleVideoCallIceCandidate(data) {
        try {
            this.log('Handling ICE candidate', 'info');
            await this.peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
            this.log('ICE candidate processed', 'success');
        } catch (error) {
            this.log(`Error handling ICE candidate: ${error.message}`, 'error');
        }
    }
    
    handleVideoCallEnd(data) {
        this.log('Call ended by other party', 'info');
        this.endCall();
    }
    
    async sendVideoCallOffer(offer) {
        try {
            const response = await fetch(`/chat/${window.tradeId}/video-call/offer`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    offer: offer,
                    callId: this.currentCallId
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${await response.text()}`);
            }
            
            this.log('Call offer sent successfully', 'success');
            
        } catch (error) {
            this.log(`Error sending offer: ${error.message}`, 'error');
            this.showError('Failed to send call. Please check your connection.');
        }
    }
    
    async sendVideoCallAnswer(answer) {
        try {
            const response = await fetch(`/chat/${window.tradeId}/video-call/answer`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    answer: answer,
                    callId: this.currentCallId,
                    toUserId: this.otherUserId
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${await response.text()}`);
            }
            
            this.log('Call answer sent successfully', 'success');
            
        } catch (error) {
            this.log(`Error sending answer: ${error.message}`, 'error');
        }
    }
    
    async sendIceCandidate(candidate) {
        try {
            const response = await fetch(`/chat/${window.tradeId}/video-call/ice-candidate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    candidate: candidate,
                    callId: this.currentCallId,
                    toUserId: this.otherUserId
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${await response.text()}`);
            }
            
        } catch (error) {
            this.log(`Error sending ICE candidate: ${error.message}`, 'error');
        }
    }
    
    handleConnectionFailure() {
        this.log('Connection failed, attempting recovery...', 'warning');
        
        if (this.retryCount < this.maxRetries) {
            this.retryCount++;
            this.log(`Retry attempt ${this.retryCount}/${this.maxRetries}`, 'info');
            
            // Attempt to restart ICE
            if (this.peerConnection) {
                this.peerConnection.restartIce();
            }
        } else {
            this.log('Max retries reached, ending call', 'error');
            this.showError('Connection failed after multiple attempts. Please try again.');
            this.endCall();
        }
    }
    
    setConnectionTimeout() {
        this.connectionTimeout = setTimeout(() => {
            if (this.isCallActive && this.peerConnection && this.peerConnection.connectionState !== 'connected') {
                this.log('Connection timeout', 'error');
                this.showError('Connection timeout. Please try again.');
                this.endCall();
            }
        }, 45000); // 45 seconds
    }
    
    clearConnectionTimeout() {
        if (this.connectionTimeout) {
            clearTimeout(this.connectionTimeout);
            this.connectionTimeout = null;
        }
    }
    
    startCallTimer() {
        this.callStartTime = new Date();
        this.callTimer = setInterval(() => {
            this.updateCallTimer();
        }, 1000);
    }
    
    updateCallTimer() {
        if (this.callStartTime) {
            const elapsed = Math.floor((new Date() - this.callStartTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            const timerElement = document.getElementById('call-timer');
            if (timerElement) {
                timerElement.textContent = timeString;
            }
        }
    }
    
    updateCallUI(state) {
        const statusElement = document.getElementById('video-status');
        const startBtn = document.getElementById('start-call-btn');
        const endBtn = document.getElementById('end-call-btn');
        const controls = document.querySelectorAll('.video-control');
        
        switch (state) {
            case 'calling':
                if (statusElement) statusElement.textContent = 'Calling...';
                if (startBtn) startBtn.style.display = 'none';
                if (endBtn) endBtn.style.display = 'flex';
                controls.forEach(btn => btn.style.display = 'flex');
                break;
            case 'connected':
                if (statusElement) statusElement.textContent = 'Call connected!';
                break;
            case 'disconnected':
                if (statusElement) statusElement.textContent = 'Call disconnected';
                break;
        }
    }
    
    updateConnectionStatus(status) {
        const localStatus = document.getElementById('local-status');
        const remoteStatus = document.getElementById('remote-status');
        
        if (localStatus) {
            localStatus.textContent = 'Connected';
            localStatus.className = 'connection-status connected';
        }
        
        if (remoteStatus) {
            remoteStatus.textContent = status === 'connected' ? 'Connected' : 'Disconnected';
            remoteStatus.className = `connection-status ${status}`;
        }
    }
    
    updateRemoteVideo() {
        const remoteVideo = document.getElementById('remote-video');
        if (remoteVideo && this.remoteStream) {
            remoteVideo.srcObject = this.remoteStream;
        }
    }
    
    showError(message) {
        const statusElement = document.getElementById('video-status');
        if (statusElement) {
            statusElement.textContent = message;
            statusElement.style.color = '#dc3545';
        }
        
        // Also show in console for debugging
        this.log(`Error: ${message}`, 'error');
    }
    
    endCall() {
        this.log('Ending video call...', 'info');
        
        // Send end call signal
        if (this.currentCallId) {
            fetch(`/chat/${window.tradeId}/video-call/end`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    callId: this.currentCallId
                })
            }).catch(error => {
                this.log(`Error sending end call signal: ${error.message}`, 'error');
            });
        }
        
        this.cleanup();
    }
    
    cleanup() {
        this.log('Cleaning up video call resources...', 'info');
        
        // Stop media tracks
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
        }
        
        // Close peer connection
        if (this.peerConnection) {
            this.peerConnection.close();
            this.peerConnection = null;
        }
        
        // Clear timers
        if (this.callTimer) {
            clearInterval(this.callTimer);
            this.callTimer = null;
        }
        
        this.clearConnectionTimeout();
        
        // Reset state
        this.isCallActive = false;
        this.isInitiator = false;
        this.currentCallId = null;
        this.otherUserId = null;
        this.callStartTime = null;
        this.retryCount = 0;
        
        // Reset UI
        this.updateCallUI('idle');
        this.updateConnectionStatus('disconnected');
        
        const localVideo = document.getElementById('local-video');
        const remoteVideo = document.getElementById('remote-video');
        
        if (localVideo) localVideo.srcObject = null;
        if (remoteVideo) remoteVideo.srcObject = null;
        
        this.log('Cleanup completed', 'success');
    }
    
    log(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const logMessage = `[${timestamp}] ${message}`;
        
        console.log(logMessage);
        
        // Also log to debug element if available
        const debugLog = document.getElementById('debug-log');
        if (debugLog) {
            const logEntry = document.createElement('div');
            logEntry.textContent = logMessage;
            logEntry.style.color = type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : type === 'warning' ? '#ffc107' : '#333';
            debugLog.appendChild(logEntry);
            debugLog.scrollTop = debugLog.scrollHeight;
        }
    }
}

// Initialize video call manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.videoCallManager = new VideoCallManager();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VideoCallManager;
}


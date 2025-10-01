/**
 * WebSocket-based Video Call Implementation
 * Uses WebSocket for ICE candidate exchange and signaling
 */

class WebSocketVideoCallManager {
    constructor() {
        this.localStream = null;
        this.remoteStream = null;
        this.peerConnection = null;
        this.websocket = null;
        this.isInitiator = false;
        this.isCallActive = false;
        this.currentCallId = null;
        this.otherUserId = null;
        this.callStartTime = null;
        this.callTimer = null;
        this.connectionTimeout = null;
        this.retryCount = 0;
        this.maxRetries = 3;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        
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
        this.log('WebSocketVideoCallManager initialized', 'info');
        this.connectWebSocket();
    }
    
    connectWebSocket() {
        try {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            
            // Determine WebSocket URL based on environment
            let wsUrl;
            
            // Check if we're in production (Render deployment)
            if (window.location.hostname.includes('render.com') || 
                window.location.hostname.includes('onrender.com')) {
                // In production, use the separate WebSocket service
                // The WebSocket service will be available at a different subdomain or port
                // For Render, we'll try the WebSocket service URL
                const baseHostname = window.location.hostname.replace('skillsxchangee-main', 'skillsxchangee-websocket');
                wsUrl = `${protocol}//${baseHostname}`;
                this.log(`Attempting WebSocket connection (production): ${wsUrl}`, 'info');
            } else if (window.location.hostname === 'localhost' || 
                      window.location.hostname.includes('127.0.0.1')) {
                // Development environment
                wsUrl = `${protocol}//${window.location.hostname}:8080`;
                this.log(`Connecting to WebSocket (dev): ${wsUrl}`, 'info');
            } else {
                // Custom domain or other production environment
                wsUrl = `${protocol}//${window.location.hostname}:8080`;
                this.log(`Connecting to WebSocket (custom): ${wsUrl}`, 'info');
            }
            
            this.websocket = new WebSocket(wsUrl);
            
            this.websocket.onopen = () => {
                this.log('WebSocket connected', 'success');
                this.reconnectAttempts = 0;
                this.joinRoom();
            };
            
            this.websocket.onmessage = (event) => {
                this.handleWebSocketMessage(event);
            };
            
            this.websocket.onclose = (event) => {
                this.log(`WebSocket closed: ${event.code} - ${event.reason}`, 'warning');
                this.handleWebSocketDisconnect();
            };
            
            this.websocket.onerror = (error) => {
                this.log(`WebSocket error: ${error}`, 'error');
                // Try alternative connection methods in production
                if (window.location.hostname.includes('render.com') || 
                    window.location.hostname.includes('onrender.com')) {
                    this.tryAlternativeConnection();
                }
            };
            
        } catch (error) {
            this.log(`WebSocket connection failed: ${error.message}`, 'error');
            this.handleWebSocketDisconnect();
        }
    }
    
    tryAlternativeConnection() {
        this.log('Trying alternative WebSocket connection methods...', 'info');
        
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        let alternativeUrl;
        
        if (window.location.hostname.includes('render.com') || 
            window.location.hostname.includes('onrender.com')) {
            // Try different approaches for Render deployment
            const alternatives = [
                // Try the WebSocket service directly
                `${protocol}//skillsxchangee-websocket.onrender.com`,
                // Try with port 8080
                `${protocol}//${window.location.hostname}:8080`,
                // Try same hostname with different subdomain
                `${protocol}//${window.location.hostname.replace('skillsxchangee-main', 'skillsxchangee-websocket')}`
            ];
            
            // Try each alternative
            this.tryConnectionAlternatives(alternatives, 0);
        } else {
            // For other environments, try localhost:8080
            alternativeUrl = `${protocol}//localhost:8080`;
            this.log(`Trying alternative URL: ${alternativeUrl}`, 'info');
            this.trySingleAlternative(alternativeUrl);
        }
    }
    
    tryConnectionAlternatives(alternatives, index) {
        if (index >= alternatives.length) {
            this.log('All alternative connections failed', 'error');
            this.handleWebSocketDisconnect();
            return;
        }
        
        const url = alternatives[index];
        this.log(`Trying alternative ${index + 1}/${alternatives.length}: ${url}`, 'info');
        
        try {
            const altWebSocket = new WebSocket(url);
            
            altWebSocket.onopen = () => {
                this.log(`Alternative connection ${index + 1} successful!`, 'success');
                this.websocket = altWebSocket;
                this.setupWebSocketHandlers();
                this.joinRoom();
            };
            
            altWebSocket.onerror = (error) => {
                this.log(`Alternative ${index + 1} failed, trying next...`, 'warning');
                // Try next alternative after a short delay
                setTimeout(() => {
                    this.tryConnectionAlternatives(alternatives, index + 1);
                }, 1000);
            };
            
        } catch (error) {
            this.log(`Alternative ${index + 1} failed: ${error.message}`, 'error');
            setTimeout(() => {
                this.tryConnectionAlternatives(alternatives, index + 1);
            }, 1000);
        }
    }
    
    trySingleAlternative(url) {
        try {
            const altWebSocket = new WebSocket(url);
            
            altWebSocket.onopen = () => {
                this.log('Alternative WebSocket connection successful!', 'success');
                this.websocket = altWebSocket;
                this.setupWebSocketHandlers();
                this.joinRoom();
            };
            
            altWebSocket.onerror = (error) => {
                this.log('Alternative connection also failed', 'error');
                this.handleWebSocketDisconnect();
            };
            
        } catch (error) {
            this.log(`Alternative connection failed: ${error.message}`, 'error');
            this.handleWebSocketDisconnect();
        }
    }
    
    setupWebSocketHandlers() {
        if (!this.websocket) return;
        
        this.websocket.onmessage = (event) => {
            this.handleWebSocketMessage(event);
        };
        
        this.websocket.onclose = (event) => {
            this.log(`WebSocket closed: ${event.code} - ${event.reason}`, 'warning');
            this.handleWebSocketDisconnect();
        };
        
        this.websocket.onerror = (error) => {
            this.log(`WebSocket error: ${error}`, 'error');
        };
    }

    handleWebSocketDisconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);
            
            this.log(`Attempting to reconnect in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`, 'info');
            
            setTimeout(() => {
                this.connectWebSocket();
            }, delay);
        } else {
            this.log('Max reconnection attempts reached', 'error');
            this.showError('Connection lost. Please refresh the page.');
        }
    }
    
    joinRoom() {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'join',
                userId: window.userId,
                tradeId: window.tradeId
            }));
        }
    }
    
    handleWebSocketMessage(event) {
        try {
            const data = JSON.parse(event.data);
            
            switch (data.type) {
                case 'joined':
                    this.log(`Joined room: ${data.roomId}`, 'success');
                    break;
                case 'offer':
                    this.handleOffer(data);
                    break;
                case 'answer':
                    this.handleAnswer(data);
                    break;
                case 'ice-candidate':
                    this.handleIceCandidate(data);
                    break;
                case 'end-call':
                    this.handleEndCall(data);
                    break;
                case 'error':
                    this.log(`WebSocket error: ${data.message}`, 'error');
                    break;
                default:
                    this.log(`Unknown message type: ${data.type}`, 'warning');
            }
        } catch (error) {
            this.log(`Error parsing WebSocket message: ${error.message}`, 'error');
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
                    autoGainControl: true,
                    sampleRate: 48000
                }
            });
            
            this.log('Media access granted', 'success');
            
            // Display local stream in video element
            const localVideo = document.getElementById('local-video');
            if (localVideo) {
                localVideo.srcObject = this.localStream;
                this.log('Local video stream displayed', 'success');
            } else {
                this.log('Local video element not found!', 'error');
            }
            
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
                rtcpMuxPolicy: 'require',
                iceTransportPolicy: 'all'
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
            this.log(`Stream tracks: ${event.streams[0].getTracks().length}`, 'info');
            
            // Log track details
            event.streams[0].getTracks().forEach((track, index) => {
                this.log(`Track ${index}: ${track.kind} - enabled: ${track.enabled}, muted: ${track.muted}`, 'info');
            });
            
            this.remoteStream = event.streams[0];
            this.updateRemoteVideo();
            this.updateConnectionStatus('connected');
        };
        
        // Handle ICE candidates with enhanced logging
        this.peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                this.log(`ICE candidate: ${event.candidate.type} ${event.candidate.protocol}`, 'info');
                this.sendIceCandidate(event.candidate);
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
            } else if (state === 'connected' || state === 'completed') {
                this.updateConnectionStatus('connected');
            }
        };
        
        // Handle ICE gathering state changes
        this.peerConnection.onicegatheringstatechange = () => {
            const state = this.peerConnection.iceGatheringState;
            this.log(`ICE gathering state: ${state}`, 'info');
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
            
            // Check WebSocket connection
            if (!this.websocket || this.websocket.readyState !== WebSocket.OPEN) {
                this.log('WebSocket not connected, attempting to reconnect...', 'warning');
                this.connectWebSocket();
                await this.waitForWebSocketConnection();
            }
            
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
            this.sendOffer(offer);
            
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
    
    async waitForWebSocketConnection() {
        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => {
                reject(new Error('WebSocket connection timeout'));
            }, 10000);
            
            const checkConnection = () => {
                if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                    clearTimeout(timeout);
                    resolve();
                } else {
                    setTimeout(checkConnection, 100);
                }
            };
            
            checkConnection();
        });
    }
    
    async handleOffer(data) {
        try {
            this.log(`Handling call offer from: ${data.fromUserId}`, 'info');
            
            // Set up for incoming call
            this.currentCallId = data.callId;
            this.otherUserId = data.fromUserId;
            this.isInitiator = false;
            
            // Show incoming call notification using the notification service
            this.showIncomingCallNotification(data.fromUserId);
            
            // Auto-accept for now (you can modify this behavior)
            // In a real app, you'd want to show a proper UI with accept/decline buttons
            const acceptCall = true; // For debugging - auto-accept
            
            if (acceptCall) {
                // Initialize media and peer connection
                const mediaReady = await this.initializeMedia();
                if (!mediaReady) return;
                
                const peerReady = await this.initializePeerConnection();
                if (!peerReady) return;
                
                // Set remote description
                await this.peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer));
                
                // Create and send answer
                this.log('Creating answer...', 'info');
                const answer = await this.peerConnection.createAnswer();
                this.log('Answer created successfully', 'success');
                
                await this.peerConnection.setLocalDescription(answer);
                this.log('Local description set for answer', 'success');
                
                this.sendAnswer(answer);
                this.log('Answer sent via WebSocket', 'success');
                
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
    
    async handleAnswer(data) {
        try {
            this.log(`📞 Received answer from: ${data.fromUserId}`, 'info');
            this.log(`Answer data: ${JSON.stringify(data.answer, null, 2)}`, 'info');
            
            await this.peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer));
            this.log('✅ Call answer processed successfully', 'success');
            
            // Update UI to show connection
            this.updateCallUI('connected');
            
        } catch (error) {
            this.log(`❌ Error handling call answer: ${error.message}`, 'error');
            this.showError('Failed to process call answer. Please try again.');
        }
    }
    
    async handleIceCandidate(data) {
        try {
            this.log('Handling ICE candidate', 'info');
            await this.peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
            this.log('ICE candidate processed', 'success');
        } catch (error) {
            this.log(`Error handling ICE candidate: ${error.message}`, 'error');
        }
    }
    
    handleEndCall(data) {
        this.log('Call ended by other party', 'info');
        this.endCall();
    }
    
    sendOffer(offer) {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'offer',
                toUserId: this.otherUserId,
                offer: offer,
                callId: this.currentCallId
            }));
            this.log('Call offer sent via WebSocket', 'success');
        } else {
            this.log('WebSocket not connected, cannot send offer', 'error');
        }
    }
    
    sendAnswer(answer) {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'answer',
                toUserId: this.otherUserId,
                answer: answer,
                callId: this.currentCallId
            }));
            this.log('Call answer sent via WebSocket', 'success');
        } else {
            this.log('WebSocket not connected, cannot send answer', 'error');
        }
    }
    
    sendIceCandidate(candidate) {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'ice-candidate',
                toUserId: this.otherUserId,
                candidate: candidate,
                callId: this.currentCallId
            }));
        } else {
            this.log('WebSocket not connected, cannot send ICE candidate', 'error');
        }
    }
    
    sendEndCall() {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'end-call',
                toUserId: this.otherUserId,
                callId: this.currentCallId
            }));
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
            case 'idle':
                if (statusElement) statusElement.textContent = 'Ready to call';
                if (startBtn) startBtn.style.display = 'flex';
                if (endBtn) endBtn.style.display = 'none';
                controls.forEach(btn => btn.style.display = 'none');
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
            this.log('Remote video stream displayed', 'success');
            
            // Add event listeners for debugging
            remoteVideo.onloadedmetadata = () => {
                this.log('Remote video metadata loaded', 'success');
            };
            
            remoteVideo.oncanplay = () => {
                this.log('Remote video can start playing', 'success');
            };
            
            remoteVideo.onerror = (error) => {
                this.log(`Remote video error: ${error}`, 'error');
            };
        } else {
            this.log(`Remote video setup failed - Element: ${!!remoteVideo}, Stream: ${!!this.remoteStream}`, 'error');
        }
    }
    
    showIncomingCallNotification(fromUserId) {
        this.log(`📞 Incoming call from: ${fromUserId}`, 'info');
        
        // Show notification using the notification service if available
        if (window.notificationService) {
            window.notificationService.showIncomingCallNotification(
                `User ${fromUserId}`,
                fromUserId,
                this.currentCallId
            );
        }
        
        // Also show a simple alert for debugging
        alert(`📞 Incoming video call from User ${fromUserId}\n\nThis call will be auto-accepted for testing.`);
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
        
        // Send end call signal via WebSocket
        this.sendEndCall();
        
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
    window.websocketVideoCallManager = new WebSocketVideoCallManager();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WebSocketVideoCallManager;
}

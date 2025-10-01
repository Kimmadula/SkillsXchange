/**
 * Simple WebRTC Wrapper for SkillsXchangee
 * Handles WebRTC state management and signaling properly
 */

class SimpleWebRTC {
    constructor(options = {}) {
        this.localStream = null;
        this.remoteStream = null;
        this.peerConnection = null;
        this.isInitiator = false;
        this.isConnected = false;
        this.callId = null;
        this.partnerId = null;
        
        // Configuration
        this.config = {
            iceServers: options.iceServers || [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ],
            iceCandidatePoolSize: 10,
            bundlePolicy: 'max-bundle',
            rtcpMuxPolicy: 'require',
            iceTransportPolicy: 'all'
        };
        
        // Callbacks
        this.onLocalStream = options.onLocalStream || (() => {});
        this.onRemoteStream = options.onRemoteStream || (() => {});
        this.onConnectionStateChange = options.onConnectionStateChange || (() => {});
        this.onError = options.onError || (() => {});
        this.onLog = options.onLog || (() => {});
    }
    
    log(message, type = 'info') {
        console.log(`[SimpleWebRTC] ${message}`);
        this.onLog(message, type);
    }
    
    async startCall(partnerId, callId) {
        try {
            this.log('Starting video call...');
            this.partnerId = partnerId;
            this.callId = callId;
            this.isInitiator = true;
            
            // Get user media
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: { width: 1280, height: 720 },
                audio: { echoCancellation: true, noiseSuppression: true }
            });
            
            this.onLocalStream(this.localStream);
            this.log('Local stream obtained');
            
            // Create peer connection
            await this.createPeerConnection();
            
            // Add local stream to peer connection
            this.localStream.getTracks().forEach(track => {
                this.peerConnection.addTrack(track, this.localStream);
            });
            
            // Create offer
            const offer = await this.peerConnection.createOffer();
            await this.peerConnection.setLocalDescription(offer);
            
            this.log('Offer created and set as local description');
            
            // Send offer via your signaling method
            await this.sendOffer(offer);
            
            this.log('Video call initiated successfully');
            return true;
            
        } catch (error) {
            this.log(`Error starting call: ${error.message}`, 'error');
            this.onError(error);
            return false;
        }
    }
    
    async answerCall(partnerId, callId, offer) {
        try {
            this.log('Answering video call...');
            this.partnerId = partnerId;
            this.callId = callId;
            this.isInitiator = false;
            
            // Get user media
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: { width: 1280, height: 720 },
                audio: { echoCancellation: true, noiseSuppression: true }
            });
            
            this.onLocalStream(this.localStream);
            this.log('Local stream obtained');
            
            // Create peer connection
            await this.createPeerConnection();
            
            // Add local stream to peer connection
            this.localStream.getTracks().forEach(track => {
                this.peerConnection.addTrack(track, this.localStream);
            });
            
            // Set remote description (the offer)
            await this.peerConnection.setRemoteDescription(offer);
            this.log('Remote description set');
            
            // Create answer
            const answer = await this.peerConnection.createAnswer();
            await this.peerConnection.setLocalDescription(answer);
            
            this.log('Answer created and set as local description');
            
            // Send answer via your signaling method
            await this.sendAnswer(answer);
            
            this.log('Video call answered successfully');
            return true;
            
        } catch (error) {
            this.log(`Error answering call: ${error.message}`, 'error');
            this.onError(error);
            return false;
        }
    }
    
    async createPeerConnection() {
        this.log('Creating peer connection...');
        
        this.peerConnection = new RTCPeerConnection(this.config);
        
        // Handle ICE candidates
        this.peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                this.log('ICE candidate generated');
                this.sendIceCandidate(event.candidate);
            }
        };
        
        // Handle remote stream
        this.peerConnection.ontrack = (event) => {
            this.log('Remote stream received');
            this.remoteStream = event.streams[0];
            this.onRemoteStream(this.remoteStream);
        };
        
        // Handle connection state changes
        this.peerConnection.onconnectionstatechange = () => {
            const state = this.peerConnection.connectionState;
            this.log(`Connection state: ${state}`);
            this.onConnectionStateChange(state);
            
            if (state === 'connected') {
                this.isConnected = true;
                this.log('Call connected successfully!');
            } else if (state === 'failed') {
                this.isConnected = false;
                this.log('Connection failed', 'error');
            }
        };
        
        // Handle ICE connection state changes
        this.peerConnection.oniceconnectionstatechange = () => {
            const state = this.peerConnection.iceConnectionState;
            this.log(`ICE connection state: ${state}`);
            
            if (state === 'failed') {
                this.log('ICE connection failed, attempting restart...', 'warning');
                this.peerConnection.restartIce();
            }
        };
        
        this.log('Peer connection created successfully');
    }
    
    async handleOffer(offer) {
        try {
            this.log('Handling incoming offer...');
            
            if (!this.peerConnection) {
                await this.createPeerConnection();
            }
            
            await this.peerConnection.setRemoteDescription(offer);
            this.log('Remote offer set successfully');
            
        } catch (error) {
            this.log(`Error handling offer: ${error.message}`, 'error');
            this.onError(error);
        }
    }
    
    async handleAnswer(answer) {
        try {
            this.log('Handling incoming answer...');
            
            if (!this.peerConnection) {
                this.log('No peer connection to handle answer', 'error');
                return;
            }
            
            await this.peerConnection.setRemoteDescription(answer);
            this.log('Remote answer set successfully');
            
        } catch (error) {
            this.log(`Error handling answer: ${error.message}`, 'error');
            this.onError(error);
        }
    }
    
    async handleIceCandidate(candidate) {
        try {
            this.log('Handling ICE candidate...');
            
            if (!this.peerConnection) {
                this.log('No peer connection to handle ICE candidate', 'error');
                return;
            }
            
            await this.peerConnection.addIceCandidate(candidate);
            this.log('ICE candidate added successfully');
            
        } catch (error) {
            this.log(`Error handling ICE candidate: ${error.message}`, 'error');
            this.onError(error);
        }
    }
    
    endCall() {
        this.log('Ending video call...');
        
        // Stop local stream
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
        }
        
        // Close peer connection
        if (this.peerConnection) {
            this.peerConnection.close();
            this.peerConnection = null;
        }
        
        // Reset state
        this.isConnected = false;
        this.isInitiator = false;
        this.callId = null;
        this.partnerId = null;
        this.remoteStream = null;
        
        this.log('Call ended');
    }
    
    // These methods should be implemented based on your signaling system
    async sendOffer(offer) {
        // Implement your offer sending logic here
        // Example: Send via Pusher, WebSocket, or HTTP
        this.log('Sending offer...');
        // await fetch('/api/send-offer', { method: 'POST', body: JSON.stringify({ offer }) });
    }
    
    async sendAnswer(answer) {
        // Implement your answer sending logic here
        this.log('Sending answer...');
        // await fetch('/api/send-answer', { method: 'POST', body: JSON.stringify({ answer }) });
    }
    
    async sendIceCandidate(candidate) {
        // Implement your ICE candidate sending logic here
        this.log('Sending ICE candidate...');
        // await fetch('/api/send-ice-candidate', { method: 'POST', body: JSON.stringify({ candidate }) });
    }
    
    // Utility methods
    toggleMute() {
        if (this.localStream) {
            const audioTrack = this.localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled;
                this.log(`Audio ${audioTrack.enabled ? 'unmuted' : 'muted'}`);
                return !audioTrack.enabled;
            }
        }
        return false;
    }
    
    toggleVideo() {
        if (this.localStream) {
            const videoTrack = this.localStream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.enabled = !videoTrack.enabled;
                this.log(`Video ${videoTrack.enabled ? 'enabled' : 'disabled'}`);
                return !videoTrack.enabled;
            }
        }
        return false;
    }
    
    getConnectionState() {
        return this.peerConnection ? this.peerConnection.connectionState : 'disconnected';
    }
    
    isCallActive() {
        return this.isConnected && this.peerConnection && this.peerConnection.connectionState === 'connected';
    }
}

// Export for use in your application
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SimpleWebRTC;
} else {
    window.SimpleWebRTC = SimpleWebRTC;
}

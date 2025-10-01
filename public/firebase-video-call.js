/**
 * Firebase Video Call Service for SkillsXchangee
 * Replaces WebSocket signaling with Firebase Realtime Database
 */

import { initializeApp } from 'firebase/app';
import { getDatabase, ref, set, onValue, off, remove, push } from 'firebase/database';
import { firebaseConfig } from './firebase-config.js';

class FirebaseVideoCall {
    constructor(options = {}) {
        this.localStream = null;
        this.remoteStream = null;
        this.peerConnection = null;
        this.isInitiator = false;
        this.isConnected = false;
        this.callId = null;
        this.partnerId = null;
        this.userId = options.userId || null;
        this.tradeId = options.tradeId || null;
        
        // Firebase setup
        this.app = initializeApp(firebaseConfig);
        this.database = getDatabase(this.app);
        this.roomRef = null;
        this.callRef = null;
        
        // WebRTC Configuration
        this.config = {
            iceServers: options.iceServers || [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' },
                { urls: 'stun:stun.relay.metered.ca:80' },
                {
                    urls: 'turn:asia.relay.metered.ca:80',
                    username: '0582eeabe15281e17e922394',
                    credential: 'g7fjNoaIyTpLnkaf'
                }
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
        this.onCallReceived = options.onCallReceived || (() => {});
        this.onCallEnded = options.onCallEnded || (() => {});
    }
    
    log(message, type = 'info') {
        console.log(`[FirebaseVideoCall] ${message}`);
        this.onLog(message, type);
    }
    
    // Initialize Firebase room
    async initializeRoom(userId, tradeId) {
        try {
            this.userId = userId;
            this.tradeId = tradeId;
            this.roomRef = ref(this.database, `rooms/trade_${tradeId}`);
            
            // Join the room
            await this.joinRoom();
            this.log(`Joined room: trade_${tradeId}`, 'success');
            return true;
        } catch (error) {
            this.log(`Error initializing room: ${error.message}`, 'error');
            this.onError(error);
            return false;
        }
    }
    
    // Join Firebase room
    async joinRoom() {
        const userRef = ref(this.database, `rooms/trade_${this.tradeId}/users/${this.userId}`);
        await set(userRef, {
            userId: this.userId,
            status: 'online',
            joinedAt: Date.now()
        });
        
        // Listen for call events
        this.setupCallListeners();
    }
    
    // Setup Firebase listeners for call events
    setupCallListeners() {
        const callsRef = ref(this.database, `rooms/trade_${this.tradeId}/calls`);
        
        onValue(callsRef, (snapshot) => {
            const calls = snapshot.val();
            if (!calls) return;
            
            // Check for incoming calls
            Object.keys(calls).forEach(callId => {
                const call = calls[callId];
                
                // Handle incoming offer
                if (call.toUserId === this.userId && call.type === 'offer' && !this.isInitiator) {
                    this.log('Incoming call received', 'info');
                    this.onCallReceived(call);
                }
                
                // Handle incoming answer
                if (call.fromUserId === this.userId && call.type === 'answer' && this.isInitiator) {
                    this.log('Call answered', 'info');
                    this.handleAnswer(call.answer);
                }
                
                // Handle ICE candidates
                if (call.type === 'ice-candidate') {
                    if ((call.toUserId === this.userId && call.fromUserId !== this.userId) ||
                        (call.fromUserId === this.userId && call.toUserId !== this.userId)) {
                        this.handleIceCandidate(call.candidate);
                    }
                }
                
                // Handle call end
                if (call.type === 'end-call' && 
                    (call.toUserId === this.userId || call.fromUserId === this.userId)) {
                    this.log('Call ended by partner', 'info');
                    this.endCall();
                    this.onCallEnded();
                }
            });
        });
    }
    
    // Start a video call
    async startCall(partnerId) {
        try {
            this.log('Starting video call...');
            this.partnerId = partnerId;
            this.callId = `call_${Date.now()}_${this.userId}`;
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
            
            // Send offer via Firebase
            await this.sendOffer(offer);
            
            this.log('Video call initiated successfully');
            return true;
            
        } catch (error) {
            this.log(`Error starting call: ${error.message}`, 'error');
            this.onError(error);
            return false;
        }
    }
    
    // Answer a video call
    async answerCall(offer) {
        try {
            this.log('Answering video call...');
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
            
            // Send answer via Firebase
            await this.sendAnswer(answer);
            
            this.log('Video call answered successfully');
            return true;
            
        } catch (error) {
            this.log(`Error answering call: ${error.message}`, 'error');
            this.onError(error);
            return false;
        }
    }
    
    // Create WebRTC peer connection
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
    
    // Send offer via Firebase
    async sendOffer(offer) {
        const callRef = ref(this.database, `rooms/trade_${this.tradeId}/calls/${this.callId}`);
        await set(callRef, {
            type: 'offer',
            fromUserId: this.userId,
            toUserId: this.partnerId,
            offer: offer,
            callId: this.callId,
            timestamp: Date.now()
        });
        this.log('Offer sent via Firebase');
    }
    
    // Send answer via Firebase
    async sendAnswer(answer) {
        const callRef = ref(this.database, `rooms/trade_${this.tradeId}/calls/${this.callId}`);
        await set(callRef, {
            type: 'answer',
            fromUserId: this.userId,
            toUserId: this.partnerId,
            answer: answer,
            callId: this.callId,
            timestamp: Date.now()
        });
        this.log('Answer sent via Firebase');
    }
    
    // Send ICE candidate via Firebase
    async sendIceCandidate(candidate) {
        const callRef = ref(this.database, `rooms/trade_${this.tradeId}/calls/${this.callId}_ice_${Date.now()}`);
        await set(callRef, {
            type: 'ice-candidate',
            fromUserId: this.userId,
            toUserId: this.partnerId,
            candidate: candidate,
            callId: this.callId,
            timestamp: Date.now()
        });
        this.log('ICE candidate sent via Firebase');
    }
    
    // Handle incoming offer
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
    
    // Handle incoming answer
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
    
    // Handle ICE candidate
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
    
    // End the call
    async endCall() {
        this.log('Ending video call...');
        
        // Send end call signal via Firebase
        if (this.callId && this.partnerId) {
            const callRef = ref(this.database, `rooms/trade_${this.tradeId}/calls/${this.callId}_end`);
            await set(callRef, {
                type: 'end-call',
                fromUserId: this.userId,
                toUserId: this.partnerId,
                callId: this.callId,
                timestamp: Date.now()
            });
        }
        
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
    
    // Cleanup Firebase listeners
    cleanup() {
        if (this.roomRef) {
            off(this.roomRef);
        }
        this.endCall();
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
    module.exports = FirebaseVideoCall;
} else {
    window.FirebaseVideoCall = FirebaseVideoCall;
}

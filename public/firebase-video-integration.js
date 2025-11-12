/**
 * Firebase Video Call Integration for SkillsXchangee
 * Replaces WebSocket/Pusher with Firebase Realtime Database
 */

class FirebaseVideoIntegration {
    constructor(options = {}) {
        this.userId = options.userId;
        this.tradeId = options.tradeId;
        this.partnerId = options.partnerId;
        this.callId = null;
        this.isInitiator = false;
        this.isConnected = false;
        this.isActive = false;
        this.isEndingCall = false; // Guard flag to prevent infinite recursion
        
        // Firebase setup
        this.app = null;
        this.database = null;
        this.roomRef = null;
        this.callRef = null;
        this.callsListenerCallback = null; // Store callback function for cleanup
        this.usersListenerCallback = null; // Store callback function for cleanup
        
        // WebRTC state
        this.localStream = null;
        this.remoteStream = null;
        this.peerConnection = null;
        this.startTime = null;
        this.timer = null;
        // Buffer for remote ICE candidates that arrive before remote description is set
        this.pendingRemoteCandidates = [];
        
        // Callbacks
        this.onCallReceived = options.onCallReceived || (() => {});
        this.onCallAnswered = options.onCallAnswered || (() => {});
        this.onCallEnded = options.onCallEnded || (() => {});
        this.onConnectionStateChange = options.onConnectionStateChange || (() => {});
        this.onError = options.onError || (() => {});
        this.onLog = options.onLog || (() => {});
        this.onStatusUpdate = options.onStatusUpdate || (() => {});
        this.onParticipantUpdate = options.onParticipantUpdate || (() => {});
        
        // WebRTC Configuration with updated TURN servers
        this.config = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' },
                { urls: 'stun:stun.relay.metered.ca:80' },
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
            ],
            iceCandidatePoolSize: 10,
            bundlePolicy: 'max-bundle',
            rtcpMuxPolicy: 'require',
            iceTransportPolicy: 'all'
        };
    }
    
    log(message, type = 'info') {
        console.log(`[FirebaseVideoIntegration] ${message}`);
        this.onLog(message, type);
    }
    
    // Initialize Firebase
    async initialize() {
        try {
            this.log('🔥 Initializing Firebase video integration...');
            
            // Check if Firebase is available globally
            if (typeof firebase === 'undefined') {
                throw new Error('Firebase SDK not loaded. Please include Firebase CDN scripts.');
            }
            
            // Get Firebase config from global variable
            const firebaseConfig = window.firebaseConfig;
            if (!firebaseConfig) {
                throw new Error('Firebase configuration not found. Please ensure firebase-config.js is loaded.');
            }
            
            // Initialize Firebase (check if already exists)
            try {
                this.app = firebase.app();
                this.log('Using existing Firebase app');
            } catch (error) {
                // Check if the error is about duplicate app
                if (error.code === 'app/duplicate-app') {
                    this.app = firebase.app();
                    this.log('Using existing Firebase app (duplicate resolved)');
                } else {
                    this.app = firebase.initializeApp(firebaseConfig);
                    this.log('Created new Firebase app');
                }
            }
            this.database = firebase.database();
            
            // Create a unique room for this trade with only 2 users
            this.roomId = `trade_${this.tradeId}_${Math.min(this.userId, this.partnerId)}_${Math.max(this.userId, this.partnerId)}`;
            this.roomRef = this.database.ref(`video_rooms/${this.roomId}`);
            
            // Join the room
            await this.joinRoom();
            
            // Setup Firebase listeners
            this.setupFirebaseListeners();
            
            this.log(`✅ Firebase video integration initialized successfully for room: ${this.roomId}`);
            return true;
            
        } catch (error) {
            this.log(`❌ Firebase initialization error: ${error.message}`, 'error');
            this.onError(error);
            return false;
        }
    }
    
    // Join Firebase room
    async joinRoom() {
        // Add user to the room with validation
        const userRef = this.roomRef.child(`users/${this.userId}`);
        await userRef.set({
            userId: this.userId,
            status: 'online',
            joinedAt: Date.now(),
            lastSeen: Date.now()
        });
        
        // Set up room metadata
        const roomMetaRef = this.roomRef.child('metadata');
        await roomMetaRef.set({
            tradeId: this.tradeId,
            user1: Math.min(this.userId, this.partnerId),
            user2: Math.max(this.userId, this.partnerId),
            createdAt: Date.now(),
            maxUsers: 2
        });
        
        this.log(`Joined room: ${this.roomId}`);
        
        // Set up user presence cleanup
        this.setupPresenceCleanup();
    }
    
    // Setup Firebase listeners for call events
    setupFirebaseListeners() {
        // Prevent duplicate listeners - if already attached, detach first
        if (this.callsListenerCallback && this.roomRef) {
            this.log('⚠️ Listeners already attached, detaching old ones first...');
            this.detachCallListeners();
        }
        
        const callsRef = this.roomRef.child('calls');
        const initializationTime = Date.now();
        
        // Track which calls we've already processed to avoid duplicates
        const processedCalls = new Set();
        
        // Store the callback function so we can detach it later
        this.callsListenerCallback = (snapshot) => {
            const calls = snapshot.val();
            if (!calls) return;
            
            // Check for incoming calls - only process new ones (created after initialization)
            Object.keys(calls).forEach(callId => {
                const call = calls[callId];
                
                // Skip very old calls (older than 5 minutes) to prevent processing stale data
                const callAge = Date.now() - (call.timestamp || 0);
                if (callAge > 300000) { // 5 minutes
                    return;
                }
                
                // Skip if we've already processed this call
                if (processedCalls.has(callId)) {
                    return;
                }
                
                // Handle incoming offer - only for new offers after initialization
                if (call.toUserId === this.userId && call.type === 'offer' && !this.isInitiator) {
                    // Only process if this is a new offer (timestamped after initialization or within last 10 seconds)
                    if (call.timestamp && call.timestamp > (initializationTime - 10000)) {
                        this.log('📞 Incoming call received');
                        this.handleIncomingCall(call);
                        processedCalls.add(callId);
                    }
                }
                
                // Handle incoming answer - initiator should accept partner's answer
                // Accept answers that match our current callId and are addressed to us
                if (call.type === 'answer' && this.isInitiator && this.isActive) {
                    if (call.callId === this.callId && call.toUserId === this.userId && call.answer) {
                        this.log('📞 Call answered');
                        // Ensure answer is properly formatted
                        let answerData = call.answer;
                        if (typeof answerData === 'object' && answerData.type && answerData.sdp) {
                            // Convert to RTCSessionDescription if needed
                            if (!(answerData instanceof RTCSessionDescription)) {
                                answerData = new RTCSessionDescription(answerData);
                            }
                            this.handleCallAnswer(answerData);
                            processedCalls.add(callId);
                        } else {
                            this.log('⚠️ Answer missing type or sdp', 'warning');
                        }
                    }
                }
                
                // Handle ICE candidates - only if we have an active call and matching call ID
                if (call.type === 'ice-candidate' && this.isActive && call.callId === this.callId) {
                    if ((call.toUserId === this.userId && call.fromUserId !== this.userId) ||
                        (call.fromUserId === this.userId && call.toUserId !== this.userId)) {
                        // Only process ICE candidates created after call started
                        if (call.timestamp && call.timestamp > this.startTime) {
                            processedCalls.add(callId);
                            this.handleIceCandidate(call.candidate);
                        }
                    }
                }
                
                // Handle call end - only for active calls
                // IMPORTANT: Only handle if the call was ended by the partner (not by us)
                // This prevents infinite recursion when we end the call ourselves
                if (call.type === 'end-call' && this.isActive && !this.isEndingCall &&
                    (call.toUserId === this.userId || call.fromUserId === this.userId)) {
                    // Only process if it's for the current call AND it was ended by the partner
                    if ((!call.callId || call.callId === this.callId) && call.fromUserId !== this.userId) {
                        this.log('📞 Call ended by partner');
                        this.handleCallEnd();
                        processedCalls.add(callId);
                    }
                }
            });
            
            // Clean up old processed calls (keep only last 100 to prevent memory leak)
            if (processedCalls.size > 100) {
                const entries = Array.from(processedCalls);
                processedCalls.clear();
                entries.slice(-50).forEach(id => processedCalls.add(id));
            }
        };
        
        // Attach the listener
        callsRef.on('value', this.callsListenerCallback);
        
        // Listen for user presence changes
        const usersRef = this.roomRef.child('users');
        this.usersListenerCallback = (snapshot) => {
            const users = snapshot.val();
            if (users) {
                const userCount = Object.keys(users).length;
                this.log(`👥 Room has ${userCount} users`);
                this.onParticipantUpdate?.(users);
            }
        };
        
        // Attach the listener
        usersRef.on('value', this.usersListenerCallback);
    }
    
    // Start a video call
    async startCall(partnerId) {
        try {
            this.log('📞 Starting video call...');
            this.partnerId = partnerId;
            this.callId = `call_${Date.now()}_${this.userId}`;
            this.isInitiator = true;
            this.startTime = Date.now(); // Track when call started for filtering old ICE candidates
            
            // Update status
            this.onStatusUpdate?.('Getting camera access...');
            
            // Get user media
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: { width: 1280, height: 720 },
                audio: { echoCancellation: true, noiseSuppression: true }
            });
            
            this.log('✅ Local stream obtained');
            this.onStatusUpdate?.('Setting up connection...');
            
            // Create peer connection
            await this.createPeerConnection();
            
            // Add local stream to peer connection
            this.localStream.getTracks().forEach(track => {
                this.peerConnection.addTrack(track, this.localStream);
            });
            
            // Create offer
            this.onStatusUpdate?.('Creating offer...');
            const offer = await this.peerConnection.createOffer();
            await this.peerConnection.setLocalDescription(offer);
            
            this.log('✅ Offer created');
            this.onStatusUpdate?.('Sending offer...');
            
            // Send offer via Firebase
            await this.sendOffer(offer);
            
            this.isActive = true;
            this.startCallTimer();
            
            this.log('✅ Video call initiated successfully');
            this.onStatusUpdate?.('Call initiated, waiting for answer...');
            return true;
            
        } catch (error) {
            this.log(`❌ Error starting call: ${error.message}`, 'error');
            this.onStatusUpdate?.(`Error: ${error.message}`);
            this.onError(error);
            return false;
        }
    }
    
    // Answer a video call
    async answerCall(offer) {
        try {
            this.log('📞 Answering video call...');
            this.isInitiator = false;
            this.startTime = Date.now(); // Track when call started for filtering old ICE candidates
            
            // Get user media
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: { width: 1280, height: 720 },
                audio: { echoCancellation: true, noiseSuppression: true }
            });
            
            this.log('✅ Local stream obtained');
            
            // Create peer connection
            await this.createPeerConnection();
            
            // Add local stream to peer connection
            this.localStream.getTracks().forEach(track => {
                this.peerConnection.addTrack(track, this.localStream);
            });
            
            // Set remote description
            // Ensure offer is an RTCSessionDescription
            let rtcOffer;
            if (offer instanceof RTCSessionDescription) {
                rtcOffer = offer;
            } else if (typeof offer === 'object' && offer.type && offer.sdp) {
                rtcOffer = new RTCSessionDescription(offer);
            } else {
                throw new Error('Invalid offer format: must be RTCSessionDescription or object with type and sdp');
            }
            
            await this.peerConnection.setRemoteDescription(rtcOffer);
            this.log('✅ Remote description set');
            
            // Create answer
            const answer = await this.peerConnection.createAnswer();
            await this.peerConnection.setLocalDescription(answer);
            
            this.log('✅ Answer created');
            
            // Send answer via Firebase
            await this.sendAnswer(answer);
            
            // Process any pending remote ICE candidates that arrived before remote description
            if (this.pendingRemoteCandidates.length > 0) {
                this.log(`🔄 Processing ${this.pendingRemoteCandidates.length} buffered ICE candidates (callee)...`);
                for (const candidate of this.pendingRemoteCandidates) {
                    try {
                        await this.peerConnection.addIceCandidate(candidate);
                        this.log('✅ Buffered ICE candidate processed (callee)');
                    } catch (e) {
                        this.log(`❌ Error processing buffered ICE candidate (callee): ${e.message}`, 'error');
                    }
                }
                this.pendingRemoteCandidates = [];
                this.log('✅ All buffered ICE candidates processed (callee)');
            }

            this.isActive = true;
            this.startCallTimer();
            
            this.log('✅ Video call answered successfully');
            return true;
            
        } catch (error) {
            this.log(`❌ Error answering call: ${error.message}`, 'error');
            this.onError(error);
            return false;
        }
    }
    
    // Create WebRTC peer connection
    async createPeerConnection() {
        this.log('🔗 Creating peer connection...');
        
        // Reset remote stream notification flag when creating new connection
        this.remoteStreamNotified = false;
        
        this.peerConnection = new RTCPeerConnection(this.config);
        
        // Handle ICE candidates
        this.peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                this.log('📡 ICE candidate generated');
                this.sendIceCandidate(event.candidate);
            }
        };
        
        // Handle remote stream
        // Track if we've already notified about remote stream to prevent duplicate callbacks
        this.remoteStreamNotified = this.remoteStreamNotified || false;
        this.peerConnection.ontrack = (event) => {
            this.log('📹 Remote stream received');
            
            // Store the first stream that arrives
            if (!this.remoteStream && event.streams && event.streams[0]) {
                this.remoteStream = event.streams[0];
                this.log('📹 Remote stream tracks:', this.remoteStream.getTracks().map(t => t.kind).join(', '));
            }
            
            // Only notify once, even if multiple tracks arrive (audio + video)
            if (!this.remoteStreamNotified && this.remoteStream) {
                this.remoteStreamNotified = true;
                // Delay slightly to ensure all tracks are added
                setTimeout(() => {
                    this.log('✅ Notifying about remote stream');
                    this.onCallAnswered(this.remoteStream);
                }, 100);
            }
        };
        
        // Handle connection state changes
        this.peerConnection.onconnectionstatechange = () => {
            const state = this.peerConnection.connectionState;
            this.log(`🔗 Connection state: ${state}`);
            this.onConnectionStateChange(state);
            
            if (state === 'connected') {
                this.isConnected = true;
                this.log('✅ Call connected successfully!');
            } else if (state === 'failed') {
                this.isConnected = false;
                this.log('❌ Connection failed', 'error');
            }
        };
        
        // Handle ICE connection state changes
        this.peerConnection.oniceconnectionstatechange = () => {
            const state = this.peerConnection.iceConnectionState;
            this.log(`🧊 ICE connection state: ${state}`);
            
            if (state === 'failed') {
                this.log('⚠️ ICE connection failed, attempting restart...', 'warning');
                this.peerConnection.restartIce();
            }
        };
        
        this.log('✅ Peer connection created');
    }
    
    // Send offer via Firebase
    async sendOffer(offer) {
        const callRef = this.roomRef.child(`calls/${this.callId}`);
        // Serialize the RTCSessionDescription to plain object for Firebase storage
        const offerData = {
            type: offer.type,
            sdp: offer.sdp
        };
        await callRef.set({
            type: 'offer',
            fromUserId: this.userId,
            toUserId: this.partnerId,
            offer: offerData, // Store as plain object with type and sdp
            callId: this.callId,
            timestamp: Date.now()
        });
        this.log('📤 Offer sent via Firebase');
    }
    
    // Send answer via Firebase
    async sendAnswer(answer) {
        const callRef = this.roomRef.child(`calls/${this.callId}`);
        // Serialize the RTCSessionDescription to plain object for Firebase storage
        const answerData = {
            type: answer.type,
            sdp: answer.sdp
        };
        await callRef.set({
            type: 'answer',
            fromUserId: this.userId,
            toUserId: this.partnerId,
            answer: answerData, // Store as plain object with type and sdp
            callId: this.callId,
            timestamp: Date.now()
        });
        this.log('📤 Answer sent via Firebase');
    }
    
    // Send ICE candidate via Firebase
    async sendIceCandidate(candidate) {
        const callRef = this.roomRef.child(`calls/${this.callId}_ice_${Date.now()}`);
        await callRef.set({
            type: 'ice-candidate',
            fromUserId: this.userId,
            toUserId: this.partnerId,
            candidate: candidate,
            callId: this.callId,
            timestamp: Date.now()
        });
        this.log('📤 ICE candidate sent via Firebase');
    }
    
    // Handle incoming call
    async handleIncomingCall(call) {
        this.log('📞 Handling incoming call...');
        this.callId = call.callId;
        this.partnerId = call.fromUserId;
        
        // Ensure the call object has the offer properly formatted
        if (call.offer && typeof call.offer === 'object') {
            // If offer is already an object, ensure it has type and sdp
            if (call.offer.type && call.offer.sdp) {
                // Good, it's already in the right format
                this.log('✅ Offer found in call object');
            } else {
                this.log('⚠️ Offer object missing type or sdp, attempting to fix...');
            }
        }
        
        // Show incoming call notification
        this.onCallReceived(call);
    }
    
    // Handle call answer
    async handleCallAnswer(answer) {
        this.log('📞 Handling call answer...');
        
        if (!this.peerConnection) {
            this.log('❌ No peer connection to handle answer', 'error');
            return;
        }
        
        // Ensure answer is an RTCSessionDescription
        let rtcAnswer;
        if (answer instanceof RTCSessionDescription) {
            rtcAnswer = answer;
        } else if (typeof answer === 'object' && answer.type && answer.sdp) {
            rtcAnswer = new RTCSessionDescription(answer);
        } else {
            this.log('❌ Invalid answer format', 'error');
            return;
        }
        
        await this.peerConnection.setRemoteDescription(rtcAnswer);
        this.log('✅ Remote answer set');

        // Process any pending remote ICE candidates that arrived before remote description
        if (this.pendingRemoteCandidates.length > 0) {
            this.log(`🔄 Processing ${this.pendingRemoteCandidates.length} buffered ICE candidates (caller)...`);
            for (const candidate of this.pendingRemoteCandidates) {
                try {
                    await this.peerConnection.addIceCandidate(candidate);
                    this.log('✅ Buffered ICE candidate processed (caller)');
                } catch (e) {
                    this.log(`❌ Error processing buffered ICE candidate (caller): ${e.message}`, 'error');
                }
            }
            this.pendingRemoteCandidates = [];
            this.log('✅ All buffered ICE candidates processed (caller)');
        }
    }
    
    // Handle ICE candidate
    async handleIceCandidate(candidate) {
        // Ignore when there's no active call or peer connection
        if (!this.peerConnection || !this.isActive) {
            return;
        }

        // If remote description isn't set yet, buffer the candidate
        const remoteDescSet = this.peerConnection.remoteDescription && this.peerConnection.remoteDescription.type;
        if (!remoteDescSet) {
            this.pendingRemoteCandidates.push(candidate);
            this.log(`🔄 ICE candidate buffered (${this.pendingRemoteCandidates.length} total)`);
            return;
        }

        // Otherwise, add immediately
        this.log('📡 Handling ICE candidate...');
        await this.peerConnection.addIceCandidate(candidate);
        this.log('✅ ICE candidate added');
    }
    
    // Handle call end (called when partner ends the call)
    // This should ONLY handle UI cleanup, NOT trigger another endCall()
    handleCallEnd() {
        this.log('📞 Handling call end from partner...');
        
        // Prevent re-entry
        if (this.isEndingCall) {
            this.log('⚠️ Already ending call, skipping...');
            return;
        }
        
        // Set guard flag to prevent recursion
        this.isEndingCall = true;
        
        try {
            // DON'T call endCall() here - just clean up UI and resources
            // Partner already wrote to Firebase, so we just need to clean up locally
            
            // Stop camera/mic
            if (this.localStream) {
                try {
                    this.localStream.getTracks().forEach(track => track.stop());
                } catch (error) {
                    this.log(`⚠️ Error stopping local stream tracks: ${error.message}`, 'warning');
                }
                this.localStream = null;
            }
            
            // Close peer connection
            if (this.peerConnection) {
                try {
                    this.peerConnection.close();
                } catch (error) {
                    this.log(`⚠️ Error closing peer connection: ${error.message}`, 'warning');
                }
                this.peerConnection = null;
            }
            
            // Stop timer
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
            
            // Reset state
            this.isConnected = false;
            this.isInitiator = false;
            this.isActive = false;
            this.callId = null;
            this.partnerId = null;
            this.remoteStream = null;
            
            // Notify UI to clean up
            this.onCallEnded();
        } catch (error) {
            this.log(`❌ Error in handleCallEnd: ${error.message}`, 'error');
            this.onError(error);
        } finally {
            // Reset guard flag after a short delay to allow cleanup to complete
            setTimeout(() => {
                this.isEndingCall = false;
            }, 1000);
        }
    }
    
    // End the call (called when user explicitly ends the call)
    async endCall() {
        // Stop if already ending - prevent infinite loop
        if (this.isEndingCall) {
            this.log('⚠️ Already ending call, skipping...');
            return;
        }
        
        // Set guard flag to prevent recursion
        this.isEndingCall = true;
        
        try {
            this.log('📞 Ending video call...');
            
            // 1. Stop camera/mic first
            if (this.localStream) {
                try {
                    this.localStream.getTracks().forEach(track => track.stop());
                } catch (error) {
                    this.log(`⚠️ Error stopping local stream tracks: ${error.message}`, 'warning');
                }
                this.localStream = null;
            }
            
            // 2. Close peer connection
            if (this.peerConnection) {
                try {
                    this.peerConnection.close();
                } catch (error) {
                    this.log(`⚠️ Error closing peer connection: ${error.message}`, 'warning');
                }
                this.peerConnection = null;
            }
            
            // 3. CRITICAL: Remove Firebase listeners FIRST before writing to Firebase
            // This prevents the listener from triggering handleCallEnd() when we write
            this.detachCallListeners();
            
            // 4. Now safe to write to Firebase (only if we have an active call)
            if (this.callId && this.partnerId && this.isActive) {
                try {
                    const callRef = this.roomRef.child(`calls/${this.callId}_end`);
                    await callRef.set({
                        type: 'end-call',
                        fromUserId: this.userId,
                        toUserId: this.partnerId,
                        callId: this.callId,
                        timestamp: Date.now()
                    });
                    this.log('📤 End call signal sent to Firebase');
                } catch (error) {
                    this.log(`⚠️ Error sending end call signal: ${error.message}`, 'warning');
                    // Continue with cleanup even if Firebase write fails
                }
            }
            
            // 5. Clean up remaining resources (timer and state)
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
            
            // Reset state
            this.isConnected = false;
            this.isInitiator = false;
            this.isActive = false;
            this.callId = null;
            this.partnerId = null;
            this.remoteStream = null;
            
            // 6. Reattach listeners for future calls (after a short delay)
            setTimeout(() => {
                if (!this.isActive && this.roomRef) {
                    this.setupFirebaseListeners();
                    this.log('🔌 Reattached Firebase listeners for future calls');
                }
            }, 2000);
            
            this.log('✅ Call ended');
        } catch (error) {
            this.log(`❌ Error in endCall: ${error.message}`, 'error');
            this.onError(error);
        } finally {
            // Reset guard flag after a short delay to allow cleanup to complete
            setTimeout(() => {
                this.isEndingCall = false;
            }, 1000);
        }
    }
    
    // Detach Firebase listeners to prevent infinite recursion
    detachCallListeners() {
        try {
            if (this.callsListenerCallback && this.roomRef) {
                const callsRef = this.roomRef.child('calls');
                callsRef.off('value', this.callsListenerCallback);
                this.callsListenerCallback = null;
                this.log('🔌 Detached calls listener');
            }
        } catch (error) {
            this.log(`⚠️ Error detaching calls listener: ${error.message}`, 'warning');
        }
    }
    
    // Clean up call resources (shared by endCall and handleCallEnd)
    cleanupCallResources() {
        // Stop local stream
        if (this.localStream) {
            try {
                this.localStream.getTracks().forEach(track => track.stop());
            } catch (error) {
                this.log(`⚠️ Error stopping local stream tracks: ${error.message}`, 'warning');
            }
            this.localStream = null;
        }
        
        // Close peer connection
        if (this.peerConnection) {
            try {
                this.peerConnection.close();
            } catch (error) {
                this.log(`⚠️ Error closing peer connection: ${error.message}`, 'warning');
            }
            this.peerConnection = null;
        }
        
        // Stop timer
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
        
        // Reset state
        this.isConnected = false;
        this.isInitiator = false;
        this.isActive = false;
        this.callId = null;
        this.partnerId = null;
        this.remoteStream = null;
    }
    
    // Setup presence cleanup
    setupPresenceCleanup() {
        // Update last seen every 30 seconds
        this.presenceInterval = setInterval(() => {
            if (this.roomRef) {
                const userRef = this.roomRef.child(`users/${this.userId}`);
                userRef.update({ lastSeen: Date.now() });
            }
        }, 30000);
        
        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
    }
    
    // Start call timer
    startCallTimer() {
        this.startTime = Date.now();
        this.timer = setInterval(() => {
            const elapsed = Date.now() - this.startTime;
            const minutes = Math.floor(elapsed / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);
            const timeString = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            
            // Update timer display with null check
            const timerElement = document.getElementById('call-timer');
            if (timerElement && timerElement.textContent !== undefined) {
                try {
                    timerElement.textContent = timeString;
                } catch (error) {
                    // Element might have been removed from DOM, clear timer
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                }
            }
        }, 1000);
    }
    
    // Toggle mute
    toggleMute() {
        if (this.localStream) {
            const audioTrack = this.localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled;
                this.log(`🔇 Audio ${audioTrack.enabled ? 'unmuted' : 'muted'}`);
                return !audioTrack.enabled;
            }
        }
        return false;
    }
    
    // Toggle video
    toggleVideo() {
        if (this.localStream) {
            const videoTrack = this.localStream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.enabled = !videoTrack.enabled;
                this.log(`📹 Video ${videoTrack.enabled ? 'enabled' : 'disabled'}`);
                return !videoTrack.enabled;
            }
        }
        return false;
    }
    
    // Get connection state
    getConnectionState() {
        return this.peerConnection ? this.peerConnection.connectionState : 'disconnected';
    }
    
    // Check if call is active
    isCallActive() {
        return this.isActive && this.peerConnection && this.peerConnection.connectionState === 'connected';
    }
    
    // Cleanup
    cleanup() {
        // Stop presence updates
        if (this.presenceInterval) {
            clearInterval(this.presenceInterval);
            this.presenceInterval = null;
        }
        
        // Detach all listeners first
        this.detachCallListeners();
        
        if (this.usersListenerCallback && this.roomRef) {
            try {
                const usersRef = this.roomRef.child('users');
                usersRef.off('value', this.usersListenerCallback);
                this.usersListenerCallback = null;
            } catch (error) {
                this.log(`⚠️ Error detaching users listener: ${error.message}`, 'warning');
            }
        }
        
        // Remove user from room
        if (this.roomRef) {
            try {
                const userRef = this.roomRef.child(`users/${this.userId}`);
                userRef.remove();
                this.roomRef.off();
            } catch (error) {
                this.log(`⚠️ Error removing user from room: ${error.message}`, 'warning');
            }
        }
        
        // End call if active (but don't write to Firebase during cleanup)
        if (this.isActive) {
            this.cleanupCallResources();
        }
        
        this.log('🧹 Cleanup completed');
    }
}

// Export for use in your application
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FirebaseVideoIntegration;
} else {
    window.FirebaseVideoIntegration = FirebaseVideoIntegration;
}
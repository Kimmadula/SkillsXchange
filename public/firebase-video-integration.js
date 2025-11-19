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
        this.isProcessingAnswer = false; // Guard flag to prevent race conditions in answer processing
        this.answerProcessed = false; // Track if answer has been successfully processed
        this.processedAnswerCallId = null; // Track which callId's answer we've processed
        
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
                
                // Log all calls for debugging (but don't process duplicates)
                if (call.type === 'answer' || call.type === 'offer') {
                    this.log(`🔍 Firebase listener detected ${call.type}: callId=${call.callId}, fromUserId=${call.fromUserId}, toUserId=${call.toUserId}, our userId=${this.userId}, isInitiator=${this.isInitiator}, isActive=${this.isActive}`);
                }
                
                // Skip if we've already processed this call (but allow re-processing answers if needed)
                if (processedCalls.has(callId) && call.type !== 'answer') {
                    return;
                }
                
                // For answers, allow re-processing if remote description isn't set yet
                if (processedCalls.has(callId) && call.type === 'answer') {
                    if (this.peerConnection && this.peerConnection.remoteDescription) {
                        this.log(`⚠️ Answer ${callId} already processed and remote description is set, skipping`);
                        return;
                    } else {
                        this.log(`🔄 Re-processing answer ${callId} - remote description not set yet`);
                    }
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
                if (call.type === 'answer') {
                    // CRITICAL: Check if we've already processed this answer
                    if (this.answerProcessed && this.processedAnswerCallId === call.callId) {
                        // Already processed, skip silently (don't log to reduce noise)
                        return;
                    }
                    
                    // CRITICAL: Check if we're currently processing an answer
                    if (this.isProcessingAnswer) {
                        this.log('⚠️ Already processing answer, skipping duplicate', 'warning');
                        return;
                    }
                    
                    this.log(`🔍 Checking answer: callId=${call.callId}, this.callId=${this.callId}, toUserId=${call.toUserId}, this.userId=${this.userId}, isInitiator=${this.isInitiator}, isActive=${this.isActive}`);
                    
                    // Check if this answer is for us
                    const isForUs = call.toUserId === this.userId || call.toUserId == this.userId;
                    // Match callId exactly or check if one contains the other (for flexible matching)
                    const callIdMatches = call.callId === this.callId || 
                        (typeof call.callId === 'string' && typeof this.callId === 'string' && 
                         (call.callId.includes(this.callId) || this.callId.includes(call.callId)));
                    
                    if (isForUs && (callIdMatches || !this.callId) && this.isInitiator && this.isActive && call.answer) {
                        // Mark as processed immediately to prevent race conditions
                        processedCalls.add(callId);
                        
                        this.log('📞 Call answered - processing answer');
                        // Ensure answer is properly formatted
                        let answerData = call.answer;
                        if (typeof answerData === 'object' && answerData.type && answerData.sdp) {
                            // Convert to RTCSessionDescription if needed
                            if (!(answerData instanceof RTCSessionDescription)) {
                                answerData = new RTCSessionDescription(answerData);
                            }
                            
                            // Check if we've already set remote description
                            if (this.peerConnection && this.peerConnection.remoteDescription) {
                                const currentRemoteDesc = this.peerConnection.remoteDescription;
                                if (currentRemoteDesc.type === 'answer') {
                                    // Check if SDP matches
                                    if (currentRemoteDesc.sdp === answerData.sdp) {
                                        this.log('✅ Answer already processed (SDP matches), skipping duplicate', 'info');
                                        this.answerProcessed = true;
                                        this.processedAnswerCallId = call.callId;
                                        return;
                                    } else {
                                        this.log('⚠️ Remote description already set but SDP differs, this should not happen', 'warning');
                                    }
                                }
                            }
                            
                            // Process the answer
                            this.handleCallAnswer(answerData);
                        } else {
                            this.log('⚠️ Answer missing type or sdp', 'warning');
                            this.log('Answer data:', answerData);
                        }
                    } else {
                        // Log why answer is being ignored (only once per callId to reduce noise)
                        if (!processedCalls.has(callId + '_ignored')) {
                            if (!isForUs) {
                                this.log(`⚠️ Answer not for us: toUserId=${call.toUserId}, our userId=${this.userId}`);
                            }
                            if (!callIdMatches && this.callId) {
                                this.log(`⚠️ Answer callId mismatch: call.callId=${call.callId}, this.callId=${this.callId}`);
                            }
                            if (!this.isInitiator) {
                                this.log('⚠️ Not the initiator, ignoring answer');
                            }
                            if (!this.isActive) {
                                this.log('⚠️ Call not active, ignoring answer');
                            }
                            if (!call.answer) {
                                this.log('⚠️ Answer missing in call object');
                            }
                            processedCalls.add(callId + '_ignored');
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
    async startCall(partnerId, existingStream = null) {
        try {
            this.log('📞 Starting video call...');
            this.partnerId = partnerId;
            this.callId = `call_${Date.now()}_${this.userId}`;
            this.isInitiator = true;
            this.startTime = Date.now(); // Track when call started for filtering old ICE candidates
            // Reset answer processing flags
            this.isProcessingAnswer = false;
            this.answerProcessed = false;
            this.processedAnswerCallId = null;
            
            // Use existing stream if provided, otherwise get new one
            if (existingStream && existingStream.getTracks && existingStream.getTracks().length > 0) {
                this.log('✅ Using existing local stream');
                this.localStream = existingStream;
            } else {
                // Update status
                this.onStatusUpdate?.('Getting camera access...');
                
                try {
                    // Get user media
                    this.localStream = await navigator.mediaDevices.getUserMedia({
                        video: { width: 1280, height: 720 },
                        audio: { echoCancellation: true, noiseSuppression: true }
                    });
                    
                    this.log('✅ Local stream obtained');
                } catch (mediaError) {
                    let errorMessage = 'Failed to access camera/microphone. ';
                    if (mediaError.name === 'NotAllowedError') {
                        errorMessage += 'Please allow camera and microphone access in your browser settings.';
                    } else if (mediaError.name === 'NotFoundError') {
                        errorMessage += 'No camera or microphone found. Please connect a device.';
                    } else if (mediaError.name === 'NotReadableError') {
                        errorMessage += 'Camera or microphone is being used by another application.';
                    } else {
                        errorMessage += mediaError.message;
                    }
                    this.log(`❌ ${errorMessage}`, 'error');
                    this.onStatusUpdate?.(errorMessage);
                    this.onError(new Error(errorMessage));
                    throw new Error(errorMessage);
                }
            }
            
            // Validate local stream before proceeding
            if (!this.localStream || typeof this.localStream.getTracks !== 'function') {
                const errorMsg = 'Local stream is invalid or not available';
                this.log(`❌ ${errorMsg}`, 'error');
                this.onStatusUpdate?.(errorMsg);
                throw new Error(errorMsg);
            }
            
            // Log stream details
            const tracks = this.localStream.getTracks();
            if (!tracks || tracks.length === 0) {
                const errorMsg = 'Local stream has no tracks';
                this.log(`❌ ${errorMsg}`, 'error');
                this.onStatusUpdate?.(errorMsg);
                throw new Error(errorMsg);
            }
            this.log(`📹 Local stream has ${tracks.length} tracks: ${tracks.map(t => `${t.kind}(${t.enabled ? 'enabled' : 'disabled'})`).join(', ')}`);
            
            this.onStatusUpdate?.('Setting up connection...');
            
            // Create peer connection AFTER getting user media
            await this.createPeerConnection();
            
            // Validate peer connection was created
            if (!this.peerConnection) {
                const errorMsg = 'Failed to create peer connection';
                this.log(`❌ ${errorMsg}`, 'error');
                this.onStatusUpdate?.(errorMsg);
                throw new Error(errorMsg);
            }
            
            // Add local stream to peer connection
            let tracksAdded = 0;
            this.localStream.getTracks().forEach(track => {
                // Ensure track is enabled
                if (!track.enabled) {
                    this.log(`⚠️ Track ${track.kind} is disabled, enabling it...`);
                    track.enabled = true;
                }
                this.peerConnection.addTrack(track, this.localStream);
                tracksAdded++;
                this.log(`✅ Added ${track.kind} track to peer connection`);
            });
            
            if (tracksAdded === 0) {
                throw new Error('No tracks added to peer connection');
            }
            
            this.log(`✅ Added ${tracksAdded} tracks to peer connection`);
            
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
    async answerCall(offer, existingStream = null) {
        try {
            this.log('📞 Answering video call...');
            this.isInitiator = false;
            this.startTime = Date.now(); // Track when call started for filtering old ICE candidates
            // Reset answer processing flags (shouldn't be needed for callee, but reset for safety)
            this.isProcessingAnswer = false;
            this.answerProcessed = false;
            this.processedAnswerCallId = null;
            
            // Use existing stream if provided, otherwise get new one
            if (existingStream && existingStream.getTracks && existingStream.getTracks().length > 0) {
                this.log('✅ Using existing local stream');
                this.localStream = existingStream;
            } else {
                try {
                    // Get user media
                    this.localStream = await navigator.mediaDevices.getUserMedia({
                        video: { width: 1280, height: 720 },
                        audio: { echoCancellation: true, noiseSuppression: true }
                    });
                    
                    this.log('✅ Local stream obtained');
                } catch (mediaError) {
                    let errorMessage = 'Failed to access camera/microphone. ';
                    if (mediaError.name === 'NotAllowedError') {
                        errorMessage += 'Please allow camera and microphone access in your browser settings.';
                    } else if (mediaError.name === 'NotFoundError') {
                        errorMessage += 'No camera or microphone found. Please connect a device.';
                    } else if (mediaError.name === 'NotReadableError') {
                        errorMessage += 'Camera or microphone is being used by another application.';
                    } else {
                        errorMessage += mediaError.message;
                    }
                    this.log(`❌ ${errorMessage}`, 'error');
                    this.onError(new Error(errorMessage));
                    throw new Error(errorMessage);
                }
            }
            
            // Validate local stream before proceeding
            if (!this.localStream || typeof this.localStream.getTracks !== 'function') {
                const errorMsg = 'Local stream is invalid or not available';
                this.log(`❌ ${errorMsg}`, 'error');
                throw new Error(errorMsg);
            }
            
            // Log stream details
            const tracks = this.localStream.getTracks();
            if (!tracks || tracks.length === 0) {
                const errorMsg = 'Local stream has no tracks';
                this.log(`❌ ${errorMsg}`, 'error');
                throw new Error(errorMsg);
            }
            this.log(`📹 Local stream has ${tracks.length} tracks: ${tracks.map(t => `${t.kind}(${t.enabled ? 'enabled' : 'disabled'})`).join(', ')}`);
            
            // Create peer connection AFTER getting user media
            await this.createPeerConnection();
            
            // Validate peer connection was created
            if (!this.peerConnection) {
                const errorMsg = 'Failed to create peer connection';
                this.log(`❌ ${errorMsg}`, 'error');
                throw new Error(errorMsg);
            }
            
            // Add local stream to peer connection
            let tracksAdded = 0;
            this.localStream.getTracks().forEach(track => {
                // Ensure track is enabled
                if (!track.enabled) {
                    this.log(`⚠️ Track ${track.kind} is disabled, enabling it...`);
                    track.enabled = true;
                }
                this.peerConnection.addTrack(track, this.localStream);
                tracksAdded++;
                this.log(`✅ Added ${track.kind} track to peer connection`);
            });
            
            if (tracksAdded === 0) {
                throw new Error('No tracks added to peer connection');
            }
            
            this.log(`✅ Added ${tracksAdded} tracks to peer connection`);
            
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
            // Add null check
            if (!this.peerConnection) {
                this.log('⚠️ PeerConnection is null in ICE candidate handler', 'warning');
                return;
            }
            
            try {
                if (event.candidate) {
                    const candidateType = event.candidate.type; // 'host', 'srflx', 'relay'
                    const candidateProtocol = event.candidate.protocol; // 'udp', 'tcp'
                    const candidateAddress = event.candidate.address;
                    this.log(`📡 ICE candidate generated: ${candidateType} ${candidateProtocol} ${candidateAddress || ''}`);
                    
                    // Log if using TURN (relay) - important for NAT traversal
                    if (candidateType === 'relay') {
                        this.log('✅ Using TURN server (relay) for NAT traversal', 'success');
                    } else if (candidateType === 'srflx') {
                        this.log('✅ Using STUN server (srflx) for NAT discovery', 'info');
                    }
                    
                    // Send ICE candidate (errors are handled internally)
                    this.sendIceCandidate(event.candidate).catch(err => {
                        // Already logged in sendIceCandidate, just prevent unhandled rejection
                        this.log('⚠️ ICE candidate send failed (non-critical)', 'warning');
                    });
                } else {
                    this.log('✅ ICE candidate gathering completed');
                }
            } catch (error) {
                this.log(`❌ Error in ICE candidate handler: ${error.message}`, 'error');
            }
        };
        
        // Handle remote stream
        // Track if we've already notified about remote stream to prevent duplicate callbacks
        this.remoteStreamNotified = this.remoteStreamNotified || false;
        this.peerConnection.ontrack = (event) => {
            this.log('📹 Remote track received:', event.track.kind, event.track.id);
            
            // Store the first stream that arrives
            if (!this.remoteStream && event.streams && event.streams[0]) {
                this.remoteStream = event.streams[0];
                
                // Validate stream before accessing getTracks
                if (this.remoteStream && typeof this.remoteStream.getTracks === 'function') {
                    const tracks = this.remoteStream.getTracks();
                    if (tracks) {
                        this.log('📹 Remote stream tracks:', tracks.length, 'tracks:', tracks.map(t => `${t.kind}(${t.id})`).join(', '));
                        
                        // Log track states
                        tracks.forEach(track => {
                            this.log(`  - Track ${track.kind}: readyState=${track.readyState}, enabled=${track.enabled}, muted=${track.muted}`);
                        });
                    }
                } else {
                    this.log('⚠️ Remote stream is invalid, cannot get tracks', 'warning');
                }
            } else if (this.remoteStream && event.streams && event.streams[0]) {
                // Additional tracks added to existing stream
                if (typeof this.remoteStream.getTracks === 'function') {
                    const tracks = this.remoteStream.getTracks();
                    if (tracks) {
                        this.log('📹 Additional track added. Total tracks:', tracks.length);
                    }
                }
            }
            
            // Only notify once, even if multiple tracks arrive (audio + video)
            // Wait a bit longer to ensure all tracks are added (especially if audio and video come separately)
            if (!this.remoteStreamNotified && this.remoteStream) {
                // Add null check before accessing getTracks
                if (!this.remoteStream || typeof this.remoteStream.getTracks !== 'function') {
                    this.log('⚠️ Remote stream is invalid, cannot get tracks', 'warning');
                    return;
                }
                
                const tracks = this.remoteStream.getTracks();
                // Wait until we have at least one track, and give time for both audio and video
                if (tracks && tracks.length > 0) {
                    this.remoteStreamNotified = true;
                    // Delay to ensure all tracks are added (audio and video may arrive separately)
                    setTimeout(() => {
                        // Double-check stream still exists and is valid
                        if (!this.remoteStream || typeof this.remoteStream.getTracks !== 'function') {
                            this.log('⚠️ Remote stream became invalid during delay', 'warning');
                            this.remoteStreamNotified = false;
                            return;
                        }
                        
                        const finalTracks = this.remoteStream.getTracks();
                        this.log('✅ Notifying about remote stream with', finalTracks ? finalTracks.length : 0, 'tracks');
                        if (finalTracks && finalTracks.length > 0) {
                            this.onCallAnswered(this.remoteStream);
                        } else {
                            this.log('⚠️ No tracks in stream after delay, waiting longer...', 'warning');
                            // Reset flag and wait for more tracks
                            this.remoteStreamNotified = false;
                        }
                    }, 500); // Increased delay to 500ms to allow both tracks to arrive
                } else {
                    this.log('⚠️ Track event but stream has no tracks yet, waiting...', 'warning');
                }
            }
        };
        
        // Handle connection state changes
        this.peerConnection.onconnectionstatechange = () => {
            // Add null check to prevent errors
            if (!this.peerConnection) {
                this.log('⚠️ PeerConnection is null in connection state change handler', 'warning');
                return;
            }
            
            try {
                const state = this.peerConnection.connectionState;
                if (!state) {
                    this.log('⚠️ Connection state is undefined', 'warning');
                    return;
                }
                
                this.log(`🔗 Connection state: ${state}`);
                this.onConnectionStateChange(state);
                
                if (state === 'connected') {
                    this.isConnected = true;
                    this.log('✅ Call connected successfully!');
                } else if (state === 'failed') {
                    this.isConnected = false;
                    this.log('❌ Connection failed', 'error');
                }
            } catch (error) {
                this.log(`❌ Error in connection state change handler: ${error.message}`, 'error');
            }
        };
        
        // Handle ICE connection state changes
        this.peerConnection.oniceconnectionstatechange = () => {
            // Add null check to prevent errors
            if (!this.peerConnection) {
                this.log('⚠️ PeerConnection is null in ICE connection state change handler', 'warning');
                return;
            }
            
            try {
                const state = this.peerConnection.iceConnectionState;
                const gatheringState = this.peerConnection.iceGatheringState;
                
                if (!state) {
                    this.log('⚠️ ICE connection state is undefined', 'warning');
                    return;
                }
                
                this.log(`🧊 ICE connection state: ${state}, gathering: ${gatheringState}`);
                
                if (state === 'connected' || state === 'completed') {
                    this.log('✅ ICE connection established successfully!', 'success');
                    // Log which candidates were used
                    if (this.peerConnection && typeof this.peerConnection.getStats === 'function') {
                        this.peerConnection.getStats().then(stats => {
                            if (stats) {
                                stats.forEach(report => {
                                    if (report.type === 'candidate-pair' && report.state === 'succeeded') {
                                        this.log(`✅ Active candidate pair: ${report.localCandidateId} <-> ${report.remoteCandidateId}`, 'success');
                                    }
                                });
                            }
                        }).catch(err => {
                            // Stats API might not be available in all browsers
                            this.log('⚠️ Could not get connection stats', 'warning');
                        });
                    }
                } else if (state === 'failed') {
                    this.log('❌ ICE connection failed - may need TURN servers or check firewall', 'error');
                    this.log('⚠️ Attempting ICE restart...', 'warning');
                    try {
                        if (this.peerConnection && typeof this.peerConnection.restartIce === 'function') {
                            this.peerConnection.restartIce();
                        }
                    } catch (restartError) {
                        this.log(`⚠️ Failed to restart ICE: ${restartError.message}`, 'warning');
                    }
                } else if (state === 'checking') {
                    this.log('🔍 ICE connection checking - trying to establish connection...', 'info');
                } else if (state === 'disconnected') {
                    this.log('⚠️ ICE connection disconnected', 'warning');
                }
            } catch (error) {
                this.log(`❌ Error in ICE connection state change handler: ${error.message}`, 'error');
            }
        };
        
        // Handle ICE gathering state
        this.peerConnection.onicegatheringstatechange = () => {
            // Add null check to prevent errors
            if (!this.peerConnection) {
                this.log('⚠️ PeerConnection is null in ICE gathering state change handler', 'warning');
                return;
            }
            
            try {
                const state = this.peerConnection.iceGatheringState;
                if (!state) {
                    this.log('⚠️ ICE gathering state is undefined', 'warning');
                    return;
                }
                
                this.log(`🧊 ICE gathering state: ${state}`);
                
                if (state === 'complete') {
                    this.log('✅ ICE candidate gathering completed', 'success');
                }
            } catch (error) {
                this.log(`❌ Error in ICE gathering state change handler: ${error.message}`, 'error');
            }
        };
        
        this.log('✅ Peer connection created');
    }
    
    // Send offer via Firebase
    async sendOffer(offer) {
        try {
            // Validate inputs
            if (!offer || !offer.type || !offer.sdp) {
                throw new Error('Invalid offer: missing type or sdp');
            }
            if (!this.roomRef) {
                throw new Error('Firebase room reference not initialized');
            }
            if (!this.callId) {
                throw new Error('Call ID not set');
            }
            if (!this.userId || !this.partnerId) {
                throw new Error('User IDs not set');
            }
            
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
        } catch (error) {
            this.log(`❌ Error sending offer: ${error.message}`, 'error');
            this.onError(error);
            throw error;
        }
    }
    
    // Send answer via Firebase
    async sendAnswer(answer) {
        try {
            // Validate inputs
            if (!answer || !answer.type || !answer.sdp) {
                throw new Error('Invalid answer: missing type or sdp');
            }
            if (!this.roomRef) {
                throw new Error('Firebase room reference not initialized');
            }
            if (!this.callId) {
                throw new Error('Call ID not set');
            }
            if (!this.userId || !this.partnerId) {
                throw new Error('User IDs not set');
            }
            
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
        } catch (error) {
            this.log(`❌ Error sending answer: ${error.message}`, 'error');
            this.onError(error);
            throw error;
        }
    }
    
    // Send ICE candidate via Firebase
    async sendIceCandidate(candidate) {
        try {
            // Validate inputs
            if (!candidate) {
                throw new Error('Invalid ICE candidate: candidate is null or undefined');
            }
            if (!this.roomRef) {
                throw new Error('Firebase room reference not initialized');
            }
            if (!this.callId) {
                // ICE candidates can be sent before callId is set, skip silently
                this.log('⚠️ Skipping ICE candidate - call ID not set yet', 'warning');
                return;
            }
            if (!this.userId || !this.partnerId) {
                this.log('⚠️ Skipping ICE candidate - user IDs not set', 'warning');
                return;
            }
            
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
        } catch (error) {
            // ICE candidate errors are non-critical, log but don't throw
            this.log(`⚠️ Error sending ICE candidate (non-critical): ${error.message}`, 'warning');
        }
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
        // CRITICAL: Prevent duplicate processing
        if (this.isProcessingAnswer) {
            this.log('⚠️ Answer processing already in progress, skipping duplicate', 'warning');
            return;
        }
        
        // CRITICAL: Check if answer is already processed
        if (this.answerProcessed) {
            this.log('⚠️ Answer already processed, skipping duplicate', 'warning');
            return;
        }
        
        this.log('📞 Handling call answer...');
        
        if (!this.peerConnection) {
            this.log('❌ No peer connection to handle answer', 'error');
            return;
        }
        
        // CRITICAL: Check if remote description is already set to answer
        if (this.peerConnection.remoteDescription) {
            const currentType = this.peerConnection.remoteDescription.type;
            if (currentType === 'answer') {
                const currentSdp = this.peerConnection.remoteDescription.sdp;
                const newSdp = answer.sdp || (answer instanceof RTCSessionDescription ? answer.sdp : null);
                if (currentSdp === newSdp) {
                    this.log('✅ Remote description already set with same SDP, marking as processed');
                    this.answerProcessed = true;
                    this.processedAnswerCallId = this.callId;
                    return;
                } else {
                    this.log('⚠️ Remote description already set but SDP differs - this should not happen', 'warning');
                    // Don't try to set again, just mark as processed
                    this.answerProcessed = true;
                    this.processedAnswerCallId = this.callId;
                    return;
                }
            }
        }
        
        // Set processing flag
        this.isProcessingAnswer = true;
        
        // Ensure answer is an RTCSessionDescription
        let rtcAnswer;
        if (answer instanceof RTCSessionDescription) {
            rtcAnswer = answer;
        } else if (typeof answer === 'object' && answer.type && answer.sdp) {
            rtcAnswer = new RTCSessionDescription(answer);
        } else {
            this.log('❌ Invalid answer format', 'error');
            this.log('Answer object:', answer);
            this.isProcessingAnswer = false;
            return;
        }
        
        try {
            // Check connection state before setting
            const signalingState = this.peerConnection.signalingState;
            this.log(`📊 Signaling state before setting answer: ${signalingState}`);
            
            // Only set if in correct state
            if (signalingState === 'have-local-offer') {
                await this.peerConnection.setRemoteDescription(rtcAnswer);
                this.log('✅ Remote answer set successfully');
                this.log(`📊 Connection state after setting answer: ${this.peerConnection.connectionState}`);
                this.log(`🧊 ICE connection state: ${this.peerConnection.iceConnectionState}`);
                this.log(`📊 Signaling state after setting answer: ${this.peerConnection.signalingState}`);

                // Mark as processed
                this.answerProcessed = true;
                this.processedAnswerCallId = this.callId;

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
            } else {
                // Check if already set
                if (this.peerConnection.remoteDescription && this.peerConnection.remoteDescription.type === 'answer') {
                    this.log(`⚠️ Signaling state is ${signalingState}, but remote description is already set to answer`);
                    this.log('✅ Answer already processed, continuing...');
                    this.answerProcessed = true;
                    this.processedAnswerCallId = this.callId;
                } else {
                    throw new Error(`Cannot set remote answer in signaling state: ${signalingState}. Expected: have-local-offer`);
                }
            }
        } catch (error) {
            // If it's an invalid state error and remote description is already set, treat as success
            if (error.name === 'InvalidStateError') {
                if (this.peerConnection.remoteDescription && this.peerConnection.remoteDescription.type === 'answer') {
                    this.log('✅ InvalidStateError but remote description is already set to answer - treating as success');
                    this.answerProcessed = true;
                    this.processedAnswerCallId = this.callId;
                    
                    // Process buffered candidates if any
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
                    return; // Don't call onError for this case
                } else {
                    this.log(`❌ Error setting remote description: ${error.message}`, 'error');
                    this.log(`Error details: ${error.name}, ${error.code || ''}`);
                    this.log(`Signaling state: ${this.peerConnection.signalingState}`);
                    this.onError(error);
                }
            } else {
                this.log(`❌ Error setting remote description: ${error.message}`, 'error');
                this.log(`Error details: ${error.name}, ${error.code || ''}`);
                this.onError(error);
            }
        } finally {
            // Always clear processing flag
            this.isProcessingAnswer = false;
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
            this.isProcessingAnswer = false;
            this.answerProcessed = false;
            this.processedAnswerCallId = null;
            
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
        // Reset answer processing flags
        this.isProcessingAnswer = false;
        this.answerProcessed = false;
        this.processedAnswerCallId = null;
        
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
        if (!this.peerConnection) {
            return 'disconnected';
        }
        try {
            return this.peerConnection.connectionState || 'disconnected';
        } catch (error) {
            this.log(`⚠️ Error getting connection state: ${error.message}`, 'warning');
            return 'disconnected';
        }
    }
    
    // Check if call is active
    isCallActive() {
        if (!this.isActive || !this.peerConnection) {
            return false;
        }
        try {
            return this.peerConnection.connectionState === 'connected';
        } catch (error) {
            this.log(`⚠️ Error checking call active state: ${error.message}`, 'warning');
            return false;
        }
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
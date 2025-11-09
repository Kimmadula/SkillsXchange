export class VideoCallManager {
    constructor(tradeId, userId, partnerId, firebaseVideoCall) {
        this.tradeId = tradeId;
        this.userId = userId;
        this.partnerId = partnerId;
        this.firebaseVideoCall = firebaseVideoCall;
        this.isCallActive = false;
        this.localStream = null;
        this.remoteVideoSet = false;
        this.pendingIncomingOffer = null;
        this.videoCallListenersInitialized = false;
        this.initializingVideoCall = false;
        
        // Video call state
        this.videoCallState = {
            isActive: false,
            isInitiator: false,
            partnerId: null,
            callId: null,
            peerConnection: null
        };
        
        // Call timer
        this.callTimer = null;
        this.callStartTime = null;
        
        // DOM elements
        this.videoModal = document.getElementById('video-chat-modal');
        this.startCallBtn = document.getElementById('start-call-btn');
        this.endCallBtn = document.getElementById('end-call-btn');
        this.localVideo = document.getElementById('local-video');
        this.remoteVideo = document.getElementById('remote-video');
        this.remoteVideoItem = document.getElementById('remote-video-item');
        this.videoStatus = document.getElementById('video-status');
        this.callTimerElement = document.getElementById('call-timer');
        this.closeVideoBtn = document.getElementById('close-video-btn');
        
        // Store globally for compatibility
        window.localStream = null;
        window.firebaseVideoCall = null;
    }

    initialize() {
        this.setupEventListeners();
        this.initializeFirebase();
    }

    setupEventListeners() {
        // Open video chat button
        const videoCallBtn = document.getElementById('video-call-btn');
        if (videoCallBtn) {
            videoCallBtn.addEventListener('click', () => {
                this.openVideoChat();
            });
        }

        // Start call button
        if (this.startCallBtn) {
            this.startCallBtn.addEventListener('click', () => {
                this.startCall();
            });
        }

        // End call button
        if (this.endCallBtn) {
            this.endCallBtn.addEventListener('click', () => {
                this.endCall();
            });
        }

        // Close modal button
        if (this.closeVideoBtn) {
            this.closeVideoBtn.addEventListener('click', () => {
                this.closeVideoChat();
            });
        }

        // Make functions globally available for compatibility
        window.openVideoChat = () => this.openVideoChat();
        window.closeVideoChat = () => this.closeVideoChat();
        window.startVideoCallFull = () => this.startCall();
        window.endVideoCall = () => this.endCall();
        window.handleVideoCallOffer = (data) => this.handleVideoCallOffer(data);
        window.handleVideoCallAnswer = (data) => this.handleVideoCallAnswer(data);
        window.handleVideoCallEnd = (data) => this.handleVideoCallEnd(data);
        window.handleIceCandidate = (data) => this.handleIceCandidate(data);
    }

    async openVideoChat() {
        console.log('🎥 Opening video chat...');
        
        if (!this.videoModal) {
            console.error('❌ Video chat modal not found');
            return;
        }

        this.videoModal.style.display = 'flex';

        // Check if we're on HTTPS
        if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
            console.error('❌ HTTPS required for camera access');
            this.showError('Camera access requires HTTPS. Please use https://skillsxchange.site instead of http://');
            this.videoModal.style.display = 'none';
            return;
        }

        // Initialize camera first and wait for it to complete
        try {
            console.log('📹 Initializing camera...');

            // Check if getUserMedia is supported
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                console.error('❌ Camera not supported');
                this.showError('Camera is not supported in this browser. Please use a modern browser.');
                return;
            }

            // Request camera access with better error handling
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: 'user'
                },
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });

            console.log('✅ Camera access granted');

            // Display local video
            if (this.localVideo) {
                this.localVideo.srcObject = stream;
                this.localVideo.style.display = 'block';
                this.localVideo.muted = true; // Mute local video to prevent echo
                this.localVideo.autoplay = true;
                this.localVideo.playsInline = true;
                this.localVideo.play();
            }

            // Store stream
            this.localStream = stream;
            window.localStream = stream;

            console.log('✅ Video chat opened successfully');

        } catch (error) {
            console.error('❌ Error accessing camera:', error);

            let errorMessage = 'Camera access is required for video calls. ';

            if (error.name === 'NotAllowedError') {
                errorMessage += 'Please allow camera and microphone access in your browser settings and try again.';
            } else if (error.name === 'NotFoundError') {
                errorMessage += 'No camera found. Please connect a camera and try again.';
            } else if (error.name === 'NotSupportedError') {
                errorMessage += 'Camera not supported. Please use a modern browser.';
            } else if (error.name === 'SecurityError') {
                errorMessage += 'Camera access blocked due to security restrictions. Please use HTTPS.';
            } else {
                errorMessage += 'Please check your camera settings and try again.';
            }

            this.showError(errorMessage);
            this.videoModal.style.display = 'none';
        }
    }

    closeVideoChat() {
        console.log('🎥 Closing video chat...');

        if (this.videoModal) {
            this.videoModal.style.display = 'none';
        }

        // Stop local stream
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
            window.localStream = null;
        }

        // Clear video elements
        if (this.localVideo) {
            this.localVideo.srcObject = null;
            this.localVideo.style.display = 'none';
        }

        if (this.remoteVideo) {
            try {
                this.remoteVideo.pause();
            } catch (e) {}
            if (this.remoteVideo.srcObject) {
                this.remoteVideo.srcObject.getTracks().forEach(track => track.stop());
            }
            this.remoteVideo.srcObject = null;
            this.remoteVideo.style.display = 'none';
            this.remoteVideoSet = false;
        }

        // End call if active
        if (this.isCallActive) {
            this.endCall();
        }

        console.log('✅ Video chat closed');
    }

    async initializeFirebase() {
        // Initialize Firebase video call integration
        if (typeof FirebaseVideoIntegration === 'undefined') {
            console.warn('FirebaseVideoIntegration not available');
            return;
        }

        if (this.videoCallListenersInitialized) {
            console.log('⚠️ Video call listeners already initialized, skipping...');
            return;
        }

        if (this.initializingVideoCall) {
            // Wait for current initialization to complete
            while (this.initializingVideoCall) {
                await new Promise(resolve => setTimeout(resolve, 100));
            }
            return;
        }

        this.initializingVideoCall = true;
        console.log('🔧 Setting up Firebase video call listeners...');

        try {
            this.firebaseVideoCall = new FirebaseVideoIntegration({
                userId: this.userId,
                tradeId: this.tradeId,
                partnerId: this.partnerId,
                onCallReceived: async (call) => {
                    console.log('📞 Incoming call received via Firebase:', call);

                    // Show notification for incoming call
                    if (window.notificationService) {
                        console.log('📞 Showing notification for incoming call from:', call.fromUserId);
                        window.notificationService.showIncomingCallNotification(
                            'Partner',
                            call.fromUserId,
                            this.tradeId
                        );
                    }

                    await this.handleVideoCallOffer(call);
                },
                onCallAnswered: (remoteStream) => {
                    console.log('📞 Call answered via Firebase');
                    this.handleVideoCallAnswer({ answer: null, remoteStream: remoteStream });
                },
                onCallEnded: () => {
                    console.log('📞 Call ended via Firebase');
                    this.handleVideoCallEnd({});
                },
                onConnectionStateChange: (state) => {
                    console.log('📞 Connection state changed:', state);
                    this.updateCallStatus(state);
                },
                onError: (error) => {
                    console.error('❌ Firebase video call error:', error);
                    this.updateCallStatus('Error: ' + error.message);
                },
                onLog: (message, type) => {
                    console.log(`[FirebaseVideoCall] ${message}`);
                },
                onStatusUpdate: (status) => {
                    this.updateCallStatus(status);
                }
            });

            // Initialize Firebase
            const success = await this.firebaseVideoCall.initialize();

            if (success) {
                console.log('✅ Firebase video call integration initialized successfully');
                this.videoCallListenersInitialized = true;
                window.firebaseVideoCall = this.firebaseVideoCall;
            } else {
                console.error('❌ Failed to initialize Firebase video call integration');
                this.videoCallListenersInitialized = true; // Mark as initialized to prevent retries
            }
        } catch (error) {
            console.error('❌ Error setting up Firebase video call listeners:', error);
            this.videoCallListenersInitialized = true;
        } finally {
            this.initializingVideoCall = false;
        }
    }

    async startCall() {
        console.log('🚀 Starting video call with Firebase...');

        try {
            // Ensure local stream is available
            if (!this.localStream && !window.localStream) {
                console.log('⚠️ No local stream available, trying to initialize camera...');
                
                try {
                    // Try to initialize camera if not available
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                width: { ideal: 1280 },
                                height: { ideal: 720 },
                                facingMode: 'user'
                            },
                            audio: true
                        });
                        
                        this.localStream = stream;
                        window.localStream = stream;
                        
                        // Display local video
                        if (this.localVideo) {
                            this.localVideo.srcObject = stream;
                            this.localVideo.style.display = 'block';
                            this.localVideo.play();
                        }
                        
                        console.log('✅ Camera initialized successfully');
                    } else {
                        throw new Error('Camera not supported');
                    }
                } catch (error) {
                    console.error('❌ Failed to initialize camera:', error);
                    this.showError('Camera access is required for video calls. Please allow camera access and try again.');
                    return;
                }
            }
            
            console.log('✅ Local stream available, proceeding with call');

            // Initialize Firebase video call if not already initialized
            if (!this.firebaseVideoCall) {
                console.log('🔧 Initializing Firebase video call...');
                await this.initializeFirebase();

                // Wait a moment for initialization to complete
                await new Promise(resolve => setTimeout(resolve, 500));

                if (!this.firebaseVideoCall) {
                    console.error('❌ Firebase video call failed to initialize');
                    this.showError('Video call service not available. Please refresh the page.');
                    return;
                }
            }

            // Update UI to show calling state
            if (this.videoModal) {
                if (this.videoStatus) {
                    this.videoStatus.textContent = 'Starting call...';
                }
                
                // Show call controls
                if (this.startCallBtn) {
                    this.startCallBtn.style.display = 'none';
                }
                if (this.endCallBtn) {
                    this.endCallBtn.style.display = 'inline-block';
                }
            }

            // If we have a pending incoming offer, treat this as Accept
            if (this.pendingIncomingOffer && this.firebaseVideoCall) {
                try {
                    // Stop ringtone if any
                    if (window.notificationService && typeof window.notificationService.stopRingtone === 'function') {
                        window.notificationService.stopRingtone();
                    }

                    const { rtcOffer, fromUserId, callId } = this.pendingIncomingOffer;
                    console.log('📞 Accepting incoming call with stored offer');
                    const ok = await this.firebaseVideoCall.answerCall(rtcOffer);
                    if (!ok) {
                        throw new Error('Failed to answer call');
                    }

                    // Mark state as callee
                    this.videoCallState.isActive = true;
                    this.videoCallState.isInitiator = false;
                    this.videoCallState.partnerId = fromUserId;
                    this.videoCallState.callId = callId;
                    if (this.firebaseVideoCall && this.firebaseVideoCall.peerConnection) {
                        this.videoCallState.peerConnection = this.firebaseVideoCall.peerConnection;
                    }

                    // Prepare UI for remote stream arrival
                    if (this.remoteVideoItem) {
                        this.remoteVideoItem.style.display = 'flex';
                        this.remoteVideoItem.style.visibility = 'visible';
                    }

                    this.pendingIncomingOffer = null; // clear pending offer
                    this.isCallActive = true;
                    this.startCallTimer();
                    return;
                } catch (e) {
                    console.error('❌ Error accepting incoming call:', e);
                    this.showError('Failed to accept incoming call.');
                    return;
                }
            }

            // Validate partner ID
            if (!this.partnerId || this.partnerId === null || this.partnerId === undefined || isNaN(this.partnerId)) {
                console.error('❌ Partner ID not found', {
                    currentUser: this.userId,
                    partnerId: this.partnerId
                });
                this.showError('No partner found for this trade. Make sure the trade request has been accepted.');
                return;
            }

            console.log('📞 Starting call with partner ID:', this.partnerId);

            // Update UI to show calling state
            if (this.videoStatus) {
                this.videoStatus.textContent = 'Initializing...';
            }

            // Start the call using Firebase
            const success = await this.firebaseVideoCall.startCall(this.partnerId);

            if (success) {
                // Setup local video display
                if (this.localVideo && this.firebaseVideoCall.localStream) {
                    this.localVideo.srcObject = this.firebaseVideoCall.localStream;
                    this.localVideo.style.display = 'block';
                    this.localVideo.muted = true; // Mute local video to prevent echo
                    this.localVideo.autoplay = true;
                    this.localVideo.playsInline = true;
                }

                // Update status
                if (this.videoStatus) {
                    this.videoStatus.textContent = 'Call in progress...';
                }

                // Update UI buttons
                if (this.startCallBtn) {
                    this.startCallBtn.style.display = 'none';
                }
                if (this.endCallBtn) {
                    this.endCallBtn.style.display = 'inline-block';
                }

                this.isCallActive = true;
                this.startCallTimer();

                console.log('✅ Video call initiated successfully with Firebase');
            } else {
                throw new Error('Failed to start video call with Firebase');
            }

        } catch (error) {
            console.error('❌ Error starting video call:', error);
            this.showError('Failed to start video call: ' + error.message);
            this.endCall();
        }
    }

    endCall() {
        console.log('🛑 Ending video call...');

        // End Firebase video call
        if (this.firebaseVideoCall) {
            this.firebaseVideoCall.endCall();
        }

        // Stop call timer
        this.stopCallTimer();

        // Stop local stream
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
            window.localStream = null;
        }

        // Clear remote video
        if (this.remoteVideo) {
            try {
                this.remoteVideo.pause();
            } catch (e) {}
            if (this.remoteVideo.srcObject) {
                this.remoteVideo.srcObject.getTracks().forEach(track => track.stop());
            }
            this.remoteVideo.srcObject = null;
            this.remoteVideo.style.display = 'none';
            this.remoteVideoSet = false; // Reset flag for next call
        }

        // Clear local video safely
        if (this.localVideo) {
            try {
                this.localVideo.pause();
            } catch (e) {}
            this.localVideo.srcObject = null;
        }

        // Reset UI
        if (this.startCallBtn) {
            this.startCallBtn.style.display = 'inline-block';
        }
        if (this.endCallBtn) {
            this.endCallBtn.style.display = 'none';
        }

        if (this.videoStatus) {
            this.videoStatus.textContent = 'Call ended';
        }

        this.isCallActive = false;

        console.log('✅ Video call ended');
    }

    async handleVideoCallOffer(data) {
        console.log('📞 Handling video call offer:', data);

        try {
            // Ensure video chat modal is visible when answering
            if (this.videoModal) {
                if (this.videoModal.style.display === 'none' || !this.videoModal.style.display) {
                    this.videoModal.style.display = 'flex';
                    console.log('✅ Video chat modal opened for incoming call');
                }
            }

            // Store offer and wait for explicit user Accept
            if (!this.firebaseVideoCall) {
                throw new Error('Firebase video call not available');
            }

            const offerData = data.offer;
            if (!offerData) {
                throw new Error('No offer found in call data');
            }

            let rtcOffer;
            if (offerData instanceof RTCSessionDescription) {
                rtcOffer = offerData;
            } else if (typeof offerData === 'object' && offerData.type && offerData.sdp) {
                rtcOffer = new RTCSessionDescription({ type: offerData.type, sdp: offerData.sdp });
            } else if (typeof offerData === 'string') {
                const parsed = JSON.parse(offerData);
                rtcOffer = new RTCSessionDescription(parsed);
            } else {
                console.error('Invalid offer format:', offerData);
                throw new Error('Invalid offer format: offer must have type and sdp properties');
            }

            // Ring and show UI, but do not auto-answer
            this.pendingIncomingOffer = { rtcOffer, fromUserId: data.fromUserId, callId: data.callId };

            if (this.videoStatus) {
                this.videoStatus.textContent = 'Incoming call...';
            }

            if (this.startCallBtn) {
                this.startCallBtn.style.display = 'inline-block';
            }
            if (this.endCallBtn) {
                this.endCallBtn.style.display = 'none';
            }

            // Ringtone handled by notificationService; stop it on accept later

        } catch (error) {
            console.error('Error handling offer:', error);
            this.showError('Failed to answer call: ' + error.message);
            this.endCall();
        }
    }

    async handleVideoCallAnswer(data) {
        console.log('📞 Handling video call answer:', data);

        try {
            // If we have a remote stream from Firebase, display it
            if (data.remoteStream) {
                // Ensure video chat modal is visible
                if (this.videoModal) {
                    if (this.videoModal.style.display === 'none' || !this.videoModal.style.display) {
                        this.videoModal.style.display = 'flex';
                        console.log('✅ Video chat modal made visible');
                    }
                }

                if (this.remoteVideo && !this.remoteVideoSet) {
                    // Only set once to prevent play() interruptions
                    this.remoteVideoSet = true;
                    this.remoteVideo.srcObject = data.remoteStream;
                    this.remoteVideo.style.display = 'block';
                    this.remoteVideo.autoplay = true;
                    this.remoteVideo.playsInline = true;

                    // Wait for video to be ready before playing
                    this.remoteVideo.onloadedmetadata = () => {
                        this.remoteVideo.play().catch(error => {
                            console.error('Error playing remote video:', error);
                        });
                    };

                        // Update video call state
                        this.videoCallState.isActive = true;
                        if (this.firebaseVideoCall && this.firebaseVideoCall.peerConnection) {
                            this.videoCallState.peerConnection = this.firebaseVideoCall.peerConnection;
                        }

                        // Check WebRTC connection state if available
                        if (this.firebaseVideoCall && this.firebaseVideoCall.peerConnection) {
                            console.log('🔗 WebRTC Connection State:', this.firebaseVideoCall.peerConnection.connectionState);
                            console.log('🧊 ICE Connection State:', this.firebaseVideoCall.peerConnection.iceConnectionState);

                            // If connection is not fully established, wait for it
                            if (this.firebaseVideoCall.peerConnection.connectionState !== 'connected') {
                                console.warn('⚠️ WebRTC connection not fully established yet. Video may not render until connection is complete.');

                                const onConnectionStateChange = () => {
                                    const state = this.firebaseVideoCall.peerConnection.connectionState;
                                    console.log('🔗 Connection state changed to:', state);

                                    if (state === 'connected' || state === 'failed') {
                                        this.firebaseVideoCall.peerConnection.removeEventListener('connectionstatechange', onConnectionStateChange);
                                    }
                                };

                                this.firebaseVideoCall.peerConnection.addEventListener('connectionstatechange', onConnectionStateChange);
                            }

                            this.isCallActive = true;
                            this.startCallTimer();
                        }
                }
            }
        } catch (error) {
            console.error('Error handling answer:', error);
            this.showError('Failed to handle call answer: ' + error.message);
        }
    }

    handleVideoCallEnd(data) {
        console.log('📞 Video call ended:', data);

        // Stop any notification sounds/alarms
        if (window.notificationService && typeof window.notificationService.stopRingtone === 'function') {
            window.notificationService.stopRingtone();
        }

        // Reset remote video flag
        this.remoteVideoSet = false;
        this.pendingIncomingOffer = null;

        this.endCall();
    }

    startCallTimer() {
        this.callStartTime = Date.now();
        this.callTimer = setInterval(() => {
            const elapsed = Date.now() - this.callStartTime;
            const minutes = Math.floor(elapsed / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);
            const timeString = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            
            if (this.callTimerElement) {
                this.callTimerElement.textContent = timeString;
            }
        }, 1000);
    }

    stopCallTimer() {
        if (this.callTimer) {
            clearInterval(this.callTimer);
            this.callTimer = null;
        }
        this.callStartTime = null;
        if (this.callTimerElement) {
            this.callTimerElement.textContent = '00:00';
        }
    }

    updateCallStatus(status) {
        if (!this.videoStatus) return;

        // Remove existing status classes
        this.videoStatus.className = this.videoStatus.className.replace(/\b(connected|disconnected|lost|ended)\b/g, '');

        switch (status.toLowerCase()) {
            case 'connected':
                this.videoStatus.className += ' connected';
                this.videoStatus.innerHTML = '✅ ' + status;
                break;
            case 'disconnected':
                this.videoStatus.className += ' disconnected';
                this.videoStatus.innerHTML = '❌ ' + status;
                break;
            case 'connection lost':
                this.videoStatus.className += ' lost';
                this.videoStatus.innerHTML = '⚠️ ' + status;
                break;
            case 'call ended':
                this.videoStatus.className += ' ended';
                this.videoStatus.innerHTML = '📴 ' + status;
                break;
            default:
                this.videoStatus.innerHTML = '🔄 ' + status;
        }

        console.log('Call status:', status);
    }

    showError(message) {
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
                    <p class="text-sm">${this.escapeHtml(message)}</p>
                </div>
            </div>
        `;

        // Insert the error message at the top of the chat container
        const chatContainer = document.querySelector('.chat-container') || document.querySelector('.bg-white');
        if (chatContainer) {
            chatContainer.insertBefore(errorDiv, chatContainer.firstChild);

            // Remove after 5 seconds
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.parentNode.removeChild(errorDiv);
                }
            }, 5000);
        } else {
            // Fallback to alert
            alert(message);
        }
    }

    async handleIceCandidate(data) {
        console.log('📞 Handling ICE candidate:', data);
        
        try {
            if (this.videoCallState.peerConnection) {
                await this.videoCallState.peerConnection.addIceCandidate(data.candidate);
            } else if (this.firebaseVideoCall && this.firebaseVideoCall.peerConnection) {
                await this.firebaseVideoCall.peerConnection.addIceCandidate(data.candidate);
            } else {
                console.warn('⚠️ No peer connection available for ICE candidate');
            }
        } catch (error) {
            console.error('Error handling ICE candidate:', error);
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    destroy() {
        // Cleanup
        this.endCall();
        this.closeVideoChat();

        if (this.firebaseVideoCall) {
            this.firebaseVideoCall.endCall();
        }

        // Remove global references
        window.localStream = null;
        window.firebaseVideoCall = null;
    }
}

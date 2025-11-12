@extends('layouts.chat')

@section('content')
{{-- New modern design CSS --}}
<link rel="stylesheet" href="{{ asset('css/session.css') }}">
<link rel="stylesheet" href="{{ asset('css/video.css') }}">
<script>
    // Initialize global variables for the chat session
    window.currentUserId = parseInt('{{ auth()->id() }}');
    window.tradeId = parseInt('{{ $trade->id }}');
    window.authUserId = parseInt('{{ Auth::id() }}');
    window.partnerId = parseInt('{{ $partner->id }}');
    window.partnerName = '{{ addslashes(($partner->firstname ?? "Unknown") . " " . ($partner->lastname ?? "User")) }}';
    window.initialMessageCount = parseInt('{{ $messages->count() }}');
    
    // Initialize video call session data (will be populated by auto-initialization)
    window.videoCallSession = {
        tradeId: null,
        userId: null,
        partnerId: null,
        partnerName: null,
        firebaseRoomPath: null,
        firebaseRoomId: null,
        initialized: false,
        firebaseConnected: false
    };
    
    // Auto-initialize video call session on page load
    document.addEventListener('DOMContentLoaded', async function() {
        console.log('🚀 Auto-initializing video call session...');
        
        // Disable video call button until initialization completes
        const videoCallBtn = document.getElementById('video-call-btn');
        if (videoCallBtn) {
            videoCallBtn.disabled = true;
            videoCallBtn.style.opacity = '0.5';
            videoCallBtn.style.cursor = 'not-allowed';
            videoCallBtn.title = 'Initializing video call...';
        }
        
        try {
            // Fetch session data from API
            const response = await fetch('/api/trades/get-current-session?trade_id=' + window.tradeId, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            // Check if response is HTML (error page) instead of JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('❌ Received non-JSON response from API:', contentType);
                console.error('Response preview:', text.substring(0, 200));
                throw new Error('API returned HTML instead of JSON. Check if the endpoint exists.');
            }
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: response.statusText }));
                throw new Error('Failed to fetch session data: ' + (errorData.message || response.statusText));
            }
            
            const result = await response.json();
            
            if (!result.success || !result.data) {
                throw new Error(result.message || 'No session data returned');
            }
            
            // Store session data globally
            window.videoCallSession = {
                tradeId: result.data.tradeId,
                userId: result.data.userId,
                partnerId: result.data.partnerId,
                partnerName: result.data.partnerName,
                firebaseRoomPath: result.data.firebaseRoomPath,
                firebaseRoomId: result.data.firebaseRoomId,
                initialized: true,
                firebaseConnected: false
            };
            
            console.log('✅ Session data loaded:', window.videoCallSession);
            
            // Prepare Firebase room connection
            await prepareFirebaseRoom();
            
            // Enable video call button
            if (videoCallBtn) {
                videoCallBtn.disabled = false;
                videoCallBtn.style.opacity = '1';
                videoCallBtn.style.cursor = 'pointer';
                videoCallBtn.title = 'Start Video Call';
            }
            
            console.log('✅ Video call session auto-initialization complete');
            
        } catch (error) {
            console.error('❌ Failed to auto-initialize video call session:', error);
            
            // Still enable button but show warning
            if (videoCallBtn) {
                videoCallBtn.disabled = false;
                videoCallBtn.style.opacity = '1';
                videoCallBtn.style.cursor = 'pointer';
                videoCallBtn.title = 'Video call (initialization failed - may have delays)';
            }
        }
    });
    
    /**
     * Prepare Firebase room connection and set user presence
     */
    async function prepareFirebaseRoom() {
        if (!window.videoCallSession.initialized) {
            console.error('❌ Cannot prepare Firebase room: session not initialized');
            return;
        }
        
        if (typeof firebase === 'undefined') {
            console.error('❌ Firebase SDK not loaded');
            return;
        }
        
        try {
            console.log('🔥 Preparing Firebase room connection...');
            
            // Get Firebase config
            const firebaseConfig = window.firebaseConfig;
            if (!firebaseConfig) {
                throw new Error('Firebase configuration not found');
            }
            
            // Initialize Firebase app if not already initialized
            let app;
            try {
                app = firebase.app();
            } catch (error) {
                if (error.code === 'app/duplicate-app') {
                    app = firebase.app();
                } else {
                    app = firebase.initializeApp(firebaseConfig);
                }
            }
            
            const database = firebase.database();
            // Use the full path: video_rooms/{roomId}
            const roomRef = database.ref(window.videoCallSession.firebaseRoomPath);
            
            // Set user presence as online with ready: false
            const userRef = roomRef.child(`users/${window.videoCallSession.userId}`);
            await userRef.set({
                userId: window.videoCallSession.userId,
                status: 'online',
                ready: false,
                joinedAt: Date.now(),
                lastSeen: Date.now()
            });
            
            console.log('✅ User presence set in Firebase room');
            
            // Set up room metadata
            const roomMetaRef = roomRef.child('metadata');
            await roomMetaRef.set({
                tradeId: window.videoCallSession.tradeId,
                user1: Math.min(window.videoCallSession.userId, window.videoCallSession.partnerId),
                user2: Math.max(window.videoCallSession.userId, window.videoCallSession.partnerId),
                createdAt: Date.now(),
                maxUsers: 2
            });
            
            // Listen for partner online status
            const partnerRef = roomRef.child(`users/${window.videoCallSession.partnerId}`);
            partnerRef.on('value', (snapshot) => {
                const partnerData = snapshot.val();
                if (partnerData) {
                    const isOnline = partnerData.status === 'online';
                    console.log(`👤 Partner ${isOnline ? 'is online' : 'is offline'}`);
                    
                    // Update UI to show partner status if needed
                    const videoCallBtn = document.getElementById('video-call-btn');
                    if (videoCallBtn && isOnline) {
                        videoCallBtn.title = 'Start Video Call (Partner is online)';
                    }
                } else {
                    console.log('👤 Partner is offline');
                }
            });
            
            // Update last seen every 30 seconds
            setInterval(() => {
                userRef.update({ lastSeen: Date.now() });
            }, 30000);
            
            // Clean up on page unload
            window.addEventListener('beforeunload', () => {
                userRef.remove();
            });
            
            window.videoCallSession.firebaseConnected = true;
            console.log('✅ Firebase room prepared and connected');
            
        } catch (error) {
            console.error('❌ Error preparing Firebase room:', error);
            throw error;
        }
    }
    
    // Define openVideoChat immediately as a fallback (will be overridden by app.js if it loads)
    window.openVideoChat = function() {
        console.log('🎥 Opening video chat modal (fallback function)...');
        const videoModal = document.getElementById('video-chat-modal');
        if (videoModal) {
            videoModal.style.display = 'flex';
            console.log('✅ Video chat modal opened');
            
            // Initialize video status
            const videoStatus = document.getElementById('video-status');
            if (videoStatus) {
                videoStatus.textContent = 'Ready to start call';
            }
            
            // Show start call button, hide end call button
            const startCallBtn = document.getElementById('start-call-btn');
            const endCallBtn = document.getElementById('end-call-btn');
            if (startCallBtn) startCallBtn.style.display = 'inline-block';
            if (endCallBtn) endCallBtn.style.display = 'none';
        } else {
            console.error('❌ Video chat modal not found');
            alert('Video chat modal not found. Please refresh the page.');
        }
    };
    
    // Define closeVideoChat immediately as a fallback
    window.closeVideoChat = function() {
        console.log('❌ Closing video chat modal (fallback function)...');
        const videoModal = document.getElementById('video-chat-modal');
        if (videoModal) {
            videoModal.style.display = 'none';
            console.log('✅ Video chat modal closed');
            
            // Stop any active streams
            if (window.localStream) {
                window.localStream.getTracks().forEach(track => track.stop());
                window.localStream = null;
            }
            
            const localVideo = document.getElementById('local-video');
            const remoteVideo = document.getElementById('remote-video');
            if (localVideo) {
                localVideo.srcObject = null;
            }
            if (remoteVideo) {
                remoteVideo.srcObject = null;
            }
        }
    };
    

    // Emoji picker functions
    window.toggleEmojiPicker = function() {
        const picker = document.getElementById('emoji-picker');
        if (picker.style.display === 'none' || picker.style.display === '') {
            picker.style.display = 'block';
        } else {
            picker.style.display = 'none';
        }
    };

    window.insertEmoji = function(emoji) {
        const input = document.getElementById('message-input');
        const currentValue = input.value;
        const cursorPosition = input.selectionStart;
        
        // Insert emoji at cursor position
        const newValue = currentValue.slice(0, cursorPosition) + emoji + currentValue.slice(cursorPosition);
        input.value = newValue;
        
        // Set cursor position after the emoji
        input.setSelectionRange(cursorPosition + emoji.length, cursorPosition + emoji.length);
        
        // Focus back to input
        input.focus();
        
        // Hide emoji picker
        document.getElementById('emoji-picker').style.display = 'none';
    };

    // Close emoji picker when clicking outside
    document.addEventListener('click', function(event) {
        const picker = document.getElementById('emoji-picker');
        const button = document.getElementById('emoji-button');
        
        if (picker && !picker.contains(event.target) && !button.contains(event.target)) {
            picker.style.display = 'none';
        }
    });
    
    // Mobile tasks toggle functionality
    window.toggleMobileTasks = function() {
        const tasksSidebar = document.querySelector('.tasks-sidebar');
        const toggleButton = document.getElementById('mobile-tasks-toggle');
        const closeButton = document.getElementById('close-tasks-mobile');
        
        if (tasksSidebar && toggleButton) {
            tasksSidebar.style.display = 'flex';
            tasksSidebar.classList.add('show');
            toggleButton.textContent = '✖️';
            toggleButton.title = 'Hide Tasks';
            
            if (closeButton) {
                closeButton.style.display = 'inline-block';
            }
        }
    };
    
    // Close mobile tasks
    window.closeMobileTasks = function() {
        console.log('Closing mobile tasks...');
        const tasksSidebar = document.querySelector('.tasks-sidebar');
        const toggleButton = document.getElementById('mobile-tasks-toggle');
        const closeButton = document.getElementById('close-tasks-mobile');
        
        if (tasksSidebar && toggleButton) {
            tasksSidebar.style.display = 'none';
            tasksSidebar.classList.remove('show', 'fullscreen');
            toggleButton.textContent = '☑️';
            toggleButton.title = 'Show Tasks';
            
            if (closeButton) {
                closeButton.style.display = 'none';
            }
            
            // Show chat panel again if it was hidden
            const chatPanel = document.querySelector('.chat-panel');
            if (chatPanel) {
                chatPanel.style.display = 'flex';
            }
            
            console.log('Mobile tasks closed successfully');
        }
    };
    
    // Close desktop tasks
    window.closeTasksDesktop = function() {
        console.log('Closing desktop tasks...');
        const tasksSidebar = document.querySelector('.tasks-sidebar');
        const closeButton = document.getElementById('close-tasks-desktop');
        
        if (tasksSidebar) {
            tasksSidebar.style.display = 'none';
            tasksSidebar.classList.remove('show', 'fullscreen');
            
            if (closeButton) {
                closeButton.style.display = 'none';
            }
            
            console.log('Desktop tasks closed successfully');
        }
    };
    
    // Mobile full-screen tasks functionality
    window.toggleFullScreenTasks = function() {
        const tasksSidebar = document.querySelector('.tasks-sidebar');
        const fullScreenButton = document.getElementById('mobile-full-tasks');
        const chatPanel = document.querySelector('.chat-panel');
        
        if (tasksSidebar && fullScreenButton && chatPanel) {
            const isFullScreen = tasksSidebar.classList.contains('fullscreen');
            
            if (isFullScreen) {
                // Exit full screen
                tasksSidebar.classList.remove('fullscreen');
                chatPanel.style.display = 'flex';
                fullScreenButton.textContent = '📋';
                fullScreenButton.title = 'Full Screen Tasks';
            } else {
                // Enter full screen
                tasksSidebar.classList.add('fullscreen');
                chatPanel.style.display = 'none';
                fullScreenButton.textContent = '💬';
                fullScreenButton.title = 'Back to Chat';
            }
        }
    };
    
    // Initialize mobile tasks visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        const tasksSidebar = document.querySelector('.tasks-sidebar');
        const toggleButton = document.getElementById('mobile-tasks-toggle');
        
        // Check if we're on mobile
        if (window.innerWidth <= 768) {
            if (tasksSidebar) {
                tasksSidebar.style.display = 'none';
            }
            if (toggleButton) {
                toggleButton.style.display = 'inline-block';
            }
            const fullScreenButton = document.getElementById('mobile-full-tasks');
            if (fullScreenButton) {
                fullScreenButton.style.display = 'inline-block';
            }
            const closeButton = document.getElementById('close-tasks-mobile');
            if (closeButton) {
                closeButton.style.display = 'inline-block';
            }
        } else {
            // Desktop - show desktop close button
            const desktopCloseButton = document.getElementById('close-tasks-desktop');
            if (desktopCloseButton) {
                desktopCloseButton.style.display = 'inline-block';
            }
        }
        
        // Update task count badge - Now handled by TaskManager
        // updateTaskCountBadge(); // Removed - TaskManager handles this
    });
    
    // OLD updateTaskCountBadge REMOVED - Now handled by TaskManager.updateTaskCountBadge()
    
    // Show notification function
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 12px 16px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            font-size: 0.875rem;
            max-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;
        notification.textContent = message;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const tasksSidebar = document.querySelector('.tasks-sidebar');
        const toggleButton = document.getElementById('mobile-tasks-toggle');
        
        if (window.innerWidth <= 768) {
            if (toggleButton) {
                toggleButton.style.display = 'inline-block';
            }
            const fullScreenButton = document.getElementById('mobile-full-tasks');
            if (fullScreenButton) {
                fullScreenButton.style.display = 'inline-block';
            }
            const closeButton = document.getElementById('close-tasks-mobile');
            if (closeButton) {
                closeButton.style.display = 'inline-block';
            }
            const desktopCloseButton = document.getElementById('close-tasks-desktop');
            if (desktopCloseButton) {
                desktopCloseButton.style.display = 'none';
            }
        } else {
            if (tasksSidebar) {
                tasksSidebar.style.display = 'flex';
                tasksSidebar.classList.remove('show', 'fullscreen');
            }
            if (toggleButton) {
                toggleButton.style.display = 'none';
            }
            const fullScreenButton = document.getElementById('mobile-full-tasks');
            if (fullScreenButton) {
                fullScreenButton.style.display = 'none';
            }
            const closeButton = document.getElementById('close-tasks-mobile');
            if (closeButton) {
                closeButton.style.display = 'none';
            }
            const desktopCloseButton = document.getElementById('close-tasks-desktop');
            if (desktopCloseButton) {
                desktopCloseButton.style.display = 'inline-block';
            }
        }
    });
    
    // Pusher Configuration
    window.PUSHER_APP_KEY = '{{ env('PUSHER_APP_KEY', '5c02e54d01ca577ae77e') }}';
    window.PUSHER_APP_CLUSTER = '{{ env('PUSHER_APP_CLUSTER', 'ap1') }}';
    
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
    
    // Handle user joined event
    function handleUserJoined(data) {
        console.log('👤 User joined:', data);
        
        // Check if the joined user is our partner
        if (data.user_id == window.partnerId) {
            updatePresenceStatus(true, data.user_name || 'Partner');
            console.log('✅ Partner is now online');
        }
    }
    
    // Handle user left event
    function handleUserLeft(data) {
        console.log('👤 User left:', data);
        
        // Check if the left user is our partner
        if (data.user_id == window.partnerId) {
            updatePresenceStatus(false, data.user_name || 'Partner');
            console.log('❌ Partner is now offline');
        }
    }
    
    // Update presence status display
    function updatePresenceStatus(isOnline, userName = 'Partner') {
        const presenceStatus = document.getElementById('presence-status');
        if (presenceStatus) {
            if (isOnline) {
                presenceStatus.innerHTML = '🟢 ' + userName + ' is online';
                presenceStatus.style.color = '#10b981'; // Green color
            } else {
                presenceStatus.innerHTML = '🔴 ' + userName + ' is offline';
                presenceStatus.style.color = '#6b7280'; // Gray color
            }
        }
    }
    
    // Broadcast user presence
    function broadcastUserPresence(action) {
        if (typeof window.Echo !== 'undefined') {
            try {
                // Send presence event to the trade channel
                window.Echo.channel('trade-{{ $trade->id }}')
                    .whisper('presence', {
                        user_id: window.authUserId,
                        user_name: '{{ Auth::user()->firstname }} {{ Auth::user()->lastname }}',
                        action: action, // 'joined' or 'left'
                        timestamp: Date.now()
                    });
                console.log('📡 Broadcasted presence:', action);
            } catch (error) {
                console.error('Error broadcasting presence:', error);
            }
        }
    }
    
    // Check initial presence status
    function checkInitialPresenceStatus() {
        console.log('🔍 Checking initial presence status...');
        
        // Broadcast that current user joined
        broadcastUserPresence('joined');
        
        // For now, we'll assume partner is offline initially
        // In a real implementation, you might want to check with the server
        // or use a presence channel to get the current state
        updatePresenceStatus(false, '{{ $partner->firstname ?? "Partner" }}');
        
        // You could add an API call here to check if the partner is currently online
        // fetch('/api/trade/{{ $trade->id }}/presence')
        //     .then(response => response.json())
        //     .then(data => {
        //         if (data.partner_online) {
        //             updatePresenceStatus(true, data.partner_name);
        //         }
        //     })
        //     .catch(error => {
        //         console.log('Could not check initial presence status:', error);
        //     });
    }
    
    // Listen for user presence events
    function initializePresenceListeners() {
        if (typeof window.Echo !== 'undefined') {
            try {
                window.Echo.channel('trade-{{ $trade->id }}')
                    .listen('user-joined', function(data) {
                        console.log('User joined:', data);
                            handleUserJoined(data);
                    });

                window.Echo.channel('trade-{{ $trade->id }}')
                    .listen('user-left', function(data) {
                        console.log('User left:', data);
                        handleUserLeft(data);
                    })
                    .listenForWhisper('presence', function(data) {
                        console.log('Presence whisper received:', data);
                        if (data.action === 'joined') {
                            handleUserJoined(data);
                        } else if (data.action === 'left') {
                            handleUserLeft(data);
                        }
                    });
                    
                console.log('✅ Presence listeners initialized');
            } catch (error) {
                console.error('Error setting up presence listeners:', error);
            }
        } else {
            console.error('Laravel Echo not available. Make sure Pusher is properly configured.');
        }
    }
    
    // Cleanup presence when user leaves
    function cleanupPresence() {
        console.log('🧹 Cleaning up presence...');
        broadcastUserPresence('left');
    }
    
    // Add cleanup on page unload
    window.addEventListener('beforeunload', cleanupPresence);
    window.addEventListener('pagehide', cleanupPresence);
    
    // Define updateCallStatus function before Firebase initialization
    function updateCallStatus(status) {
        const statusElement = document.getElementById('video-status');
        if (statusElement && statusElement.textContent !== undefined) {
            try {
                statusElement.textContent = status;
            } catch (error) {
                console.warn('⚠️ Error updating call status:', error);
                return;
            }
            
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

    // Define video call handler functions early to make them globally available
    function handleVideoCallEnd(data) {
        console.log('📞 Video call ended:', data);
        
        // Stop any notification sounds/alarms
        if (window.notificationService && typeof window.notificationService.stopRingtone === 'function') {
            window.notificationService.stopRingtone();
        }
        
        // Reset remote video flag
        remoteVideoSet = false;
        
        if (typeof window.endVideoCall === 'function') {
            window.endVideoCall();
        }
    }

    // Pending incoming offer to require explicit user Accept
    let pendingIncomingOffer = null;

    async function handleVideoCallOffer(data) {
        console.log('📞 Handling video call offer:', data);
        
        try {
            // Ensure video chat modal is visible when answering
            const videoModal = document.getElementById('video-chat-modal');
            if (videoModal) {
                if (videoModal.style.display === 'none' || !videoModal.style.display) {
                    videoModal.style.display = 'flex';
                    console.log('✅ Video chat modal opened for incoming call');
                }
            }
            
            // Store offer and wait for explicit user Accept
            if (!firebaseVideoCall) throw new Error('Firebase video call not available');

            const offerData = data.offer;
            if (!offerData) throw new Error('No offer found in call data');

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
            pendingIncomingOffer = { rtcOffer, fromUserId: data.fromUserId, callId: data.callId };
            const statusElement = document.getElementById('video-status');
            if (statusElement) statusElement.textContent = 'Incoming call...';
            const startBtn = document.getElementById('start-call-btn');
            const endBtn = document.getElementById('end-call-btn');
            if (startBtn) startBtn.style.display = 'inline-block';
            if (endBtn) endBtn.style.display = 'none';

            // Ringtone handled by notificationService; stop it on accept later
            
        } catch (error) {
            console.error('Error handling offer:', error);
            alert('Failed to answer call: ' + error.message);
            if (typeof window.endVideoCall === 'function') {
                window.endVideoCall();
            }
        }
    }

    // Track if remote video has been set to prevent duplicate assignments
    let remoteVideoSet = false;
    
    async function handleVideoCallAnswer(data) {
        console.log('📞 Handling video call answer:', data);
        
        try {
            // If we have a remote stream from Firebase, display it
            if (data.remoteStream) {
                const remoteVideo = document.getElementById('remote-video');
                const remoteVideoItem = document.getElementById('remote-video-item');
                const videoModal = document.getElementById('video-chat-modal');
                
                // Ensure video chat modal is visible
                if (videoModal) {
                    if (videoModal.style.display === 'none' || !videoModal.style.display) {
                        videoModal.style.display = 'flex';
                        console.log('✅ Video chat modal made visible');
                    }
                }
                
                if (remoteVideo && !remoteVideoSet) {
                    // Only set once to prevent play() interruptions
                    remoteVideoSet = true;
                    
                    // Clear any existing stream first
                    if (remoteVideo.srcObject) {
                        remoteVideo.srcObject.getTracks().forEach(track => track.stop());
                    }
                    
                    // Ensure remote video container is visible
                    if (remoteVideoItem) {
                        remoteVideoItem.style.display = 'flex';
                        remoteVideoItem.style.visibility = 'visible';
                        console.log('✅ Remote video container made visible');
                    }
                    
                    // Check if stream has tracks
                    const tracks = data.remoteStream.getTracks();
                    console.log('📹 Remote stream tracks:', tracks.map(t => `${t.kind} (${t.readyState})`).join(', '));
                    
                    remoteVideo.srcObject = data.remoteStream;
                    remoteVideo.style.display = 'block';
                    remoteVideo.style.visibility = 'visible';
                    remoteVideo.style.width = '100%';
                    remoteVideo.style.height = '100%';
                    remoteVideo.style.opacity = '1';
                    remoteVideo.style.backgroundColor = 'transparent';
                    remoteVideo.style.position = 'relative';
                    remoteVideo.style.zIndex = '1';
                    remoteVideo.autoplay = true;
                    remoteVideo.playsInline = true;
                    remoteVideo.muted = false; // Allow audio for remote video
                    
                    // Ensure video container doesn't hide the video
                    if (remoteVideoItem) {
                        remoteVideoItem.style.backgroundColor = '#000';
                        remoteVideoItem.style.overflow = 'hidden';
                        remoteVideoItem.style.zIndex = '1';
                    }
                    
                    // Update connection status
                    const remoteStatus = document.getElementById('remote-status');
                    if (remoteStatus) {
                        remoteStatus.textContent = 'Connected';
                        remoteStatus.style.color = '#10b981';
                    }
                    
                    // Force video to play immediately and handle with user interaction
                    const playVideo = async () => {
                        try {
                            // Check video element state
                            console.log('🎬 Attempting to play remote video...', {
                                srcObject: !!remoteVideo.srcObject,
                                paused: remoteVideo.paused,
                                readyState: remoteVideo.readyState,
                                videoWidth: remoteVideo.videoWidth,
                                videoHeight: remoteVideo.videoHeight
                            });
                            
                            // Try to play
                            await remoteVideo.play();
                            
                            console.log('✅ Remote video started playing');
                            console.log('📐 Remote video dimensions:', {
                                videoWidth: remoteVideo.videoWidth,
                                videoHeight: remoteVideo.videoHeight,
                                offsetWidth: remoteVideo.offsetWidth,
                                offsetHeight: remoteVideo.offsetHeight,
                                clientWidth: remoteVideo.clientWidth,
                                clientHeight: remoteVideo.clientHeight,
                                paused: remoteVideo.paused,
                                readyState: remoteVideo.readyState
                            });
                            console.log('📐 Remote video item dimensions:', remoteVideoItem ? {
                                offsetWidth: remoteVideoItem.offsetWidth,
                                offsetHeight: remoteVideoItem.offsetHeight,
                                clientWidth: remoteVideoItem.clientWidth,
                                clientHeight: remoteVideoItem.clientHeight,
                                display: window.getComputedStyle(remoteVideoItem).display,
                                visibility: window.getComputedStyle(remoteVideoItem).visibility,
                                opacity: window.getComputedStyle(remoteVideoItem).opacity,
                                zIndex: window.getComputedStyle(remoteVideoItem).zIndex
                            } : 'Container not found');
                            
                            // Log computed styles for debugging
                            const videoStyles = window.getComputedStyle(remoteVideo);
                            console.log('🎨 Remote video computed styles:', {
                                display: videoStyles.display,
                                visibility: videoStyles.visibility,
                                opacity: videoStyles.opacity,
                                width: videoStyles.width,
                                height: videoStyles.height,
                                zIndex: videoStyles.zIndex,
                                position: videoStyles.position
                            });
                            
                        } catch (e) {
                            console.error('❌ Remote video play error:', e.name, e.message);
                            console.error('Error details:', e);
                            if (e.name === 'AbortError') {
                                // Ignore aborts triggered by stream changes during teardown
                                return;
                            }
                            
                            // Try again after user interaction
                            if (e.name === 'NotAllowedError' || e.name === 'NotSupportedError') {
                                console.log('⚠️ Autoplay blocked, video will play on next user interaction');
                                // Set up a click handler to play on next interaction
                                const playOnInteraction = () => {
                                    remoteVideo.play().catch(err => {
                                        console.error('Play failed even after interaction:', err);
                                    });
                                    document.removeEventListener('click', playOnInteraction);
                                    document.removeEventListener('touchstart', playOnInteraction);
                                };
                                document.addEventListener('click', playOnInteraction, { once: true });
                                document.addEventListener('touchstart', playOnInteraction, { once: true });
                            } else {
                                // Retry after a delay
                                setTimeout(async () => {
                                    try {
                                        await remoteVideo.play();
                                        console.log('✅ Remote video started playing on retry');
                                    } catch (retryErr) {
                                        console.error('❌ Final play attempt failed:', retryErr.name, retryErr.message);
                                    }
                                }, 500);
                            }
                        }
                    };
                    
                    // Wait a moment for the stream to be ready
                    setTimeout(playVideo, 150);
                    
                    // Also try playing on loadedmetadata event
                    remoteVideo.addEventListener('loadedmetadata', () => {
                        console.log('📹 Remote video metadata loaded, attempting play...');
                        playVideo();
                    }, { once: true });
                    
                    // Try playing on loadeddata event as well
                    remoteVideo.addEventListener('loadeddata', () => {
                        console.log('📹 Remote video data loaded, attempting play...');
                        if (remoteVideo.paused) {
                            playVideo();
                        }
                    }, { once: true });
                    
                    // Track when video actually starts playing/showing frames
                    remoteVideo.addEventListener('playing', () => {
                        console.log('🎬✅ Remote video is now playing and rendering!');
                        console.log('📐 Video has dimensions:', {
                            videoWidth: remoteVideo.videoWidth,
                            videoHeight: remoteVideo.videoHeight
                        });
                    }, { once: true });
                    
                    remoteVideo.addEventListener('canplay', () => {
                        console.log('📹 Remote video can play');
                        if (remoteVideo.paused) {
                            playVideo();
                        }
                    }, { once: true });
                    
                    // Check periodically if video has dimensions (means it's rendering)
                    const checkVideoRendering = setInterval(() => {
                        if (remoteVideo.videoWidth > 0 && remoteVideo.videoHeight > 0) {
                            console.log('✅ Remote video is rendering!', {
                                videoWidth: remoteVideo.videoWidth,
                                videoHeight: remoteVideo.videoHeight,
                                paused: remoteVideo.paused,
                                currentTime: remoteVideo.currentTime
                            });
                            clearInterval(checkVideoRendering);
                        }
                    }, 500);
                    
                    // Clear the interval after 10 seconds
                    setTimeout(() => {
                        clearInterval(checkVideoRendering);
                        if (remoteVideo.videoWidth === 0 || remoteVideo.videoHeight === 0) {
                            console.error('⚠️ Remote video still has no dimensions after 10 seconds!');
                            console.error('Video state:', {
                                srcObject: !!remoteVideo.srcObject,
                                paused: remoteVideo.paused,
                                readyState: remoteVideo.readyState,
                                networkState: remoteVideo.networkState,
                                error: remoteVideo.error
                            });
                        }
                    }, 10000);
                    
                    console.log('✅ Remote video stream received and displayed');
                } else if (remoteVideoSet) {
                    console.log('⚠️ Remote video already set, skipping duplicate assignment');
                } else if (!remoteVideo) {
                    console.error('❌ Remote video element not found!');
                }
                
                // Check WebRTC connection state if available
                if (firebaseVideoCall && firebaseVideoCall.peerConnection) {
                    console.log('🔗 WebRTC Connection State:', firebaseVideoCall.peerConnection.connectionState);
                    console.log('🧊 ICE Connection State:', firebaseVideoCall.peerConnection.iceConnectionState);
                    
                    // If connection isn't fully established, the video might not render yet
                    if (firebaseVideoCall.peerConnection.connectionState !== 'connected') {
                        console.warn('⚠️ WebRTC connection not fully established yet. Video may not render until connection is complete.');
                        
                        // Listen for connection state change
                        const onConnectionStateChange = () => {
                            const state = firebaseVideoCall.peerConnection.connectionState;
                            console.log('🔗 Connection state changed to:', state);
                            
                            if (state === 'connected' && remoteVideo && remoteVideo.paused) {
                                console.log('🎬 Connection established, attempting to play video...');
                                remoteVideo.play().catch(e => {
                                    console.error('Failed to play after connection:', e);
                                });
                            }
                            
                            if (state === 'connected' || state === 'failed' || state === 'closed') {
                                firebaseVideoCall.peerConnection.removeEventListener('connectionstatechange', onConnectionStateChange);
                            }
                        };
                        
                        firebaseVideoCall.peerConnection.addEventListener('connectionstatechange', onConnectionStateChange);
                    }
                }
                
                videoCallState.isActive = true;
                videoCallState.isConnected = true;
            }
            
        } catch (error) {
            console.error('Error handling answer:', error);
        }
    }

    // Make video call functions globally available immediately
    window.handleVideoCallEnd = handleVideoCallEnd;
    window.handleVideoCallOffer = handleVideoCallOffer;
    window.handleVideoCallAnswer = handleVideoCallAnswer;

    // OLD VIDEO CALL FUNCTIONS REMOVED - Now handled by VideoCallManager
    // openVideoChat, closeVideoChat are now in VideoCallManager

    // Modified to return a promise and only initialize once
    let initializingVideoCall = false;
    
    async function initializeVideoCallListenersOnce() {
        if (videoCallListenersInitialized) {
            console.log('⚠️ Video call listeners already initialized, skipping...');
            return true;
        }
        
        if (initializingVideoCall) {
            // Wait for current initialization to complete
            while (initializingVideoCall) {
                await new Promise(resolve => setTimeout(resolve, 100));
            }
            return videoCallListenersInitialized;
        }
        
        initializingVideoCall = true;
        console.log('🔧 Setting up Firebase video call listeners...');
        
        return new Promise((resolve) => {
            initializeVideoCallListeners().then(() => {
                initializingVideoCall = false;
                resolve(true);
            }).catch(() => {
                initializingVideoCall = false;
                resolve(false);
            });
        });
    }
    
    function initializeVideoCallListeners() {
        if (videoCallListenersInitialized) {
            return Promise.resolve(true);
        }
        
        return new Promise((resolve, reject) => {
        
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
                        
                        await window.handleVideoCallOffer(call);
                    },
                    onCallAnswered: (remoteStream) => {
                        console.log('📞 Call answered via Firebase');
                        window.handleVideoCallAnswer({ answer: null, remoteStream: remoteStream });
                    },
                    onCallEnded: () => {
                        console.log('📞 Call ended via Firebase');
                        window.handleVideoCallEnd({});
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
                        videoCallListenersInitialized = true;
                        console.log('✅ Video call listeners initialized successfully');
                        resolve(true);
                    } else {
                        console.error('❌ Failed to initialize Firebase video call integration');
                        videoCallListenersInitialized = true; // Mark as initialized to prevent retries
                        resolve(false);
                    }
                }).catch(error => {
                    console.error('❌ Firebase initialization error:', error);
                    videoCallListenersInitialized = true;
                    resolve(false);
                });
            } else {
                console.error('❌ FirebaseVideoIntegration not available');
                videoCallListenersInitialized = true;
                resolve(false);
            }
        } catch (error) {
            console.error('❌ Error setting up Firebase video call listeners:', error);
            videoCallListenersInitialized = true;
            resolve(false);
        }
        });
    }

    // Firebase video call listeners will be initialized on first user interaction
    // This is handled by VideoCallManager.setupLazyFirebaseInitialization()
    // No need to auto-initialize here - it will happen when user clicks/touches/interacts with the page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('💡 Firebase video call will initialize on first user interaction (click, touch, or keypress)');
        console.log('💡 This allows receiving incoming calls without opening the modal first');
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


    /* Emoji button hover effect */
    #emoji-button:hover {
        background-color: #f3f4f6 !important;
    }

    #emoji-button:active {
        background-color: #e5e7eb !important;
    }

    /* Emoji picker styles */
    .emoji-btn:hover {
        background-color: #f3f4f6 !important;
    }

    .emoji-btn:active {
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

@include('chat.partials.video-modal')

<div style="height: 100vh; display: flex; flex-direction: column;">
    @include('chat.partials.session-header')
    
    <!-- Main Content -->
    <div style="flex: 1; display: flex; overflow: hidden;" class="main-content-container">
        @include('chat.partials.chat-panel')
        
        @include('chat.partials.tasks-sidebar')
    </div>

    @include('chat.partials.session-footer')
</div>

<!-- Legacy Firebase WebRTC Script (may be redundant with VideoCallManager) -->
<!-- Note: This script may be redundant now that VideoCallManager handles video calls -->
<!-- Keeping for backward compatibility, can be removed after testing -->
<script>
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
                                
                                // Initialize Firebase after a short delay to ensure it's loaded
                                setTimeout(() => {
                                    this.initFirebase();
                                }, 100);
                            }
                            
                            initFirebase() {
                                try {
                                    console.log('🔍 Initializing Firebase for WebRTC signaling...');
                                    console.log('🔍 window.firebaseDatabase available:', !!window.firebaseDatabase);
                                    console.log('🔍 firebase object available:', typeof firebase !== 'undefined');
                                    
                                    // First try to use the global Firebase database from firebase-config.js
                                    if (window.firebaseDatabase) {
                                        this.database = window.firebaseDatabase;
                                        console.log('✅ Firebase database initialized from global reference (v9 compat)');
                                        return true;
                                    }
                                    
                                    // If not available, wait a bit and retry
                                    console.log('🔄 Firebase database not available, waiting and retrying...');
                                    setTimeout(() => {
                                        if (window.firebaseDatabase) {
                                            this.database = window.firebaseDatabase;
                                            console.log('✅ Firebase database initialized from global reference (retry)');
                                        } else {
                                            console.log('🔄 Still not available, trying direct initialization...');
                                            this.initFirebaseDirect();
                                        }
                                    }, 1000);
                                    
                                    return false;
                                    
                                } catch (error) {
                                    console.error('❌ Error initializing Firebase:', error);
                                    console.error('❌ Error details:', {
                                        name: error.name,
                                        message: error.message,
                                        code: error.code,
                                        stack: error.stack
                                    });
                                    return false;
                                }
                            }
                            
                            initFirebaseDirect() {
                                try {
                                    // Fallback: Wait for Firebase to be fully loaded
                                    if (typeof firebase === 'undefined') {
                                        console.error('❌ Firebase SDK not loaded');
                                        return false;
                                    }
                                    
                                    // Check if Firebase app is initialized
                                    let app;
                                    try {
                                        app = firebase.app();
                                        console.log('✅ Using existing Firebase app');
                                    } catch (error) {
                                        if (error.code === 'app/no-app') {
                                            console.error('❌ Firebase app not initialized. Please ensure firebase-config.js is loaded first.');
                                            return false;
                                        }
                                        throw error;
                                    }
                                    
                                    // Get database reference
                                    this.database = firebase.database();
                                    console.log('✅ Firebase database initialized for WebRTC signaling');
                                    return true;
                                    
                                } catch (error) {
                                    console.error('❌ Error initializing Firebase directly:', error);
                                    return false;
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
                                        from: window.authUserId || 'unknown'
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
                                        from: window.authUserId || 'unknown'
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
                                        from: window.authUserId || 'unknown'
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
                                    if (answerData && answerData.sdp && answerData.from !== window.authUserId) {
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
                                    } else if (answerData && answerData.from === window.authUserId) {
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
                                    if (candidateData && candidateData.candidate && candidateData.from !== window.authUserId) {
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
                                    } else if (candidateData && candidateData.from === window.authUserId) {
                                        console.log('🧊 Ignoring own ICE candidate');
                                    }
                                });
                            }
                            
                            displayRemoteStream() {
                                const remoteVideo = document.getElementById('remote-video');
                                const remoteVideoItem = document.getElementById('remote-video-item');
                                
                                if (remoteVideo && this.remoteStream) {
                                    // Ensure container is visible
                                    if (remoteVideoItem) {
                                        remoteVideoItem.style.display = 'flex';
                                        remoteVideoItem.style.visibility = 'visible';
                                    }
                                    
                                    remoteVideo.srcObject = this.remoteStream;
                                    remoteVideo.style.display = 'block';
                                    remoteVideo.style.visibility = 'visible';
                                    remoteVideo.style.width = '100%';
                                    remoteVideo.style.height = '100%';
                                    remoteVideo.autoplay = true;
                                    remoteVideo.playsInline = true;
                                    remoteVideo.muted = false;
                                    
                                    // Update connection status
                                    const remoteStatus = document.getElementById('remote-status');
                                    if (remoteStatus) {
                                        remoteStatus.textContent = 'Connected';
                                        remoteStatus.style.color = '#10b981';
                                    }
                                    
                                    // Play the video
                                    remoteVideo.play().catch(e => {
                                        console.log('Remote video play error:', e.name);
                                    });
                                    
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
                        
                        // Auto-setup callee removed (no auto-answer). Incoming offers are handled via Firebase callbacks
                        
                        // openVideoChat will be defined later in the main script
                        
                        
                        // Event listener removed - handled by VideoCallManager to prevent duplicates
                        // VideoCallManager.setupEventListeners() will attach the listener
                            
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
                            
                            // End call button already has event listener above (line 1973), no need to duplicate
                            
                            // Add event listeners for remaining buttons
                            
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
                            
                            // Define video chat functions immediately after DOM is loaded
                            console.log('🔧 Video chat functions are handled by VideoCallManager in app.js');
                            // Note: openVideoChat and closeVideoChat are defined in app.js after VideoCallManager initialization
                            
                            // Consolidated video call functions
                            window.startVideoCall = async function() {
                                console.log('🚀 Starting video call...');
                                
                                // Check if we have a local stream
                                if (!window.localStream) {
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
                                            
                                            window.localStream = stream;
                                            
                                            // Display local video
                                            const localVideo = document.getElementById('local-video');
                                            if (localVideo) {
                                                localVideo.srcObject = stream;
                                                localVideo.style.display = 'block';
                                                localVideo.play();
                                            }
                                            
                                            console.log('✅ Camera initialized successfully');
                                        } else {
                                            throw new Error('Camera not supported');
                                        }
                                    } catch (error) {
                                        console.error('❌ Failed to initialize camera:', error);
                                        alert('Camera access is required for video calls. Please allow camera access and try again.');
                                        return;
                                    }
                                }
                                
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
                                
                                // If we have a pending incoming offer, treat this as Accept
                                if (typeof pendingIncomingOffer !== 'undefined' && pendingIncomingOffer && firebaseVideoCall) {
                                    try {
                                        // Stop ringtone if any
                                        if (window.notificationService && typeof window.notificationService.stopRingtone === 'function') {
                                            window.notificationService.stopRingtone();
                                        }

                                        const { rtcOffer, fromUserId, callId } = pendingIncomingOffer;
                                        console.log('📞 Accepting incoming call with stored offer');
                                        const ok = await firebaseVideoCall.answerCall(rtcOffer);
                                        if (!ok) throw new Error('Failed to answer call');

                                        // Mark state as callee
                                        videoCallState.isActive = true;
                                        videoCallState.isInitiator = false;
                                        videoCallState.partnerId = fromUserId;
                                        videoCallState.callId = callId;

                                        // Prepare UI for remote stream arrival
                                        const remoteVideoItem = document.getElementById('remote-video-item');
                                        if (remoteVideoItem) {
                                            remoteVideoItem.style.display = 'flex';
                                            remoteVideoItem.style.visibility = 'visible';
                                        }

                                        pendingIncomingOffer = null; // clear pending offer
                                        return;
                                    } catch (e) {
                                        console.error('❌ Error accepting incoming call:', e);
                                        alert('Failed to accept incoming call.');
                                        return;
                                    }
                                }

                                // Otherwise, place an outgoing call
                                if (typeof window.startVideoCallFull === 'function') {
                                    window.startVideoCallFull();
                                } else {
                                    console.log('startVideoCallFull not available yet, will retry...');
                                    setTimeout(() => {
                                        if (typeof window.startVideoCallFull === 'function') {
                                            window.startVideoCallFull();
                                        }
                                    }, 1000);
                                }
                            };
                            
                            window.startVideoCallFull = async function() {
                                console.log('🚀 Starting video call with Firebase...');
                                
                                try {
                                    // Initialize Firebase video call if not already initialized
                                    if (!firebaseVideoCall) {
                                        console.log('🔧 Initializing Firebase video call...');
                                        await initializeVideoCallListenersOnce();
                                        
                                        // Wait a moment for initialization to complete
                                        await new Promise(resolve => setTimeout(resolve, 500));
                                        
                                        if (!firebaseVideoCall) {
                                            console.error('❌ Firebase video call failed to initialize');
                                            alert('Video call service not available. Please refresh the page.');
                                            return;
                                        }
                                    }
                                    
                                    // Get partner ID - use window.partnerId which is set from controller
                                    const partnerId = window.partnerId;
                                    
                                    if (!partnerId || partnerId === null || partnerId === undefined || isNaN(partnerId)) {
                                        console.error('❌ Partner ID not found', {
                                            currentUser: {{ auth()->id() }},
                                            tradeOwner: {{ $trade->user_id }},
                                            partnerId: partnerId,
                                            windowPartnerId: window.partnerId
                                        });
                                        alert('No partner found for this trade. Make sure the trade request has been accepted.');
                                        return;
                                    }
                                    
                                    console.log('📞 Starting call with partner ID:', partnerId);
                                    
                                    // Update UI to show calling state
                                    const statusElement = document.getElementById('video-status');
                                    if (statusElement) {
                                        statusElement.textContent = 'Initializing...';
                                    }
                                    
                                    // Start the call using Firebase
                                    const success = await firebaseVideoCall.startCall(partnerId);
                                    
                                    if (success) {
                                        // Setup local video display
                                        const localVideo = document.getElementById('local-video');
                                        if (localVideo && firebaseVideoCall.localStream) {
                                            localVideo.srcObject = firebaseVideoCall.localStream;
                                            localVideo.style.display = 'block';
                                            localVideo.muted = true; // Mute local video to prevent echo
                                            localVideo.autoplay = true;
                                            localVideo.playsInline = true;
                                        }
                                        
                                        // Update status
                                        if (statusElement) {
                                            statusElement.textContent = 'Call in progress...';
                                        }
                                        
                                        console.log('✅ Video call initiated successfully with Firebase');
                                    } else {
                                        throw new Error('Failed to start video call with Firebase');
                                    }
                                    
                                } catch (error) {
                                    console.error('❌ Error starting video call:', error);
                                    alert('Failed to start video call: ' + error.message);
                                    if (typeof window.endVideoCall === 'function') {
                                        window.endVideoCall();
                                    }
                                }
                            };
                            
            window.endVideoCall = function() {
                                console.log('🛑 Ending video call...');
                                
                                // End Firebase video call
                                if (window.firebaseVideoCall) {
                                    window.firebaseVideoCall.endCall();
                                }
                                
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
                    try { remoteVideo.pause(); } catch(e) {}
                                    if (remoteVideo.srcObject) {
                                        remoteVideo.srcObject.getTracks().forEach(track => track.stop());
                                    }
                                    remoteVideo.srcObject = null;
                                    remoteVideo.style.display = 'none';
                                    remoteVideoSet = false; // Reset flag for next call
                                }

                // Clear local video safely
                const localVideo = document.getElementById('local-video');
                if (localVideo) {
                    try { localVideo.pause(); } catch(e) {}
                    localVideo.srcObject = null;
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
                            
                        });
                    </script>

<!-- Session Tasks Panels (required by TaskManager) -->
<div id="session-tasks-panels" style="margin-top: 8px;">
	<div data-content="my-tasks" style="margin-top:8px;">
		<div class="progress" style="height:8px;background:#eee;border-radius:6px;overflow:hidden;margin:6px 0;">
			<div class="progress-fill" style="height:8px;width:0;background:#3b82f6;"></div>
		</div>
		<div style="font-size:.85rem;color:#6b7280;">Progress: <span class="progress-percentage">0%</span></div>
		<div id="my-tasks"></div>
	</div>

	<div data-content="partner-tasks" style="margin-top:8px;">
		<div class="progress" style="height:8px;background:#eee;border-radius:6px;overflow:hidden;margin:6px 0;">
			<div class="progress-fill" style="height:8px;width:0;background:#10b981;"></div>
		</div>
		<div style="font-size:.85rem;color:#6b7280;">Progress: <span class="progress-percentage">0%</span></div>
		<div id="partner-tasks"></div>
	</div>
</div>

<!-- Add Task Modal -->

<!-- Report User Modal -->
<div id="report-user-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1100;">
	<div style="max-width: 520px; width: 92%; margin: 10vh auto; background: #ffffff; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
		<div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
			<h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: #111827;">Report {{ $partner->firstname }} {{ $partner->lastname }}</h3>
			<button type="button" onclick="closeReportUserModal()" style="background: transparent; border: none; font-size: 1.25rem; cursor: pointer; color: #6b7280;">×</button>
		</div>
		<div style="padding: 16px 20px;">
			<form id="report-user-form">
				<input type="hidden" name="reported_user_id" id="reported_user_id" value="{{ $partner->id }}">
				<div style="margin-bottom: 12px;">
					<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Reason</label>
					<select id="report_reason" required style="width: 100%; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
						<option value="" selected disabled>Select a reason</option>
						<option value="harassment">Harassment/Bullying</option>
						<option value="spam">Spam/Scam</option>
						<option value="inappropriate">Inappropriate Content</option>
						<option value="fraud">Fraud/Fake Identity</option>
						<option value="safety">Safety Concerns</option>
						<option value="other">Other</option>
					</select>
				</div>
				<div>
					<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Details</label>
					<textarea id="report_description" required minlength="10" maxlength="2000" placeholder="Describe what happened..." style="width: 100%; min-height: 120px; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 6px; resize: vertical;"></textarea>
					<small style="color: #6b7280;">Include relevant details (dates, what was said/done). No private info.</small>
				</div>
			</form>
		</div>
		<div style="padding: 12px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; gap: 8px;">
			<button type="button" onclick="closeReportUserModal()" style="background: #e5e7eb; color: #111827; border: none; border-radius: 6px; padding: 8px 12px; font-size: 0.9rem; cursor: pointer;">Cancel</button>
			<button type="button" onclick="submitUserReport()" id="report-submit-btn" style="background: #ef4444; color: white; border: none; border-radius: 6px; padding: 8px 12px; font-size: 0.9rem; cursor: pointer;">Submit Report</button>
		</div>
	</div>
</div>
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
                <input type="text" id="task-title" required aria-label="Task title"
                    style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 4px; font-weight: 500;">Description (Optional)</label>
                <textarea id="task-description" rows="3" aria-label="Task description"
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
                    <select id="task-priority" aria-label="Task priority" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 4px; font-weight: 500;">Due Date (Optional)</label>
                    <input type="date" id="task-due-date" min="{{ date('Y-m-d', strtotime('+1 day')) }}" aria-label="Task due date"
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
        key: window.PUSHER_APP_KEY || '5c02e54d01ca577ae77e',
        cluster: window.PUSHER_APP_CLUSTER || 'ap1',
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

    // OLD CHAT ECHO LISTENER REMOVED - Now handled by ChatManager.setupEchoListeners()

    // OLD TASK ECHO LISTENERS REMOVED - Now handled by TaskManager.setupEchoListeners()

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

    // Video chat functions are now defined earlier in DOMContentLoaded event
    
    // startVideoCallFull is now defined earlier in DOMContentLoaded event
    
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
    
    // endVideoCall function is now defined earlier in DOMContentLoaded event
    
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

    // Duplicate function definitions removed - using the ones defined earlier

    // Handle ICE candidate
    async function handleIceCandidate(data) {
        console.log('📞 Handling ICE candidate:', data);
        
        try {
            await videoCallState.peerConnection.addIceCandidate(data.candidate);
            
        } catch (error) {
            console.error('Error handling ICE candidate:', error);
        }
    }

    // Duplicate function definitions removed - using the ones defined earlier
    
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
            const response = await fetch(`/chat/{{ $trade->id }}/video-call/messages?since=${lastPollTime}`);
            
            // Check if response is HTML (error page) instead of JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.error('❌ Received non-JSON response:', contentType);
                console.error('Response text:', await response.text());
                return;
            }
            
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
            // If it's a JSON parsing error, it might be an HTML error page
            if (error.message.includes('Unexpected token') && error.message.includes('<!DOCTYPE')) {
                console.error('❌ Received HTML error page instead of JSON. Check if the route exists.');
            }
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

// OLD CHAT MESSAGE SENDING CODE REMOVED - Now handled by ChatManager
// The message form submit is handled by ChatManager.setupEventListeners()

// OLD CHAT UI FUNCTIONS REMOVED - Now handled by ChatManager
// addMessageToChat, flashChatArea, showNewMessageIndicator are now in ChatManager

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

// OLD CHAT MESSAGE UPDATE FUNCTIONS REMOVED - Now handled by ChatManager
// removeMessageFromChat, updateMessageInChat are now in ChatManager

// Task handling
// OLD TASK FUNCTIONS REMOVED - Now handled by TaskManager
// toggleTask, updateTask, updateProgress, updateTaskCount are now in TaskManager

// OLD TASK MODAL FUNCTIONS REMOVED - Now handled by TaskManager
// showAddTaskModal, hideAddTaskModal, handleModalClick are now in TaskManager

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

// OLD TASK FORM HANDLERS REMOVED - Now handled by TaskManager
// Edit task form and add task form handlers are now in TaskManager.setupEventListeners()
// clearTaskForm is now in TaskManager

// OLD TASK CRUD FUNCTIONS REMOVED - Now handled by TaskManager
// editTask, showEditTaskModal, hideEditTaskModal, handleEditTaskModalClick, deleteTask are now in TaskManager

// Utility function - kept as it's just a simple redirect
function viewTaskDetails(taskId) {
    // Redirect to task details page
    window.open(`/tasks/${taskId}`, '_blank');
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
        
        // Get fresh CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        console.log('Using CSRF token:', csrfToken);
        
        fetch(`/tasks/${taskId}/submit`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData, 
            credentials: 'same-origin'
        })
        .then(response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                // If not JSON, it's likely an HTML error page
                throw new Error('Server returned HTML instead of JSON. This usually indicates a server error.');
            }
        })
        .then(data => {
            if (data.success) {
                hideTaskSubmissionModal();
                showSuccess('Work submitted successfully!');
                // Refresh task status
                location.reload();
            } else {
                let errorMessage = 'Failed to submit work: ' + (data.error || 'Unknown error');
                if (data.debug) {
                    errorMessage += '\n\nDebug info: ' + JSON.stringify(data.debug, null, 2);
                }
                showError(errorMessage);
            }
        })
        .catch(error => {
            console.error('Submit error:', error);
            showError('Failed to submit work: ' + error.message + '. Please check the console for details.');
        });
    });
}

function hideTaskSubmissionModal() {
    const modal = document.getElementById('task-submission-modal');
    if (modal) {
        modal.remove();
    }
}

// Duplicate showSuccess and showError functions removed - using the ones defined earlier

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

// OLD TASK UI FUNCTIONS REMOVED - Now handled by TaskManager
// addTaskToUI, removeTaskFromUI, updateTaskInUI are now in TaskManager

// Track session start time for duration calculation
window.sessionStartTime = Date.now();

// Session-end rating modal functions
function showSessionRatingModal(tradeId, ratedUserId, sessionDuration = 0) {
    const modal = document.getElementById('session-rating-modal');
    if (!modal) return;
    
    // Set form data
    document.getElementById('session-rating-trade-id').value = tradeId || '';
    document.getElementById('session-rating-rated-user-id').value = ratedUserId || '';
    document.getElementById('session-rating-duration').value = sessionDuration;
    
    // Reset form
    document.getElementById('session-rating-form').reset();
    resetAllStarRatings();
    
    // Show modal
        modal.style.display = 'block';
    }

function closeSessionRatingModal() {
    const modal = document.getElementById('session-rating-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function resetAllStarRatings() {
    document.querySelectorAll('.rating-stars').forEach(ratingGroup => {
        ratingGroup.querySelectorAll('.star').forEach(star => {
            star.classList.remove('active');
        });
    });
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
let sessionStart = new Date('{{ $trade->start_date }} {{ $trade->available_from ?? "00:00:00" }}');
let currentTimeElement = document.getElementById('current-time');
let sessionDurationElement = document.getElementById('session-duration');

// Update current time every second
let timeInterval = setInterval(function() {
    // Check if elements still exist before updating
    if (!currentTimeElement || !currentTimeElement.parentNode) {
        // Element was removed, clear interval
        clearInterval(timeInterval);
        timeInterval = null;
        return;
    }
    
    try {
        const now = new Date();
        if (currentTimeElement && currentTimeElement.textContent !== undefined) {
            currentTimeElement.textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }
        
        // Calculate session duration
        if (sessionDurationElement && sessionDurationElement.parentNode) {
            const diff = Math.floor((now - sessionStart) / 60000);
            if (diff < 0) {
                if (sessionDurationElement.textContent !== undefined) {
                    sessionDurationElement.textContent = 'Not started yet';
                }
            } else if (diff < 60) {
                if (sessionDurationElement.textContent !== undefined) {
                    sessionDurationElement.textContent = diff + ' minutes';
                }
            } else {
                const hours = Math.floor(diff / 60);
                const minutes = diff % 60;
                if (sessionDurationElement.textContent !== undefined) {
                    sessionDurationElement.textContent = hours + 'h ' + minutes + 'm';
                }
            }
        } else {
            // Element removed, clear interval
            clearInterval(timeInterval);
            timeInterval = null;
        }
    } catch (error) {
        console.warn('⚠️ Error updating time elements:', error);
        // Clear interval if elements are gone
        clearInterval(timeInterval);
        timeInterval = null;
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

// Report User: open/close helpers
function openReportUserModal() {
    var modal = document.getElementById('report-user-modal');
    if (modal) modal.style.display = 'block';
}

function closeReportUserModal() {
    var modal = document.getElementById('report-user-modal');
    if (modal) modal.style.display = 'none';
}

// Report User: submit
function submitUserReport() {
    const reason = document.getElementById('report_reason').value;
    const description = document.getElementById('report_description').value.trim();
    const reportedUserId = document.getElementById('reported_user_id').value;
    const submitBtn = document.getElementById('report-submit-btn');

    if (!reason) { showError('Please select a reason.'); return; }
    if (!description || description.length < 10) { showError('Please provide at least 10 characters.'); return; }

    submitBtn.disabled = true;
    const original = submitBtn.textContent;
    submitBtn.textContent = 'Submitting...';

    fetch('{{ route("chat.report-user", $trade->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            reported_user_id: parseInt(reportedUserId),
            reason: reason,
            description: description
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeReportUserModal();
            showSuccess('Report submitted. Thank you.');
        } else {
            showError(data.error || 'Failed to submit report.');
        }
    })
    .catch(err => {
        console.error('Report error:', err);
        showError('Failed to submit report. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = original;
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
    
    // Final confirmation
    if (confirm('Are you sure you want to end this session? This will process skill learning and update your profile. This action cannot be undone.')) {
        // Call backend API to complete session and process skill learning
        completeSession();
    }
}

function completeSession() {
    // Show loading state
    const endButton = document.querySelector('button[onclick="endSession()"]');
    const originalText = endButton.textContent;
    endButton.textContent = 'Processing...';
    endButton.disabled = true;
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    
    fetch('{{ route("chat.complete-session", $trade->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            throw new Error('Server returned HTML instead of JSON. This usually indicates a server error.');
        }
    })
    .then(data => {
        if (data.success) {
            // Show success message with skill learning results
            let successMessage = 'Session completed successfully!';
            if (data.skill_learning_results) {
                successMessage += '\n\nSkills learned and added to your profile!';
            }
            showSuccess(successMessage);
            
            // Show rating modal before redirecting
            setTimeout(() => {
                // Get the other user ID for rating
                const currentUserId = {{ auth()->user()->id }};
                const tradeOwnerId = {{ $trade->user_id }};
                const otherUserId = currentUserId === tradeOwnerId ? 
                    {{ $trade->requests()->where('status', 'accepted')->first()->requester_id ?? 'null' }} : 
                    tradeOwnerId;
                
                // Calculate session duration (if available)
                const sessionDuration = Math.floor((Date.now() - (window.sessionStartTime || Date.now())) / 1000);
                
                // Show rating modal with proper parameters
                if (otherUserId && typeof showSessionRatingModal === 'function') {
                    showSessionRatingModal({{ $trade->id }}, otherUserId, sessionDuration);
                } else {
                    // If no other user or rating modal not available, redirect directly
                window.location.href = '{{ route("trades.ongoing") }}';
                }
            }, 1500);
        } else {
            showError('Failed to complete session: ' + (data.error || 'Unknown error'));
            // Re-enable button
            endButton.textContent = originalText;
            endButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Complete session error:', error);
        showError('Failed to complete session: ' + error.message + '. Please try again.');
        // Re-enable button
        endButton.textContent = originalText;
        endButton.disabled = false;
    });
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
        document.getElementById('video-status').textContent = 'Initializing video chat...';
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

// initializeVideoChat function removed - using openVideoChat() instead

// Duplicate startVideoCall function removed - using the one defined earlier

// Metered API Configuration
const METERED_API_KEY = '511852cda421697270ed9af8b089038b39a7';
const METERED_API_URL = 'https://skillsxchange.metered.live/api/v1/turn/credentials';

// Fetch TURN server credentials from Metered API
// Duplicate fetchTurnCredentials function removed - using the one defined earlier

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

<style>
/* Desktop Styles for Tasks */
@media (min-width: 769px) {
    .desktop-only {
        display: inline-block !important;
    }
    
    .mobile-only {
        display: none !important;
    }
}

/* Mobile Responsive Styles for Tasks */
@media (max-width: 768px) {
    .main-content-container {
        flex-direction: row !important;
    }
    
    .chat-panel {
        flex: 1 !important;
        width: 100% !important;
        border-right: none !important;
        border-bottom: none !important;
        min-height: 100vh;
        max-height: 100vh;
        overflow-y: auto;
    }
    
    .tasks-sidebar {
        display: none !important; /* Completely hidden by default on mobile */
    }
    
    /* Show mobile toggle button */
    .mobile-only {
        display: inline-block !important;
    }
    
    /* Hide desktop close button on mobile */
    .desktop-only {
        display: none !important;
    }
    
    /* Show tasks when toggled - as overlay */
    .tasks-sidebar.show {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 1000 !important;
        background: white !important;
        display: flex !important;
        flex-direction: column !important;
    }
    
    /* Full screen mode for tasks */
    .tasks-sidebar.fullscreen {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 1000 !important;
        background: white !important;
        display: flex !important;
        flex-direction: column !important;
    }
    
    /* Make task items more mobile-friendly with more space */
    .task-item {
        margin-bottom: 12px !important;
        padding: 16px !important;
        min-height: 80px !important;
        border-radius: 8px !important;
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
    }
    
    .task-item > div {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 12px !important;
    }
    
    .task-item > div > div:first-child {
        width: 100% !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 12px !important;
        margin-bottom: 8px !important;
    }
    
    .task-item > div > div:last-child {
        width: 100% !important;
        justify-content: flex-start !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
        margin-top: 8px !important;
    }
    
    /* Make buttons larger and more touch-friendly on mobile */
    .task-item button {
        font-size: 0.8rem !important;
        padding: 8px 12px !important;
        min-height: 36px !important;
        min-width: 60px !important;
        border-radius: 6px !important;
        font-weight: 500 !important;
    }
    
    /* Adjust task details for mobile */
    .task-item > div:last-child {
        margin-left: 0 !important;
        font-size: 0.7rem !important;
        flex-direction: column !important;
        gap: 4px !important;
    }
    
    /* Make progress bars more visible on mobile */
    .task-item + div {
        margin-top: 8px !important;
    }
}

/* Tablet styles */
@media (max-width: 1024px) and (min-width: 769px) {
    .tasks-sidebar {
        width: 300px !important;
    }
}

/* Ensure tasks are always accessible */
@media (max-width: 768px) {
    .tasks-sidebar {
        position: relative !important;
        z-index: 10 !important;
    }
    
    /* Add a scroll indicator for tasks */
    .tasks-sidebar::after {
        content: "↑ Scroll to see more tasks";
        position: sticky;
        bottom: 0;
        background: #f3f4f6;
        padding: 8px;
        text-align: center;
        font-size: 0.75rem;
        color: #6b7280;
        border-top: 1px solid #e5e7eb;
        display: block;
    }
    
    /* Improve mobile task interaction */
    .task-item {
        touch-action: manipulation;
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
    }
    
    .task-item button {
        touch-action: manipulation;
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
        min-height: 32px;
        min-width: 32px;
    }
    
    /* Make dropdown menus more mobile-friendly */
    .dropdown-menu {
        position: absolute !important;
        z-index: 1000 !important;
        min-width: 120px !important;
    }
}

/* Improve task interaction on mobile */
@media (max-width: 768px) {
    .task-item input[type="checkbox"] {
        width: 24px !important;
        height: 24px !important;
        margin-right: 12px !important;
        accent-color: #3b82f6 !important;
    }
    
    .task-item span {
        font-size: 1rem !important;
        line-height: 1.5 !important;
        font-weight: 500 !important;
    }
    
    /* Make badges more readable on mobile */
    .task-item .badge {
        font-size: 0.75rem !important;
        padding: 4px 8px !important;
        margin: 2px !important;
        border-radius: 4px !important;
        font-weight: 500 !important;
    }
}

/* Notification animations */
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
</style>

{{-- Include session rating modal --}}
@include('components.ratings.session-end-modal')

{{-- Load session managers --}}

@endsection

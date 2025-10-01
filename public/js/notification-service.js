/**
 * Notification Service for Video Calls
 * Handles browser notifications, in-app alerts, and sound notifications
 */

class NotificationService {
    constructor() {
        this.permission = 'default';
        this.notification = null;
        this.audioContext = null;
        this.ringtone = null;
        this.isRinging = false;
        this.ringInterval = null;
        
        this.init();
    }
    
    async init() {
        // Request notification permission
        await this.requestNotificationPermission();
        
        // Initialize audio context for ringtone
        this.initAudio();
        
        console.log('🔔 Notification service initialized');
    }
    
    async requestNotificationPermission() {
        if (!('Notification' in window)) {
            console.warn('This browser does not support notifications');
            return false;
        }
        
        if (Notification.permission === 'granted') {
            this.permission = 'granted';
            return true;
        }
        
        if (Notification.permission !== 'denied') {
            const permission = await Notification.requestPermission();
            this.permission = permission;
            return permission === 'granted';
        }
        
        return false;
    }
    
    initAudio() {
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            this.createRingtone();
        } catch (error) {
            console.warn('Audio context not supported:', error);
        }
    }
    
    createRingtone() {
        // Create a simple ringtone using Web Audio API
        const sampleRate = this.audioContext.sampleRate;
        const duration = 0.5; // 0.5 seconds
        const buffer = this.audioContext.createBuffer(1, sampleRate * duration, sampleRate);
        const data = buffer.getChannelData(0);
        
        // Create a pleasant ringtone pattern
        for (let i = 0; i < data.length; i++) {
            const t = i / sampleRate;
            // Mix of frequencies for a nice ringtone
            data[i] = 0.3 * (
                Math.sin(2 * Math.PI * 800 * t) * Math.exp(-t * 3) +
                Math.sin(2 * Math.PI * 1000 * t) * Math.exp(-t * 3) +
                Math.sin(2 * Math.PI * 1200 * t) * Math.exp(-t * 3)
            );
        }
        
        this.ringtone = buffer;
    }
    
    playRingtone() {
        if (!this.audioContext || !this.ringtone || this.isRinging) return;
        
        this.isRinging = true;
        
        const playTone = () => {
            if (!this.isRinging) return;
            
            const source = this.audioContext.createBufferSource();
            source.buffer = this.ringtone;
            source.connect(this.audioContext.destination);
            source.start();
            
            // Play again after a short pause
            setTimeout(playTone, 600);
        };
        
        playTone();
    }
    
    stopRingtone() {
        this.isRinging = false;
    }
    
    showIncomingCallNotification(callerName, callerId, tradeId) {
        // Show browser notification
        this.showBrowserNotification(callerName, callerId, tradeId);
        
        // Show in-app notification
        this.showInAppNotification(callerName, callerId, tradeId);
        
        // Play ringtone
        this.playRingtone();
        
        // Update page title to show incoming call
        this.updatePageTitle(`📞 Incoming call from ${callerName}`);
    }
    
    showBrowserNotification(callerName, callerId, tradeId) {
        if (this.permission !== 'granted') return;
        
        const options = {
            body: `${callerName} is calling you`,
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            tag: `video-call-${tradeId}`,
            requireInteraction: true,
            actions: [
                {
                    action: 'answer',
                    title: 'Answer',
                    icon: '/images/answer-icon.png'
                },
                {
                    action: 'decline',
                    title: 'Decline',
                    icon: '/images/decline-icon.png'
                }
            ],
            data: {
                callerId: callerId,
                tradeId: tradeId,
                callerName: callerName
            }
        };
        
        this.notification = new Notification(`📞 Incoming Video Call`, options);
        
        this.notification.onclick = () => {
            window.focus();
            this.answerCall(callerId, tradeId);
            this.clearNotification();
        };
        
        // Auto-close after 30 seconds if not interacted with
        setTimeout(() => {
            if (this.notification) {
                this.notification.close();
            }
        }, 30000);
    }
    
    showInAppNotification(callerName, callerId, tradeId) {
        // Remove existing notification if any
        this.clearInAppNotification();
        
        const notificationHtml = `
            <div id="incoming-call-notification" class="incoming-call-notification">
                <div class="notification-content">
                    <div class="caller-info">
                        <div class="caller-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="caller-details">
                            <h4>${callerName}</h4>
                            <p>Incoming video call</p>
                        </div>
                    </div>
                    <div class="call-actions">
                        <button class="answer-btn" onclick="notificationService.answerCall(${callerId}, ${tradeId})">
                            <i class="fas fa-phone"></i>
                        </button>
                        <button class="decline-btn" onclick="notificationService.declineCall(${callerId}, ${tradeId})">
                            <i class="fas fa-phone-slash"></i>
                        </button>
                    </div>
                </div>
                <div class="notification-pulse"></div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', notificationHtml);
        
        // Add CSS if not already added
        this.addNotificationStyles();
    }
    
    addNotificationStyles() {
        if (document.getElementById('notification-styles')) return;
        
        const styles = `
            <style id="notification-styles">
                .incoming-call-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border-radius: 15px;
                    padding: 20px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                    z-index: 10000;
                    min-width: 300px;
                    animation: slideInRight 0.3s ease-out;
                    position: relative;
                    overflow: hidden;
                }
                
                .notification-content {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    position: relative;
                    z-index: 2;
                }
                
                .caller-info {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }
                
                .caller-avatar {
                    font-size: 2.5rem;
                    color: rgba(255,255,255,0.9);
                }
                
                .caller-details h4 {
                    margin: 0;
                    font-size: 1.2rem;
                    font-weight: 600;
                }
                
                .caller-details p {
                    margin: 5px 0 0 0;
                    font-size: 0.9rem;
                    opacity: 0.8;
                }
                
                .call-actions {
                    display: flex;
                    gap: 10px;
                }
                
                .answer-btn, .decline-btn {
                    width: 50px;
                    height: 50px;
                    border: none;
                    border-radius: 50%;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.2rem;
                    transition: all 0.2s ease;
                }
                
                .answer-btn {
                    background: #10b981;
                    color: white;
                }
                
                .answer-btn:hover {
                    background: #059669;
                    transform: scale(1.1);
                }
                
                .decline-btn {
                    background: #ef4444;
                    color: white;
                }
                
                .decline-btn:hover {
                    background: #dc2626;
                    transform: scale(1.1);
                }
                
                .notification-pulse {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(255,255,255,0.1);
                    border-radius: 15px;
                    animation: pulse 2s infinite;
                }
                
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes pulse {
                    0% {
                        transform: scale(1);
                        opacity: 1;
                    }
                    100% {
                        transform: scale(1.05);
                        opacity: 0;
                    }
                }
                
                .page-title-call {
                    animation: blink 1s infinite;
                }
                
                @keyframes blink {
                    0%, 50% { opacity: 1; }
                    51%, 100% { opacity: 0.5; }
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', styles);
    }
    
    updatePageTitle(title) {
        const originalTitle = document.title;
        document.title = title;
        
        // Add blinking effect to title
        document.title = `<span class="page-title-call">${title}</span>`;
        
        // Store original title for restoration
        this.originalTitle = originalTitle;
    }
    
    restorePageTitle() {
        if (this.originalTitle) {
            document.title = this.originalTitle;
        }
    }
    
    answerCall(callerId, tradeId) {
        console.log('📞 Answering call from:', callerId);
        
        // Stop ringtone
        this.stopRingtone();
        
        // Clear notifications
        this.clearNotification();
        this.clearInAppNotification();
        
        // Restore page title
        this.restorePageTitle();
        
        // Trigger answer call function
        if (window.handleIncomingCall) {
            window.handleIncomingCall(callerId, tradeId);
        }
    }
    
    declineCall(callerId, tradeId) {
        console.log('📞 Declining call from:', callerId);
        
        // Stop ringtone
        this.stopRingtone();
        
        // Clear notifications
        this.clearNotification();
        this.clearInAppNotification();
        
        // Restore page title
        this.restorePageTitle();
        
        // Send decline response
        this.sendCallResponse('decline', callerId, tradeId);
    }
    
    async sendCallResponse(action, callerId, tradeId) {
        try {
            const response = await fetch(`/api/video-call/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    caller_id: callerId,
                    trade_id: tradeId,
                    action: action
                })
            });
            
            if (!response.ok) {
                throw new Error('Failed to send call response');
            }
            
            console.log(`✅ Call ${action} sent successfully`);
        } catch (error) {
            console.error(`❌ Error sending call ${action}:`, error);
        }
    }
    
    clearNotification() {
        if (this.notification) {
            this.notification.close();
            this.notification = null;
        }
    }
    
    clearInAppNotification() {
        const notification = document.getElementById('incoming-call-notification');
        if (notification) {
            notification.remove();
        }
    }
    
    clearAllNotifications() {
        this.clearNotification();
        this.clearInAppNotification();
        this.stopRingtone();
        this.restorePageTitle();
    }
}

// Initialize notification service
window.notificationService = new NotificationService();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationService;
}

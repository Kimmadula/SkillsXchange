/**
 * Enhanced Session Monitor
 * Comprehensive session management with error handling and user notifications
 */
(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        SESSION_LIFETIME_MINUTES: 525600, // 1 year - sessions persist until logout
        WARNING_THRESHOLD_MINUTES: 0, // No warnings - sessions don't expire automatically
        REFRESH_INTERVAL_SECONDS: 900, // Check session every 15 minutes (further reduced frequency)
        KEEP_ALIVE_INTERVAL_SECONDS: 3600, // Send keep-alive every 60 minutes (reduced frequency)
        MAX_RETRY_ATTEMPTS: 2, // Reduced retry attempts
        RETRY_DELAY_MS: 3000, // Increased delay between retries
        REQUEST_THROTTLE_MS: 2000, // Minimum delay between requests
        PERSISTENT_SESSION: true // Sessions persist until explicit logout
    };

    // State management
    let state = {
        lastActivityTime: Date.now(),
        sessionWarningTimer: null,
        sessionLogoutTimer: null,
        keepAliveTimer: null,
        refreshTimer: null,
        retryCount: 0,
        isSessionExpired: false,
        isWarningShown: false,
        isOnline: navigator.onLine,
        isRequestInProgress: false, // Prevent duplicate requests
        lastRequestTime: 0 // Track last request time for throttling
    };

    // Initialize the session monitor
    function init() {
        console.log('Enhanced Session Monitor initialized');
        
        // Skip session monitoring for chat routes
        if (window.location.pathname.includes('/chat/')) {
            console.log('Skipping session monitoring for chat route');
            return;
        }
        
        // Set up event listeners
        setupEventListeners();
        
        // Start monitoring
        startSessionMonitoring();
        
        // Start keep-alive
        startKeepAlive();
        
        // Monitor online/offline status
        monitorConnectionStatus();
        
        // Check for existing session expiration
        checkSessionStatus();
    }

    /**
     * Set up event listeners for user activity
     */
    function setupEventListeners() {
        const activityEvents = [
            'mousemove', 'keydown', 'scroll', 'click', 'touchstart',
            'mousedown', 'keyup', 'focus', 'blur'
        ];

        activityEvents.forEach(eventType => {
            document.addEventListener(eventType, handleUserActivity, true);
        });

        // Handle page visibility changes
        document.addEventListener('visibilitychange', handleVisibilityChange);
        
        // Handle beforeunload
        window.addEventListener('beforeunload', handleBeforeUnload);
        
        // Handle online/offline events
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
    }

    /**
     * Handle user activity
     */
    function handleUserActivity() {
        if (state.isSessionExpired) return;
        
        state.lastActivityTime = Date.now();
        
        // Reset timers on activity
        if (!state.isWarningShown) {
            resetTimers();
        }
    }

    /**
     * Handle page visibility change
     */
    function handleVisibilityChange() {
        if (document.hidden) {
            console.log('Page hidden - pausing session monitoring');
            pauseMonitoring();
        } else {
            console.log('Page visible - resuming session monitoring');
            resumeMonitoring();
        }
    }

    /**
     * Handle before page unload
     */
    function handleBeforeUnload(event) {
        if (state.isSessionExpired) {
            event.preventDefault();
            event.returnValue = 'Your session has expired. Please log in again.';
            return event.returnValue;
        }
    }

    /**
     * Handle online status
     */
    function handleOnline() {
        state.isOnline = true;
        console.log('Connection restored');
        
        // Resume monitoring
        resumeMonitoring();
        
        // Check session status
        checkSessionStatus();
    }

    /**
     * Handle offline status
     */
    function handleOffline() {
        state.isOnline = false;
        console.log('Connection lost');
        
        // Pause monitoring
        pauseMonitoring();
    }

    /**
     * Start session monitoring
     */
    function startSessionMonitoring() {
        resetTimers();
        startRefreshTimer();
    }

    /**
     * Reset all timers
     */
    function resetTimers() {
        clearTimeout(state.sessionWarningTimer);
        clearTimeout(state.sessionLogoutTimer);
        state.isWarningShown = false;
        startTimers();
    }

    /**
     * Start warning and logout timers
     */
    function startTimers() {
        // For persistent sessions, we don't set automatic expiration timers
        // Sessions will only expire when user explicitly logs out
        if (CONFIG.PERSISTENT_SESSION) {
            console.log('Persistent session mode - no automatic expiration');
            return;
        }

        const lifetimeMs = CONFIG.SESSION_LIFETIME_MINUTES * 60 * 1000;
        const warningThresholdMs = CONFIG.WARNING_THRESHOLD_MINUTES * 60 * 1000;

        // Set warning timer (only if not persistent)
        if (warningThresholdMs > 0) {
            state.sessionWarningTimer = setTimeout(() => {
                showSessionWarning();
            }, lifetimeMs - warningThresholdMs);
        }

        // Set logout timer (only if not persistent)
        if (lifetimeMs > 0) {
            state.sessionLogoutTimer = setTimeout(() => {
                handleSessionExpiration();
            }, lifetimeMs);
        }
    }

    /**
     * Start refresh timer
     */
    function startRefreshTimer() {
        state.refreshTimer = setInterval(() => {
            if (state.isOnline && !state.isSessionExpired) {
                checkSessionStatus();
            }
        }, CONFIG.REFRESH_INTERVAL_SECONDS * 1000);
    }

    /**
     * Start keep-alive timer
     */
    function startKeepAlive() {
        state.keepAliveTimer = setInterval(() => {
            if (state.isOnline && !state.isSessionExpired) {
                sendKeepAlive();
            }
        }, CONFIG.KEEP_ALIVE_INTERVAL_SECONDS * 1000);
    }

    /**
     * Pause monitoring
     */
    function pauseMonitoring() {
        clearTimeout(state.sessionWarningTimer);
        clearTimeout(state.sessionLogoutTimer);
        clearInterval(state.refreshTimer);
        clearInterval(state.keepAliveTimer);
    }

    /**
     * Resume monitoring
     */
    function resumeMonitoring() {
        if (state.isSessionExpired) return;
        
        startSessionMonitoring();
        startKeepAlive();
    }

    /**
     * Check session status
     */
    function checkSessionStatus() {
        if (!state.isOnline || state.isSessionExpired || state.isRequestInProgress) return;
        
        // Throttle requests to prevent rate limiting
        const now = Date.now();
        if (now - state.lastRequestTime < CONFIG.REQUEST_THROTTLE_MS) {
            console.log('Request throttled to prevent rate limiting');
            return;
        }
        
        state.isRequestInProgress = true;
        state.lastRequestTime = now;

        fetch('/user/session-status', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.status === 401) {
                // Only handle 401 if not in persistent mode
                if (!CONFIG.PERSISTENT_SESSION) {
                    handleSessionExpiration();
                }
                return;
            }
            return response.json();
        })
        .then(data => {
            state.isRequestInProgress = false; // Reset request flag
            if (data && data.expired && !CONFIG.PERSISTENT_SESSION) {
                handleSessionExpiration();
            } else if (data && data.status === 'authenticated') {
                state.retryCount = 0; // Reset retry count on success
                console.log('Session is active and persistent');
            }
        })
        .catch(error => {
            state.isRequestInProgress = false; // Reset request flag
            console.error('Session status check failed:', error);
            handleSessionCheckError();
        });
    }

    /**
     * Send keep-alive request
     */
    function sendKeepAlive() {
        if (!state.isOnline || state.isSessionExpired) return;
        
        // Throttle requests to prevent rate limiting
        const now = Date.now();
        if (now - state.lastRequestTime < CONFIG.REQUEST_THROTTLE_MS) {
            console.log('Keep-alive request throttled to prevent rate limiting');
            return;
        }
        
        state.lastRequestTime = now;

        fetch('/user/keep-alive', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.status === 401) {
                handleSessionExpiration();
                return;
            }
            return response.json();
        })
        .then(data => {
            if (data && data.status === 'success') {
                console.log('Session keep-alive successful');
                state.retryCount = 0; // Reset retry count on success
            } else {
                console.warn('Keep-alive failed:', data?.message);
                handleSessionCheckError();
            }
        })
        .catch(error => {
            console.error('Keep-alive request failed:', error);
            handleSessionCheckError();
        });
    }

    /**
     * Handle session check errors
     */
    function handleSessionCheckError() {
        state.retryCount++;
        
        if (state.retryCount >= CONFIG.MAX_RETRY_ATTEMPTS) {
            console.error('Max retry attempts reached. Session may be expired.');
            handleSessionExpiration();
        } else {
            console.log(`Retrying session check in ${CONFIG.RETRY_DELAY_MS}ms (attempt ${state.retryCount})`);
            setTimeout(() => {
                checkSessionStatus();
            }, CONFIG.RETRY_DELAY_MS);
        }
    }

    /**
     * Show session warning modal
     */
    function showSessionWarning() {
        if (state.isWarningShown || state.isSessionExpired) return;
        
        state.isWarningShown = true;
        
        // Create or show warning modal
        const modal = getOrCreateWarningModal();
        if (modal) {
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        } else {
            // Fallback alert
            alert('Your session will expire soon due to inactivity. Please click OK to extend your session.');
            extendSession();
        }
    }

    /**
     * Get or create warning modal
     */
    function getOrCreateWarningModal() {
        let modal = document.getElementById('sessionWarningModal');
        
        if (!modal) {
            modal = createWarningModal();
            document.body.appendChild(modal);
        }
        
        return modal;
    }

    /**
     * Create warning modal
     */
    function createWarningModal() {
        const modal = document.createElement('div');
        modal.id = 'sessionWarningModal';
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-labelledby', 'sessionWarningModalLabel');
        modal.setAttribute('aria-hidden', 'true');
        
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="sessionWarningModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>Session Expiring Soon!
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Your session will expire in ${CONFIG.WARNING_THRESHOLD_MINUTES} minutes due to inactivity.</p>
                        <p>Do you want to extend your session?</p>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Tip:</strong> You can also extend your session by clicking anywhere on the page.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="logoutBtn">
                            <i class="fas fa-sign-out-alt me-1"></i>Log Out
                        </button>
                        <button type="button" class="btn btn-primary" id="extendSessionBtn">
                            <i class="fas fa-clock me-1"></i>Extend Session
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Add event listeners
        modal.querySelector('#extendSessionBtn').addEventListener('click', extendSession);
        modal.querySelector('#logoutBtn').addEventListener('click', logoutUser);
        
        // Handle modal close
        modal.addEventListener('hidden.bs.modal', function() {
            if (!state.isSessionExpired) {
                console.log('Session warning modal closed. Logout timer still active.');
            }
        });
        
        return modal;
    }

    /**
     * Extend session
     */
    function extendSession() {
        if (state.isSessionExpired) return;
        
        sendKeepAlive();
        
        // Hide modal if open
        const modal = document.getElementById('sessionWarningModal');
        if (modal) {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) bootstrapModal.hide();
        }
        
        // Reset warning state
        state.isWarningShown = false;
        
        // Show success message
        showNotification('Session extended successfully!', 'success');
    }

    /**
     * Handle session expiration
     */
    function handleSessionExpiration() {
        if (state.isSessionExpired) return;
        
        state.isSessionExpired = true;
        console.log('Session expired - redirecting to login');
        
        // Clear all timers
        pauseMonitoring();
        
        // Hide any open modals
        const modal = document.getElementById('sessionWarningModal');
        if (modal) {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) bootstrapModal.hide();
        }
        
        // Show expiration message
        showNotification('Your session has expired. Redirecting to login...', 'warning');
        
        // Redirect to login with expired flag
        setTimeout(() => {
            window.location.href = '/login?expired=true';
        }, 2000);
    }

    /**
     * Logout user
     */
    function logoutUser() {
        console.log('User initiated logout');
        
        // Clear all timers
        pauseMonitoring();
        
        // Redirect to logout
        window.location.href = '/logout';
    }

    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        
        notification.innerHTML = `
            <i class="fas fa-${getIconForType(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    /**
     * Get icon for notification type
     */
    function getIconForType(type) {
        const icons = {
            'success': 'check-circle',
            'warning': 'exclamation-triangle',
            'error': 'exclamation-circle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    /**
     * Monitor connection status
     */
    function monitorConnectionStatus() {
        setInterval(() => {
            const isOnline = navigator.onLine;
            if (isOnline !== state.isOnline) {
                if (isOnline) {
                    handleOnline();
                } else {
                    handleOffline();
                }
            }
        }, 1000);
    }

    /**
     * Get CSRF token
     */
    function getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    /**
     * Public API
     */
    window.SessionMonitor = {
        extendSession: extendSession,
        checkStatus: checkSessionStatus,
        isExpired: () => state.isSessionExpired,
        isOnline: () => state.isOnline,
        getLastActivity: () => state.lastActivityTime,
        forceLogout: logoutUser
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

/**
 * Session Monitor - Handles client-side session expiration
 */
class SessionMonitor {
    constructor() {
        this.sessionTimeout = 60 * 60 * 1000; // 60 minutes in milliseconds
        this.warningTime = 5 * 60 * 1000; // 5 minutes before expiration
        this.checkInterval = 30 * 1000; // Check every 30 seconds
        this.lastActivity = Date.now();
        this.warningShown = false;
        
        this.init();
    }
    
    init() {
        // Track user activity
        this.trackActivity();
        
        // Start monitoring
        this.startMonitoring();
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                this.checkSession();
            }
        });
    }
    
    trackActivity() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.lastActivity = Date.now();
                this.warningShown = false;
            }, true);
        });
    }
    
    startMonitoring() {
        setInterval(() => {
            this.checkSession();
        }, this.checkInterval);
    }
    
    checkSession() {
        const now = Date.now();
        const timeSinceActivity = now - this.lastActivity;
        const timeUntilExpiration = this.sessionTimeout - timeSinceActivity;
        
        // Show warning if session is about to expire
        if (timeUntilExpiration <= this.warningTime && timeUntilExpiration > 0 && !this.warningShown) {
            this.showWarning(timeUntilExpiration);
        }
        
        // Redirect to login if session has expired
        if (timeSinceActivity >= this.sessionTimeout) {
            this.handleSessionExpiration();
        }
    }
    
    showWarning(timeUntilExpiration) {
        this.warningShown = true;
        const minutes = Math.ceil(timeUntilExpiration / (60 * 1000));
        
        // Create warning modal
        const modal = document.createElement('div');
        modal.id = 'session-warning-modal';
        modal.className = 'modal fade show';
        modal.style.display = 'block';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Session Expiring Soon
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p>Your session will expire in <strong>${minutes} minute(s)</strong> due to inactivity.</p>
                        <p>Click "Extend Session" to continue, or you will be automatically logged out.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">
                            Logout Now
                        </button>
                        <button type="button" class="btn btn-primary" onclick="sessionMonitor.extendSession()">
                            Extend Session
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'session-warning-backdrop';
        
        document.body.appendChild(modal);
        document.body.appendChild(backdrop);
        
        // Auto-remove after warning time
        setTimeout(() => {
            if (document.getElementById('session-warning-modal')) {
                this.handleSessionExpiration();
            }
        }, timeUntilExpiration);
    }
    
    extendSession() {
        this.lastActivity = Date.now();
        this.warningShown = false;
        
        // Remove warning modal
        const modal = document.getElementById('session-warning-modal');
        const backdrop = document.getElementById('session-warning-backdrop');
        
        if (modal) modal.remove();
        if (backdrop) backdrop.remove();
        
        // Show success message
        this.showMessage('Session extended successfully!', 'success');
    }
    
    handleSessionExpiration() {
        // Remove any existing modals
        const modal = document.getElementById('session-warning-modal');
        const backdrop = document.getElementById('session-warning-backdrop');
        
        if (modal) modal.remove();
        if (backdrop) backdrop.remove();
        
        // Show expiration message
        this.showMessage('Your session has expired. Redirecting to login...', 'warning');
        
        // Redirect to login after a short delay
        setTimeout(() => {
            window.location.href = '/login?expired=1';
        }, 2000);
    }
    
    showMessage(message, type) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button>
            </div>
        `;
        
        // Add to toast container
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        
        container.appendChild(toast);
        
        // Show toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }
}

// Initialize session monitor when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.sessionMonitor = new SessionMonitor();
});

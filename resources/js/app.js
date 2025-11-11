// Import managers
import { ChatManager } from './chat/ChatManager.js';
import { VideoCallManager } from './video/VideoCallManager.js';
import { TaskManager } from './tasks/TaskManager.js';
// Import session UI helpers (tab switching, etc.)
import './session.js';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Initializing SkillsXchange Session...');

    // Get data from window variables (set by Blade template) or DOM attributes
    const tradeId = window.tradeId || parseInt(document.querySelector('.app-container')?.dataset.tradeId || 
                     document.querySelector('[data-trade-id]')?.dataset.tradeId || '0');
    const userId = window.authUserId || window.currentUserId || 
                  parseInt(document.querySelector('.app-container')?.dataset.userId || 
                     document.querySelector('[data-user-id]')?.dataset.userId || '0');
    const partnerId = window.partnerId || 
                      parseInt(document.querySelector('.app-container')?.dataset.partnerId || 
                     document.querySelector('[data-partner-id]')?.dataset.partnerId || '0');
    const partnerName = window.partnerName || 'Partner';

    // Validate data
    if (!tradeId || !userId) {
        console.error('Missing required data: tradeId or userId');
        console.error('Available window variables:', {
            tradeId: window.tradeId,
            userId: window.authUserId || window.currentUserId,
            partnerId: window.partnerId
        });
        return;
    }

    if (!partnerId) {
        console.warn('Partner ID not found, some features may be limited');
    }

    console.log('📊 Session Data:', { tradeId, userId, partnerId, partnerName });

    // Check for Laravel Echo
    if (typeof window.Echo === 'undefined') {
        console.warn('⚠️ Laravel Echo not available, real-time features will use polling fallback');
    }

    // Initialize managers
    try {
        let chatManager, videoManager, taskManager;

        // Chat Manager
        try {
            chatManager = new ChatManager(tradeId, userId, window.Echo, partnerName);
            chatManager.initialize();
            console.log('✅ Chat Manager initialized');
        } catch (error) {
            console.error('❌ Chat Manager initialization failed:', error);
        }

        // Video Call Manager
        try {
            videoManager = new VideoCallManager(
                tradeId,
                userId,
                partnerId,
                window.firebaseVideoCall // From external script, may be null initially
            );
            videoManager.initialize();
            console.log('✅ Video Call Manager initialized');
        } catch (error) {
            console.error('❌ Video Call Manager initialization failed:', error);
        }

        // Task Manager
        try {
            taskManager = new TaskManager(tradeId, userId, window.Echo);
            taskManager.initialize();
            console.log('✅ Task Manager initialized');
        } catch (error) {
            console.error('❌ Task Manager initialization failed:', error);
        }

        // Expose to window for debugging and global access
        window.app = {
            chat: chatManager,
            video: videoManager,
            tasks: taskManager,
            tradeId,
            userId,
            partnerId,
            partnerName
        };

        // Set up global event handlers that need to be accessible from blade
        // These override the ones set in individual managers for consistency
        window.openVideoChat = () => {
            if (window.app.video) {
                window.app.video.openVideoChat();
            } else {
                console.error('Video Manager not available');
            }
        };

        window.closeVideoChat = () => {
            if (window.app.video) {
                window.app.video.closeVideoChat();
            }
        };

        window.toggleTask = (taskId) => {
            if (window.app.tasks) {
                window.app.tasks.toggleTask(taskId);
            } else {
                console.error('Task Manager not available');
            }
        };

        window.editTask = (taskId) => {
            if (window.app.tasks) {
                window.app.tasks.editTask(taskId);
            } else {
                console.error('Task Manager not available');
            }
        };

        window.deleteTask = (taskId) => {
            if (window.app.tasks) {
                window.app.tasks.deleteTask(taskId);
            } else {
                console.error('Task Manager not available');
            }
        };

        window.showAddTaskModal = () => {
            if (window.app.tasks) {
                window.app.tasks.showAddTaskModal();
            } else {
                console.error('Task Manager not available');
            }
        };

        window.hideAddTaskModal = () => {
            if (window.app.tasks) {
                window.app.tasks.hideAddTaskModal();
            }
        };

        window.showEditTaskModal = () => {
            if (window.app.tasks) {
                window.app.tasks.showEditTaskModal();
            } else {
                console.error('Task Manager not available');
            }
        };

        window.hideEditTaskModal = () => {
            if (window.app.tasks) {
                window.app.tasks.hideEditTaskModal();
            } else {
                console.error('Task Manager not available');
            }
        };

        window.handleEditTaskModalClick = (event) => {
            if (window.app.tasks) {
                window.app.tasks.handleEditTaskModalClick(event);
            } else {
                console.error('Task Manager not available');
            }
        };

        window.endSession = () => {
            if (confirm('Are you sure you want to end this session?')) {
                window.location.href = `/chat/${tradeId}/complete`;
            }
        };

        window.openReportUserModal = () => {
            // TODO: Implement report modal
            console.log('Open report modal');
            alert('Report user feature coming soon');
        };

		// Legacy compatibility shims for older inline Blade functions
		window.addMessageToChat = (message, senderName, timestamp, isOwn) => {
			try {
				if (window.app && window.app.chat && typeof window.app.chat.addMessage === 'function') {
					return window.app.chat.addMessage(message, senderName, timestamp, isOwn);
				}
			} catch (e) {
				console.error('addMessageToChat shim error:', e);
			}
		};

		window.handleModalClick = (event) => {
			// Close the add-task modal when clicking on the overlay
			if (event && event.target && event.target.id === 'add-task-modal') {
				if (window.app && window.app.tasks) {
					window.app.tasks.hideAddTaskModal();
				}
			}
		};

        // Cross-manager communication handlers
        // If a task is completed, we might want to notify chat or vice versa
        if (window.app.tasks && window.app.chat) {
            // Example: When task is completed, could send a chat message
            // This is optional and can be implemented later
        }

        console.log('✅ All managers initialized successfully');
        console.log('💡 Access managers via window.app in console');
        console.log('💡 Available methods:');
        console.log('   - window.app.chat.sendMessage()');
        console.log('   - window.app.video.startCall()');
        console.log('   - window.app.tasks.createTask()');

    } catch (error) {
        console.error('❌ Initialization error:', error);
        console.error('Stack trace:', error.stack);
    }

    // Handle page unload
    window.addEventListener('beforeunload', () => {
        console.log('🧹 Cleaning up...');
        
        // Cleanup managers
        if (window.app) {
            if (window.app.chat && typeof window.app.chat.destroy === 'function') {
                window.app.chat.destroy();
            }
            if (window.app.video && typeof window.app.video.destroy === 'function') {
                window.app.video.destroy();
            }
            // Task manager doesn't need cleanup, but we could add it if needed
        }
    });

    // Handle visibility change (tab switching)
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            console.log('📱 Tab hidden');
        } else {
            console.log('📱 Tab visible');
            // Could refresh data or reconnect here if needed
        }
    });
});

// Export for potential use in other files
export { ChatManager, VideoCallManager, TaskManager };

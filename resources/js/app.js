// Import bootstrap first to initialize Echo and Pusher
import './bootstrap.js';

// Import managers
import { ChatManager } from './chat/ChatManager.js';
import { VideoCallManager } from './video/VideoCallManager.js';
import { TaskManager } from './tasks/TaskManager.js';
// Import session UI helpers (tab switching, etc.)
import './session.js';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', async () => {
    // Check if we're on a page that needs session initialization
    // Only initialize on pages with trade session data (chat pages, session pages)
    const hasSessionContainer = document.querySelector('.app-container') || 
                                document.querySelector('[data-trade-id]') ||
                                document.querySelector('#chat-messages') ||
                                document.querySelector('.chat-panel');
    
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

    // Only initialize if we have required data AND we're on a session page
    // Silently skip initialization on pages that don't need it (like listing pages)
    if (!tradeId || !userId) {
        // Only log if we're on a page that should have this data
        if (hasSessionContainer) {
            console.warn('⚠️ Session initialization skipped: Missing tradeId or userId');
            console.warn('Available window variables:', {
                tradeId: window.tradeId,
                userId: window.authUserId || window.currentUserId,
                partnerId: window.partnerId
            });
        }
        // Silently return - this is expected on pages like /trades/ongoing
        return;
    }
    
    console.log('🚀 Initializing SkillsXchange Session...');

    if (!partnerId) {
        console.warn('Partner ID not found, some features may be limited');
    }

    console.log('📊 Session Data:', { tradeId, userId, partnerId, partnerName });

    // Wait for Echo to be available (it might load via Vite/bootstrap.js or fallback)
    const waitForEcho = (maxWait = 3000) => {
        return new Promise((resolve) => {
            if (typeof window.Echo !== 'undefined' && window.Echo !== null) {
                console.log('✅ Laravel Echo is available');
                resolve(window.Echo);
                return;
            }
            
            let waitTime = 0;
            const checkInterval = setInterval(() => {
                waitTime += 100;
                if (typeof window.Echo !== 'undefined' && window.Echo !== null) {
                    clearInterval(checkInterval);
                    console.log('✅ Laravel Echo loaded after', waitTime, 'ms');
                    resolve(window.Echo);
                } else if (waitTime >= maxWait) {
                    clearInterval(checkInterval);
                    console.warn('⚠️ Laravel Echo not available after', maxWait, 'ms, using polling fallback');
                    resolve(null);
                }
            }, 100);
        });
    };

    // Wait for Echo before initializing managers
    const echo = await waitForEcho(3000);

    // Initialize managers
    try {
        let chatManager, videoManager, taskManager;

        // Chat Manager
        try {
            chatManager = new ChatManager(tradeId, userId, echo || window.Echo, partnerName);
            chatManager.initialize();
            console.log('✅ Chat Manager initialized');
            
            // If Echo becomes available later, re-initialize listeners
            if (!echo) {
                const checkEchoInterval = setInterval(() => {
                    if (typeof window.Echo !== 'undefined' && window.Echo !== null) {
                        clearInterval(checkEchoInterval);
                        console.log('🔄 Echo became available, re-initializing Chat Manager...');
                        chatManager.echo = window.Echo;
                        // Stop polling if running
                        if (chatManager.messagePollingInterval) {
                            clearInterval(chatManager.messagePollingInterval);
                            chatManager.messagePollingInterval = null;
                        }
                        chatManager.setupEchoListeners();
                    }
                }, 500);
                // Stop checking after 10 seconds
                setTimeout(() => clearInterval(checkEchoInterval), 10000);
            }
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
            taskManager = new TaskManager(tradeId, userId, echo || window.Echo);
            taskManager.initialize();
            console.log('✅ Task Manager initialized');
            
            // If Echo becomes available later, re-initialize listeners
            if (!echo) {
                const checkEchoInterval = setInterval(() => {
                    if (typeof window.Echo !== 'undefined' && window.Echo !== null) {
                        clearInterval(checkEchoInterval);
                        console.log('🔄 Echo became available, re-initializing Task Manager...');
                        taskManager.echo = window.Echo;
                        taskManager.setupEchoListeners();
                    }
                }, 500);
                // Stop checking after 10 seconds
                setTimeout(() => clearInterval(checkEchoInterval), 10000);
            }
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

        // openReportUserModal is now implemented in session.blade.php
        // The function is defined inline in the session view, so we don't define it here
        // to avoid conflicts. The session view's function will handle opening the modal.

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

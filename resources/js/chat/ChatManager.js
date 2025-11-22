export class ChatManager {
    constructor(tradeId, userId, echo, partnerName) {
        this.tradeId = tradeId;
        this.userId = userId;
        this.echo = echo;
        this.partnerName = partnerName;
        this.messagesContainer = document.getElementById('messages');
        this.messageForm = document.getElementById('message-form');
        this.messageInput = document.getElementById('message-input');
        this.sendButton = document.getElementById('send-button');
        this.presenceStatus = document.getElementById('presence-status');

        // State management
        this.isSending = false;
        // Initialize with count of messages already rendered on page to prevent duplicates
        this.lastMessageCount = window.initialMessageCount || this.getInitialMessageCount();
        this.messagePollingInterval = null;
        this.messageListenerSetup = false; // Prevent duplicate listener setup
        this.pollingFrequency = 5000; // Start with 5 seconds (optimized)
        this.minPollingFrequency = 5000; // Never poll faster than 5 seconds
        this.maxPollingFrequency = 15000; // Never poll slower than 15 seconds
        this.lastActivity = Date.now();
        this.consecutiveEmptyPolls = 0;
        this.isPolling = false; // Prevent overlapping requests
        this.lastPollTime = 0; // Track last poll time for debouncing
        this.authFailureCount = 0; // Track authentication failures

        // CSRF token
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                        document.querySelector('[name="csrf-token"]')?.content;
    }

    initialize() {
        // Ensure lastMessageCount is set correctly based on existing messages
        if (this.lastMessageCount === 0) {
            this.lastMessageCount = this.getInitialMessageCount();
        }

        this.setupEventListeners();
        this.setupEchoListeners();
        this.setupPollingFallback();
        this.formatExistingTimestamps();
        this.scrollToBottom();
    }

    getInitialMessageCount() {
        // Count messages already rendered on the page
        if (this.messagesContainer) {
            const existingMessages = this.messagesContainer.querySelectorAll('.message');
            return existingMessages.length;
        }
        // Fallback to window variable if available
        return window.initialMessageCount || 0;
    }

    formatExistingTimestamps() {
        // Format all existing timestamps from server to user's local timezone
        // But preserve server-provided display_time if it exists (more accurate)
        const timestampElements = document.querySelectorAll('.message-time[data-timestamp]');
        timestampElements.forEach(element => {
            // Check if element already has server-formatted time (from display_time)
            if (element.textContent && element.textContent.match(/\d{1,2}:\d{2}\s?(AM|PM)/i)) {
                // Already formatted by server, keep it
                return;
            }

            const timestamp = element.getAttribute('data-timestamp');
            if (timestamp) {
                try {
                    const date = new Date(timestamp);
                    // Use 12-hour format with AM/PM to match server format
                    const localTime = date.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                    element.textContent = localTime;
                } catch (e) {
                    console.error('Error formatting timestamp:', e);
                }
            }
        });
    }

    setupEventListeners() {
        // Handle form submission
        this.messageForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });

        // Handle Enter key (send), Shift+Enter (new line)
        this.messageInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Handle image upload button
        const sendImageBtn = document.getElementById('send-image-btn');
        if (sendImageBtn) {
            sendImageBtn.addEventListener('click', () => {
                this.handleImageUpload();
            });
        }

        // Handle file upload button
        const attachFileBtn = document.getElementById('attach-file-btn');
        if (attachFileBtn) {
            attachFileBtn.addEventListener('click', () => {
                this.handleFileUpload();
            });
        }

        // Handle emoji button
        const emojiButton = document.getElementById('emoji-button');
        if (emojiButton) {
            emojiButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleEmojiPicker();
            });
        }

        // Handle emoji selection
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('emoji-item')) {
                const emoji = e.target.getAttribute('data-emoji');
                this.insertEmoji(emoji);
            }
        });

        // Close emoji picker when clicking outside
        document.addEventListener('click', (e) => {
            const picker = document.getElementById('emoji-picker');
            const button = document.getElementById('emoji-button');
            if (picker && button && !picker.contains(e.target) && !button.contains(e.target)) {
                picker.style.display = 'none';
            }
        });

        // Track user activity for polling optimization
        ['click', 'keypress', 'mousemove', 'scroll'].forEach(event => {
            document.addEventListener(event, () => {
                this.lastActivity = Date.now();

                // If user becomes active and polling is slow, speed it up
                if (this.pollingFrequency > 5000) {
                    this.pollingFrequency = 5000;
                    this.startSmartMessagePolling();
                }
            }, { passive: true });
        });
    }

    handleImageUpload() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = false;

        input.onchange = (e) => {
            const file = e.target.files[0];
            if (file) {
                this.uploadFile(file, 'image');
            }
        };

        input.click();
    }

    handleFileUpload() {
        const input = document.createElement('input');
        input.type = 'file';
        input.multiple = false;

        input.onchange = (e) => {
            const file = e.target.files[0];
            if (file) {
                this.uploadFile(file, 'file');
            }
        };

        input.click();
    }

    async uploadFile(file, type = 'file') {
        // Validate file size (max 10MB)
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            this.showError('File size must be less than 10MB');
            return;
        }

        // Show loading state
        const originalText = this.sendButton?.textContent;
        if (this.sendButton) {
            this.sendButton.textContent = 'Uploading...';
            this.sendButton.disabled = true;
        }

        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', type);
            formData.append('message', ''); // Empty message for file-only messages

            const url = `/chat/${this.tradeId}/message`;

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData,
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                console.log('✅ File uploaded successfully');
                // The message from server already contains the file reference with URL
                // Just add it to UI - the server response should have the formatted message
                const messageText = data.message?.message || (type === 'image' ? `[IMAGE:${file.name}]` : `[FILE:${file.name}]`);
                const currentUserName = document.querySelector('[data-user-name]')?.getAttribute('data-user-name') || 'You';

                // If it's an image, create a preview URL for immediate display
                if (type === 'image' && file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        window.tempImageData = e.target.result;
                        this.addMessage(messageText, currentUserName, new Date().toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true}), true);
                    };
                    reader.readAsDataURL(file);
                } else {
                    this.addMessage(messageText, currentUserName, new Date().toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true}), true);
                }

                // Clear input if needed
                if (this.messageInput) {
                    this.messageInput.value = '';
                }
            } else {
                throw new Error(data.error || 'Failed to upload file');
            }
        } catch (error) {
            console.error('Upload file error:', error);
            this.showError('Failed to upload file. Please try again.');
        } finally {
            // Reset button state
            if (this.sendButton) {
                this.sendButton.disabled = false;
                this.sendButton.textContent = originalText || 'Send';
            }
        }
    }

    setupEchoListeners() {
        if (!this.echo) {
            console.warn('Laravel Echo not available, using polling fallback');
            this.startSmartMessagePolling();
            return;
        }

        // Stop polling if it's running - we're switching to real-time
        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
            this.messagePollingInterval = null;
            console.log('🔄 Stopped polling, switching to Pusher real-time');
        }

        // Connection status listeners
        if (this.echo.connector?.pusher?.connection) {
            this.echo.connector.pusher.connection.bind('connected', () => {
                console.log('✅ Pusher connected - real-time messaging enabled');
                // Stop polling when Pusher successfully connects
                if (this.messagePollingInterval) {
                    clearInterval(this.messagePollingInterval);
                    this.messagePollingInterval = null;
                    console.log('🔄 Stopped polling - Pusher is now connected');
                }
                this.updateConnectionStatus('connected');
            });

            this.echo.connector.pusher.connection.bind('disconnected', () => {
                console.warn('⚠️ Pusher disconnected - falling back to polling');
                this.updateConnectionStatus('disconnected');
                // Fallback to polling if disconnected
                if (!this.messagePollingInterval) {
                    this.startSmartMessagePolling();
                }
            });

            this.echo.connector.pusher.connection.bind('error', () => {
                console.error('❌ Pusher connection error - falling back to polling');
                this.updateConnectionStatus('error');
                // Fallback to polling on error
                if (!this.messagePollingInterval) {
                    this.startSmartMessagePolling();
                }
            });

            this.echo.connector.pusher.connection.bind('connecting', () => {
                console.log('🔄 Pusher connecting...');
                this.updateConnectionStatus('connecting');
            });
        }

        // Listen for new messages via Pusher (real-time)
        // Laravel Echo automatically handles channel subscription for public channels
        const channelName = `trade-${this.tradeId}`;

        // Prevent duplicate listener setup
        if (this.messageListenerSetup) {
            console.log('⚠️ Message listener already set up, skipping...');
            return;
        }

        try {
            console.log('📡 Setting up message listener for channel:', channelName);

            // Get or create the channel - Echo handles subscription automatically
            const channel = this.echo.channel(channelName);

            if (!channel) {
                throw new Error('Failed to create channel');
            }

            console.log('📡 Channel created:', channelName);

            // Set up the listener - this automatically subscribes to the channel
            // The event name must match broadcastAs() in MessageSent.php: 'new-message'
            channel.listen('new-message', (data) => {
                console.log('📨 Received new message via Pusher:', data);

                // Validate data structure
                if (!data) {
                    console.error('❌ No data received');
                    return;
                }

                // Handle both possible data structures
                let messageData, senderName, timestamp;

                if (data.message) {
                    // Standard structure from broadcastWith()
                    messageData = data.message;
                    senderName = data.sender_name || (messageData.sender ? `${messageData.sender.firstname || ''} ${messageData.sender.lastname || ''}`.trim() : '');
                    timestamp = data.timestamp || (messageData.created_at ? new Date(messageData.created_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : '');
                } else if (data.id) {
                    // Direct message object structure
                    messageData = data;
                    senderName = data.sender_name || (data.sender ? `${data.sender.firstname || ''} ${data.sender.lastname || ''}`.trim() : '');
                    timestamp = data.timestamp || (data.created_at ? new Date(data.created_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : '');
                } else {
                    console.error('❌ Invalid message data structure:', data);
                    return;
                }

                if (!messageData || !messageData.id) {
                    console.error('❌ Invalid message data - missing ID:', messageData);
                    return;
                }

                const isOwnMessage = messageData.sender_id === this.userId;

                // Check if message already exists (by ID) to prevent duplicates
                const existingMessage = this.messagesContainer?.querySelector(`[data-message-id="${messageData.id}"]`);
                if (existingMessage) {
                    console.log('📨 Message already exists, updating timestamp:', messageData.id);
                    // Update timestamp if needed
                    const timestampElement = existingMessage.querySelector('.message-time');
                    if (timestampElement && timestamp) {
                        timestampElement.textContent = timestamp;
                    }
                    return;
                }

                // Add the message
                console.log('📨 Adding new message via Pusher:', {
                    id: messageData.id,
                    sender: messageData.sender_id,
                    isOwn: isOwnMessage
                });
                this.addMessage(messageData, senderName, timestamp, isOwnMessage);
            });

            // Mark as set up to prevent duplicates
            this.messageListenerSetup = true;
            console.log('✅ Message listener set up successfully for event: new-message');

            // Verify listener is working after a short delay
            setTimeout(() => {
                const pusherChannel = this.echo.connector?.pusher?.channels?.channels?.[channelName];
                if (pusherChannel) {
                    console.log('✅ Channel subscription confirmed:', channelName);
                } else {
                    console.warn('⚠️ Channel subscription not yet confirmed, but listener is set up');
                }
            }, 2000);

        } catch (error) {
            console.error('❌ Error setting up Echo listener:', error);
            console.error('❌ Error details:', error.message, error.stack);

            // Fallback to polling if Echo listener setup fails
            if (!this.messagePollingInterval) {
                console.log('🔄 Falling back to message polling...');
                this.startSmartMessagePolling();
            }
        }
    }

    setupPollingFallback() {
        // Only start polling if Echo is not available
        // This is a fallback - real-time WebSocket is preferred
        // Don't start polling if Echo exists (even if still connecting) - let Pusher connection handlers manage polling
        if (!this.echo) {
            console.log('🔄 Laravel Echo not available, starting smart message polling fallback...');
            this.startSmartMessagePolling();
        } else {
            console.log('✅ Using Pusher for real-time messages - polling will only start if Pusher fails');
            // Ensure polling is stopped if Echo exists (Pusher will handle fallback if needed)
            if (this.messagePollingInterval) {
                clearInterval(this.messagePollingInterval);
                this.messagePollingInterval = null;
            }
        }
    }

    async sendMessage() {
        const message = this.messageInput?.value.trim();

        if (!message || this.isSending) {
            return;
        }

        this.isSending = true;

        // Show loading state
        const originalText = this.sendButton?.textContent;
        if (this.sendButton) {
            this.sendButton.textContent = 'Sending...';
            this.sendButton.disabled = true;
        }

        // Add message to UI immediately (optimistic update)
        const tempId = 'temp_' + Date.now();
        const currentUserName = document.querySelector('[data-user-name]')?.getAttribute('data-user-name') || 'You';
        this.addMessage(message, currentUserName, new Date().toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true}), true, tempId);

        // Clear input
        if (this.messageInput) {
            this.messageInput.value = '';
            this.messageInput.style.height = 'auto';
        }

        try {
            const url = `/chat/${this.tradeId}/message`;

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ message }),
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('📨 Response data:', data);

            if (data.success) {
                console.log('✅ Message sent successfully');
                // Update the temporary message with the real one and mark it as confirmed
                this.updateMessageInChat(tempId, data.message);

                // Update timestamp with server-formatted time and set message ID
                const messageElement = document.querySelector(`[data-temp-id="${tempId}"]`);
                if (messageElement && data.message) {
                    // Set the message ID to prevent duplicates from Pusher
                    if (data.message.id) {
                        messageElement.setAttribute('data-message-id', data.message.id);
                    }
                    // Update timestamp
                    if (data.message.display_time || data.message.created_at) {
                        const serverTime = data.message.display_time || new Date(data.message.created_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                        const timestampElement = messageElement.querySelector('.message-time');
                        if (timestampElement) {
                            timestampElement.textContent = serverTime;
                        }
                    }
                    messageElement.setAttribute('data-confirmed', 'true');
                    messageElement.removeAttribute('data-temp-id');
                }
            } else {
                throw new Error(data.error || 'Failed to send message');
            }
        } catch (error) {
            console.error('Send message error:', error);
            // Remove the temporary message if it failed
            this.removeMessageFromChat(tempId);
            this.showError('Failed to send message. Please try again.');
        } finally {
            // Reset button state
            if (this.sendButton) {
                this.sendButton.disabled = false;
                this.sendButton.textContent = originalText || 'Send';
            }

            // Prevent rapid sending (300ms cooldown)
            setTimeout(() => {
                this.isSending = false;
            }, 300);
        }
    }

    addMessage(message, senderName, timestamp, isOwn, tempId = null) {
        // Check for duplicate messages to prevent double display
        if (isOwn) {
            const messageText = typeof message === 'string' ? message : message.message;
            const existingMessages = this.messagesContainer?.querySelectorAll('.message');
            const lastMessage = existingMessages?.[existingMessages.length - 1];

            if (lastMessage) {
                const lastMessageText = lastMessage.querySelector('.message-text')?.textContent;
                if (lastMessageText === messageText) {
                    console.log('Duplicate message detected, skipping...');
                    return lastMessage;
                }
            }
        }

        if (!this.messagesContainer) {
            console.error('Messages container not found');
            return;
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isOwn ? 'own' : ''}`;

        // Add message ID if available to prevent duplicates
        if (message && typeof message === 'object' && message.id) {
            messageDiv.setAttribute('data-message-id', message.id);
        }

        if (tempId) {
            messageDiv.setAttribute('data-temp-id', tempId);
        }

        // Handle both string messages and message objects
        const messageText = typeof message === 'string' ? message : message.message;

        // Always convert to user's local timezone for consistency
        // Server times are in UTC, but we want to show user's local time
        let messageTime;
        if (message && message.created_at) {
            // Parse the server timestamp and convert to local time
            try {
                const date = new Date(message.created_at);
                // Use 12-hour format with AM/PM in user's local timezone
                messageTime = date.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            } catch {
                // Fallback to current time if parsing fails
                messageTime = new Date().toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }
        } else if (timestamp) {
            // Use provided timestamp - could be ISO string or already formatted string
            // Check if it's already a formatted time string (contains AM/PM or matches time pattern)
            if (typeof timestamp === 'string' && (timestamp.match(/\d{1,2}:\d{2}\s?(AM|PM)/i) || timestamp.match(/^\d{1,2}:\d{2}$/))) {
                // Already formatted, use as-is
                messageTime = timestamp;
            } else {
                // Try to parse as ISO date string
                try {
                    const date = new Date(timestamp);
                    // Check if date is valid
                    if (!isNaN(date.getTime())) {
                        messageTime = date.toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                    } else {
                        // Invalid date, use timestamp as-is
                        messageTime = timestamp;
                    }
                } catch {
                    // Parsing failed, use timestamp as-is
                    messageTime = timestamp;
                }
            }
        } else {
            // Last resort - current time
            messageTime = new Date().toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }

        // Check if message contains image, video, or file
        let messageContent = '';
        if (messageText.includes('[IMAGE:') && messageText.includes(']')) {
            // Parse format: [IMAGE:filename|url] or [IMAGE:filename]
            const match = messageText.match(/\[IMAGE:(.+?)(?:\|(.+?))?\]/);
            const fileName = match?.[1] || 'image';
            const fileUrl = match?.[2] || window.tempImageData || '#';
            const escapedFileUrl = fileUrl.replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const escapedFileUrlForJs = fileUrl.replace(/'/g, "\\'");
            messageContent = `
                <div class="message-bubble">
                    <div class="message-text">
                        <img src="${escapedFileUrl}" alt="${this.escapeHtml(fileName)}" class="chat-image" onerror="this.style.display='none'" style="max-width: 200px; border-radius: 8px; cursor: pointer;" onclick="window.open('${escapedFileUrlForJs}', '_blank')">
                        <div style="font-size: 0.75rem; opacity: 0.8; margin-top: 4px;">${this.escapeHtml(fileName)}</div>
                    </div>
                    <div class="message-time">${messageTime}</div>
                </div>
            `;
        } else if (messageText.includes('[VIDEO:') && messageText.includes(']')) {
            // Parse format: [VIDEO:filename|url] or [VIDEO:filename]
            const match = messageText.match(/\[VIDEO:(.+?)(?:\|(.+?))?\]/);
            const fileName = match?.[1] || 'video';
            const fileUrl = match?.[2] || window.tempVideoData || '#';
            messageContent = `
                <div class="message-bubble">
                    <div class="message-text">
                        <video controls style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                            <source src="${fileUrl}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <div style="font-size: 0.75rem; opacity: 0.8; margin-top: 4px;">${this.escapeHtml(fileName)}</div>
                    </div>
                    <div class="message-time">${messageTime}</div>
                </div>
            `;
        } else if (messageText.includes('[FILE:') && messageText.includes(']')) {
            // Parse format: [FILE:filename|url] or [FILE:filename]
            const match = messageText.match(/\[FILE:(.+?)(?:\|(.+?))?\]/);
            const fileName = match?.[1] || 'file';
            const fileUrl = match?.[2] || '#';
            messageContent = `
                <div class="message-bubble">
                    <div class="message-text">
                        <a href="${fileUrl}" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; background: #f3f4f6; border-radius: 6px; text-decoration: none; color: #1f2937;">
                            <span>📎</span>
                            <span style="font-weight: 500;">${this.escapeHtml(fileName)}</span>
                        </a>
                    </div>
                    <div class="message-time">${messageTime}</div>
                </div>
            `;
        } else {
            messageContent = `
                <div class="message-bubble">
                    <div class="message-text">${this.escapeHtml(messageText)}</div>
                    <div class="message-time">${messageTime}</div>
                </div>
            `;
        }

        messageDiv.innerHTML = messageContent;
        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();

        // Flash effect for new messages (only for incoming messages, not your own)
        if (!isOwn) {
            console.log('🆕 New message added dynamically:', messageText);
            this.flashChatArea();
            this.showNewMessageIndicator();
        }

        return messageDiv;
    }

    updateMessageInChat(tempId, message) {
        const messageElement = document.querySelector(`[data-temp-id="${tempId}"]`);
        if (!messageElement) return;

        const time = new Date(message.created_at).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });

        const messageText = message.message;
        const messageBubble = messageElement.querySelector('.message-bubble');

        if (messageBubble) {
            messageBubble.innerHTML = `
                <div class="message-text">${this.escapeHtml(messageText)}</div>
                <div class="message-time">${time}</div>
            `;
        }
    }

    removeMessageFromChat(tempId) {
        const messageElement = document.querySelector(`[data-temp-id="${tempId}"]`);
        if (messageElement) {
            messageElement.remove();
        }
    }

    scrollToBottom() {
        if (this.messagesContainer) {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }
    }

    updateConnectionStatus(status) {
        const statusDot = this.presenceStatus?.querySelector('.status-dot');
        const statusText = this.presenceStatus?.querySelector('span:last-child');

        if (!statusDot || !statusText) return;

        switch(status) {
            case 'connected':
                statusDot.style.background = '#10b981';
                statusDot.classList.remove('offline');
                statusText.textContent = 'Partner is online';
                break;
            case 'disconnected':
                statusDot.style.background = '#f59e0b';
                statusDot.classList.add('offline');
                statusText.textContent = 'Partner is offline';
                break;
            case 'error':
                statusDot.style.background = '#ef4444';
                statusDot.classList.add('offline');
                statusText.textContent = 'Connection Error';
                break;
            default:
                statusDot.style.background = '#6b7280';
                statusDot.classList.add('offline');
                statusText.textContent = 'Connecting...';
        }
    }

    // Polling fallback methods
    startSmartMessagePolling() {
        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
        }

        // Enforce minimum frequency
        const actualFrequency = Math.max(this.pollingFrequency, this.minPollingFrequency);
        const actualFrequencyMs = Math.min(actualFrequency, this.maxPollingFrequency);

        console.log(`🔄 Starting message polling at ${actualFrequencyMs}ms interval`);

        this.messagePollingInterval = setInterval(() => {
            this.checkForNewMessages();
        }, actualFrequencyMs);
    }

    async checkForNewMessages() {
        // Debounce: Prevent overlapping requests
        const now = Date.now();
        const timeSinceLastPoll = now - this.lastPollTime;

        if (this.isPolling) {
            console.log('⏸️ Polling already in progress, skipping...');
            return;
        }

        // Enforce minimum time between polls (debounce)
        if (timeSinceLastPoll < this.minPollingFrequency) {
            const waitTime = this.minPollingFrequency - timeSinceLastPoll;
            console.log(`⏳ Debouncing: waiting ${waitTime}ms before next poll`);
            return;
        }

        this.isPolling = true;
        this.lastPollTime = now;

        try {
            // Get CSRF token for the request
            const csrfToken = this.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(`/chat/${this.tradeId}/messages`, {
                method: 'GET',
                credentials: 'same-origin', // Include cookies for session
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken || '' // Include CSRF token
                }
            });

            // Handle auth errors gracefully
            if (response.status === 401 || response.status === 403) {
                console.warn('⚠️ Authentication failed for message polling - session may have expired');
                // Stop polling and try to reload page to re-authenticate
                if (this.messagePollingInterval) {
                    clearInterval(this.messagePollingInterval);
                    this.messagePollingInterval = null;
                }
                // Only reload if we get multiple auth failures
                if (!this.authFailureCount) this.authFailureCount = 0;
                this.authFailureCount++;
                if (this.authFailureCount >= 3) {
                    console.error('❌ Multiple authentication failures - reloading page');
                    window.location.reload();
                }
                this.isPolling = false;
                return;
            }

            // Reset auth failure count on success
            if (response.ok) {
                this.authFailureCount = 0;

                // Update CSRF token from response headers if available
                const newCsrfToken = response.headers.get('X-CSRF-TOKEN');
                if (newCsrfToken) {
                    this.csrfToken = newCsrfToken;
                    // Update meta tag if it exists
                    const metaTag = document.querySelector('meta[name="csrf-token"]');
                    if (metaTag) {
                        metaTag.setAttribute('content', newCsrfToken);
                    }
                }
            }

            if (!response.ok) {
                // If it's a 401/403, we already handled it above
                if (response.status === 401 || response.status === 403) {
                    return;
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                // If lastMessageCount is still 0, initialize it with current count
                // This handles cases where initialization didn't work properly
                if (this.lastMessageCount === 0 && data.count > 0) {
                    this.lastMessageCount = data.count;
                    console.log(`📊 Initialized message count: ${this.lastMessageCount}`);
                    return; // Don't add messages on first poll if they're already rendered
                }

                if (data.count > this.lastMessageCount) {
                    // Get only the new messages
                    const newMessages = data.messages.slice(this.lastMessageCount);
                    this.lastMessageCount = data.count;

                    // Reset activity tracking
                    this.lastActivity = Date.now();
                    this.consecutiveEmptyPolls = 0;

                    // Add only new messages to chat (avoid duplicates by checking message IDs)
                    newMessages.forEach(msg => {
                        // Check if message already exists by ID to prevent duplicates
                        const existingMessage = this.messagesContainer?.querySelector(`[data-message-id="${msg.id}"]`);
                        if (!existingMessage) {
                            if (msg.sender_id !== this.userId) {
                                const senderName = msg.sender ?
                                    `${msg.sender.firstname} ${msg.sender.lastname}` :
                                    this.partnerName;
                                const timestamp = msg.display_time || (msg.created_at ? new Date(msg.created_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : '');
                                this.addMessage(msg, senderName, timestamp, false);
                            }
                        } else {
                            console.log(`⏭️ Skipping duplicate message ID: ${msg.id}`);
                        }
                    });

                    console.log(`📨 Received ${newMessages.length} new messages`);
                } else if (data.count < this.lastMessageCount) {
                    // Message count decreased (unlikely but handle it)
                    console.log(`⚠️ Message count decreased from ${this.lastMessageCount} to ${data.count}, resetting...`);
                    this.lastMessageCount = data.count;
                }
            } else {
                this.consecutiveEmptyPolls++;

                // Adjust frequency based on empty polls (slow down if many empty polls)
                if (this.consecutiveEmptyPolls > 10) {
                    const oldFrequency = this.pollingFrequency;
                    this.pollingFrequency = Math.min(this.maxPollingFrequency, this.pollingFrequency + 2000);
                    if (oldFrequency !== this.pollingFrequency) {
                        console.log(`🔄 Slowing down polling to ${this.pollingFrequency}ms after ${this.consecutiveEmptyPolls} empty polls`);
                        this.startSmartMessagePolling(); // Restart with new frequency
                    }
                }
            }
        } catch (error) {
            console.error('Error checking for new messages:', error);
            this.consecutiveEmptyPolls++;

            // Slow down on errors
            if (this.consecutiveEmptyPolls % 5 === 0) {
                const oldFrequency = this.pollingFrequency;
                this.pollingFrequency = Math.min(this.maxPollingFrequency, this.pollingFrequency + 1000);
                if (oldFrequency !== this.pollingFrequency) {
                    console.log(`🔄 Slowing down polling to ${this.pollingFrequency}ms due to errors`);
                    this.startSmartMessagePolling();
                }
            }
        } finally {
            this.isPolling = false;
        }
    }

    flashChatArea() {
        if (!this.messagesContainer) return;

        // Create flash overlay
        const flashOverlay = document.createElement('div');
        flashOverlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(59, 130, 246, 0.3), rgba(16, 185, 129, 0.3));
            border-radius: 8px;
            pointer-events: none;
            z-index: 10;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;

        // Position the overlay relative to chat messages
        this.messagesContainer.style.position = 'relative';
        this.messagesContainer.appendChild(flashOverlay);

        // Trigger flash animation
        setTimeout(() => {
            flashOverlay.style.opacity = '1';
        }, 50);

        setTimeout(() => {
            flashOverlay.style.opacity = '0';
        }, 150);

        // Remove overlay after animation
        setTimeout(() => {
            if (flashOverlay.parentNode) {
                flashOverlay.parentNode.removeChild(flashOverlay);
            }
        }, 500);
    }

    showNewMessageIndicator() {
        const indicator = document.getElementById('new-message-indicator');
        if (indicator) {
            indicator.style.display = 'inline-block';

            // Hide after 3 seconds
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 3000);
        }
    }

    toggleEmojiPicker() {
        const picker = document.getElementById('emoji-picker');
        if (picker) {
            if (picker.style.display === 'none' || picker.style.display === '') {
                picker.style.display = 'block';
            } else {
                picker.style.display = 'none';
            }
        }
    }

    insertEmoji(emoji) {
        if (!this.messageInput) {
            return;
        }

        const currentValue = this.messageInput.value;
        const cursorPosition = this.messageInput.selectionStart || currentValue.length;

        // Insert emoji at cursor position
        const newValue = currentValue.slice(0, cursorPosition) + emoji + currentValue.slice(cursorPosition);
        this.messageInput.value = newValue;

        // Set cursor position after the emoji
        const newCursorPosition = cursorPosition + emoji.length;
        this.messageInput.setSelectionRange(newCursorPosition, newCursorPosition);

        // Focus back to input
        this.messageInput.focus();

        // Hide emoji picker
        const picker = document.getElementById('emoji-picker');
        if (picker) {
            picker.style.display = 'none';
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showError(message) {
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
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        errorDiv.textContent = message;

        document.body.appendChild(errorDiv);

        // Remove after 5 seconds
        setTimeout(() => {
            errorDiv.style.opacity = '0';
            errorDiv.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.parentNode.removeChild(errorDiv);
                }
            }, 300);
        }, 5000);
    }

    destroy() {
        // Cleanup
        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
        }

        if (this.echo) {
            this.echo.leave(`trade-${this.tradeId}`);
        }
    }
}

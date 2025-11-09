<div class="chat-panel">
    <div class="chat-header">
        <div class="presence-indicator" id="presence-status">
            <div class="status-dot"></div>
            <span>Partner is online</span>
        </div>
        <div class="chat-actions">
            <button class="action-btn" id="attach-file-btn" title="Attach file">📎</button>
            <button class="action-btn" id="send-image-btn" title="Send image">📷</button>
        </div>
    </div>

    <div class="messages-container" id="messages">
        @foreach($messages as $message)
            <div class="message {{ $message->sender_id === Auth::id() ? 'own' : '' }}">
                <div class="message-bubble">
                    <div class="message-text">{{ $message->message }}</div>
                    <div class="message-time">{{ $message->created_at->format('g:i A') }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="message-input-area">
        <form id="message-form">
            <div class="input-wrapper">
                <div class="input-container">
                    <textarea class="message-input" id="message-input" placeholder="Type your message..." rows="1"></textarea>
                    <button class="emoji-btn" id="emoji-button" type="button">😊</button>
                </div>
                <button class="send-btn" id="send-button" type="submit">Send</button>
            </div>
        </form>
    </div>
</div>

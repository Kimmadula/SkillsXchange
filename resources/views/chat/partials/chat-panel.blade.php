<div class="chat-panel">
    <div class="chat-header">
        <div class="presence-indicator" id="presence-status">
            <div class="status-dot"></div>
            <span>Online</span>
        </div>
        <span id="new-message-indicator" style="display: none; background: #ef4444; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; animation: pulse 2s infinite;">NEW</span>
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
                    <div class="message-time" data-timestamp="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('g:i A') }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="message-input-area">
        <form id="message-form">
            <div class="input-wrapper">
                <div class="input-container" style="position: relative;">
                    <textarea class="message-input" id="message-input" placeholder="Type your message..." rows="1"></textarea>
                    <button class="emoji-btn" id="emoji-button" type="button">😊</button>
                    
                    <!-- Emoji Picker -->
                    <div id="emoji-picker" style="display: none; position: absolute; bottom: 100%; right: 0; margin-bottom: 8px; background: white; border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); padding: 12px; width: 300px; max-height: 200px; overflow-y: auto; z-index: 1000;">
                        <div style="display: grid; grid-template-columns: repeat(8, 1fr); gap: 4px;">
                            <button type="button" class="emoji-item" data-emoji="😀" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">😀</button>
                            <button type="button" class="emoji-item" data-emoji="😊" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">😊</button>
                            <button type="button" class="emoji-item" data-emoji="😂" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">😂</button>
                            <button type="button" class="emoji-item" data-emoji="😍" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">😍</button>
                            <button type="button" class="emoji-item" data-emoji="😎" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">😎</button>
                            <button type="button" class="emoji-item" data-emoji="🤔" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">🤔</button>
                            <button type="button" class="emoji-item" data-emoji="👍" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">👍</button>
                            <button type="button" class="emoji-item" data-emoji="👎" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">👎</button>
                            <button type="button" class="emoji-item" data-emoji="❤️" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">❤️</button>
                            <button type="button" class="emoji-item" data-emoji="🎉" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">🎉</button>
                            <button type="button" class="emoji-item" data-emoji="🔥" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">🔥</button>
                            <button type="button" class="emoji-item" data-emoji="✅" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">✅</button>
                            <button type="button" class="emoji-item" data-emoji="❌" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">❌</button>
                            <button type="button" class="emoji-item" data-emoji="⭐" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">⭐</button>
                            <button type="button" class="emoji-item" data-emoji="💯" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">💯</button>
                            <button type="button" class="emoji-item" data-emoji="🚀" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; border-radius: 4px; transition: background-color 0.2s;">🚀</button>
                        </div>
                    </div>
                </div>
                <button class="send-btn" id="send-button" type="submit">Send</button>
            </div>
        </form>
    </div>
</div>

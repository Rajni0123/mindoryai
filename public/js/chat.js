const chatMessages = document.getElementById('chatMessages');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
const clearBtn = document.getElementById('clearBtn');
const suggestions = document.getElementById('suggestions');
const suggestionBtns = document.querySelectorAll('.suggestion-btn');

const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Auto-resize textarea
messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 200) + 'px';

    sendBtn.disabled = this.value.trim() === '';
});

// Enter to send (Shift+Enter for new line)
messageInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (this.value.trim() !== '') {
            sendMessage();
        }
    }
});

// Send button click
sendBtn.addEventListener('click', sendMessage);

// Clear chat button
clearBtn.addEventListener('click', async function() {
    if (!confirm('Clear all chat history?')) return;

    try {
        const response = await fetch('/chat/clear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        if (response.ok) {
            chatMessages.innerHTML = `
                <div class="welcome-message">
                    <div class="welcome-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17L12 22L22 17" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 12L12 17L22 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h2>Hello! How can I help you today?</h2>
                    <p>Ask me anything, and I'll do my best to assist you.</p>
                </div>
            `;
            suggestions.style.display = 'flex';
        }
    } catch (error) {
        console.error('Clear error:', error);
        alert('Failed to clear chat history. Please try again.');
    }
});

// Suggestion buttons
suggestionBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        const text = this.getAttribute('data-text');
        messageInput.value = text;
        messageInput.style.height = 'auto';
        messageInput.style.height = messageInput.scrollHeight + 'px';
        sendBtn.disabled = false;
        messageInput.focus();
    });
});

// Add user message to chat
function addUserMessage(text) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message user-message';
    messageDiv.innerHTML = `
        <div class="message-content">
            <div class="avatar user-avatar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div class="text">${escapeHtml(text)}</div>
        </div>
    `;
    chatMessages.appendChild(messageDiv);
    scrollToBottom();
}

// Add assistant message container
function addAssistantMessage() {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message assistant-message';
    messageDiv.innerHTML = `
        <div class="message-content">
            <div class="avatar assistant-avatar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="text typing-indicator">
                <span></span><span></span><span></span>
            </div>
        </div>
    `;
    chatMessages.appendChild(messageDiv);
    scrollToBottom();
    return messageDiv.querySelector('.text');
}

// Send message with streaming
async function sendMessage() {
    const message = messageInput.value.trim();
    if (!message) return;

    // Hide suggestions after first message
    suggestions.style.display = 'none';

    // Add user message
    addUserMessage(message);

    // Reset input
    messageInput.value = '';
    messageInput.style.height = 'auto';
    sendBtn.disabled = true;

    // Add assistant message container
    const textElement = addAssistantMessage();

    try {
        const response = await fetch('/chat/stream', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ message })
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let fullText = '';

        // Remove typing indicator
        textElement.classList.remove('typing-indicator');
        textElement.innerHTML = '';

        // Read streaming response
        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            const chunk = decoder.decode(value);
            const lines = chunk.split('\n');

            for (const line of lines) {
                if (line.startsWith('data: ')) {
                    const data = line.slice(6);

                    if (data === '[DONE]') {
                        break;
                    }

                    try {
                        const parsed = JSON.parse(data);
                        if (parsed.content) {
                            fullText += parsed.content;
                            textElement.textContent = fullText;
                            scrollToBottom();
                        }
                    } catch (e) {
                        // Ignore parse errors for incomplete chunks
                    }
                }
            }
        }

    } catch (error) {
        textElement.classList.remove('typing-indicator');
        textElement.innerHTML = '<span style="color: #ef4444;">⚠️ Sorry, something went wrong. Please try again.</span>';
        console.error('Error:', error);
    }
}

// Scroll to bottom
function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

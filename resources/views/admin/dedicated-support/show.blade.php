@extends('layouts.admin')

@section('title', 'Support Chat - ' . ($chat->user->name ?? 'User'))

@section('content')
<div class="p-6 h-[calc(100vh-24px)] flex flex-col bg-[#0a0a0a]">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.dedicated-support.index') }}" class="p-2 hover:bg-gray-800 rounded-lg transition">
                <span class="material-icons-outlined text-gray-400">arrow_back</span>
            </a>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                    {{ strtoupper(substr($chat->user->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white">{{ $chat->user->name ?? 'Unknown User' }}</h1>
                    <p class="text-gray-400 text-sm">{{ $chat->user->mobile ?? $chat->user->email }} | Ultimate Plan</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($chat->status !== 'closed')
            <form method="POST" action="{{ route('admin.dedicated-support.close', $chat->id) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm rounded-lg transition flex items-center gap-2">
                    <span class="material-icons-outlined text-sm">check_circle</span>
                    Close Chat
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('admin.dedicated-support.reopen', $chat->id) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition flex items-center gap-2">
                    <span class="material-icons-outlined text-sm">refresh</span>
                    Reopen Chat
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Chat Container -->
    <div class="flex-1 bg-[#1a1a1a] rounded-lg border border-gray-800 flex flex-col overflow-hidden">
        <!-- Messages Area -->
        <div id="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
            @forelse($chat->messages as $message)
            <div class="flex {{ $message->sender_type === 'admin' ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">
                <div class="max-w-[70%] {{ $message->sender_type === 'admin' ? 'bg-blue-600' : 'bg-[#2a2a2a]' }} rounded-2xl px-4 py-3 {{ $message->sender_type === 'admin' ? 'rounded-br-sm' : 'rounded-bl-sm' }}">
                    <p class="text-white text-sm whitespace-pre-wrap">{{ $message->message }}</p>
                    <div class="flex items-center justify-end gap-2 mt-1">
                        <span class="text-xs {{ $message->sender_type === 'admin' ? 'text-blue-200' : 'text-gray-500' }}">
                            {{ $message->created_at->format('M d, h:i A') }}
                        </span>
                        @if($message->sender_type === 'admin')
                        <span class="material-icons-outlined text-xs {{ $message->is_read ? 'text-blue-300' : 'text-gray-400' }}">
                            {{ $message->is_read ? 'done_all' : 'done' }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-12">
                <span class="material-icons-outlined text-4xl text-gray-600 mb-2">chat_bubble_outline</span>
                <p class="text-gray-400">No messages yet</p>
            </div>
            @endforelse
        </div>

        <!-- Reply Box -->
        @if($chat->status !== 'closed')
        <div class="p-4 border-t border-gray-800 bg-[#111111]">
            <form id="replyForm" method="POST" action="{{ route('admin.dedicated-support.reply', $chat->id) }}" class="flex gap-3">
                @csrf
                <div class="flex-1 relative">
                    <textarea name="message" id="messageInput" rows="1" placeholder="Type your reply..."
                        class="w-full bg-[#1a1a1a] border border-gray-700 rounded-xl px-4 py-3 text-sm text-white focus:border-blue-500 focus:outline-none resize-none"
                        style="min-height: 48px; max-height: 150px;"></textarea>
                </div>
                <button type="submit" id="sendBtn" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded-xl transition flex items-center gap-2">
                    <span class="material-icons-outlined text-sm">send</span>
                    Send
                </button>
            </form>
        </div>
        @else
        <div class="p-4 border-t border-gray-800 bg-[#111111] text-center">
            <p class="text-gray-500 text-sm">This chat is closed. Reopen to send messages.</p>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById('messagesContainer');
    const messageInput = document.getElementById('messageInput');
    const replyForm = document.getElementById('replyForm');
    const sendBtn = document.getElementById('sendBtn');
    let lastMessageId = {{ $chat->messages->last()?->id ?? 0 }};

    // Scroll to bottom
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    scrollToBottom();

    // Auto-resize textarea
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 150) + 'px';
        });

        // Send on Enter (Shift+Enter for new line)
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                replyForm.dispatchEvent(new Event('submit'));
            }
        });
    }

    // AJAX form submission
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const message = messageInput.value.trim();
            if (!message) return;

            sendBtn.disabled = true;

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const msgHtml = `
                        <div class="flex justify-end" data-message-id="${data.message.id}">
                            <div class="max-w-[70%] bg-blue-600 rounded-2xl px-4 py-3 rounded-br-sm">
                                <p class="text-white text-sm whitespace-pre-wrap">${escapeHtml(data.message.message)}</p>
                                <div class="flex items-center justify-end gap-2 mt-1">
                                    <span class="text-xs text-blue-200">${data.message.created_at}</span>
                                    <span class="material-icons-outlined text-xs text-gray-400">done</span>
                                </div>
                            </div>
                        </div>
                    `;
                    messagesContainer.insertAdjacentHTML('beforeend', msgHtml);
                    lastMessageId = data.message.id;
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                    scrollToBottom();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to send message. Please try again.');
            })
            .finally(() => {
                sendBtn.disabled = false;
                messageInput.focus();
            });
        });
    }

    // Poll for new messages every 5 seconds
    function pollMessages() {
        fetch(`{{ route('admin.dedicated-support.messages', $chat->id) }}?last_id=${lastMessageId}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    if (!document.querySelector(`[data-message-id="${msg.id}"]`)) {
                        const isAdmin = msg.sender_type === 'admin';
                        const msgHtml = `
                            <div class="flex ${isAdmin ? 'justify-end' : 'justify-start'}" data-message-id="${msg.id}">
                                <div class="max-w-[70%] ${isAdmin ? 'bg-blue-600' : 'bg-[#2a2a2a]'} rounded-2xl px-4 py-3 ${isAdmin ? 'rounded-br-sm' : 'rounded-bl-sm'}">
                                    <p class="text-white text-sm whitespace-pre-wrap">${escapeHtml(msg.message)}</p>
                                    <div class="flex items-center justify-end gap-2 mt-1">
                                        <span class="text-xs ${isAdmin ? 'text-blue-200' : 'text-gray-500'}">${msg.created_at}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                        messagesContainer.insertAdjacentHTML('beforeend', msgHtml);
                        lastMessageId = Math.max(lastMessageId, msg.id);
                        if (!isAdmin) scrollToBottom();
                    }
                });
            }
        })
        .catch(error => console.error('Poll error:', error));
    }

    setInterval(pollMessages, 5000);

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endpush
@endsection

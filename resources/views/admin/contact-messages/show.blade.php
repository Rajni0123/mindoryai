<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Message from {{ $message->name }} | Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        @include('admin.partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.contact-messages.index') }}" class="text-gray-500 hover:text-white text-xs mb-1 inline-flex items-center gap-1">
                            <span class="material-icons-outlined text-sm">arrow_back</span>
                            Back to Messages
                        </a>
                        <h1 class="text-base font-semibold text-white">Message from {{ $message->name }}</h1>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $message->created_at->format('M d, Y \a\t h:i A') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @php
                        $statusColors = [
                            'new' => 'bg-blue-500/20 text-blue-300',
                            'read' => 'bg-yellow-500/20 text-yellow-300',
                            'replied' => 'bg-green-500/20 text-green-300',
                            'closed' => 'bg-gray-500/20 text-gray-400',
                        ];
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusColors[$message->status] ?? '' }}">
                            {{ ucfirst($message->status) }}
                        </span>

                        @if($message->status !== 'closed')
                        <form action="{{ route('admin.contact-messages.status', $message->id) }}" method="POST" class="inline">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="closed">
                            <button class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-xs rounded-lg transition-colors">
                                Close
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </header>

            <div class="p-6 max-w-3xl">
                @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/20 rounded-lg text-green-300 text-sm">
                    {{ session('success') }}
                </div>
                @endif

                <!-- Contact Info -->
                <div class="card rounded-lg p-5 mb-4">
                    <h3 class="text-xs font-medium text-gray-500 uppercase mb-3">Contact Information</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500 text-xs mb-0.5">Name</p>
                            <p class="text-white font-medium">{{ $message->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs mb-0.5">Email</p>
                            <a href="mailto:{{ $message->email }}" class="text-blue-400 hover:underline">{{ $message->email }}</a>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs mb-0.5">Phone</p>
                            <p class="text-gray-300">{{ $message->phone ?: 'Not provided' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs mb-0.5">IP Address</p>
                            <p class="text-gray-400 text-xs font-mono">{{ $message->ip_address }}</p>
                        </div>
                    </div>
                </div>

                <!-- Message -->
                <div class="card rounded-lg p-5 mb-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="material-icons-outlined text-blue-400 text-lg">mail</span>
                        <h3 class="text-white font-semibold text-sm">{{ $message->subject }}</h3>
                    </div>
                    <div class="text-gray-300 text-sm leading-relaxed whitespace-pre-wrap">{{ $message->message }}</div>
                </div>

                <!-- Admin Reply (if exists) -->
                @if($message->admin_reply)
                <div class="card rounded-lg p-5 mb-4 border-green-500/20">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="material-icons-outlined text-green-400 text-lg">reply</span>
                        <h3 class="text-green-300 font-semibold text-sm">Your Reply</h3>
                        <span class="text-gray-500 text-xs ml-auto">
                            {{ $message->replied_at?->format('M d, Y h:i A') }}
                            @if($message->repliedByUser) by {{ $message->repliedByUser->name }} @endif
                        </span>
                    </div>
                    <div class="text-gray-300 text-sm leading-relaxed whitespace-pre-wrap">{{ $message->admin_reply }}</div>
                </div>
                @endif

                <!-- Reply Form -->
                <div class="card rounded-lg p-5">
                    <h3 class="text-white font-semibold text-sm mb-3 flex items-center gap-2">
                        <span class="material-icons-outlined text-lg">edit</span>
                        {{ $message->admin_reply ? 'Update Reply' : 'Send Reply' }}
                    </h3>
                    <form action="{{ route('admin.contact-messages.reply', $message->id) }}" method="POST">
                        @csrf
                        <textarea name="admin_reply" rows="6" required
                                  class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition resize-none mb-3"
                                  placeholder="Type your reply here... This will be sent to {{ $message->email }}">{{ old('admin_reply', $message->admin_reply) }}</textarea>

                        @if($errors->has('admin_reply'))
                        <p class="text-red-400 text-xs mb-2">{{ $errors->first('admin_reply') }}</p>
                        @endif

                        <div class="flex items-center gap-3">
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <span class="material-icons-outlined text-sm">send</span>
                                Send Reply via Email
                            </button>
                            <p class="text-gray-500 text-xs">Reply will be sent to {{ $message->email }}</p>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

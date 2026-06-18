<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Contact Messages | Admin</title>

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
                        <h1 class="text-base font-semibold text-white">Contact Messages</h1>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $unreadCount }} unread message{{ $unreadCount != 1 ? 's' : '' }}</p>
                    </div>
                </div>
            </header>

            <div class="p-6">
                @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/20 rounded-lg text-green-300 text-sm">
                    {{ session('success') }}
                </div>
                @endif

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <form method="GET" class="flex flex-wrap items-center gap-3 w-full">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search by name, email, subject..."
                               class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 outline-none w-64">
                        <select name="status"
                                class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none"
                                onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                            <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
                            <option value="replied" {{ request('status') === 'replied' ? 'selected' : '' }}>Replied</option>
                            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                        <button type="submit" class="px-3 py-2 bg-white/5 hover:bg-white/10 text-sm rounded-lg transition-colors">Search</button>
                    </form>
                </div>

                <!-- Messages Table -->
                <div class="card rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Subject</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($messages as $msg)
                            <tr class="border-b border-white/5 hover:bg-white/[0.02] {{ $msg->status === 'new' ? 'bg-blue-500/5' : '' }}">
                                <td class="px-4 py-3">
                                    @php
                                    $statusColors = [
                                        'new' => 'bg-blue-500/20 text-blue-300',
                                        'read' => 'bg-yellow-500/20 text-yellow-300',
                                        'replied' => 'bg-green-500/20 text-green-300',
                                        'closed' => 'bg-gray-500/20 text-gray-400',
                                    ];
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $statusColors[$msg->status] ?? 'bg-gray-500/20 text-gray-400' }}">
                                        {{ ucfirst($msg->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-white font-medium">{{ $msg->name }}</td>
                                <td class="px-4 py-3 text-gray-400">{{ $msg->email }}</td>
                                <td class="px-4 py-3 text-gray-300">{{ Str::limit($msg->subject, 30) }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $msg->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.contact-messages.show', $msg->id) }}"
                                           class="px-2.5 py-1 bg-white/5 hover:bg-white/10 text-xs rounded-lg transition-colors inline-flex items-center gap-1">
                                            <span class="material-icons-outlined text-xs">visibility</span>
                                            View
                                        </a>
                                        <form action="{{ route('admin.contact-messages.destroy', $msg->id) }}" method="POST" onsubmit="return confirm('Delete this message?')">
                                            @csrf @method('DELETE')
                                            <button class="px-2.5 py-1 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs rounded-lg transition-colors inline-flex items-center gap-1">
                                                <span class="material-icons-outlined text-xs">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                    <span class="material-icons-outlined text-3xl mb-2 block">inbox</span>
                                    No contact messages yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($messages->hasPages())
                <div class="mt-4">
                    {{ $messages->withQueryString()->links() }}
                </div>
                @endif
            </div>
        </main>
    </div>
</body>
</html>

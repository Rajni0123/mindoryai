<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notifications Management</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .card:hover { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.12); }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        @include('admin.partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-semibold text-white">Notifications Management</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Send and manage push notifications to app users</p>
                    </div>
                    <a href="{{ route('admin.notifications.create') }}" class="px-4 py-2 bg-gradient-to-r from-[#0df259] to-[#06b6d4] text-sm text-black font-medium rounded-lg hover:opacity-90 transition">
                        <span class="material-icons-outlined text-sm align-middle mr-1">add</span>
                        Create Notification
                    </a>
                </div>
            </header>

            <div class="p-6">
                @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/20 rounded-lg text-sm text-green-300">
                    {{ session('success') }}
                </div>
                @endif

                <div class="card rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-[#0a0a0a] border-b border-gray-800/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Title</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Recipients</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Created By</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Sent At</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800/50">
                                @forelse($notifications as $notification)
                                <tr class="hover:bg-white/5 transition">
                                    <td class="px-4 py-3 text-white">{{ $notification->title }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs rounded-full
                                            @if($notification->type === 'success') bg-green-500/10 text-green-400
                                            @elseif($notification->type === 'warning') bg-yellow-500/10 text-yellow-400
                                            @elseif($notification->type === 'error') bg-red-500/10 text-red-400
                                            @else bg-blue-500/10 text-blue-400
                                            @endif">
                                            {{ ucfirst($notification->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($notification->is_global)
                                            <span class="text-green-400">All Users</span>
                                        @else
                                            <span class="text-gray-400">{{ $notification->user->name ?? 'Specific User' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($notification->is_read)
                                            <span class="text-gray-500">Read</span>
                                        @else
                                            <span class="text-green-400">Unread</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-400">{{ $notification->creator->name ?? 'Admin' }}</td>
                                    <td class="px-4 py-3 text-gray-400">{{ $notification->created_at->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <form action="{{ route('admin.notifications.destroy', $notification) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:text-red-300 transition" onclick="return confirm('Are you sure you want to delete this notification?')">
                                                <span class="material-icons-outlined text-sm">delete</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        No notifications sent yet. Create your first notification to get started.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($notifications->hasPages())
                <div class="mt-4">
                    {{ $notifications->links() }}
                </div>
                @endif
            </div>
        </main>
    </div>
</body>
</html>

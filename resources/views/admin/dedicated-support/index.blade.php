@extends('layouts.admin')

@section('title', 'Dedicated Support - Admin')

@section('content')
<div class="p-6 bg-[#0a0a0a] min-h-screen">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Dedicated Support</h1>
            <p class="text-gray-400 text-sm mt-1">Manage support chats from Ultimate plan users</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-[#1a1a1a] rounded-lg p-4 border border-gray-800">
            <div class="text-2xl font-bold text-white">{{ $stats['total'] }}</div>
            <div class="text-xs text-gray-400">Total Chats</div>
        </div>
        <div class="bg-[#1a1a1a] rounded-lg p-4 border border-yellow-600/30">
            <div class="text-2xl font-bold text-yellow-400">{{ $stats['pending'] }}</div>
            <div class="text-xs text-yellow-400/70">Awaiting Reply</div>
        </div>
        <div class="bg-[#1a1a1a] rounded-lg p-4 border border-blue-600/30">
            <div class="text-2xl font-bold text-blue-400">{{ $stats['open'] }}</div>
            <div class="text-xs text-blue-400/70">Open Chats</div>
        </div>
        <div class="bg-[#1a1a1a] rounded-lg p-4 border border-gray-600/30">
            <div class="text-2xl font-bold text-gray-400">{{ $stats['closed'] }}</div>
            <div class="text-xs text-gray-500">Closed</div>
        </div>
        <div class="bg-[#1a1a1a] rounded-lg p-4 border border-red-600/30">
            <div class="text-2xl font-bold text-red-400">{{ $stats['unread'] }}</div>
            <div class="text-xs text-red-400/70">Unread Messages</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-[#1a1a1a] rounded-lg p-4 border border-gray-800 mb-6">
        <form method="GET" action="{{ route('admin.dedicated-support.index') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-gray-400 mb-1">Search User</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Name, Mobile, or Email..."
                    class="w-full bg-[#0a0a0a] border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Status</label>
                <select name="status" class="bg-[#0a0a0a] border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                Filter
            </button>
            @if($search || $status !== 'all')
            <a href="{{ route('admin.dedicated-support.index') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm rounded-lg transition">
                Clear
            </a>
            @endif
        </form>
    </div>

    <!-- Chat List -->
    <div class="bg-[#1a1a1a] rounded-lg border border-gray-800 overflow-hidden">
        @if($chats->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#111111]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Unread</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Last Message</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Assigned To</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @foreach($chats as $chat)
                    <tr class="hover:bg-[#222222] transition {{ $chat->unread_count > 0 ? 'bg-yellow-500/5' : '' }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                    {{ strtoupper(substr($chat->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-white font-medium text-sm">{{ $chat->user->name ?? 'Unknown' }}</div>
                                    <div class="text-gray-500 text-xs">{{ $chat->user->mobile ?? $chat->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($chat->status === 'pending')
                            <span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 text-xs rounded-full">Awaiting Reply</span>
                            @elseif($chat->status === 'open')
                            <span class="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs rounded-full">Open</span>
                            @else
                            <span class="px-2 py-1 bg-gray-500/20 text-gray-400 text-xs rounded-full">Closed</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($chat->unread_count > 0)
                            <span class="px-2 py-1 bg-red-500 text-white text-xs rounded-full font-bold">{{ $chat->unread_count }}</span>
                            @else
                            <span class="text-gray-600 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-sm">
                            @if($chat->last_message_at)
                            {{ $chat->last_message_at->diffForHumans() }}
                            @else
                            <span class="text-gray-600">No messages</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-sm">
                            @if($chat->assignedAdmin)
                            {{ $chat->assignedAdmin->name }}
                            @else
                            <span class="text-gray-600">Unassigned</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.dedicated-support.show', $chat->id) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition">
                                <span class="material-icons-outlined text-sm">chat</span>
                                Open Chat
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-gray-800">
            {{ $chats->links() }}
        </div>
        @else
        <div class="p-12 text-center">
            <span class="material-icons-outlined text-4xl text-gray-600 mb-3">forum</span>
            <p class="text-gray-400">No support chats found</p>
            <p class="text-gray-600 text-sm mt-1">Ultimate plan users can start a chat from the mobile app</p>
        </div>
        @endif
    </div>
</div>
@endsection

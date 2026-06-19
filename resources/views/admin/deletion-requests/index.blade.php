@extends('layouts.admin')

@section('title', 'Account Deletion Requests')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Account Deletion Requests</h1>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</div>
            <div class="text-sm text-yellow-700">Pending</div>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['processing'] }}</div>
            <div class="text-sm text-blue-700">Processing</div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</div>
            <div class="text-sm text-green-700">Completed</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="text-2xl font-bold text-red-600">{{ $stats['rejected'] }}</div>
            <div class="text-sm text-red-700">Rejected</div>
        </div>
    </div>

    <!-- Filter -->
    <div class="mb-4">
        <form method="GET" class="flex gap-2">
            <select name="status" class="border rounded-lg px-4 py-2">
                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700">Filter</button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mobile</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($requests as $request)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ $request->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($request->user)
                        <div class="text-sm font-medium text-gray-900">{{ $request->user->name }}</div>
                        <div class="text-sm text-gray-500">ID: {{ $request->user->id }}</div>
                        @else
                        <span class="text-gray-400">User Deleted</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $request->mobile }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $request->reason_label }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{!! $request->status_badge !!}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $request->created_at->diffForHumans() }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('admin.deletion-requests.show', $request) }}"
                           class="text-teal-600 hover:text-teal-900">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No deletion requests found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Deletion Request #' . $deletionRequest->id)

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Deletion Request #{{ $deletionRequest->id }}</h1>
        <a href="{{ route('admin.deletion-requests.index') }}" class="text-teal-600 hover:text-teal-800">&larr; Back to List</a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
        {{ session('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Request Details -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Request Details</h2>

            <div class="space-y-4">
                <div>
                    <label class="text-sm text-gray-500">Status</label>
                    <div class="mt-1">{!! $deletionRequest->status_badge !!}</div>
                </div>

                <div>
                    <label class="text-sm text-gray-500">Reason</label>
                    <div class="mt-1 text-gray-900">{{ $deletionRequest->reason_label }}</div>
                </div>

                @if($deletionRequest->feedback)
                <div>
                    <label class="text-sm text-gray-500">User Feedback</label>
                    <div class="mt-1 text-gray-900 bg-gray-50 p-3 rounded">{{ $deletionRequest->feedback }}</div>
                </div>
                @endif

                <div>
                    <label class="text-sm text-gray-500">Requested At</label>
                    <div class="mt-1 text-gray-900">{{ $deletionRequest->created_at->format('d M Y, h:i A') }}</div>
                </div>

                @if($deletionRequest->processed_at)
                <div>
                    <label class="text-sm text-gray-500">Processed At</label>
                    <div class="mt-1 text-gray-900">{{ $deletionRequest->processed_at->format('d M Y, h:i A') }}</div>
                </div>
                @endif

                @if($deletionRequest->processedBy)
                <div>
                    <label class="text-sm text-gray-500">Processed By</label>
                    <div class="mt-1 text-gray-900">{{ $deletionRequest->processedBy->name }}</div>
                </div>
                @endif

                @if($deletionRequest->admin_notes)
                <div>
                    <label class="text-sm text-gray-500">Admin Notes</label>
                    <div class="mt-1 text-gray-900 bg-gray-50 p-3 rounded whitespace-pre-wrap">{{ $deletionRequest->admin_notes }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- User Details -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">User Details</h2>

            @if($deletionRequest->user)
            <div class="space-y-4">
                <div>
                    <label class="text-sm text-gray-500">Name</label>
                    <div class="mt-1 text-gray-900">{{ $deletionRequest->user->name }}</div>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Mobile</label>
                    <div class="mt-1 text-gray-900">{{ $deletionRequest->user->mobile }}</div>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Email</label>
                    <div class="mt-1 text-gray-900">{{ $deletionRequest->user->email ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Registered</label>
                    <div class="mt-1 text-gray-900">{{ $deletionRequest->user->created_at->format('d M Y') }}</div>
                </div>

                @if($userStats)
                <div class="border-t pt-4 mt-4">
                    <label class="text-sm text-gray-500 font-medium">Data to be Deleted</label>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="text-xl font-bold text-gray-800">{{ $userStats['chats'] }}</div>
                            <div class="text-xs text-gray-500">Chats</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="text-xl font-bold text-gray-800">{{ $userStats['messages'] }}</div>
                            <div class="text-xs text-gray-500">Messages</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="text-xl font-bold text-gray-800">{{ $userStats['quizzes'] }}</div>
                            <div class="text-xs text-gray-500">Quizzes</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="text-xl font-bold text-gray-800">{{ $userStats['videos'] }}</div>
                            <div class="text-xs text-gray-500">Videos</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @else
            <div class="text-gray-500">User has been deleted.</div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    @if($deletionRequest->status !== 'completed')
    <div class="mt-6 bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Actions</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Update Status -->
            <form action="{{ route('admin.deletion-requests.update-status', $deletionRequest) }}" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Update Status</label>
                    <select name="status" class="w-full border rounded-lg px-4 py-2">
                        <option value="pending" {{ $deletionRequest->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ $deletionRequest->status == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="completed" {{ $deletionRequest->status == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="rejected" {{ $deletionRequest->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notes</label>
                    <textarea name="admin_notes" rows="2" class="w-full border rounded-lg px-4 py-2">{{ $deletionRequest->admin_notes }}</textarea>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Update Status
                </button>
            </form>

            <!-- Quick Actions -->
            @if($deletionRequest->user && $deletionRequest->status !== 'completed')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quick Actions</label>
                </div>

                <!-- Delete Account -->
                <form action="{{ route('admin.deletion-requests.process', $deletionRequest) }}" method="POST"
                      onsubmit="return confirm('Are you sure you want to PERMANENTLY DELETE this user and all their data? This cannot be undone!')">
                    @csrf
                    <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                        Delete Account & All Data
                    </button>
                </form>

                <!-- Reject -->
                <form action="{{ route('admin.deletion-requests.reject', $deletionRequest) }}" method="POST" class="mt-4">
                    @csrf
                    <input type="text" name="reason" placeholder="Rejection reason..." required
                           class="w-full border rounded-lg px-4 py-2 mb-2">
                    <button type="submit" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                        Reject Request
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection

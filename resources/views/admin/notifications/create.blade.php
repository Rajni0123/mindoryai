<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Notification</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .input-field {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.2s;
        }
        .input-field:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(13, 242, 89, 0.5);
            outline: none;
        }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        @include('admin.partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-semibold text-white">Create Notification</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Send push notification to app users</p>
                    </div>
                    <a href="{{ route('admin.notifications.index') }}" class="px-4 py-2 bg-gray-800 text-sm text-white font-medium rounded-lg hover:bg-gray-700 transition">
                        Back to List
                    </a>
                </div>
            </header>

            <div class="p-6">
                <div class="max-w-3xl mx-auto">
                    @if ($errors->any())
                    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-sm text-red-300">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('admin.notifications.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <div class="card rounded-lg p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Notification Title</label>
                                <input type="text" name="title" value="{{ old('title') }}" required
                                    class="input-field w-full px-4 py-2.5 rounded-lg text-white text-sm"
                                    placeholder="Enter notification title">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Message</label>
                                <textarea name="message" required rows="4"
                                    class="input-field w-full px-4 py-2.5 rounded-lg text-white text-sm"
                                    placeholder="Enter notification message">{{ old('message') }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Notification Type</label>
                                <select name="type" required
                                    class="input-field w-full px-4 py-2.5 rounded-lg text-white text-sm">
                                    <option value="info" {{ old('type') === 'info' ? 'selected' : '' }}>Info</option>
                                    <option value="success" {{ old('type') === 'success' ? 'selected' : '' }}>Success</option>
                                    <option value="warning" {{ old('type') === 'warning' ? 'selected' : '' }}>Warning</option>
                                    <option value="error" {{ old('type') === 'error' ? 'selected' : '' }}>Error</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Send To</label>
                                <select name="send_to" id="send_to" required
                                    class="input-field w-full px-4 py-2.5 rounded-lg text-white text-sm"
                                    onchange="toggleUserSelection()">
                                    <option value="all" {{ old('send_to') === 'all' ? 'selected' : '' }}>All Users</option>
                                    <option value="specific" {{ old('send_to') === 'specific' ? 'selected' : '' }}>Specific Users</option>
                                </select>
                            </div>

                            <div id="user_selection" style="display: none;">
                                <label class="block text-sm font-medium text-gray-400 mb-2">Select Users</label>
                                <div class="max-h-48 overflow-y-auto input-field rounded-lg p-3">
                                    @foreach($users as $user)
                                    <label class="flex items-center py-2 hover:bg-white/5 rounded px-2 cursor-pointer">
                                        <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                            class="mr-2 rounded bg-gray-800 border-gray-700 text-green-500 focus:ring-green-500">
                                        <span class="text-sm text-white">{{ $user->name }}</span>
                                        <span class="text-xs text-gray-500 ml-auto">{{ $user->mobile ?? $user->email }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Schedule (Optional)</label>
                                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                                    class="input-field w-full px-4 py-2.5 rounded-lg text-white text-sm">
                                <p class="mt-1 text-xs text-gray-500">Leave empty to send immediately</p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.notifications.index') }}"
                                class="px-4 py-2 bg-gray-800 text-sm text-white font-medium rounded-lg hover:bg-gray-700 transition">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-4 py-2 bg-gradient-to-r from-[#0df259] to-[#06b6d4] text-sm text-black font-medium rounded-lg hover:opacity-90 transition">
                                Send Notification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleUserSelection() {
            const sendTo = document.getElementById('send_to').value;
            const userSelection = document.getElementById('user_selection');
            userSelection.style.display = sendTo === 'specific' ? 'block' : 'none';
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleUserSelection();
        });
    </script>
</body>
</html>

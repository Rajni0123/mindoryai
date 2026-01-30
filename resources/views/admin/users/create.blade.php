<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create User | Admin Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "neon-blue": "#3ddcff",
                        "neon-violet": "#9d5bff",
                        "soft-grey": "#d1d1d1"
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"],
                        "body": ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="font-body text-white">
    <div class="flex h-screen">
        <!-- Sidebar -->
        @include('admin.partials.sidebar')

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-gray-900/30 backdrop-blur-sm border-b border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold font-display text-white">Create New User</h2>
                        <p class="text-soft-grey/70 text-sm mt-1">Manually create a new user account with custom settings</p>
                    </div>
                    <a href="{{ route('admin.users') }}" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition">
                        ← Back to Users
                    </a>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                @if($errors->any())
                <div class="bg-red-500/20 border border-red-500/50 rounded-lg p-4 mb-6">
                    <ul class="list-disc list-inside text-red-300">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="bg-gray-900/40 backdrop-blur-sm border border-gray-800 rounded-xl p-8">
                    <form action="{{ route('admin.users.store') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-soft-grey mb-2">Name *</label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                    class="w-full px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-lg focus:ring-2 focus:ring-neon-blue focus:border-transparent text-white">
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-soft-grey mb-2">Email *</label>
                                <input type="email" name="email" value="{{ old('email') }}" required
                                    class="w-full px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-lg focus:ring-2 focus:ring-neon-blue focus:border-transparent text-white">
                            </div>

                            <!-- Password -->
                            <div>
                                <label class="block text-sm font-medium text-soft-grey mb-2">Password *</label>
                                <input type="password" name="password" required
                                    class="w-full px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-lg focus:ring-2 focus:ring-neon-blue focus:border-transparent text-white">
                                <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
                            </div>

                            <!-- Role -->
                            <div>
                                <label class="block text-sm font-medium text-soft-grey mb-2">Role *</label>
                                <select name="role" required
                                    class="w-full px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-lg focus:ring-2 focus:ring-neon-blue focus:border-transparent text-white">
                                    <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>

                            <!-- Plan -->
                            <div>
                                <label class="block text-sm font-medium text-soft-grey mb-2">Subscription Plan</label>
                                <select name="plan_id"
                                    class="w-full px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-lg focus:ring-2 focus:ring-neon-blue focus:border-transparent text-white">
                                    <option value="">Free (No Plan)</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->name }} — ₹{{ number_format($plan->price) }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Plan controls credits, AI access, and features automatically</p>
                            </div>
                        </div>

                        <!-- Account Status -->
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-white mb-4">Account Status</h3>
                            <div class="flex items-center p-3 bg-gray-800/30 rounded-lg w-fit">
                                <input type="checkbox" name="is_active" id="is_active" value="1" checked
                                    class="w-4 h-4 text-neon-blue border-gray-600 rounded focus:ring-neon-blue">
                                <label for="is_active" class="ml-2 text-sm font-medium text-soft-grey">
                                    Active Account
                                </label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="mt-8 flex gap-4">
                            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-neon-blue to-neon-violet text-black font-bold rounded-lg hover:shadow-[0_0_20px_0_rgba(61,220,255,0.5)] transition-all">
                                Create User
                            </button>
                            <a href="{{ route('admin.users') }}" class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

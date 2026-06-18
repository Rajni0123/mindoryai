<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Profile | Admin Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #09090b;
            min-height: 100vh;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="text-white">
    <div class="flex h-screen">
        <!-- Sidebar -->
        @include('admin.partials.sidebar')

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="glass-effect border-b border-white/5 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Admin Profile</h2>
                        <p class="text-gray-400 text-sm mt-1">Manage your account settings</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                @if(session('success'))
                <div class="bg-green-500/20 border border-green-500/50 rounded-lg p-4 flex items-center gap-3 mb-6">
                    <span class="material-icons-outlined text-green-400">check_circle</span>
                    <p class="text-green-300">{{ session('success') }}</p>
                </div>
                @endif

                @if($errors->any())
                <div class="bg-red-500/20 border border-red-500/50 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <span class="material-icons-outlined text-red-400">error</span>
                        <div>
                            <p class="text-red-300 font-semibold mb-2">There were some errors with your submission:</p>
                            <ul class="list-disc list-inside text-red-300 text-sm space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @endif

                <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Profile Information -->
                    <div class="glass-effect rounded-xl p-6">
                        <h3 class="text-lg font-bold font-display text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined text-blue-400">person</span>
                            Profile Information
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-semibold text-blue-400 mb-2">
                                    Name
                                </label>
                                <input type="text"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $admin->name) }}"
                                       required
                                       class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="Your Name">
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-semibold text-blue-400 mb-2">
                                    Email Address
                                </label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="{{ old('email', $admin->email) }}"
                                       required
                                       class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="admin@example.com">
                            </div>

                            <div>
                                <label for="mobile" class="block text-sm font-semibold text-blue-400 mb-2">
                                    Mobile Number
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">+91</span>
                                    <input type="tel"
                                           id="mobile"
                                           name="mobile"
                                           value="{{ old('mobile', $admin->mobile) }}"
                                           maxlength="10"
                                           pattern="[6-9][0-9]{9}"
                                           required
                                           class="w-full pl-14 pr-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                           placeholder="9876543210"
                                           onkeypress="return /[0-9]/.test(event.key)">
                                </div>
                                <p class="mt-2 text-xs text-soft-grey/70">Used for OTP login authentication</p>
                            </div>

                            <div class="bg-blue-500/10 border border-blue-500/50 rounded-lg p-3 flex items-start gap-2">
                                <span class="material-icons-outlined text-blue-400 text-sm mt-0.5">info</span>
                                <p class="text-xs text-blue-300">Role: <strong>{{ ucfirst($admin->role) }}</strong> | Joined: <strong>{{ $admin->created_at->format('M d, Y') }}</strong></p>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="glass-effect rounded-xl p-6">
                        <h3 class="text-lg font-bold font-display text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined text-purple-400">lock</span>
                            Change Password
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label for="current_password" class="block text-sm font-semibold text-blue-400 mb-2">
                                    Current Password
                                </label>
                                <input type="password"
                                       id="current_password"
                                       name="current_password"
                                       class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="Enter current password">
                                <p class="mt-2 text-xs text-soft-grey/70">Leave blank if you don't want to change password</p>
                            </div>

                            <div>
                                <label for="new_password" class="block text-sm font-semibold text-blue-400 mb-2">
                                    New Password
                                </label>
                                <input type="password"
                                       id="new_password"
                                       name="new_password"
                                       class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="Enter new password (min 8 characters)">
                            </div>

                            <div>
                                <label for="new_password_confirmation" class="block text-sm font-semibold text-blue-400 mb-2">
                                    Confirm New Password
                                </label>
                                <input type="password"
                                       id="new_password_confirmation"
                                       name="new_password_confirmation"
                                       class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="Confirm new password">
                            </div>

                            <div class="bg-yellow-500/10 border border-yellow-500/50 rounded-lg p-3 flex items-start gap-2">
                                <span class="material-icons-outlined text-yellow-400 text-sm mt-0.5">info</span>
                                <p class="text-xs text-yellow-300">Password must be at least 8 characters long. To change password, you must enter your current password.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Two-Factor Authentication -->
                    <div class="glass-effect rounded-xl p-6">
                        <h3 class="text-lg font-bold font-display text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined text-green-400">security</span>
                            Two-Factor Authentication (2FA)
                        </h3>

                        <div class="space-y-4">
                            @if($admin->two_factor_enabled)
                                <div class="bg-green-500/20 border border-green-500/50 rounded-lg p-4 flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="material-icons-outlined text-green-400">check_circle</span>
                                        <div>
                                            <p class="text-green-300 font-semibold">2FA is enabled</p>
                                            <p class="text-green-300/70 text-sm mt-1">Your account is protected with two-factor authentication</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.2fa.setup') }}"
                                       class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm font-semibold rounded-lg transition-colors whitespace-nowrap">
                                        Manage 2FA
                                    </a>
                                </div>
                            @else
                                <div class="bg-yellow-500/20 border border-yellow-500/50 rounded-lg p-4 flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="material-icons-outlined text-yellow-400">warning</span>
                                        <div>
                                            <p class="text-yellow-300 font-semibold">2FA is not enabled</p>
                                            <p class="text-yellow-300/70 text-sm mt-1">Add an extra layer of security to your admin account</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.2fa.setup') }}"
                                       class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg hover:shadow-[0_0_20px_0_rgba(61,220,255,0.5)] transition-all whitespace-nowrap">
                                        Setup 2FA
                                    </a>
                                </div>
                            @endif

                            <div class="bg-blue-500/10 border border-blue-500/50 rounded-lg p-3 flex items-start gap-2">
                                <span class="material-icons-outlined text-blue-400 text-sm mt-0.5">info</span>
                                <p class="text-xs text-blue-300">When 2FA is enabled, you'll need to enter a 6-digit code from your authenticator app after OTP verification during login.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('admin.dashboard') }}"
                           class="px-6 py-3 bg-gray-700 text-white font-semibold rounded-lg hover:bg-gray-600 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-8 py-3 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg hover:shadow-[0_0_20px_0_rgba(61,220,255,0.5)] transition-all">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>

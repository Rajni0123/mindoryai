<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit User | Admin Dashboard</title>

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
                        <div class="flex items-center gap-2 mb-2">
                            <a href="{{ route('admin.users') }}" class="text-soft-grey hover:text-neon-blue transition-colors">
                                <span class="material-icons-outlined">arrow_back</span>
                            </a>
                            <h2 class="text-2xl font-bold font-display text-white">Edit User</h2>
                        </div>
                        <p class="text-soft-grey/70 text-sm">Manage user permissions and credit limits</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6 max-w-4xl">
                    @csrf
                    @method('PUT')

                    <!-- User Information -->
                    <div class="bg-gray-900/50 backdrop-blur-sm border border-gray-800 rounded-xl p-6">
                        <h3 class="text-lg font-bold font-display text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined text-neon-blue">person</span>
                            User Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-semibold text-neon-blue mb-2">
                                    Name <span class="text-red-400">*</span>
                                </label>
                                <input type="text"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $user->name) }}"
                                       required
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors">
                                @error('name')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-semibold text-neon-blue mb-2">
                                    Email <span class="text-red-400">*</span>
                                </label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="{{ old('email', $user->email) }}"
                                       required
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors">
                                @error('email')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Plan Management -->
                    <div class="bg-gray-900/50 backdrop-blur-sm border border-gray-800 rounded-xl p-6">
                        <h3 class="text-lg font-bold font-display text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined text-neon-violet">workspace_premium</span>
                            Plan Management
                        </h3>

                        <div class="mb-4">
                            <label for="plan_id" class="block text-sm font-semibold text-neon-blue mb-2">
                                Assigned Plan
                            </label>
                            <select id="plan_id"
                                    name="plan_id"
                                    class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-neon-blue transition-colors">
                                <option value="" {{ !$user->plan_id ? 'selected' : '' }}>Free (No Plan)</option>
                                @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ $user->plan_id == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} — ₹{{ number_format($plan->price) }}/{{ $plan->billing_period ?? 'month' }}
                                </option>
                                @endforeach
                            </select>
                            <p class="text-soft-grey/70 text-xs mt-1">Changing the plan will auto-update credits, AI access, and subscription</p>
                        </div>

                        <!-- Current Plan Info -->
                        @if($user->plan)
                        <div class="p-4 bg-green-500/10 border border-green-500/30 rounded-lg mb-4">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="material-icons-outlined text-green-400" style="font-size: 18px;">verified</span>
                                <p class="text-green-300 font-semibold">{{ $user->plan->name }}</p>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div class="p-2 bg-gray-800/50 rounded-lg">
                                    <p class="text-soft-grey/70 text-[10px] uppercase">Tokens</p>
                                    <p class="text-white font-semibold text-sm">{{ $user->plan->unlimited_credits ? '∞' : number_format($user->plan->message_tokens ?? 0) }}</p>
                                </div>
                                <div class="p-2 bg-gray-800/50 rounded-lg">
                                    <p class="text-soft-grey/70 text-[10px] uppercase">Validity</p>
                                    <p class="text-white font-semibold text-sm">{{ $user->plan->validity_days ? $user->plan->validity_days . ' days' : 'Lifetime' }}</p>
                                </div>
                                <div class="p-2 bg-gray-800/50 rounded-lg">
                                    <p class="text-soft-grey/70 text-[10px] uppercase">AI Access</p>
                                    <p class="text-white font-semibold text-sm">
                                        @php
                                            $aiCount = ($user->plan->can_use_gpt4 ? 1 : 0) + ($user->plan->can_use_claude ? 1 : 0) + ($user->plan->can_use_deepseek ? 1 : 0) + ($user->plan->can_use_grok ? 1 : 0);
                                        @endphp
                                        {{ $aiCount }}/4 providers
                                    </p>
                                </div>
                                <div class="p-2 bg-gray-800/50 rounded-lg">
                                    <p class="text-soft-grey/70 text-[10px] uppercase">Price</p>
                                    <p class="text-white font-semibold text-sm">₹{{ number_format($user->plan->price) }}</p>
                                </div>
                            </div>
                            <!-- AI providers included -->
                            <div class="mt-3 flex flex-wrap gap-2">
                                @if($user->plan->can_use_gpt4)
                                <span class="px-2 py-0.5 bg-green-500/10 text-green-400 rounded text-[10px] font-medium">GPT-4</span>
                                @endif
                                @if($user->plan->can_use_claude)
                                <span class="px-2 py-0.5 bg-orange-500/10 text-orange-400 rounded text-[10px] font-medium">Claude</span>
                                @endif
                                @if($user->plan->can_use_deepseek)
                                <span class="px-2 py-0.5 bg-blue-500/10 text-blue-400 rounded text-[10px] font-medium">DeepSeek</span>
                                @endif
                                @if($user->plan->can_use_grok)
                                <span class="px-2 py-0.5 bg-purple-500/10 text-purple-400 rounded text-[10px] font-medium">Grok</span>
                                @endif
                            </div>
                        </div>
                        @else
                        <div class="p-4 bg-gray-500/10 border border-gray-500/30 rounded-lg mb-4 flex items-center gap-3">
                            <span class="material-icons-outlined text-gray-400">person_outline</span>
                            <div>
                                <p class="text-gray-300 font-semibold">Free User</p>
                                <p class="text-gray-400 text-xs">No plan assigned — limited access</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Account Status -->
                    <div class="bg-gray-900/50 backdrop-blur-sm border border-gray-800 rounded-xl p-6">
                        <h3 class="text-lg font-bold font-display text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined text-neon-blue">shield</span>
                            Account Status
                        </h3>

                        <div class="flex items-center gap-3">
                            <input type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded bg-gray-800/50 border-gray-700 text-green-500 focus:ring-green-500 focus:ring-offset-0">
                            <label for="is_active" class="text-sm text-soft-grey">
                                Account is active (Uncheck to block user)
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('admin.users') }}"
                           class="px-6 py-3 text-soft-grey hover:text-white transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-8 py-3 bg-gradient-to-r from-neon-blue to-neon-violet text-black font-bold rounded-lg hover:shadow-[0_0_20px_0_rgba(61,220,255,0.5)] transition-all">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>

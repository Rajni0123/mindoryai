<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard</title>

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
            <!-- Header -->
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-semibold text-white">Dashboard</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Welcome back, {{ auth()->user()->name }}</p>
                    </div>
                    <div class="text-xs text-gray-500">{{ now()->format('M d, Y') }}</div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6 space-y-6">
                <!-- Stats Grid -->
                <div class="grid grid-cols-4 gap-4">
                    <!-- Total Users -->
                    <div class="card rounded-lg p-4 transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs text-gray-500 font-medium">Total Users</p>
                                <p class="text-2xl font-semibold text-white mt-1">{{ $stats['total_users'] }}</p>
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-blue-400" style="font-size: 16px;">people</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Plans -->
                    <div class="card rounded-lg p-4 transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs text-gray-500 font-medium">Pricing Plans</p>
                                <p class="text-2xl font-semibold text-white mt-1">{{ $stats['total_plans'] }}</p>
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-indigo-400" style="font-size: 16px;">payments</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-800/50">
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="text-gray-500">Active</span>
                                <span class="text-green-400 font-medium">{{ $stats['active_plans'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Features -->
                    <div class="card rounded-lg p-4 transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs text-gray-500 font-medium">Features</p>
                                <p class="text-2xl font-semibold text-white mt-1">{{ $stats['total_features'] }}</p>
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-purple-400" style="font-size: 16px;">stars</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-800/50">
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="text-gray-500">Active</span>
                                <span class="text-green-400 font-medium">{{ $stats['active_features'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Active Subscriptions -->
                    <div class="card rounded-lg p-4 transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs text-gray-500 font-medium">Paid Users</p>
                                <p class="text-2xl font-semibold text-white mt-1">{{ \App\Models\User::whereNotNull('plan_id')->count() }}</p>
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-amber-400" style="font-size: 16px;">workspace_premium</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-800/50">
                            <p class="text-[10px] text-gray-500">With active plans</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Recent Users Table -->
                    <div class="card rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-800/50">
                            <h2 class="text-sm font-semibold text-white">Recent Users</h2>
                        </div>
                        <div class="divide-y divide-gray-800/50">
                            @php $recentUsers = \App\Models\User::with('plan')->orderBy('created_at', 'desc')->limit(10)->get(); @endphp
                            @forelse($recentUsers as $recentUser)
                            <div class="px-4 py-3 hover:bg-white/5 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center flex-shrink-0">
                                            <span class="text-[10px] font-semibold text-white">
                                                {{ strtoupper(substr($recentUser->name ?? $recentUser->mobile ?? 'U', 0, 1)) }}
                                            </span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-white truncate">{{ $recentUser->name ?? $recentUser->mobile }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $recentUser->email }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        @if($recentUser->plan)
                                        <span class="inline-block px-2 py-0.5 bg-green-500/10 text-green-400 rounded text-[10px] font-medium">
                                            {{ $recentUser->plan->name }}
                                        </span>
                                        @else
                                        <span class="inline-block px-2 py-0.5 bg-gray-500/10 text-gray-400 rounded text-[10px] font-medium">
                                            Free
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="px-4 py-8 text-center">
                                <span class="material-icons-outlined text-gray-700" style="font-size: 32px;">people</span>
                                <p class="text-xs text-gray-500 mt-2">No users yet</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="space-y-4">
                        <!-- System Status -->
                        <div class="card rounded-lg p-4">
                            <h2 class="text-sm font-semibold text-white mb-3">System Status</h2>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-green-400"></div>
                                        <span class="text-xs text-gray-400">Database</span>
                                    </div>
                                    <span class="text-xs text-green-400 font-medium">Online</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-green-400"></div>
                                        <span class="text-xs text-gray-400">API Services</span>
                                    </div>
                                    <span class="text-xs text-green-400 font-medium">Active</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-green-400"></div>
                                        <span class="text-xs text-gray-400">Storage</span>
                                    </div>
                                    <span class="text-xs text-green-400 font-medium">Connected</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card rounded-lg p-4">
                            <h2 class="text-sm font-semibold text-white mb-3">Quick Actions</h2>
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('admin.pricing.create') }}" class="flex items-center gap-2 px-3 py-2 bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 rounded text-xs font-medium transition-colors">
                                    <span class="material-icons-outlined" style="font-size: 14px;">add</span>
                                    <span>Add Plan</span>
                                </a>
                                <a href="{{ route('admin.users') }}" class="flex items-center gap-2 px-3 py-2 bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20 rounded text-xs font-medium transition-colors">
                                    <span class="material-icons-outlined" style="font-size: 14px;">people</span>
                                    <span>Users</span>
                                </a>
                                <a href="{{ route('admin.settings') }}" class="flex items-center gap-2 px-3 py-2 bg-gray-800/50 text-gray-400 hover:bg-gray-800 rounded text-xs font-medium transition-colors">
                                    <span class="material-icons-outlined" style="font-size: 14px;">settings</span>
                                    <span>Settings</span>
                                </a>
                                <a href="{{ route('admin.ai-settings') }}" class="flex items-center gap-2 px-3 py-2 bg-gray-800/50 text-gray-400 hover:bg-gray-800 rounded text-xs font-medium transition-colors">
                                    <span class="material-icons-outlined" style="font-size: 14px;">psychology</span>
                                    <span>AI Models</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

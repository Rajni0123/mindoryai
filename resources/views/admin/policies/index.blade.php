<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Policies Management | Admin Dashboard</title>

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
                        <h2 class="text-2xl font-bold text-white">Policies Management</h2>
                        <p class="text-gray-400 text-sm mt-1">Manage Privacy Policy, Terms of Service, Refund Policy, Cancellation Policy, About Us, and Help & Support pages</p>
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

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @forelse($policies as $policy)
                    <div class="glass-effect rounded-lg overflow-hidden border-{{ $policy->is_enabled ? 'green-500/50' : 'gray-700' }}">
                        <div class="p-4 border-b border-white/5 flex items-center justify-between">
                            <h6 class="font-semibold text-white flex items-center gap-2">
                                @if($policy->key == 'privacy_policy')
                                <span class="material-icons-outlined text-blue-400">verified_user</span>
                                @elseif($policy->key == 'terms_of_service')
                                <span class="material-icons-outlined text-purple-400">description</span>
                                @elseif($policy->key == 'refund_policy')
                                <span class="material-icons-outlined text-green-400">currency_rupee</span>
                                @elseif($policy->key == 'cancellation_policy')
                                <span class="material-icons-outlined text-red-400">cancel</span>
                                @elseif($policy->key == 'cookie_policy')
                                <span class="material-icons-outlined text-amber-400">cookie</span>
                                @elseif($policy->key == 'about')
                                <span class="material-icons-outlined text-indigo-400">info</span>
                                @elseif($policy->key == 'support')
                                <span class="material-icons-outlined text-yellow-400">support_agent</span>
                                @else
                                <span class="material-icons-outlined text-gray-400">article</span>
                                @endif
                                {{ $policy->title }}
                            </h6>
                            <span class="px-2 py-1 rounded text-xs font-medium {{ $policy->is_enabled ? 'bg-green-500/20 text-green-400' : 'bg-gray-700 text-gray-400' }}">
                                {{ $policy->is_enabled ? 'Visible' : 'Hidden' }}
                            </span>
                        </div>
                        <div class="p-4">
                            <div class="space-y-1 text-xs text-gray-400">
                                <p><strong class="text-gray-300">Key:</strong> {{ $policy->key }}</p>
                                <p><strong class="text-gray-300">Order:</strong> {{ $policy->order }}</p>
                                <p><strong class="text-gray-300">Sections:</strong> {{ count($policy->content['sections'] ?? []) }}</p>
                            </div>
                        </div>
                        <div class="p-4 border-t border-white/5 flex items-center justify-between gap-2">
                            <a href="{{ route('admin.policies.edit', $policy->id) }}" class="flex items-center gap-1 px-3 py-2 bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 rounded text-xs font-medium transition-colors">
                                <span class="material-icons-outlined text-sm">edit</span>
                                Edit
                            </a>
                            <form action="{{ route('admin.policies.toggle', $policy->id) }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="flex items-center gap-1 px-3 py-2 {{ $policy->is_enabled ? 'bg-amber-500/20 text-amber-400 hover:bg-amber-500/30' : 'bg-green-500/20 text-green-400 hover:bg-green-500/30' }} rounded text-xs font-medium transition-colors">
                                    <span class="material-icons-outlined text-sm">{{ $policy->is_enabled ? 'visibility_off' : 'visibility' }}</span>
                                    {{ $policy->is_enabled ? 'Hide' : 'Show' }}
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full">
                        <div class="glass-effect rounded-lg p-8 text-center">
                            <span class="material-icons-outlined text-gray-600 text-4xl">folder_open</span>
                            <p class="text-gray-400 mt-2">No policies found. Run the PolicySeeder to create default policies.</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </main>
    </div>
</body>
</html>

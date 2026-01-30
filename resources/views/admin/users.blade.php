<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Users Management</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .card:hover { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.12); }
        .stat-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.06); }
        .filter-tab { transition: all 0.15s; }
        .filter-tab.active { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border-color: rgba(59, 130, 246, 0.3); }
        .plan-select { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #e5e7eb; }
        .plan-select:focus { border-color: rgba(59,130,246,0.5); outline: none; }
        .plan-select option { background: #1a1a1a; color: #e5e7eb; }
        .toast { animation: slideIn 0.3s ease-out, fadeOut 0.3s ease-in 2.7s; }
        @keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
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
                        <h1 class="text-base font-semibold text-white">Users Management</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Manage users, plans, and access</p>
                    </div>
                    <a href="{{ route('admin.users.create') }}"
                       class="flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                        <span class="material-icons-outlined" style="font-size: 16px;">person_add</span>
                        Create User
                    </a>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                <!-- Toast notification -->
                <div id="toast" class="hidden fixed top-4 right-4 z-50 px-4 py-3 rounded-lg text-sm font-medium shadow-lg toast"></div>

                @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-green-400" style="font-size: 16px;">check_circle</span>
                    <p class="text-green-300">{{ session('success') }}</p>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-red-400" style="font-size: 16px;">error</span>
                    <p class="text-red-300">{{ session('error') }}</p>
                </div>
                @endif

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="stat-card rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-blue-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-blue-400" style="font-size: 20px;">people</span>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-wider">Total Users</p>
                                <p class="text-lg font-semibold text-white">{{ number_format($stats['total_users']) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-green-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-green-400" style="font-size: 20px;">verified</span>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-wider">Paid Users</p>
                                <p class="text-lg font-semibold text-white">{{ number_format($stats['paid_users']) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-gray-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-gray-400" style="font-size: 20px;">person_outline</span>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-wider">Free Users</p>
                                <p class="text-lg font-semibold text-white">{{ number_format($stats['free_users']) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-purple-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-purple-400" style="font-size: 20px;">trending_up</span>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-wider">New Today</p>
                                <p class="text-lg font-semibold text-white">{{ number_format($stats['new_today']) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search & Filters -->
                <div class="flex flex-col sm:flex-row gap-3 mb-4">
                    <!-- Search -->
                    <div class="relative flex-1">
                        <span class="material-icons-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" style="font-size: 18px;">search</span>
                        <input type="text" id="searchInput" placeholder="Search by name, email, or mobile..."
                               value="{{ request('search') }}"
                               class="w-full pl-10 pr-4 py-2.5 bg-white/5 border border-gray-800 rounded-lg text-sm text-white placeholder-gray-500 focus:border-blue-500/50 focus:outline-none transition-colors">
                    </div>

                    <!-- Filter Tabs -->
                    <div class="flex gap-1.5 flex-wrap">
                        <a href="{{ route('admin.users', ['search' => request('search')]) }}"
                           class="filter-tab px-3 py-2 rounded-lg text-xs font-medium border border-gray-800 text-gray-400 hover:text-white {{ request('filter', 'all') === 'all' ? 'active' : '' }}">
                            All
                        </a>
                        <a href="{{ route('admin.users', ['filter' => 'paid', 'search' => request('search')]) }}"
                           class="filter-tab px-3 py-2 rounded-lg text-xs font-medium border border-gray-800 text-gray-400 hover:text-white {{ request('filter') === 'paid' ? 'active' : '' }}">
                            Paid
                        </a>
                        <a href="{{ route('admin.users', ['filter' => 'free', 'search' => request('search')]) }}"
                           class="filter-tab px-3 py-2 rounded-lg text-xs font-medium border border-gray-800 text-gray-400 hover:text-white {{ request('filter') === 'free' ? 'active' : '' }}">
                            Free
                        </a>
                        <a href="{{ route('admin.users', ['filter' => 'active', 'search' => request('search')]) }}"
                           class="filter-tab px-3 py-2 rounded-lg text-xs font-medium border border-gray-800 text-gray-400 hover:text-white {{ request('filter') === 'active' ? 'active' : '' }}">
                            Active
                        </a>
                        <a href="{{ route('admin.users', ['filter' => 'inactive', 'search' => request('search')]) }}"
                           class="filter-tab px-3 py-2 rounded-lg text-xs font-medium border border-gray-800 text-gray-400 hover:text-white {{ request('filter') === 'inactive' ? 'active' : '' }}">
                            Inactive
                        </a>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-white/5 border-b border-gray-800/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">User</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Plan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Credits</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Joined</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Change Plan</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800/50">
                                @forelse($users as $user)
                                <tr class="hover:bg-white/5 transition-colors" data-user-id="{{ $user->id }}"
                                    data-name="{{ strtolower($user->name) }}"
                                    data-email="{{ strtolower($user->email ?? '') }}"
                                    data-mobile="{{ $user->mobile ?? '' }}">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-xs font-medium text-white">{{ $user->name }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $user->email ?? $user->mobile }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3" id="plan-badge-{{ $user->id }}">
                                        @if($user->plan_id && $user->plan)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-500/10 text-green-400 rounded text-[10px] font-medium">
                                            <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                                            Paid - {{ $user->plan->name }}
                                        </span>
                                        @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-500/10 text-gray-400 rounded text-[10px] font-medium">
                                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                            Free
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-[10px]">
                                            @if($user->userCredits)
                                                @if($user->userCredits->unlimited_mode)
                                                    <span class="text-yellow-400 font-medium">Unlimited</span>
                                                @else
                                                    <span class="text-gray-300">{{ $user->userCredits->available_credits ?? 0 }} credits</span>
                                                @endif
                                            @else
                                                <span class="text-gray-500">No credits</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($user->is_active)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-500/10 text-green-400 rounded text-[10px] font-medium">
                                            <span class="w-1 h-1 bg-green-400 rounded-full"></span>
                                            Active
                                        </span>
                                        @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-500/10 text-gray-400 rounded text-[10px] font-medium">
                                            <span class="w-1 h-1 bg-gray-400 rounded-full"></span>
                                            Inactive
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-[10px] text-gray-500">{{ $user->created_at->format('M d, Y') }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <select class="plan-select rounded-lg text-[11px] px-2 py-1.5 w-32"
                                                onchange="changePlan({{ $user->id }}, this.value)"
                                                data-original="{{ $user->plan_id ?? '' }}">
                                            <option value="" {{ !$user->plan_id ? 'selected' : '' }}>Free</option>
                                            @foreach($plans as $plan)
                                            <option value="{{ $plan->id }}" {{ $user->plan_id == $plan->id ? 'selected' : '' }}>
                                                {{ $plan->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="p-1.5 bg-blue-500/10 text-blue-400 rounded hover:bg-blue-500/20 transition-colors"
                                               title="Edit">
                                                <span class="material-icons-outlined" style="font-size: 14px;">edit</span>
                                            </a>

                                            <form action="{{ route('admin.users.toggle-active', $user) }}"
                                                  method="POST"
                                                  class="inline-block">
                                                @csrf
                                                <button type="submit"
                                                        class="p-1.5 {{ $user->is_active ? 'bg-red-500/10 text-red-400 hover:bg-red-500/20' : 'bg-green-500/10 text-green-400 hover:bg-green-500/20' }} rounded transition-colors"
                                                        title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <span class="material-icons-outlined" style="font-size: 14px;">{{ $user->is_active ? 'block' : 'check_circle' }}</span>
                                                </button>
                                            </form>

                                            @if($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.delete', $user) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Are you sure you want to delete this user?');"
                                                  class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="p-1.5 bg-gray-500/10 text-gray-400 rounded hover:bg-gray-500/20 transition-colors"
                                                        title="Delete">
                                                    <span class="material-icons-outlined" style="font-size: 14px;">delete</span>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <span class="material-icons-outlined text-gray-700 mb-2 block" style="font-size: 48px;">people_outline</span>
                                        <p class="text-gray-500 text-sm">No users found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- User Count -->
                <div class="mt-3 text-xs text-gray-500">
                    Showing {{ $users->count() }} user{{ $users->count() !== 1 ? 's' : '' }}
                </div>
            </div>
        </main>
    </div>

    <script>
        // CSRF token for AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.className = 'fixed top-4 right-4 z-50 px-4 py-3 rounded-lg text-sm font-medium shadow-lg toast';
            if (type === 'success') {
                toast.classList.add('bg-green-500/20', 'border', 'border-green-500/30', 'text-green-300');
            } else {
                toast.classList.add('bg-red-500/20', 'border', 'border-red-500/30', 'text-red-300');
            }
            toast.textContent = message;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        }

        // Change plan via AJAX
        function changePlan(userId, planId) {
            fetch(`/admin/users/${userId}/change-plan`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ plan_id: planId || null })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);

                    // Update badge
                    const badgeEl = document.getElementById(`plan-badge-${userId}`);
                    if (data.is_paid) {
                        badgeEl.innerHTML = `<span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-500/10 text-green-400 rounded text-[10px] font-medium">
                            <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                            Paid - ${data.plan_name}
                        </span>`;
                    } else {
                        badgeEl.innerHTML = `<span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-500/10 text-gray-400 rounded text-[10px] font-medium">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                            Free
                        </span>`;
                    }
                } else {
                    showToast(data.message || 'Failed to change plan', 'error');
                }
            })
            .catch(err => {
                showToast('Error changing plan', 'error');
                console.error(err);
            });
        }

        // Client-side search (instant filtering)
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.toLowerCase().trim();

            searchTimeout = setTimeout(() => {
                const rows = document.querySelectorAll('tbody tr[data-user-id]');
                rows.forEach(row => {
                    const name = row.dataset.name || '';
                    const email = row.dataset.email || '';
                    const mobile = row.dataset.mobile || '';

                    if (!query || name.includes(query) || email.includes(query) || mobile.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }, 200);
        });

        // Enter key → server-side search
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const val = this.value.trim();
                const url = new URL(window.location.href);
                if (val) {
                    url.searchParams.set('search', val);
                } else {
                    url.searchParams.delete('search');
                }
                window.location.href = url.toString();
            }
        });
    </script>
</body>
</html>

@php
    $siteName = config('app.name', 'BlinkStudy');

    $sections = [
        [
            'label' => 'Overview',
            'items' => [
                ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'],
                ['route' => 'admin.users', 'match' => 'admin.users*', 'icon' => 'people', 'label' => 'Users'],
            ],
        ],
        [
            'label' => 'Business',
            'items' => [
                ['route' => 'admin.pricing.index', 'match' => 'admin.pricing.*', 'icon' => 'payments', 'label' => 'Pricing Plans'],
                ['route' => 'admin.payment-gateways', 'match' => 'admin.payment-gateways*', 'icon' => 'credit_card', 'label' => 'Payment Gateways'],
            ],
        ],
        [
            'label' => 'App & Website',
            'items' => [
                ['route' => 'admin.homepage-settings.index', 'match' => 'admin.homepage-settings.*', 'icon' => 'home', 'label' => 'Homepage'],
                ['route' => 'admin.mobile-app-config.index', 'match' => 'admin.mobile-app-config.*', 'icon' => 'phone_android', 'label' => 'Mobile App'],
                ['route' => 'admin.auth-settings.index', 'match' => 'admin.auth-settings.*', 'icon' => 'login', 'label' => 'Login Methods'],
            ],
        ],
        [
            'label' => 'AI & Content',
            'items' => [
                ['route' => 'admin.ai-settings', 'match' => 'admin.ai-settings*', 'icon' => 'psychology', 'label' => 'AI Models'],
                ['route' => 'admin.exams.index', 'match' => 'admin.exams.*', 'icon' => 'school', 'label' => 'Exams'],
                ['route' => 'admin.daily-challenges.index', 'match' => 'admin.daily-challenges.*', 'icon' => 'emoji_events', 'label' => 'Daily Challenges'],
                ['route' => 'admin.notifications.index', 'match' => 'admin.notifications.*', 'icon' => 'notifications', 'label' => 'Notifications'],
            ],
        ],
        [
            'label' => 'System',
            'items' => [
                ['route' => 'admin.settings', 'match' => 'admin.settings*', 'icon' => 'settings', 'label' => 'Settings'],
            ],
        ],
    ];
@endphp

<aside class="w-60 bg-[#0a0a0a] border-r border-gray-800/60 flex flex-col min-h-screen sticky top-0 shrink-0">
    <div class="px-4 py-5 border-b border-gray-800/60">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shrink-0">
                <span class="material-icons-outlined text-white text-[18px]">auto_stories</span>
            </div>
            <div class="min-w-0">
                <h1 class="text-white font-semibold text-sm truncate">{{ $siteName }}</h1>
                <p class="text-[11px] text-gray-500">Admin Console</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-5 overflow-y-auto">
        @foreach($sections as $section)
            <div>
                <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-600">{{ $section['label'] }}</p>
                <div class="space-y-0.5">
                    @foreach($section['items'] as $item)
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium border-l-2 transition-colors {{ request()->routeIs($item['match']) ? 'bg-blue-500/10 text-blue-300 border-blue-400' : 'text-gray-400 hover:text-gray-100 hover:bg-white/5 border-transparent' }}">
                            <span class="material-icons-outlined text-[18px]">{{ $item['icon'] }}</span>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="px-3 py-4 border-t border-gray-800/60 space-y-0.5">
        <a href="{{ route('admin.profile.edit') }}"
           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium border-l-2 transition-colors {{ request()->routeIs('admin.profile.*') ? 'bg-blue-500/10 text-blue-300 border-blue-400' : 'text-gray-400 hover:text-gray-100 hover:bg-white/5 border-transparent' }}">
            <span class="material-icons-outlined text-[18px]">person</span>
            <span>Profile</span>
        </a>
        <a href="{{ route('home') }}" target="_blank"
           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium border-l-2 text-gray-400 hover:text-gray-100 hover:bg-white/5 border-transparent transition-colors">
            <span class="material-icons-outlined text-[18px]">open_in_new</span>
            <span>View Site</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-gray-400 hover:text-red-300 hover:bg-red-500/10 border-l-2 border-transparent transition-colors text-[13px] font-medium">
                <span class="material-icons-outlined text-[18px]">logout</span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

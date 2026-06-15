<aside class="w-56 bg-[#0a0a0a] border-r border-gray-800/50 flex flex-col min-h-screen sticky top-0">
    <!-- Site Name -->
    <div class="p-4 border-b border-gray-800/50">
        @php
            $siteName = \App\Models\Setting::get('site_name', 'BlinkStudy');
        @endphp
        <div class="text-center">
            <h1 class="text-white font-bold text-lg truncate">{{ $siteName }}</h1>
            <p class="text-xs text-gray-500">Admin Panel</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-2 space-y-0.5 overflow-y-auto">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.dashboard') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">dashboard</span>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('admin.pricing.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.pricing.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">payments</span>
            <span>Pricing</span>
        </a>
        <a href="{{ route('admin.settings') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.settings') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">settings</span>
            <span>Settings</span>
        </a>

        <!-- User Login Methods -->
        <a href="{{ route('admin.auth-settings.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.auth-settings.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">login</span>
            <span>User Login Methods</span>
        </a>

        <!-- SEO Settings -->
        <a href="{{ route('admin.seo-settings.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.seo-settings.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">search</span>
            <span>SEO Settings</span>
        </a>

        <a href="{{ route('admin.frontend-configs.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.frontend-configs.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">tune</span>
            <span>Frontend Configs</span>
        </a>
        <a href="{{ route('admin.mobile-app-config.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.mobile-app-config.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">phone_android</span>
            <span>Mobile App</span>
        </a>
        <a href="{{ route('admin.homepage-settings.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.homepage-settings.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">home</span>
            <span>Homepage Settings</span>
        </a>
        <a href="{{ route('admin.ai-settings') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.ai-settings') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">psychology</span>
            <span>AI Models</span>
        </a>
        <!-- AI Management Section -->
        <div class="text-[9px] uppercase text-gray-600 px-3 py-2 mt-3 font-semibold">AI Management</div>

        <a href="{{ route('admin.ai-config.prompts') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.ai-config.prompts') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">psychology</span>
            <span>System Prompts</span>
        </a>

        <a href="{{ route('admin.ai-config.usage') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.ai-config.usage') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">analytics</span>
            <span>AI Usage & Cost</span>
        </a>

        <a href="{{ route('admin.feature-models.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.feature-models.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">model_training</span>
            <span>Feature-Specific Models</span>
        </a>

        <a href="{{ route('admin.quiz-generator.settings') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.quiz-generator.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">quiz</span>
            <span>Quiz Generator</span>
        </a>

        <a href="{{ route('admin.daily-challenges.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.daily-challenges.*') ? 'bg-yellow-500/10 text-yellow-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">emoji_events</span>
            <span>Daily Challenges</span>
        </a>

        <!-- Exam Management Section -->
        <div class="text-[9px] uppercase text-gray-600 px-3 py-2 mt-3 font-semibold">Exam Management</div>

        <a href="{{ route('admin.exams.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.exams.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">school</span>
            <span>Exams</span>
        </a>

        <a href="{{ route('admin.storage-settings.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.storage-settings.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">storage</span>
            <span>Storage</span>
        </a>
        <a href="{{ route('admin.payment-gateways') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.payment-gateways') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">credit_card</span>
            <span>Gateways</span>
        </a>
        <a href="{{ route('admin.users') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.users') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">people</span>
            <span>Users</span>
        </a>
        <a href="{{ route('admin.notifications.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.notifications.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">notifications</span>
            <span>Notifications</span>
        </a>
        <a href="{{ route('admin.contact-messages.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.contact-messages.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">contact_mail</span>
            <span>Contact Messages</span>
            @php $unreadContactCount = \App\Models\ContactMessage::where('status', 'new')->count(); @endphp
            @if($unreadContactCount > 0)
            <span class="ml-auto px-1.5 py-0.5 bg-red-500 text-white text-[9px] font-bold rounded-full min-w-[18px] text-center">{{ $unreadContactCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.deletion-requests.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.deletion-requests.*') ? 'bg-red-500/10 text-red-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">delete_forever</span>
            <span>Deletion Requests</span>
            @php $pendingDeletions = \App\Models\AccountDeletionRequest::where('status', 'pending')->count(); @endphp
            @if($pendingDeletions > 0)
            <span class="ml-auto px-1.5 py-0.5 bg-red-500 text-white text-[9px] font-bold rounded-full min-w-[18px] text-center">{{ $pendingDeletions }}</span>
            @endif
        </a>
        <a href="{{ route('admin.pages.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.pages.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">article</span>
            <span>Pages</span>
        </a>
        <a href="{{ route('admin.testimonials.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.testimonials.*') ? 'bg-blue-500/10 text-blue-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">star</span>
            <span>Testimonials</span>
        </a>
    </nav>

    <!-- Bottom Actions -->
    <div class="p-2 border-t border-gray-800/50 space-y-0.5">
        <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.profile.*') ? 'bg-indigo-500/10 text-indigo-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }} transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">person</span>
            <span>Profile</span>
        </a>
        <a href="{{ route('chat') }}" class="flex items-center gap-2 px-3 py-2 rounded text-gray-400 hover:text-gray-200 hover:bg-gray-800/50 transition-all text-xs font-medium">
            <span class="material-icons-outlined text-sm">arrow_back</span>
            <span>Exit</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 rounded text-gray-400 hover:text-red-400 hover:bg-red-500/10 transition-all text-xs font-medium">
                <span class="material-icons-outlined text-sm">logout</span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

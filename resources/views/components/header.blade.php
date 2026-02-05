{{-- Shared Header — used by all pages --}}
@php
    $siteLogo = \App\Models\HomepageSetting::getValue('site_logo');
    $siteName = \App\Models\HomepageSetting::getValue('site_name', 'BlinkStudy');
    $logoIcon = \App\Models\HomepageSetting::getValue('logo_icon', 'school');
@endphp
<header class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 px-4 pt-3">
    <div class="max-w-7xl mx-auto dark:bg-[#05080a]/80 bg-white/90 backdrop-blur-xl border dark:border-white/10 border-gray-200/60 rounded-2xl shadow-lg shadow-black/5">
        <div class="px-6 h-14 flex items-center justify-between">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                @if($siteLogo)
                    <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-9 w-auto object-contain">
                @else
                    <div class="relative flex items-center justify-center w-9 h-9 rounded-lg dark:bg-[#1a1f2e] bg-primary/5 border dark:border-white/10 border-primary/10 group-hover:border-primary/50 transition-colors">
                        <span class="material-symbols-outlined text-primary text-xl group-hover:animate-pulse">{{ $logoIcon }}</span>
                    </div>
                @endif
                <span class="text-base font-bold tracking-tight dark:text-white text-slate-800 group-hover:text-primary transition-colors">{{ $siteName }}</span>
            </a>

            {{-- Nav --}}
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a class="dark:text-gray-400 text-gray-500 hover:text-primary transition-colors" href="{{ auth()->check() ? route('dashboard') : route('home') }}">Home</a>
                <a class="dark:text-gray-400 text-gray-500 hover:text-primary transition-colors" href="{{ route('plans') }}">Plans</a>
                <a class="dark:text-gray-400 text-gray-500 hover:text-primary transition-colors" href="{{ route('support') }}">Support</a>
            </nav>

            {{-- Actions --}}
            <div class="flex items-center gap-3">
                @if(Request::routeIs('home'))
                <button onclick="document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light')"
                        class="w-9 h-9 rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined text-lg text-secondary">light_mode</span>
                </button>
                @endif
                @if(auth()->check())
                    <a href="{{ route('dashboard') }}" class="hidden md:flex items-center justify-center px-5 h-9 rounded-lg bg-primary text-white text-xs font-bold hover:bg-primary/90 transition-all uppercase tracking-wider">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden md:flex items-center justify-center px-5 h-9 rounded-lg bg-primary text-white text-xs font-bold hover:bg-primary/90 transition-all uppercase tracking-wider">
                        Start Learning
                    </a>
                @endif
                {{-- Mobile menu --}}
                <button id="mobile-menu-btn" class="md:hidden dark:text-white text-gray-700" onclick="document.getElementById('mobile-nav').classList.toggle('hidden')">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>
        </div>

        {{-- Mobile Nav --}}
        <div id="mobile-nav" class="hidden md:hidden border-t dark:border-white/5 border-gray-200/60 dark:bg-[#05080a] bg-white px-6 py-4 space-y-2 rounded-b-2xl">
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="block py-2 text-sm font-medium dark:text-gray-300 text-gray-600 hover:text-primary transition-colors">Home</a>
            <a href="{{ route('plans') }}" class="block py-2 text-sm font-medium dark:text-gray-300 text-gray-600 hover:text-primary transition-colors">Plans</a>
            <a href="{{ route('support') }}" class="block py-2 text-sm font-medium dark:text-gray-300 text-gray-600 hover:text-primary transition-colors">Support</a>
            @if(auth()->check())
                <a href="{{ route('dashboard') }}" class="block py-2.5 mt-2 text-center text-sm font-bold bg-primary text-white rounded-lg">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="block py-2.5 mt-2 text-center text-sm font-bold bg-primary text-white rounded-lg">Start Learning</a>
            @endif
        </div>
    </div>
</header>
{{-- Spacer for fixed header --}}
<div class="h-16"></div>

{{-- Shared Header — premium light glass nav --}}
@php
    $siteLogo = \App\Models\HomepageSetting::getValue('site_logo');
    $siteName = \App\Models\HomepageSetting::getValue('site_name', 'BlinkStudy');
    $logoIcon = \App\Models\HomepageSetting::getValue('logo_icon', 'auto_stories');
    $isHome = Request::routeIs('home');
@endphp
<header class="fixed top-0 left-0 right-0 z-50 px-4 pt-4">
    <div class="max-w-6xl mx-auto bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-2xl shadow-card">
        <div class="px-5 h-14 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                @if($siteLogo)
                    <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-8 w-auto object-contain">
                @else
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand to-secondary flex items-center justify-center shadow-blinkstudy group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-white text-lg">{{ $logoIcon }}</span>
                    </div>
                @endif
                <span class="text-[15px] font-extrabold tracking-tight text-slate-900">{{ $siteName }}</span>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-500">
                <a class="hover:text-brand transition-colors {{ $isHome ? 'text-brand' : '' }}" href="{{ route('home') }}">Home</a>
                <a class="hover:text-brand transition-colors" href="{{ route('home') }}#features">Features</a>
                <a class="hover:text-brand transition-colors" href="{{ route('plans') }}">Plans</a>
                <a class="hover:text-brand transition-colors" href="{{ route('support') }}">Support</a>
            </nav>

            <div class="flex items-center gap-2.5">
                @if(auth()->check())
                    <a href="{{ \App\Support\ChatSubdomainUrl::appUrl() }}" class="hidden sm:inline-flex items-center justify-center px-5 h-9 rounded-xl bg-gradient-to-r from-brand to-secondary text-white text-xs font-bold shadow-blinkstudy hover:shadow-blinkstudy-lg hover:-translate-y-0.5 transition-all">
                        Open App
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center justify-center px-5 h-9 rounded-xl bg-gradient-to-r from-brand to-secondary text-white text-xs font-bold shadow-blinkstudy hover:shadow-blinkstudy-lg hover:-translate-y-0.5 transition-all">
                        Get Started
                    </a>
                @endif
                <button id="mobile-menu-btn" class="md:hidden w-9 h-9 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-700" onclick="document.getElementById('mobile-nav').classList.toggle('hidden')">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>
        </div>

        <div id="mobile-nav" class="hidden md:hidden border-t border-slate-100 px-5 py-4 space-y-1 rounded-b-2xl bg-white">
            <a href="{{ route('home') }}" class="block py-2.5 text-sm font-semibold text-slate-600 hover:text-brand">Home</a>
            <a href="{{ route('home') }}#features" class="block py-2.5 text-sm font-semibold text-slate-600 hover:text-brand">Features</a>
            <a href="{{ route('plans') }}" class="block py-2.5 text-sm font-semibold text-slate-600 hover:text-brand">Plans</a>
            <a href="{{ route('support') }}" class="block py-2.5 text-sm font-semibold text-slate-600 hover:text-brand">Support</a>
            @if(auth()->check())
                <a href="{{ \App\Support\ChatSubdomainUrl::appUrl() }}" class="block py-3 mt-2 text-center text-sm font-bold bg-gradient-to-r from-brand to-secondary text-white rounded-xl">Open App</a>
            @else
                <a href="{{ route('login') }}" class="block py-3 mt-2 text-center text-sm font-bold bg-gradient-to-r from-brand to-secondary text-white rounded-xl">Get Started</a>
            @endif
        </div>
    </div>
</header>
<div class="h-[4.5rem]"></div>

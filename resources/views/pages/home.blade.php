@extends('layouts.app')

@section('title', 'Dashboard - ' . config('app.name', 'BlinkStudy'))
@section('description', 'Your AI-powered study dashboard. Ask doubts, take quizzes, and learn with AI.')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
    * { font-family: 'Inter', -apple-system, sans-serif; }
    h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; }

    .glass-card {
        background: rgba(255,255,255,0.8);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(0,0,0,0.05);
    }
    .dark .glass-card {
        background: rgba(30,30,40,0.8);
        border: 1px solid rgba(255,255,255,0.08);
    }

    .action-card {
        transition: all 0.25s ease;
    }
    .action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -8px rgba(13,148,136,0.2);
    }
    .action-card:active {
        transform: scale(0.98);
    }

    .gradient-text {
        background: linear-gradient(135deg, #0d9488 0%, #0891b2 50%, #6366f1 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .pulse-dot {
        animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .subject-btn:hover .subject-icon {
        transform: scale(1.1);
    }
</style>
@endpush

@php
    $user = auth()->user();
    $hour = now()->format('H');
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
    $userName = $user->name ?? 'Student';
    $firstName = explode(' ', $userName)[0];
    $planName = $user->userPlan ? $user->userPlan->name : 'Free';
@endphp

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-teal-50/30 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800">
    <main class="max-w-6xl mx-auto px-4 py-6 sm:py-8">

        {{-- Greeting Header --}}
        <div class="mb-6 sm:mb-8">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-2 h-2 rounded-full bg-green-500 pulse-dot"></div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $greeting }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                Hi, <span class="gradient-text">{{ $firstName }}</span>!
            </h1>
        </div>

        {{-- Main CTA Card --}}
        <div class="glass-card rounded-2xl p-5 sm:p-6 mb-6 sm:mb-8 border-l-4 border-l-primary">
            <div class="flex items-start sm:items-center justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="material-symbols-outlined text-primary text-xl">psychology</span>
                        <span class="text-sm font-semibold text-primary">AI Ready</span>
                    </div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white mb-1">Ask Any Doubt</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Type or upload image for instant answers</p>
                </div>
                <a href="{{ route('chat') }}" class="shrink-0 bg-primary hover:bg-primary/90 text-white px-5 py-3 rounded-xl font-semibold text-sm flex items-center gap-2 transition-colors shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-lg">chat</span>
                    <span class="hidden sm:inline">Start Chat</span>
                </a>
            </div>
        </div>

        {{-- Quick Actions Grid --}}
        <div class="mb-6 sm:mb-8">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <a href="{{ route('chat') }}" class="action-card glass-card rounded-xl p-4 text-center">
                    <div class="w-11 h-11 mx-auto mb-3 rounded-xl bg-gradient-to-br from-primary to-teal-400 flex items-center justify-center shadow-lg shadow-primary/20">
                        <span class="material-symbols-outlined text-white text-xl">edit_note</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-800 dark:text-white">Ask Doubt</span>
                </a>
                <a href="{{ route('chat') }}" class="action-card glass-card rounded-xl p-4 text-center">
                    <div class="w-11 h-11 mx-auto mb-3 rounded-xl bg-gradient-to-br from-amber-500 to-orange-400 flex items-center justify-center shadow-lg shadow-amber-500/20">
                        <span class="material-symbols-outlined text-white text-xl">photo_camera</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-800 dark:text-white">Scan Image</span>
                </a>
                <a href="{{ route('chat') }}" class="action-card glass-card rounded-xl p-4 text-center">
                    <div class="w-11 h-11 mx-auto mb-3 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center shadow-lg shadow-purple-500/20">
                        <span class="material-symbols-outlined text-white text-xl">quiz</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-800 dark:text-white">AI Quiz</span>
                </a>
                <a href="{{ route('chat') }}" class="action-card glass-card rounded-xl p-4 text-center">
                    <div class="w-11 h-11 mx-auto mb-3 rounded-xl bg-gradient-to-br from-pink-500 to-rose-400 flex items-center justify-center shadow-lg shadow-pink-500/20">
                        <span class="material-symbols-outlined text-white text-xl">videocam</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-800 dark:text-white">Video</span>
                </a>
            </div>
        </div>

        {{-- Subjects Section --}}
        <div class="mb-6 sm:mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Subjects</h3>
                <a href="{{ route('chat') }}" class="text-xs font-medium text-primary hover:underline">View All</a>
            </div>
            <div class="grid grid-cols-4 sm:grid-cols-8 gap-2 sm:gap-3">
                @php
                    $subjects = [
                        ['name' => 'Math', 'icon' => 'calculate', 'color' => 'from-teal-500 to-cyan-500'],
                        ['name' => 'Science', 'icon' => 'science', 'color' => 'from-green-500 to-emerald-500'],
                        ['name' => 'English', 'icon' => 'menu_book', 'color' => 'from-blue-500 to-indigo-500'],
                        ['name' => 'Hindi', 'icon' => 'translate', 'color' => 'from-amber-500 to-yellow-500'],
                        ['name' => 'History', 'icon' => 'account_balance', 'color' => 'from-purple-500 to-violet-500'],
                        ['name' => 'Geo', 'icon' => 'public', 'color' => 'from-sky-500 to-blue-500'],
                        ['name' => 'CS', 'icon' => 'terminal', 'color' => 'from-slate-600 to-gray-600'],
                        ['name' => 'GK', 'icon' => 'lightbulb', 'color' => 'from-pink-500 to-rose-500'],
                    ];
                @endphp
                @foreach($subjects as $subject)
                <a href="{{ route('chat') }}" class="subject-btn flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition-colors">
                    <div class="subject-icon w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-gradient-to-br {{ $subject['color'] }} flex items-center justify-center shadow-md transition-transform">
                        <span class="material-symbols-outlined text-white text-lg sm:text-xl">{{ $subject['icon'] }}</span>
                    </div>
                    <span class="text-[10px] sm:text-xs font-medium text-gray-600 dark:text-gray-400">{{ $subject['name'] }}</span>
                </a>
                @endforeach
            </div>
        </div>

        {{-- Features & Plan Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-6">
            {{-- Features --}}
            <div class="lg:col-span-3 glass-card rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">What You Can Do</h3>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/5">
                        <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary text-lg">auto_fix_high</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white">Instant Doubt Solving</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Get step-by-step answers in seconds</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/5">
                        <div class="w-9 h-9 rounded-lg bg-amber-500/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-amber-500 text-lg">image</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white">Image Analysis</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Upload textbook photos</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/5">
                        <div class="w-9 h-9 rounded-lg bg-purple-500/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-purple-500 text-lg">school</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white">All Boards Supported</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">CBSE, ICSE, State Boards</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Plan Card --}}
            <div class="lg:col-span-2 glass-card rounded-2xl p-5 bg-gradient-to-br from-primary/5 to-purple-500/5 border border-primary/20">
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary">diamond</span>
                    <span class="text-sm font-bold text-primary">{{ $planName }} Plan</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Upgrade for unlimited AI access and premium features.</p>
                <a href="{{ route('plans') }}" class="block w-full bg-gradient-to-r from-primary to-teal-500 text-white text-center py-3 rounded-xl font-semibold text-sm hover:opacity-90 transition-opacity shadow-lg shadow-primary/20">
                    View Plans
                </a>
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="grid grid-cols-4 gap-2 sm:gap-3">
            <a href="{{ route('plans') }}" class="glass-card rounded-xl p-3 flex flex-col items-center gap-1.5 hover:border-primary/30 transition-colors">
                <span class="material-symbols-outlined text-primary text-xl">diamond</span>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Plans</span>
            </a>
            <a href="{{ url('/user/settings') }}" class="glass-card rounded-xl p-3 flex flex-col items-center gap-1.5 hover:border-primary/30 transition-colors">
                <span class="material-symbols-outlined text-gray-500 text-xl">settings</span>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Settings</span>
            </a>
            <a href="{{ url('/about') }}" class="glass-card rounded-xl p-3 flex flex-col items-center gap-1.5 hover:border-primary/30 transition-colors">
                <span class="material-symbols-outlined text-gray-500 text-xl">info</span>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">About</span>
            </a>
            <a href="{{ url('/support') }}" class="glass-card rounded-xl p-3 flex flex-col items-center gap-1.5 hover:border-primary/30 transition-colors">
                <span class="material-symbols-outlined text-gray-500 text-xl">support</span>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Help</span>
            </a>
        </div>

    </main>
</div>
@endsection

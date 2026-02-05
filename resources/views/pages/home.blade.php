@extends('layouts.app')

@section('title', 'Dashboard - ' . config('app.name', 'BlinkStudy'))
@section('description', 'Your AI-powered study dashboard. Ask doubts, take quizzes, and learn with AI.')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>
    .card-shadow { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
    .dark .card-shadow { box-shadow: none; }
    h1, h2, h3, h4 { font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif; }
</style>
@endpush

@php
    $user = auth()->user();
    $hour = now()->format('H');
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
    $userName = $user->name ?? 'Student';
    $firstName = explode(' ', $userName)[0];

    // Plan info
    $planName = 'Free';
    if ($user->userPlan) {
        $planName = $user->userPlan->name;
    }
@endphp

@section('content')
<div class="max-w-[1200px] mx-auto w-full px-4 sm:px-6 py-6 sm:py-8">

    {{-- Page Heading --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8 sm:mb-10">
        <div class="flex flex-col gap-2">
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black dark:text-white text-gray-900 leading-tight tracking-tight">
                {{ $greeting }}, {{ $firstName }}.
            </h1>
            <div class="flex flex-wrap items-center gap-3">
                <p class="dark:text-gray-400 text-gray-600 text-sm sm:text-base font-medium">
                    Plan: <span class="text-primary font-bold">{{ $planName }}</span>
                </p>
                <div class="flex items-center gap-1 bg-amber-500/10 text-amber-500 px-2.5 py-0.5 rounded-full text-xs sm:text-sm font-bold border border-amber-500/20">
                    <span class="material-symbols-outlined" style="font-size: 16px;">bolt</span>
                    <span>AI Powered</span>
                </div>
            </div>
        </div>
        <a href="{{ route('chat') }}" class="flex w-full sm:w-auto min-w-[160px] cursor-pointer items-center justify-center gap-2 rounded-xl h-11 sm:h-12 px-6 bg-primary text-white text-sm sm:text-base font-bold shadow-lg shadow-primary/25 hover:opacity-90 transition-all active:scale-95">
            <span class="material-symbols-outlined" style="font-size: 20px;">chat</span>
            <span>Start Chat</span>
        </a>
    </div>

    {{-- Quick Action Bar --}}
    <div class="mb-10 sm:mb-12">
        <h3 class="dark:text-white text-gray-900 text-base sm:text-lg font-bold mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-size: 20px;">auto_awesome</span>
            Quick AI Actions
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
            <a href="{{ route('chat') }}" class="flex flex-1 gap-4 rounded-xl border dark:border-white/10 border-gray-200 dark:bg-white/[0.02] bg-white p-4 sm:p-5 hover:border-primary/50 transition-colors cursor-pointer group">
                <div class="size-10 sm:size-12 rounded-lg dark:bg-primary/10 bg-teal-50 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors shrink-0">
                    <span class="material-symbols-outlined text-[24px] sm:text-[28px]">psychology_alt</span>
                </div>
                <div class="flex flex-col gap-0.5">
                    <h2 class="dark:text-white text-gray-900 text-sm sm:text-base font-bold">Ask a Doubt</h2>
                    <p class="dark:text-gray-400 text-gray-500 text-xs sm:text-sm">Get instant AI explanations</p>
                </div>
            </a>
            <a href="{{ route('chat') }}" class="flex flex-1 gap-4 rounded-xl border dark:border-white/10 border-gray-200 dark:bg-white/[0.02] bg-white p-4 sm:p-5 hover:border-primary/50 transition-colors cursor-pointer group">
                <div class="size-10 sm:size-12 rounded-lg dark:bg-amber-500/10 bg-amber-50 flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-colors shrink-0">
                    <span class="material-symbols-outlined text-[24px] sm:text-[28px]">add_a_photo</span>
                </div>
                <div class="flex flex-col gap-0.5">
                    <h2 class="dark:text-white text-gray-900 text-sm sm:text-base font-bold">Upload Image</h2>
                    <p class="dark:text-gray-400 text-gray-500 text-xs sm:text-sm">Scan questions from textbooks</p>
                </div>
            </a>
            <a href="{{ route('chat') }}" class="flex flex-1 gap-4 rounded-xl border dark:border-white/10 border-gray-200 dark:bg-white/[0.02] bg-white p-4 sm:p-5 hover:border-primary/50 transition-colors cursor-pointer group">
                <div class="size-10 sm:size-12 rounded-lg dark:bg-purple-500/10 bg-purple-50 flex items-center justify-center text-purple-500 group-hover:bg-purple-500 group-hover:text-white transition-colors shrink-0">
                    <span class="material-symbols-outlined text-[24px] sm:text-[28px]">quiz</span>
                </div>
                <div class="flex flex-col gap-0.5">
                    <h2 class="dark:text-white text-gray-900 text-sm sm:text-base font-bold">AI Quiz</h2>
                    <p class="dark:text-gray-400 text-gray-500 text-xs sm:text-sm">Test your knowledge instantly</p>
                </div>
            </a>
        </div>
    </div>

    {{-- Study Subjects --}}
    <div class="mb-10 sm:mb-12">
        <div class="flex items-center justify-between mb-5 sm:mb-6">
            <h2 class="dark:text-white text-gray-900 text-lg sm:text-2xl font-bold">Study Subjects</h2>
            <a href="{{ route('chat') }}" class="text-primary font-bold text-xs sm:text-sm hover:underline">Ask About Any Topic</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            @php
                $subjects = [
                    ['name' => 'Mathematics', 'icon' => 'calculate', 'color' => 'primary', 'desc' => 'Algebra, Geometry, Calculus'],
                    ['name' => 'Science', 'icon' => 'science', 'color' => 'green-500', 'desc' => 'Physics, Chemistry, Bio'],
                    ['name' => 'English', 'icon' => 'menu_book', 'color' => 'blue-500', 'desc' => 'Grammar, Literature'],
                    ['name' => 'Hindi', 'icon' => 'translate', 'color' => 'amber-500', 'desc' => 'Vyakaran, Sahitya'],
                    ['name' => 'Social Studies', 'icon' => 'public', 'color' => 'purple-500', 'desc' => 'History, Geography, Civics'],
                    ['name' => 'Computer Science', 'icon' => 'terminal', 'color' => 'cyan-500', 'desc' => 'Programming, IT'],
                    ['name' => 'GK', 'icon' => 'lightbulb', 'color' => 'pink-500', 'desc' => 'General Knowledge'],
                    ['name' => 'Competitive', 'icon' => 'emoji_events', 'color' => 'orange-500', 'desc' => 'JEE, NEET, UPSC'],
                ];
            @endphp

            @foreach($subjects as $subject)
            <a href="{{ route('chat') }}" class="dark:bg-white/[0.02] bg-white rounded-xl border dark:border-white/10 border-gray-200 p-4 sm:p-5 flex flex-col items-center gap-3 hover:border-{{ $subject['color'] }}/50 transition-all duration-200 group card-shadow">
                <div class="size-10 sm:size-12 rounded-lg dark:bg-{{ $subject['color'] }}/10 bg-{{ $subject['color'] }}/5 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-{{ $subject['color'] }} text-xl sm:text-2xl">{{ $subject['icon'] }}</span>
                </div>
                <div class="text-center">
                    <h4 class="text-xs sm:text-sm font-bold dark:text-white text-gray-900">{{ $subject['name'] }}</h4>
                    <p class="text-[10px] sm:text-xs dark:text-gray-500 text-gray-400 mt-0.5 hidden sm:block">{{ $subject['desc'] }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Features & AI Info --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        {{-- Main Feature Card --}}
        <div class="lg:col-span-2 dark:bg-white/[0.02] bg-white rounded-xl p-5 sm:p-6 card-shadow border dark:border-white/10 border-gray-200">
            <h3 class="text-base sm:text-lg font-bold dark:text-white text-gray-900 mb-4 sm:mb-6">What BlinkStudy AI Can Do</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div class="flex items-start gap-3 p-3 rounded-lg dark:bg-white/[0.02] bg-gray-50">
                    <span class="material-symbols-outlined text-primary shrink-0" style="font-size: 20px;">auto_awesome</span>
                    <div>
                        <h4 class="text-sm font-bold dark:text-white text-gray-900">Instant Doubt Solving</h4>
                        <p class="text-xs dark:text-gray-400 text-gray-500 mt-0.5">Type or upload any question, get step-by-step answers</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg dark:bg-white/[0.02] bg-gray-50">
                    <span class="material-symbols-outlined text-amber-500 shrink-0" style="font-size: 20px;">image</span>
                    <div>
                        <h4 class="text-sm font-bold dark:text-white text-gray-900">Image Analysis</h4>
                        <p class="text-xs dark:text-gray-400 text-gray-500 mt-0.5">Snap a photo of your textbook and get explanations</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg dark:bg-white/[0.02] bg-gray-50">
                    <span class="material-symbols-outlined text-purple-500 shrink-0" style="font-size: 20px;">quiz</span>
                    <div>
                        <h4 class="text-sm font-bold dark:text-white text-gray-900">AI Quizzes</h4>
                        <p class="text-xs dark:text-gray-400 text-gray-500 mt-0.5">Practice with auto-generated MCQ questions</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg dark:bg-white/[0.02] bg-gray-50">
                    <span class="material-symbols-outlined text-green-500 shrink-0" style="font-size: 20px;">school</span>
                    <div>
                        <h4 class="text-sm font-bold dark:text-white text-gray-900">All Subjects</h4>
                        <p class="text-xs dark:text-gray-400 text-gray-500 mt-0.5">CBSE, ICSE, State Boards & Competitive exams</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- CTA Card --}}
        <div class="bg-gradient-to-br from-primary to-teal-600 rounded-xl p-5 sm:p-6 text-white flex flex-col justify-between overflow-hidden relative card-shadow">
            <div class="relative z-10">
                <div class="flex items-center gap-1.5 mb-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">bolt</span>
                    <h3 class="font-bold text-sm opacity-80">AI Ready</h3>
                </div>
                <p class="text-xl sm:text-2xl font-black">Start Learning Now</p>
                <p class="text-sm mt-3 opacity-90 leading-relaxed">Ask any doubt in Hindi or English. Our AI gives instant, detailed answers with explanations.</p>
            </div>
            <div class="relative z-10 mt-5 sm:mt-6">
                <a href="{{ route('chat') }}" class="block w-full bg-white text-primary px-4 py-2.5 rounded-lg font-bold text-sm text-center hover:bg-gray-100 transition-colors">
                    Open AI Chat
                </a>
            </div>
            <div class="absolute -right-4 -bottom-4 opacity-20">
                <span class="material-symbols-outlined" style="font-size: 100px;">psychology</span>
            </div>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="mt-8 sm:mt-10 grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="{{ route('plans') }}" class="flex items-center gap-2 px-4 py-3 rounded-xl dark:bg-white/[0.02] bg-white border dark:border-white/10 border-gray-200 hover:border-primary/40 transition-colors group">
            <span class="material-symbols-outlined text-primary" style="font-size: 20px;">diamond</span>
            <span class="text-xs sm:text-sm font-semibold dark:text-gray-300 text-gray-700 group-hover:text-primary transition-colors">Plans</span>
        </a>
        <a href="{{ url('/user/settings') }}" class="flex items-center gap-2 px-4 py-3 rounded-xl dark:bg-white/[0.02] bg-white border dark:border-white/10 border-gray-200 hover:border-primary/40 transition-colors group">
            <span class="material-symbols-outlined text-gray-400" style="font-size: 20px;">settings</span>
            <span class="text-xs sm:text-sm font-semibold dark:text-gray-300 text-gray-700 group-hover:text-primary transition-colors">Settings</span>
        </a>
        <a href="{{ url('/about') }}" class="flex items-center gap-2 px-4 py-3 rounded-xl dark:bg-white/[0.02] bg-white border dark:border-white/10 border-gray-200 hover:border-primary/40 transition-colors group">
            <span class="material-symbols-outlined text-gray-400" style="font-size: 20px;">info</span>
            <span class="text-xs sm:text-sm font-semibold dark:text-gray-300 text-gray-700 group-hover:text-primary transition-colors">About</span>
        </a>
        <a href="{{ url('/support') }}" class="flex items-center gap-2 px-4 py-3 rounded-xl dark:bg-white/[0.02] bg-white border dark:border-white/10 border-gray-200 hover:border-primary/40 transition-colors group">
            <span class="material-symbols-outlined text-gray-400" style="font-size: 20px;">support_agent</span>
            <span class="text-xs sm:text-sm font-semibold dark:text-gray-300 text-gray-700 group-hover:text-primary transition-colors">Support</span>
        </a>
    </div>
</div>
@endsection

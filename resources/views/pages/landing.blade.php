@extends('layouts.app')

@section('title', config('app.name', 'BlinkStudy') . ' | AI Study Companion')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet"/>
<style>
    .glow-text { text-shadow: 0 0 20px rgba(13, 148, 136, 0.3); }
    .glow-border { box-shadow: 0 0 15px rgba(13, 148, 136, 0.15); }
    .glow-hover:hover { box-shadow: 0 0 25px rgba(13, 148, 136, 0.4); }
    .bg-gradient-blur {
        position: absolute; filter: blur(80px); z-index: 0; opacity: 0.3;
    }
    .glass-panel {
        background: rgba(13, 17, 23, 0.7);
        backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .neon-border { position: relative; }
    .neon-border::before {
        content: ""; position: absolute; inset: 0; border-radius: inherit; padding: 1px;
        background: linear-gradient(135deg, rgba(13,148,136,0.5), rgba(245,158,11,0.3));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none;
    }
    .grid-bg {
        background-size: 40px 40px;
        background-image:
            linear-gradient(to right, rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255,255,255,0.03) 1px, transparent 1px);
    }
    .scanline {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(to bottom, transparent 50%, rgba(13,148,136,0.02) 50%);
        background-size: 100% 4px; pointer-events: none; z-index: 10;
    }
    /* Light mode overrides */
    html:not(.dark) .glass-panel {
        background: rgba(255,255,255,0.85);
        border: 1px solid rgba(0,0,0,0.06);
    }
    html:not(.dark) .grid-bg {
        background-image:
            linear-gradient(to right, rgba(0,0,0,0.03) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(0,0,0,0.03) 1px, transparent 1px);
    }

    /* Scroll reveal animations */
    .reveal {
        opacity: 0;
        transition: opacity 0.8s ease, transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        will-change: opacity, transform;
    }
    .reveal-left {
        transform: translateX(-60px);
    }
    .reveal-right {
        transform: translateX(60px);
    }
    .reveal-up {
        transform: translateY(40px);
    }
    .reveal.revealed {
        opacity: 1;
        transform: translateX(0) translateY(0);
    }
    /* Stagger delays for grid items */
    .reveal-delay-1 { transition-delay: 0.1s; }
    .reveal-delay-2 { transition-delay: 0.2s; }
    .reveal-delay-3 { transition-delay: 0.3s; }
    .reveal-delay-4 { transition-delay: 0.4s; }
    .reveal-delay-5 { transition-delay: 0.5s; }
    .reveal-delay-6 { transition-delay: 0.6s; }
</style>
@endpush

@php
    // Helper to get homepage setting with fallback
    $hs = function($key, $default = '') use ($allHomepageSettings) {
        return $allHomepageSettings[$key] ?? $default;
    };
@endphp

@section('content')
{{-- Background effects --}}
<div class="fixed inset-0 grid-bg pointer-events-none z-0"></div>
<div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
    <div class="bg-gradient-blur w-[300px] sm:w-[600px] h-[300px] sm:h-[600px] rounded-full bg-primary/5 -top-20 -left-20 animate-pulse"></div>
    <div class="bg-gradient-blur w-[350px] sm:w-[700px] h-[350px] sm:h-[700px] rounded-full bg-cyan-500/10 bottom-0 right-0"></div>
    <div class="bg-gradient-blur w-[200px] sm:w-[400px] h-[200px] sm:h-[400px] rounded-full bg-primary/10 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></div>
</div>

<div class="relative z-10">
    {{-- ═══════════════════════ HERO ═══════════════════════ --}}
    <section class="relative py-12 sm:py-16 lg:py-32 px-4 sm:px-6 overflow-hidden min-h-[85vh] sm:min-h-0 flex items-center">
        <div class="absolute top-1/4 left-10 w-32 h-[1px] bg-gradient-to-r from-transparent to-primary/30 hidden lg:block"></div>
        <div class="absolute top-1/4 right-10 w-32 h-[1px] bg-gradient-to-l from-transparent to-primary/30 hidden lg:block"></div>
        <div class="absolute bottom-20 left-20 w-[1px] h-32 bg-gradient-to-b from-transparent to-primary/30 hidden lg:block"></div>

        <div class="max-w-7xl mx-auto relative z-20 w-full">
            <div class="grid lg:grid-cols-2 gap-8 lg:gap-16 items-center">

                {{-- ══ LEFT SIDE: Text Content ══ --}}
                <div class="text-center sm:text-left reveal reveal-left">
                    {{-- Badge --}}
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full dark:bg-[#1a1f2e] bg-white border border-primary/20 mb-6 backdrop-blur-sm shadow-lg">
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-primary"></span>
                            </span>
                            <span class="text-xs sm:text-sm font-semibold text-primary">{{ $hs('hero_badge_left', 'Made in India') }}</span>
                        </div>
                        <span class="w-[1px] h-4 dark:bg-white/20 bg-gray-300 hidden sm:block"></span>
                        <span class="text-xs dark:text-gray-400 text-gray-500 hidden sm:block">{{ $hs('hero_badge_right', 'Proudly Built for Indian Students') }}</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl md:text-5xl lg:text-6xl font-black dark:text-white text-slate-800 leading-[1.1] tracking-tight mb-4 sm:mb-6">
                        {{ $hs('hero_title_line1', 'Your AI Study') }} <br class="hidden sm:block"/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary via-secondary to-primary glow-text sm:hidden"> </span>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary via-secondary to-primary glow-text">{{ $hs('hero_title_highlight', 'Companion') }}</span>
                    </h1>

                    <p class="text-base sm:text-lg md:text-xl dark:text-gray-400 text-gray-500 mb-6 sm:mb-8 max-w-lg leading-relaxed mx-auto sm:mx-0">
                        {{ $hs('hero_description', 'Stuck on a problem? Get instant, step-by-step explanations for Math, Science, and more. Scan, ask, learn — anytime.') }}
                    </p>

                    {{-- Search Bar - Hidden on very small screens, show CTA instead --}}
                    <div class="relative max-w-lg group hidden sm:block mx-auto sm:mx-0">
                        <div class="flex justify-between px-4 mb-2 opacity-60 text-[10px] font-mono text-primary">
                            <span>{{ $hs('hero_search_label', 'DOUBT_SOLVER') }}</span>
                            <span>{{ $hs('hero_search_model', 'MODEL: GEMINI-2.0') }}</span>
                        </div>
                        <div class="absolute -inset-[1px] bg-gradient-to-r from-primary/50 via-secondary/50 to-primary/50 rounded-xl opacity-30 blur-sm group-hover:opacity-60 transition duration-500"></div>
                        <div class="relative flex items-center dark:bg-[#0d1117]/90 bg-white border dark:border-white/10 border-gray-200 rounded-xl p-2 pl-4 shadow-2xl focus-within:border-primary/60 transition-colors backdrop-blur-md">
                            <span class="material-symbols-outlined text-primary mr-3 text-2xl">psychology_alt</span>
                            <input class="flex-1 bg-transparent border-none dark:text-white text-slate-800 dark:placeholder-gray-500 placeholder-gray-400 focus:ring-0 text-base h-12 font-mono min-w-0" placeholder="Ask any doubt..." type="text"/>
                            <div class="flex items-center gap-2 pr-1">
                                <button class="p-2 rounded-lg hover:bg-white/5 transition-colors text-primary/70" title="Upload Image">
                                    <span class="material-symbols-outlined text-xl">add_a_photo</span>
                                </button>
                                <button class="p-2 rounded-lg hover:bg-white/5 transition-colors text-primary/70" title="Voice Input">
                                    <span class="material-symbols-outlined text-xl">mic</span>
                                </button>
                                <a href="{{ route('login') }}" class="flex items-center justify-center gap-1.5 h-10 px-5 rounded-full bg-gradient-to-r from-primary to-teal-500 text-white font-semibold hover:shadow-[0_0_20px_rgba(13,148,136,0.5)] hover:scale-105 transition-all duration-300 text-sm tracking-wide">
                                    {{ $hs('hero_cta_text', 'Solve') }}
                                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-2 px-1">
                            <div class="h-1 w-full dark:bg-[#1a1f2e] bg-gray-100 border dark:border-white/5 border-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-primary/50 w-1/3 animate-pulse"></div>
                            </div>
                            <span class="text-[10px] font-mono dark:text-gray-500 text-gray-400">READY</span>
                        </div>
                    </div>

                    {{-- Mobile CTA Buttons --}}
                    <div class="sm:hidden space-y-3">
                        <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 w-full h-14 rounded-2xl bg-gradient-to-r from-primary to-teal-500 text-white font-bold text-lg shadow-xl shadow-primary/30 active:scale-[0.98] transition-all">
                            <span class="material-symbols-outlined text-2xl">rocket_launch</span>
                            Start Learning Free
                        </a>
                        <div class="flex items-center justify-center gap-4 text-sm dark:text-gray-400 text-gray-500">
                            <span class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-primary text-lg">check_circle</span>
                                Free Plan Available
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-primary text-lg">check_circle</span>
                                Instant Access
                            </span>
                        </div>
                    </div>

                    {{-- Stats Row --}}
                    <div class="flex justify-center sm:justify-start gap-6 sm:gap-8 mt-8">
                        <div class="text-center sm:text-left">
                            <div class="text-2xl sm:text-2xl font-bold dark:text-white text-slate-800">{{ $hs('stat1_value', '10K+') }}</div>
                            <div class="text-xs dark:text-gray-500 text-gray-400 font-mono">{{ $hs('stat1_label', 'Students') }}</div>
                        </div>
                        <div class="w-[1px] dark:bg-white/10 bg-gray-200"></div>
                        <div class="text-center sm:text-left">
                            <div class="text-2xl sm:text-2xl font-bold dark:text-white text-slate-800">{{ $hs('stat2_value', '50K+') }}</div>
                            <div class="text-xs dark:text-gray-500 text-gray-400 font-mono">{{ $hs('stat2_label', 'Solved') }}</div>
                        </div>
                        <div class="w-[1px] dark:bg-white/10 bg-gray-200"></div>
                        <div class="text-center sm:text-left">
                            <div class="text-2xl sm:text-2xl font-bold dark:text-white text-slate-800">{{ $hs('stat3_value', '4.9') }}</div>
                            <div class="text-xs dark:text-gray-500 text-gray-400 font-mono">{{ $hs('stat3_label', 'Rating') }}</div>
                        </div>
                    </div>
                </div>

                {{-- ══ RIGHT SIDE: Demo Preview Panel ══ --}}
                <div class="relative reveal reveal-right hidden lg:block">
                    {{-- Glow blobs behind the card --}}
                    <div class="absolute -top-10 -right-10 w-64 h-64 bg-primary/15 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -bottom-10 -left-10 w-48 h-48 bg-secondary/15 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative rounded-xl overflow-hidden glass-panel neon-border">
                        <div class="absolute inset-0 bg-gradient-to-b from-transparent dark:to-[#0d1117]/80 to-white/50"></div>
                        <div class="scanline"></div>
                        <div class="relative z-10 w-full p-5 md:p-6 flex flex-col gap-5">
                            {{-- Terminal header --}}
                            <div class="flex justify-between items-center border-b dark:border-white/10 border-gray-200 pb-3">
                                <div class="flex items-center gap-2 text-xs font-mono text-primary">
                                    <span class="material-symbols-outlined text-sm">auto_stories</span>
                                    KNOWLEDGE_ANALYSIS_MODE
                                </div>
                                <div class="flex gap-1">
                                    <div class="w-2 h-2 rounded-full bg-red-500/50"></div>
                                    <div class="w-2 h-2 rounded-full bg-yellow-500/50"></div>
                                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                </div>
                            </div>

                            {{-- Animated Code block --}}
                            <div id="hero-code-block" class="dark:bg-black/40 bg-gray-50 p-4 rounded-lg border-l-2 border-primary font-mono text-xs dark:text-gray-300 text-gray-600 leading-relaxed min-h-[120px]">
                                <div id="code-cursor" class="inline text-primary animate-pulse">_</div>
                            </div>

                            {{-- Progress bars --}}
                            <div class="flex flex-col gap-3">
                                <div class="flex justify-between text-xs font-mono dark:text-gray-400 text-gray-500">
                                    <span>CONFIDENCE</span>
                                    <span class="text-primary">99.8%</span>
                                </div>
                                <div class="h-1.5 w-full dark:bg-white/10 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary w-[99%]"></div>
                                </div>
                                <div class="flex justify-between text-xs font-mono dark:text-gray-400 text-gray-500 mt-1">
                                    <span>TOPICS</span>
                                    <span class="text-secondary">Calculus, Trigonometry</span>
                                </div>
                                <div class="h-1.5 w-full dark:bg-white/10 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-secondary w-[78%]"></div>
                                </div>
                                <div class="flex justify-between text-xs font-mono dark:text-gray-400 text-gray-500 mt-1">
                                    <span>SIMILAR_QUESTIONS</span>
                                    <span class="text-purple-500 dark:text-purple-400">12,403 Found</span>
                                </div>
                                <div class="h-1.5 w-full dark:bg-white/10 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-purple-500 w-[65%] animate-pulse"></div>
                                </div>
                            </div>

                            {{-- Insight footer --}}
                            <div class="flex items-start gap-3 pt-3 border-t dark:border-white/5 border-gray-200">
                                <div class="h-6 w-6 rounded bg-primary flex items-center justify-center text-white font-bold shrink-0">
                                    <span class="material-symbols-outlined text-xs">lightbulb</span>
                                </div>
                                <div class="text-sm dark:text-gray-300 text-gray-600 font-mono">
                                    <span class="text-primary">&gt;</span> Concept Mastery: Product Rule applied successfully.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ═══════════════════════ FEATURES ═══════════════════════ --}}
    <section id="features" class="py-12 sm:py-16 lg:py-20 px-4 sm:px-6 relative">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 sm:gap-8 mb-10 sm:mb-16 border-b dark:border-white/5 border-gray-200 pb-6 sm:pb-8">
                <div class="max-w-2xl reveal reveal-left">
                    <div class="text-primary font-mono text-[10px] sm:text-xs tracking-widest mb-2 uppercase">{{ $hs('features_badge', 'Learning Modules') }}</div>
                    <h2 class="text-2xl sm:text-3xl md:text-5xl font-bold dark:text-white text-slate-800 mb-3 sm:mb-4 tracking-tight">{{ $hs('features_title', 'Academic Capabilities') }}</h2>
                    <p class="dark:text-gray-400 text-gray-500 text-sm sm:text-base lg:text-lg">{{ $hs('features_description', 'Everything you need to ace your exams and understand concepts deeply.') }}</p>
                </div>
                <a class="text-primary hover:text-secondary flex items-center gap-2 font-mono text-xs sm:text-sm transition-colors group border border-primary/20 px-3 sm:px-4 py-2 rounded bg-primary/5 hover:bg-primary/10 reveal reveal-right w-fit" href="#subjects">
                    VIEW_SUBJECTS
                    <span class="material-symbols-outlined text-sm group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 relative">
                <div class="absolute top-1/2 left-0 w-full h-[1px] dark:bg-white/5 bg-gray-100 -z-10 hidden lg:block"></div>

                @php
                    $featureColors = [
                        $hs('feature1_color', 'primary'),
                        $hs('feature2_color', 'secondary'),
                        $hs('feature3_color', 'cyan-500'),
                        $hs('feature4_color', 'pink-500'),
                    ];
                @endphp

                @for($i = 1; $i <= 4; $i++)
                @php
                    $fIcon = $hs("feature{$i}_icon", 'star');
                    $fTitle = $hs("feature{$i}_title", "Feature {$i}");
                    $fDesc = $hs("feature{$i}_description", '');
                    $fColor = $featureColors[$i - 1];
                @endphp
                <div class="bg-white dark:bg-[#111827] p-4 sm:p-5 lg:p-6 rounded-lg dark:hover:bg-white/5 hover:shadow-xl transition-all duration-300 group hover:-translate-y-2 border dark:border-white/10 border-gray-100 relative overflow-hidden shadow-sm reveal reveal-up reveal-delay-{{ $i }}">
                    <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-{{ $fColor }}/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-lg dark:bg-{{ $fColor }}/10 bg-{{ $fColor }}/5 border dark:border-{{ $fColor }}/20 border-{{ $fColor }}/10 flex items-center justify-center mb-3 sm:mb-5 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-{{ $fColor }} text-xl sm:text-2xl">{{ $fIcon }}</span>
                    </div>
                    <h3 class="text-sm sm:text-base lg:text-lg font-bold dark:text-white text-slate-800 mb-1 sm:mb-2">{{ $fTitle }}</h3>
                    <p class="text-xs sm:text-sm dark:text-gray-400 text-gray-500 leading-relaxed line-clamp-3">{{ $fDesc }}</p>
                </div>
                @endfor
            </div>
        </div>
    </section>

    {{-- ═══════════════════════ SUBJECTS ═══════════════════════ --}}
    <section id="subjects" class="py-12 sm:py-16 lg:py-20 px-4 sm:px-6 relative border-t dark:border-white/5 border-gray-200">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row items-start md:items-end justify-between gap-4 sm:gap-6 mb-6 sm:mb-8">
                <div class="reveal reveal-left">
                    <div class="text-secondary font-mono text-[10px] sm:text-xs tracking-widest mb-2 uppercase">{{ $hs('subjects_badge', 'Subject Coverage') }}</div>
                    <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold dark:text-white text-slate-800 tracking-tight">{{ $hs('subjects_title', 'Explore Topics') }}</h2>
                </div>
                <p class="dark:text-gray-400 text-gray-500 font-mono text-xs sm:text-sm max-w-md text-left md:text-right hidden md:block reveal reveal-right">
                    {{ $hs('subjects_description', 'From STEM to Humanities, our AI models are trained for every major discipline.') }}
                </p>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 sm:gap-3 lg:gap-4">
                @for($i = 1; $i <= 6; $i++)
                @php
                    $sName = $hs("subject{$i}_name", '');
                    $sIcon = $hs("subject{$i}_icon", 'school');
                    $sColor = $hs("subject{$i}_color", 'primary');
                @endphp
                @if($sName)
                <a class="group bg-white dark:bg-[#111827] p-3 sm:p-4 lg:p-6 rounded-lg sm:rounded-xl flex flex-col items-center justify-center gap-2 sm:gap-3 lg:gap-4 dark:hover:bg-white/5 transition-all duration-300 border dark:border-white/10 border-gray-100 hover:border-{{ $sColor }}/50 relative overflow-hidden shadow-sm dark:shadow-none reveal reveal-up reveal-delay-{{ $i }}" href="{{ route('login') }}">
                    <div class="absolute inset-0 bg-gradient-to-br from-{{ $sColor }}/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 lg:w-12 lg:h-12 rounded-lg dark:bg-[#1a1f2e] bg-{{ $sColor }}/5 border dark:border-white/10 border-{{ $sColor }}/10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <span class="material-symbols-outlined text-{{ $sColor }} text-lg sm:text-xl lg:text-2xl">{{ $sIcon }}</span>
                    </div>
                    <span class="text-[9px] sm:text-[10px] lg:text-xs font-mono font-bold dark:text-gray-300 text-gray-600 group-hover:text-{{ $sColor }} tracking-wider sm:tracking-widest uppercase relative z-10 text-center leading-tight">{{ $sName }}</span>
                </a>
                @endif
                @endfor
            </div>
        </div>
    </section>

    {{-- ═══════════════════════ CTA ═══════════════════════ --}}
    <section class="py-10 sm:py-16 lg:py-24 px-4 sm:px-6 relative overflow-hidden">
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="w-full max-w-3xl h-[400px] bg-gradient-radial from-primary/10 to-transparent opacity-50 blur-3xl"></div>
            <div class="absolute inset-0 grid-bg opacity-30"></div>
        </div>

        <div class="max-w-4xl mx-auto relative z-10 text-center bg-white dark:bg-[#111827] p-5 sm:p-10 lg:p-20 rounded-2xl sm:rounded-[2rem] border dark:border-white/10 border-gray-100 shadow-[0_0_50px_rgba(0,0,0,0.1)] dark:shadow-[0_0_50px_rgba(0,0,0,0.5)] reveal reveal-up">
            <div class="absolute top-3 left-3 sm:top-6 sm:left-6 w-5 h-5 sm:w-8 sm:h-8 border-t-2 border-l-2 border-primary/30"></div>
            <div class="absolute top-3 right-3 sm:top-6 sm:right-6 w-5 h-5 sm:w-8 sm:h-8 border-t-2 border-r-2 border-primary/30"></div>
            <div class="absolute bottom-3 left-3 sm:bottom-6 sm:left-6 w-5 h-5 sm:w-8 sm:h-8 border-b-2 border-l-2 border-primary/30"></div>
            <div class="absolute bottom-3 right-3 sm:bottom-6 sm:right-6 w-5 h-5 sm:w-8 sm:h-8 border-b-2 border-r-2 border-primary/30"></div>

            <div class="inline-block px-2 sm:px-3 py-1 bg-primary/10 rounded-full border border-primary/20 text-primary text-[8px] sm:text-[10px] font-mono tracking-widest mb-3 sm:mb-6">{{ $hs('cta_badge', 'STATUS: EXAM_READY') }}</div>
            <h2 class="text-xl sm:text-4xl md:text-5xl lg:text-6xl font-black dark:text-white text-slate-800 mb-3 sm:mb-6 tracking-tight leading-tight">{{ $hs('cta_title', 'Ace Your Exams Today') }}</h2>
            <p class="text-xs sm:text-base lg:text-lg dark:text-gray-400 text-gray-500 mb-5 sm:mb-10 max-w-2xl mx-auto leading-relaxed">
                {{ $hs('cta_description', 'Join thousands of students boosting their grades with AI-powered doubt solving, quizzes, and whiteboard videos.') }}
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
                <a href="{{ route('login') }}" class="w-full sm:w-auto px-6 sm:px-8 h-11 sm:h-14 rounded-xl bg-primary text-white font-bold text-sm sm:text-base lg:text-lg hover:bg-primary/90 hover:scale-105 transition-all duration-300 shadow-[0_0_20px_rgba(13,148,136,0.3)] uppercase tracking-wide flex items-center justify-center">
                    {{ $hs('cta_button_primary', 'Start Free Trial') }}
                </a>
                <a href="#subjects" class="w-full sm:w-auto px-6 sm:px-8 h-11 sm:h-14 rounded-xl dark:bg-[#1a1f2e] bg-white border dark:border-white/10 border-gray-200 dark:text-white text-slate-700 font-medium text-sm sm:text-base lg:text-lg dark:hover:bg-white/5 hover:bg-gray-50 transition-all duration-300 flex items-center justify-center">
                    {{ $hs('cta_button_secondary', 'Browse Subjects') }}
                </a>
            </div>

            <div class="mt-5 sm:mt-10 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 sm:gap-6 dark:text-gray-500 text-gray-400 text-[10px] sm:text-sm font-mono">
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm sm:text-lg text-primary">verified</span> {{ $hs('cta_badge1', '95% Better Grades') }}
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm sm:text-lg text-primary">verified</span> {{ $hs('cta_badge2', 'Millions of Solutions') }}
                </div>
                <div class="flex items-center gap-1.5 hidden sm:flex">
                    <span class="material-symbols-outlined text-sm sm:text-lg text-primary">verified</span> {{ $hs('cta_badge3', 'Interactive Learning') }}
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Scroll reveal ──
    const reveals = document.querySelectorAll('.reveal');
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
    reveals.forEach(function(el) { observer.observe(el); });

    // ── Hero code block typewriter ──
    var codeBlock = document.getElementById('hero-code-block');
    if (!codeBlock) return;

    var lines = [
        { prefix: 'Q:', text: ' Find derivative of ', highlight: 'f(x) = x²sin(x)', hClass: 'text-secondary', prefixClass: 'dark:text-gray-500 text-gray-400' },
        { prefix: 'A:', text: ' ', highlight: 'Step 1:', hClass: 'text-purple-500 dark:text-purple-400', after: ' Apply Product Rule', prefixClass: 'dark:text-gray-500 text-gray-400' },
        { prefix: '  ', text: ' ', highlight: 'd/dx [uv]', hClass: 'text-primary', after: ' = u\'v + uv\'', prefixClass: 'dark:text-gray-500 text-gray-400' },
        { prefix: '=', text: ' ', highlight: '2x·sin(x) + x²·cos(x)', hClass: 'text-primary', prefixClass: 'dark:text-gray-500 text-gray-400' }
    ];

    var cursor = document.getElementById('code-cursor');
    var lineIdx = 0;
    var charIdx = 0;
    var currentPhase = 'prefix';
    var currentLineEl = null;
    var currentSpan = null;
    var started = false;

    function startTyping() {
        if (started) return;
        started = true;
        codeBlock.innerHTML = '';
        lineIdx = 0;
        nextLine();
    }

    function nextLine() {
        if (lineIdx >= lines.length) {
            // Done — add blinking cursor
            var cur = document.createElement('span');
            cur.className = 'text-primary animate-pulse';
            cur.textContent = '_';
            var lastLine = codeBlock.lastElementChild;
            if (lastLine) lastLine.appendChild(cur);
            return;
        }

        var line = lines[lineIdx];
        currentLineEl = document.createElement('div');
        currentLineEl.className = 'flex gap-2 dark:text-gray-500 text-gray-400' + (lineIdx < lines.length - 1 ? ' mb-2' : '');
        codeBlock.appendChild(currentLineEl);

        // Prefix span
        var prefSpan = document.createElement('span');
        prefSpan.className = line.prefixClass || '';
        currentLineEl.appendChild(prefSpan);

        currentPhase = 'prefix';
        charIdx = 0;
        currentSpan = prefSpan;
        typeChar();
    }

    function typeChar() {
        var line = lines[lineIdx];
        var speed = 30;

        if (currentPhase === 'prefix') {
            if (charIdx < line.prefix.length) {
                currentSpan.textContent += line.prefix[charIdx];
                charIdx++;
                setTimeout(typeChar, speed);
            } else {
                // Move to text
                currentPhase = 'text';
                charIdx = 0;
                if (line.text) {
                    currentSpan = document.createElement('span');
                    currentSpan.className = 'dark:text-white text-slate-800';
                    currentLineEl.appendChild(currentSpan);
                }
                setTimeout(typeChar, speed);
            }
        } else if (currentPhase === 'text') {
            if (line.text && charIdx < line.text.length) {
                currentSpan.textContent += line.text[charIdx];
                charIdx++;
                setTimeout(typeChar, speed);
            } else {
                // Move to highlight
                currentPhase = 'highlight';
                charIdx = 0;
                if (line.highlight) {
                    currentSpan = document.createElement('span');
                    currentSpan.className = line.hClass || 'text-primary';
                    currentLineEl.appendChild(currentSpan);
                }
                setTimeout(typeChar, speed);
            }
        } else if (currentPhase === 'highlight') {
            if (line.highlight && charIdx < line.highlight.length) {
                currentSpan.textContent += line.highlight[charIdx];
                charIdx++;
                setTimeout(typeChar, 25);
            } else {
                // Move to after text
                currentPhase = 'after';
                charIdx = 0;
                if (line.after) {
                    currentSpan = document.createElement('span');
                    currentSpan.className = 'dark:text-gray-300 text-gray-600';
                    currentLineEl.appendChild(currentSpan);
                }
                setTimeout(typeChar, speed);
            }
        } else if (currentPhase === 'after') {
            if (line.after && charIdx < line.after.length) {
                currentSpan.textContent += line.after[charIdx];
                charIdx++;
                setTimeout(typeChar, speed);
            } else {
                // Line done — pause then next line
                lineIdx++;
                setTimeout(nextLine, 400);
            }
        }
    }

    // Start when the panel scrolls into view
    var codeObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                setTimeout(startTyping, 600);
                codeObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    codeObserver.observe(codeBlock);
});
</script>
@endpush

@extends('layouts.app')

@section('title', config('app.name', 'BlinkStudy') . ' — AI Study Companion for Indian Students')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>
    .landing-page { font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif; }
    .landing-page * { font-family: inherit; }
    .hero-glow {
        position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none;
    }
    .glass-card {
        background: rgba(255,255,255,0.88);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(255,255,255,0.9);
        box-shadow: 0 8px 32px rgba(15,23,42,0.06);
    }
    .phone-frame {
        border: 10px solid #1e1b4b;
        border-radius: 36px;
        box-shadow: 0 32px 64px rgba(112,92,246,0.18), inset 0 0 0 2px rgba(255,255,255,0.08);
    }
    .reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s ease, transform .7s cubic-bezier(.16,1,.3,1); }
    .reveal.revealed { opacity: 1; transform: translateY(0); }
    .reveal-delay-1 { transition-delay: .08s; }
    .reveal-delay-2 { transition-delay: .16s; }
    .reveal-delay-3 { transition-delay: .24s; }
    .reveal-delay-4 { transition-delay: .32s; }
    .float { animation: float 6s ease-in-out infinite; }
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
</style>
@endpush

@php
    $hs = function($key, $default = '') use ($allHomepageSettings) {
        return $allHomepageSettings[$key] ?? $default;
    };
@endphp

@section('content')
<div class="landing-page bg-[#F8FAFF] text-slate-800 overflow-x-hidden">
    {{-- Background orbs --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="hero-glow w-[420px] h-[420px] bg-brand/20 -top-32 -left-20"></div>
        <div class="hero-glow w-[360px] h-[360px] bg-secondary/15 top-1/3 -right-24"></div>
        <div class="hero-glow w-[280px] h-[280px] bg-brand-100/60 bottom-20 left-1/4"></div>
    </div>

    {{-- ═══════════════ HERO ═══════════════ --}}
    <section class="relative z-10 pt-8 pb-16 lg:pt-14 lg:pb-24 px-4 sm:px-6">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div class="reveal">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-brand/15 shadow-sm mb-6">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-bold text-brand">{{ $hs('hero_badge_left', 'Made in India') }}</span>
                        <span class="text-slate-300">·</span>
                        <span class="text-xs font-semibold text-slate-500">{{ $hs('hero_badge_right', 'Built for CBSE, ICSE & JEE') }}</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-[3.4rem] font-extrabold text-slate-900 leading-[1.08] tracking-tight mb-5">
                        {{ $hs('hero_title_line1', 'Your AI Study') }}
                        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-brand via-brand-light to-secondary">
                            {{ $hs('hero_title_highlight', 'Companion') }}
                        </span>
                    </h1>

                    <p class="text-base sm:text-lg text-slate-500 leading-relaxed mb-8 max-w-lg">
                        {{ $hs('hero_description', 'Stuck on a doubt? Scan it, ask AI, practice quizzes, battle friends, and prepare for exams — all in one beautiful app.') }}
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3 mb-10">
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 h-14 px-7 rounded-2xl bg-gradient-to-r from-brand to-secondary text-white font-bold text-base shadow-blinkstudy-lg hover:-translate-y-0.5 hover:shadow-[0_20px_40px_rgba(112,92,246,0.3)] transition-all">
                            <span class="material-symbols-outlined text-xl">rocket_launch</span>
                            {{ $hs('hero_cta_text', 'Start Learning Free') }}
                        </a>
                        <a href="{{ route('plans') }}" class="inline-flex items-center justify-center gap-2 h-14 px-7 rounded-2xl bg-white border border-slate-200 text-slate-700 font-bold text-base hover:border-brand/30 hover:bg-brand-50/50 transition-all">
                            <span class="material-symbols-outlined text-xl text-brand">payments</span>
                            View Plans
                        </a>
                    </div>

                    <div class="flex flex-wrap gap-6 sm:gap-10">
                        <div>
                            <div class="text-2xl font-extrabold text-slate-900">{{ $hs('stat1_value', '10K+') }}</div>
                            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ $hs('stat1_label', 'Students') }}</div>
                        </div>
                        <div class="w-px bg-slate-200"></div>
                        <div>
                            <div class="text-2xl font-extrabold text-slate-900">{{ $hs('stat2_value', '50K+') }}</div>
                            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ $hs('stat2_label', 'Doubts Solved') }}</div>
                        </div>
                        <div class="w-px bg-slate-200"></div>
                        <div>
                            <div class="text-2xl font-extrabold text-slate-900">{{ $hs('stat3_value', '4.9★') }}</div>
                            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ $hs('stat3_label', 'App Rating') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Phone mockup --}}
                <div class="relative reveal reveal-delay-2 hidden sm:block">
                    <div class="absolute -top-6 -right-4 w-24 h-24 rounded-2xl bg-white shadow-card border border-slate-100 p-3 float" style="animation-delay: 1s">
                        <div class="w-full h-full rounded-xl bg-emerald-50 flex items-center justify-center">
                            <span class="material-symbols-outlined text-emerald-500 text-3xl">check_circle</span>
                        </div>
                    </div>
                    <div class="absolute -bottom-4 -left-4 z-20 glass-card rounded-2xl px-4 py-3 flex items-center gap-3 shadow-card">
                        <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center">
                            <span class="material-symbols-outlined text-brand">psychology</span>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-800">AI Tutor Online</div>
                            <div class="text-[11px] text-slate-400">Instant step-by-step help</div>
                        </div>
                    </div>

                    <div class="phone-frame mx-auto max-w-[280px] bg-slate-900 overflow-hidden float">
                        <div class="bg-gradient-to-br from-brand to-secondary px-5 pt-8 pb-6">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white text-base">auto_stories</span>
                                </div>
                                <span class="text-white font-bold text-sm">BlinkStudy</span>
                            </div>
                            <p class="text-white/90 text-lg font-bold leading-tight">Good evening,<br/>Ready to learn?</p>
                        </div>
                        <div class="bg-[#F8FAFF] p-4 space-y-3 -mt-2 rounded-t-3xl">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach([['psychology','AI Tutor','#705CF6'],['document_scanner','Scan & Solve','#5B8CFF'],['quiz','Quiz','#22C55E'],['sports_esports','Battles','#F59E0B']] as $item)
                                <div class="bg-white rounded-xl p-3 border border-slate-100 shadow-sm">
                                    <span class="material-symbols-outlined text-lg" style="color:{{ $item[2] }}">{{ $item[0] }}</span>
                                    <div class="text-[10px] font-bold text-slate-700 mt-1">{{ $item[1] }}</div>
                                </div>
                                @endforeach
                            </div>
                            <div class="bg-white rounded-xl p-3 border border-slate-100">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-[10px] font-bold text-slate-500">DAILY STREAK</span>
                                    <span class="text-[10px] font-bold text-brand">🔥 7 days</span>
                                </div>
                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full w-3/4 bg-gradient-to-r from-brand to-secondary rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ TRUST STRIP ═══════════════ --}}
    <section class="relative z-10 py-6 px-4 border-y border-slate-200/60 bg-white/60 backdrop-blur-sm">
        <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-sm font-semibold text-slate-500">
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-brand text-lg">verified</span> OTP Login — No Password</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-brand text-lg">shield</span> Safe for Students</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-brand text-lg">translate</span> Hindi + English</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-brand text-lg">android</span> Android App Available</span>
        </div>
    </section>

    {{-- ═══════════════ FEATURES ═══════════════ --}}
    <section id="features" class="relative z-10 py-16 lg:py-24 px-4 sm:px-6">
        <div class="max-w-6xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-12 reveal">
                <span class="text-xs font-bold uppercase tracking-widest text-brand">{{ $hs('features_badge', 'Everything you need') }}</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 mb-4 tracking-tight">{{ $hs('features_title', 'One app. Complete exam prep.') }}</h2>
                <p class="text-slate-500 text-base sm:text-lg">{{ $hs('features_description', 'From doubt solving to mock tests — BlinkStudy is your all-in-one study OS.') }}</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @php
                    $cards = [
                        [1, $hs('feature1_icon','psychology'), $hs('feature1_title','AI Tutor'), $hs('feature1_description','Chat with your personal AI teacher. Get step-by-step explanations in Hindi or English.'), 'from-brand to-brand-light'],
                        [2, $hs('feature2_icon','document_scanner'), $hs('feature2_title','Scan & Solve'), $hs('feature2_description','Snap a photo of any question — math, science, or textbook — and get instant answers.'), 'from-secondary to-blue-400'],
                        [3, $hs('feature3_icon','quiz'), $hs('feature3_title','Smart Quiz'), $hs('feature3_description','AI-generated quizzes tailored to your class, subject, and weak topics.'), 'from-emerald-500 to-teal-400'],
                        [4, $hs('feature4_icon','sports_esports'), $hs('feature4_title','Study Battles'), $hs('feature4_description','Challenge friends in real-time quiz battles. Learn while you compete.'), 'from-amber-500 to-orange-400'],
                        [5, 'school', 'Exam Prep', 'CBSE, ICSE, JEE & NEET prep with mock tests and previous year questions.', 'from-violet-500 to-brand'],
                        [6, 'menu_book', 'Revision Hub', 'Flashcards, saved notes, and daily challenges to keep your streak alive.', 'from-pink-500 to-rose-400'],
                    ];
                @endphp
                @foreach($cards as $card)
                <div class="reveal reveal-delay-{{ min($card[0], 4) }} group bg-white rounded-2xl p-6 border border-slate-100 shadow-card hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br {{ $card[4] }} flex items-center justify-center mb-4 shadow-blinkstudy group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">{{ $card[1] }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $card[2] }}</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">{{ $card[3] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════ HOW IT WORKS ═══════════════ --}}
    <section class="relative z-10 py-16 lg:py-20 px-4 sm:px-6 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12 reveal">
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">How it works</h2>
                <p class="text-slate-500 mt-2">Start learning in under 60 seconds</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach([
                    ['1', 'login', 'Login with OTP', 'Enter your mobile number. No password, no hassle.'],
                    ['2', 'document_scanner', 'Ask or Scan', 'Type your doubt or scan a question from your book.'],
                    ['3', 'emoji_events', 'Learn & Grow', 'Get answers, take quizzes, track progress, ace exams.'],
                ] as $idx => $step)
                <div class="reveal reveal-delay-{{ $idx + 1 }} relative text-center p-8 rounded-2xl bg-[#F8FAFF] border border-slate-100">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand to-secondary text-white font-extrabold text-sm flex items-center justify-center mx-auto mb-4">{{ $step[0] }}</div>
                    <span class="material-symbols-outlined text-brand text-3xl mb-3">{{ $step[1] }}</span>
                    <h3 class="font-bold text-slate-900 text-lg mb-2">{{ $step[2] }}</h3>
                    <p class="text-sm text-slate-500">{{ $step[3] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════ SUBJECTS ═══════════════ --}}
    <section id="subjects" class="relative z-10 py-16 lg:py-20 px-4 sm:px-6">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-10 reveal">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-secondary">{{ $hs('subjects_badge', 'Subject Coverage') }}</span>
                    <h2 class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ $hs('subjects_title', 'Every subject. Every class.') }}</h2>
                </div>
                <p class="text-slate-500 text-sm max-w-md">{{ $hs('subjects_description', 'From Class 6 to JEE — our AI understands your syllabus.') }}</p>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-6 gap-3">
                @php
                    $subjectStyles = [
                        ['bg' => 'bg-brand/10', 'text' => 'text-brand', 'border' => 'hover:border-brand/30'],
                        ['bg' => 'bg-secondary/10', 'text' => 'text-secondary', 'border' => 'hover:border-secondary/30'],
                        ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-500', 'border' => 'hover:border-emerald-500/30'],
                        ['bg' => 'bg-amber-500/10', 'text' => 'text-amber-500', 'border' => 'hover:border-amber-500/30'],
                        ['bg' => 'bg-pink-500/10', 'text' => 'text-pink-500', 'border' => 'hover:border-pink-500/30'],
                        ['bg' => 'bg-violet-500/10', 'text' => 'text-violet-500', 'border' => 'hover:border-violet-500/30'],
                    ];
                    $defaultSubjects = ['Mathematics','Science','English','Social','Physics','Chemistry'];
                    $defaultIcons = ['calculate','science','menu_book','public','bolt','biotech'];
                @endphp
                @for($i = 1; $i <= 6; $i++)
                @php
                    $sName = $hs("subject{$i}_name", $defaultSubjects[$i-1] ?? '');
                    $sIcon = $hs("subject{$i}_icon", $defaultIcons[$i-1] ?? 'school');
                    $style = $subjectStyles[$i-1];
                @endphp
                @if($sName)
                <a href="{{ route('login') }}" class="reveal reveal-delay-{{ $i }} group flex flex-col items-center gap-3 p-4 sm:p-5 rounded-2xl bg-white border border-slate-100 shadow-sm hover:shadow-card {{ $style['border'] }} hover:-translate-y-1 transition-all">
                    <div class="w-11 h-11 rounded-xl {{ $style['bg'] }} flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined {{ $style['text'] }} text-xl">{{ $sIcon }}</span>
                    </div>
                    <span class="text-[11px] sm:text-xs font-bold text-slate-600 text-center leading-tight">{{ $sName }}</span>
                </a>
                @endif
                @endfor
            </div>
        </div>
    </section>

    {{-- ═══════════════ PRICING TEASER ═══════════════ --}}
    <section class="relative z-10 py-16 px-4 sm:px-6">
        <div class="max-w-6xl mx-auto reveal">
            <div class="rounded-3xl bg-gradient-to-br from-brand via-brand-light to-secondary p-8 sm:p-12 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/4"></div>
                <div class="relative z-10 grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-widest text-white/70">Flexible Plans</span>
                        <h2 class="text-3xl sm:text-4xl font-extrabold mt-2 mb-3">{{ $hs('pricing_title', 'Start free. Upgrade when ready.') }}</h2>
                        <p class="text-white/80 text-base leading-relaxed">{{ $hs('pricing_description', 'Free plan with daily limits. Premium unlocks unlimited AI, quizzes, and exam prep.') }}</p>
                    </div>
                    <div class="flex flex-col sm:flex-row md:flex-col lg:flex-row gap-3 md:items-end">
                        <a href="{{ route('plans') }}" class="inline-flex items-center justify-center gap-2 h-12 px-6 rounded-xl bg-white text-brand font-bold hover:bg-white/90 transition-all shadow-lg">
                            See All Plans
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center h-12 px-6 rounded-xl border-2 border-white/40 text-white font-bold hover:bg-white/10 transition-all">
                            Try Free Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ CTA ═══════════════ --}}
    <section class="relative z-10 py-16 lg:py-24 px-4 sm:px-6">
        <div class="max-w-3xl mx-auto text-center reveal">
            <div class="glass-card rounded-3xl p-8 sm:p-14">
                <span class="inline-block px-3 py-1 rounded-full bg-brand-50 text-brand text-xs font-bold mb-4">{{ $hs('cta_badge', 'Join 10,000+ students') }}</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4 tracking-tight">{{ $hs('cta_title', 'Ready to study smarter?') }}</h2>
                <p class="text-slate-500 text-base sm:text-lg mb-8 leading-relaxed">{{ $hs('cta_description', 'Download BlinkStudy and turn every doubt into a learning moment.') }}</p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 h-14 px-8 rounded-2xl bg-gradient-to-r from-brand to-secondary text-white font-bold text-base shadow-blinkstudy-lg hover:-translate-y-0.5 transition-all">
                        {{ $hs('cta_button_primary', 'Get Started Free') }}
                    </a>
                    <a href="{{ route('support') }}" class="w-full sm:w-auto inline-flex items-center justify-center h-14 px-8 rounded-2xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all">
                        {{ $hs('cta_button_secondary', 'Contact Support') }}
                    </a>
                </div>

                <div class="flex flex-wrap justify-center gap-6 mt-8 text-sm font-semibold text-slate-400">
                    <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-brand text-base">check_circle</span> {{ $hs('cta_badge1', 'Free plan available') }}</span>
                    <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-brand text-base">check_circle</span> {{ $hs('cta_badge2', 'Cancel anytime') }}</span>
                    <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-brand text-base">check_circle</span> {{ $hs('cta_badge3', 'Made in India') }}</span>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.documentElement.classList.remove('dark');
    document.documentElement.classList.add('light');
    document.body.classList.remove('dark:bg-[#05080a]', 'dark:text-white');
    document.body.classList.add('bg-[#F8FAFF]', 'text-slate-800');

    var reveals = document.querySelectorAll('.reveal');
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });
    reveals.forEach(function(el) { observer.observe(el); });
});
</script>
@endpush

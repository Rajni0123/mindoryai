@extends('layouts.landing')

@section('title', config('app.name', 'BlinkStudy') . ' — AI Powered Exam Preparation')

@php
    $hs = function ($key, $default = '') use ($allHomepageSettings) {
        return $allHomepageSettings[$key] ?? $default;
    };
    $playStore = 'https://play.google.com/store/apps/details?id=com.blinkstudy.app';
    $heroImage = asset('splash-student.png');
@endphp

@section('content')
{{-- Navbar --}}
<nav class="fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-lg border-b border-white/10 shadow-sm">
    <div class="flex justify-between items-center h-16 px-6 md:px-12 max-w-[1280px] mx-auto">
        <a href="{{ route('home') }}" class="text-headline-md font-bold tracking-tight text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">auto_stories</span>
            BlinkStudy
        </a>
        <div class="hidden md:flex gap-6 items-center">
            <a class="text-label-md text-on-surface-variant hover:text-on-surface transition-colors px-3 py-2 rounded-md" href="#features">Features</a>
            <a class="text-label-md text-on-surface-variant hover:text-on-surface transition-colors px-3 py-2 rounded-md" href="#how-it-works">How It Works</a>
            <a class="text-label-md text-on-surface-variant hover:text-on-surface transition-colors px-3 py-2 rounded-md" href="#battle">Battle</a>
            <a class="text-label-md text-on-surface-variant hover:text-on-surface transition-colors px-3 py-2 rounded-md" href="#download">Download</a>
            <a class="text-label-md text-on-surface-variant hover:text-on-surface transition-colors px-3 py-2 rounded-md" href="#faq">FAQ</a>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}" class="text-label-md text-on-surface-variant hover:text-on-surface hidden sm:block">Login</a>
            <a href="{{ route('login') }}" class="bg-primary hover:bg-primary/90 text-on-primary text-label-md px-4 py-2 rounded-lg transition-all hover:shadow-[0_0_15px_rgba(175,198,255,0.4)]">Get Started</a>
            <button type="button" class="md:hidden w-9 h-9 rounded-lg hover:bg-white/10 flex items-center justify-center" onclick="document.getElementById('mobile-nav').classList.toggle('hidden')" aria-label="Menu">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
    </div>
    <div id="mobile-nav" class="hidden md:hidden border-t border-white/10 px-6 py-4 space-y-2 bg-surface/95 backdrop-blur-lg">
        <a href="#features" class="block py-2 text-label-md text-on-surface-variant">Features</a>
        <a href="#how-it-works" class="block py-2 text-label-md text-on-surface-variant">How It Works</a>
        <a href="#battle" class="block py-2 text-label-md text-on-surface-variant">Battle</a>
        <a href="#download" class="block py-2 text-label-md text-on-surface-variant">Download</a>
        <a href="#faq" class="block py-2 text-label-md text-on-surface-variant">FAQ</a>
        <a href="{{ route('login') }}" class="block py-3 mt-2 text-center text-label-md font-bold bg-primary text-on-primary rounded-lg">Get Started</a>
    </div>
</nav>

<main class="pt-16">
    {{-- Hero --}}
    <section class="relative py-14 sm:py-16 lg:py-20 px-6 md:px-margin-desktop">
        <div class="absolute inset-0 z-0 pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-72 h-72 sm:w-96 sm:h-96 bg-primary/10 rounded-full blur-[100px]"></div>
            <div class="absolute bottom-1/4 right-1/4 w-64 h-64 sm:w-80 sm:h-80 bg-secondary/10 rounded-full blur-[100px]"></div>
        </div>
        <div class="max-w-[1280px] mx-auto w-full relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-14 items-center">
            <div class="flex flex-col gap-5 sm:gap-6 lg:gap-8 order-2 lg:order-1">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-primary/20 bg-primary/10 w-fit backdrop-blur-sm max-w-full">
                    <span class="material-symbols-outlined text-primary text-[16px] shrink-0">bolt</span>
                    <span class="text-label-sm text-primary leading-snug">{{ $hs('hero_badge_left', 'Made in India') }} · {{ $hs('hero_badge_right', 'CBSE, ICSE, JEE & NEET') }}</span>
                </div>
                <h1 class="text-[2rem] sm:text-4xl lg:text-5xl font-extrabold text-on-surface leading-[1.12] tracking-tight">
                    {{ $hs('hero_title_line1', 'Your AI Study') }}<br/>
                    <span class="text-gradient inline-block">{{ $hs('hero_title_highlight', 'Companion') }}</span>
                </h1>
                <p class="text-base sm:text-lg text-on-surface-variant max-w-xl leading-relaxed">
                    {{ $hs('hero_description', 'Stuck on a doubt? Scan it, ask AI, practice quizzes, battle friends, and prepare for exams — all in one beautiful app.') }}
                </p>
                <div class="flex flex-wrap gap-3 sm:gap-4 pt-1">
                    <a href="{{ $playStore }}" target="_blank" rel="noopener" class="bg-primary hover:bg-primary/90 text-on-primary text-label-md px-6 sm:px-8 py-3.5 sm:py-4 rounded-lg transition-all inline-flex items-center gap-2">
                        Download Now <span class="material-symbols-outlined text-xl">arrow_forward</span>
                    </a>
                    <a href="{{ route('login') }}" class="glass-panel hover:bg-white/10 text-on-surface text-label-md px-6 sm:px-8 py-3.5 sm:py-4 rounded-lg transition-all inline-flex items-center gap-2">
                        Try Web App <span class="material-symbols-outlined text-xl">login</span>
                    </a>
                </div>
            </div>
            <div class="relative group order-1 lg:order-2 w-full flex justify-center lg:justify-end">
                <div class="absolute -inset-1 bg-gradient-to-r from-primary to-secondary rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-700 max-w-[392px] w-full mx-auto lg:mx-0"></div>
                <div class="relative hero-visual-shell glass-card">
                    <img alt="BlinkStudy student" class="hero-visual-img" src="{{ $heroImage }}" loading="eager"/>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section class="py-xl px-6 md:px-margin-desktop border-y border-outline-variant/30 bg-surface-container-low/50 relative z-20">
        <div class="max-w-[1280px] mx-auto grid grid-cols-2 md:grid-cols-4 gap-gutter text-center">
            <div class="flex flex-col gap-xs">
                <span class="text-headline-lg text-primary">{{ $hs('stat1_value', '10K+') }}</span>
                <span class="text-label-sm text-on-surface-variant uppercase tracking-wider">{{ $hs('stat1_label', 'Active Students') }}</span>
            </div>
            <div class="flex flex-col gap-xs">
                <span class="text-headline-lg text-secondary">{{ $hs('stat2_value', '50K+') }}</span>
                <span class="text-label-sm text-on-surface-variant uppercase tracking-wider">{{ $hs('stat2_label', 'Doubts Solved') }}</span>
            </div>
            <div class="flex flex-col gap-xs">
                <span class="text-headline-lg text-tertiary">{{ $hs('stat3_value', '4.9★') }}</span>
                <span class="text-label-sm text-on-surface-variant uppercase tracking-wider">{{ $hs('stat3_label', 'App Rating') }}</span>
            </div>
            <div class="flex flex-col gap-xs">
                <span class="text-headline-lg text-on-surface">150+</span>
                <span class="text-label-sm text-on-surface-variant uppercase tracking-wider">Exam Syllabi</span>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-16 sm:py-20 lg:py-24 px-6 md:px-margin-desktop relative" id="features">
        <div class="max-w-[1280px] mx-auto flex flex-col gap-10 lg:gap-12">
            <div class="text-center max-w-2xl mx-auto flex flex-col gap-3 sm:gap-4">
                <h2 class="text-2xl sm:text-headline-lg text-on-surface font-bold">{{ $hs('features_title', 'Precision Tools for Top Percentiles') }}</h2>
                <p class="text-sm sm:text-body-md text-on-surface-variant leading-relaxed">{{ $hs('features_description', 'Everything you need to ace your exams and understand concepts deeply.') }}</p>
            </div>

            @php
                $featureRows = [
                    [
                        ['wide', 'quiz', 'primary', 'Daily AI Tests', 'Adaptive questioning that evolves with your capability. The system finds your threshold and pushes it every single day.'],
                        ['narrow', 'radar', 'secondary', 'Weakness Analysis', 'Smart weakness mapping isolates sub-topics where you lose marks most often.'],
                    ],
                    [
                        ['narrow', 'swords', 'tertiary', 'Study Battles', 'Challenge friends in real-time quiz duels. Share a room code and compete on the leaderboard.'],
                        ['wide', 'event_note', 'primary', 'Your Comeback Plan', 'A day-by-day revision path shaped around your weak topics — not generic AI fluff.'],
                    ],
                    [
                        ['narrow', 'local_fire_department', 'secondary', 'Daily Streak', 'Build momentum with streak tracking and daily challenges.'],
                        ['wide', 'document_scanner', 'tertiary', 'Scan Doubts Instantly', 'Upload any question from your book. AI breaks down solutions step-by-step in Hindi or English.'],
                    ],
                ];
                $toneMap = [
                    'primary' => ['box' => 'bg-primary/10 border-primary/20 text-primary'],
                    'secondary' => ['box' => 'bg-secondary/10 border-secondary/20 text-secondary'],
                    'tertiary' => ['box' => 'bg-tertiary/10 border-tertiary/20 text-tertiary'],
                ];
            @endphp

            <div class="flex flex-col gap-5">
                @foreach($featureRows as $row)
                <div class="feature-bento-row grid grid-cols-1 gap-5">
                    @foreach($row as $card)
                    @php
                        $size = $card[0];
                        $icon = $card[1];
                        $tone = $toneMap[$card[2]];
                    @endphp
                    <article class="feature-bento-card feature-bento-{{ $size }} glass-card rounded-xl p-6 sm:p-8 flex flex-col gap-4 hover-lift">
                        <div class="w-12 h-12 rounded-lg border flex items-center justify-center shrink-0 {{ $tone['box'] }}">
                            <span class="material-symbols-outlined">{{ $icon }}</span>
                        </div>
                        <div class="flex flex-col gap-2 flex-1">
                            <h3 class="text-lg sm:text-headline-md text-on-surface font-semibold">{{ $card[3] }}</h3>
                            <p class="text-sm sm:text-body-md text-on-surface-variant leading-relaxed">{{ $card[4] }}</p>
                        </div>
                    </article>
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="py-2xl px-6 md:px-margin-desktop bg-surface-container-low/30" id="how-it-works">
        <div class="max-w-[1280px] mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-headline-lg text-on-surface mb-4">How BlinkStudy Works</h2>
                <p class="text-body-md text-on-surface-variant">Four simple steps from doubt to rank improvement.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 relative">
                @foreach([
                    ['1', 'Take Test', 'Adaptive daily assessments.', 'bg-primary text-on-primary'],
                    ['2', 'Find Gaps', 'AI spots hidden weak topics.', 'bg-secondary text-on-secondary'],
                    ['3', 'Practice Smart', 'Focused drills on weak areas.', 'bg-tertiary text-on-tertiary'],
                    ['4', 'Improve Rank', 'Track progress over time.', 'bg-primary-container text-on-primary-container'],
                ] as $i => $step)
                <div class="flex flex-col items-center text-center gap-4 {{ $i < 3 ? 'step-connector' : '' }} relative">
                    <div class="w-16 h-16 rounded-full {{ $step[3] }} flex items-center justify-center text-headline-md font-bold z-10">{{ $step[0] }}</div>
                    <h4 class="text-headline-md text-on-surface">{{ $step[1] }}</h4>
                    <p class="text-label-sm text-on-surface-variant">{{ $step[2] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Dashboard preview --}}
    <section class="py-2xl px-6 md:px-margin-desktop">
        <div class="max-w-[1280px] mx-auto text-center flex flex-col gap-lg">
            <h2 class="text-headline-lg text-on-surface">Your Command Center</h2>
            <div class="relative mx-auto max-w-4xl w-full">
                <div class="preview-visual-shell border border-white/10 shadow-2xl shadow-primary/20 mx-auto">
                    <img alt="BlinkStudy dashboard" class="preview-visual-img" src="{{ $heroImage }}" loading="lazy"/>
                </div>
                <div class="absolute top-[20%] left-[2%] lg:left-[-8%] glass-panel p-3 rounded-lg hidden lg:block" style="animation: bounce 3s ease-in-out infinite">
                    <span class="text-label-sm text-primary">Weak Topic Radar</span>
                </div>
                <div class="absolute bottom-[20%] right-[2%] lg:right-[-4%] glass-panel p-3 rounded-lg hidden lg:block" style="animation: bounce 4s ease-in-out infinite">
                    <span class="text-label-sm text-secondary">Live Leaderboard</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Battle --}}
    <section class="py-2xl px-6 md:px-margin-desktop bg-[#0B1220]" id="battle">
        <div class="max-w-[1280px] mx-auto grid grid-cols-1 lg:grid-cols-2 gap-xl items-center">
            <div class="flex flex-col gap-lg">
                <span class="text-label-sm text-secondary font-bold uppercase tracking-widest">Compete. Learn. Win.</span>
                <h2 class="text-4xl sm:text-display-lg text-on-surface">Enter the Battle Arena</h2>
                <p class="text-body-lg text-on-surface-variant">Start a battle, share the room code with your friend, and compete in real-time quizzes. Climb tiers and prove your mastery.</p>
                <ul class="space-y-4">
                    <li class="flex items-center gap-3 text-on-surface">
                        <span class="material-symbols-outlined text-secondary">check_circle</span> 1v1 Friend Battles
                    </li>
                    <li class="flex items-center gap-3 text-on-surface">
                        <span class="material-symbols-outlined text-secondary">check_circle</span> Room Code Invite
                    </li>
                    <li class="flex items-center gap-3 text-on-surface">
                        <span class="material-symbols-outlined text-secondary">check_circle</span> Live Leaderboard Rankings
                    </li>
                </ul>
            </div>
            <div class="glass-card p-lg rounded-2xl border-secondary/30">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-headline-md text-on-surface">Live Leaderboard</h4>
                    <span class="px-2 py-1 bg-secondary/20 text-secondary text-label-sm rounded">This Week</span>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg border border-white/5">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-secondary">01</span>
                            <div class="w-8 h-8 rounded-full bg-primary/20"></div>
                            <span class="text-on-surface">Arjun K.</span>
                        </div>
                        <span class="text-primary font-bold">2,840 XP</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg border border-white/5">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-on-surface-variant">02</span>
                            <div class="w-8 h-8 rounded-full bg-secondary/20"></div>
                            <span class="text-on-surface">Priya S.</span>
                        </div>
                        <span class="text-secondary font-bold">2,715 XP</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-primary/10 rounded-lg border border-primary/30">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-primary">—</span>
                            <div class="w-8 h-8 rounded-full bg-white/10"></div>
                            <span class="text-on-surface">You</span>
                        </div>
                        <span class="text-on-surface font-bold">Start today</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Download --}}
    <section class="py-2xl px-6 md:px-margin-desktop text-center" id="download">
        <div class="max-w-4xl mx-auto flex flex-col gap-xl">
            <h2 class="text-headline-lg text-on-surface">Get BlinkStudy</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="glass-card p-lg rounded-xl flex flex-col items-center gap-4 hover-lift">
                    <span class="material-symbols-outlined text-[48px] text-primary">laptop_windows</span>
                    <h5 class="text-headline-md">Web App</h5>
                    <a href="{{ route('login') }}" class="bg-primary/10 text-primary border border-primary/20 px-4 py-2 rounded-lg w-full text-label-md">Login on Web</a>
                </div>
                <div class="glass-card p-lg rounded-xl flex flex-col items-center gap-4 hover-lift">
                    <span class="material-symbols-outlined text-[48px] text-secondary">phone_android</span>
                    <h5 class="text-headline-md">Android</h5>
                    <a href="{{ $playStore }}" target="_blank" rel="noopener" class="bg-secondary/10 text-secondary border border-secondary/20 px-4 py-2 rounded-lg w-full text-label-md">Google Play</a>
                </div>
                <div class="glass-card p-lg rounded-xl flex flex-col items-center gap-4">
                    <div class="w-24 h-24 bg-white p-2 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-4xl text-on-primary" style="color:#002d6c">qr_code_2</span>
                    </div>
                    <h5 class="text-label-md">Scan on Play Store</h5>
                    <p class="text-label-sm text-on-surface-variant">Search "BlinkStudy"</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonials --}}
    <section class="py-2xl px-6 md:px-margin-desktop bg-surface-container-low/20">
        <div class="max-w-[1280px] mx-auto">
            <h2 class="text-center text-headline-lg text-on-surface mb-12">Trusted by Students Across India</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
                @foreach([
                    ['Weakness analysis ne Physics ke weak points 3 din mein pakad liye. Ab sirf wahi padhta hoon jo zaroori hai.', 'Rohan M.', 'JEE Aspirant'],
                    ['Battle mode se padhai addictive ho gayi. Friends ke saath quiz karke percentile badhi.', 'Ananya S.', 'NEET UG Student'],
                    ['Comeback Plan har din batata hai kya revise karna hai — random padhai band.', 'Vikram K.', 'CBSE Class 12'],
                ] as $i => $t)
                <div class="glass-card p-lg rounded-xl flex flex-col gap-4 {{ $i === 1 ? 'border-primary/30 md:scale-105' : '' }}">
                    <div class="flex text-tertiary">
                        @for($s = 0; $s < 5; $s++)
                        <span class="material-symbols-outlined text-sm">star</span>
                        @endfor
                    </div>
                    <p class="text-body-md italic text-on-surface-variant">"{{ $t[0] }}"</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-outline-variant/50"></div>
                        <div>
                            <p class="text-label-md">{{ $t[1] }}</p>
                            <p class="text-label-sm text-on-surface-variant">{{ $t[2] }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-2xl px-6 md:px-margin-desktop" id="faq">
        <div class="max-w-3xl mx-auto flex flex-col gap-lg">
            <h2 class="text-center text-headline-lg text-on-surface mb-8">Frequently Asked Questions</h2>
            <div class="space-y-4">
                <details class="glass-card rounded-xl group" open>
                    <summary class="p-lg cursor-pointer flex justify-between items-center text-label-md text-on-surface font-bold list-none">
                        Is BlinkStudy free to use?
                        <span class="material-symbols-outlined transition-transform group-open:rotate-180">expand_more</span>
                    </summary>
                    <div class="px-lg pb-lg text-body-md text-on-surface-variant border-t border-white/5 pt-4">
                        Yes — free plan includes daily AI help, quizzes, and basic analysis. Premium unlocks higher limits, battles, and advanced revision tools.
                    </div>
                </details>
                <details class="glass-card rounded-xl group">
                    <summary class="p-lg cursor-pointer flex justify-between items-center text-label-md text-on-surface font-bold list-none">
                        How does weakness analysis work?
                        <span class="material-symbols-outlined transition-transform group-open:rotate-180">expand_more</span>
                    </summary>
                    <div class="px-lg pb-lg text-body-md text-on-surface-variant border-t border-white/5 pt-4">
                        We track your quiz accuracy, response patterns, and topic overlap to show exactly where you lose marks — then suggest focused practice.
                    </div>
                </details>
                <details class="glass-card rounded-xl group">
                    <summary class="p-lg cursor-pointer flex justify-between items-center text-label-md text-on-surface font-bold list-none">
                        Does it support JEE, NEET, CBSE, and UPSC?
                        <span class="material-symbols-outlined transition-transform group-open:rotate-180">expand_more</span>
                    </summary>
                    <div class="px-lg pb-lg text-body-md text-on-surface-variant border-t border-white/5 pt-4">
                        Yes. BlinkStudy supports 150+ syllabi including CBSE, ICSE, JEE, NEET, UPSC, and other competitive exams popular in India.
                    </div>
                </details>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="py-2xl px-6 md:px-margin-desktop text-center relative overflow-hidden">
        <div class="absolute inset-0 bg-primary/5 -z-10"></div>
        <div class="max-w-4xl mx-auto flex flex-col gap-lg items-center">
            <h2 class="text-4xl sm:text-display-lg text-on-surface">Ready to Study Smarter?</h2>
            <p class="text-body-lg text-on-surface-variant max-w-2xl">Join thousands of students using BlinkStudy for doubts, quizzes, battles, and exam prep.</p>
            <a href="{{ route('login') }}" class="bg-primary hover:bg-primary/90 text-on-primary text-headline-md px-12 py-5 rounded-xl transition-all shadow-xl shadow-primary/20 inline-block">
                Get Started for Free
            </a>
            <p class="text-label-sm text-on-surface-variant mt-4">OTP login · No password · Free plan available</p>
        </div>
    </section>
</main>

{{-- Footer --}}
<footer class="w-full py-16 px-6 md:px-12 border-t border-outline-variant bg-surface-container-lowest relative z-20">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 max-w-[1280px] mx-auto">
        <div class="flex flex-col gap-4 col-span-1 md:col-span-2">
            <div class="text-headline-md font-bold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined">auto_stories</span>
                BlinkStudy
            </div>
            <p class="text-body-md text-on-surface-variant max-w-sm">
                AI-powered exam preparation for Indian students — doubts, quizzes, battles, and revision in one app.
            </p>
        </div>
        <div class="flex flex-col gap-3">
            <span class="text-label-md text-on-surface font-bold mb-2">Navigation</span>
            <a class="text-body-md text-on-surface-variant hover:text-primary transition-colors" href="#features">Features</a>
            <a class="text-body-md text-on-surface-variant hover:text-primary transition-colors" href="#how-it-works">How It Works</a>
            <a class="text-body-md text-on-surface-variant hover:text-primary transition-colors" href="{{ route('plans') }}">Plans</a>
            <a class="text-body-md text-on-surface-variant hover:text-primary transition-colors" href="{{ route('support') }}">Support</a>
        </div>
        <div class="flex flex-col gap-3">
            <span class="text-label-md text-on-surface font-bold mb-2">Legal</span>
            <a class="text-body-md text-on-surface-variant hover:text-primary transition-colors" href="{{ route('privacy') }}">Privacy Policy</a>
            <a class="text-body-md text-on-surface-variant hover:text-primary transition-colors" href="{{ route('terms') }}">Terms of Service</a>
            <a class="text-body-md text-on-surface-variant hover:text-primary transition-colors" href="{{ route('refund') }}">Refund Policy</a>
        </div>
    </div>
    <div class="max-w-[1280px] mx-auto border-t border-white/5 mt-12 pt-8 text-center">
        <p class="text-label-sm text-on-surface-variant">© {{ date('Y') }} BlinkStudy. Made in India.</p>
    </div>
</footer>
@endsection

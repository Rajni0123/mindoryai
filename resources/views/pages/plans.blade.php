@extends('layouts.app')

@section('title', 'Plans - ' . ($settings['site_name'] ?? 'BlinkStudy'))

@push('styles')
<style>
    .plan-card {
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .plan-card:hover {
        transform: translateY(-6px);
    }
    .plan-popular {
        border-color: #0D9488 !important;
        box-shadow: 0 0 0 1px #0D9488, 0 20px 60px -15px rgba(13,148,136,0.25);
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-up {
        animation: fadeUp 0.5s ease-out forwards;
        opacity: 0;
    }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }

    .feature-row {
        display: flex; align-items: center; gap: 8px;
        padding: 3px 0;
    }
</style>
@endpush

@section('content')
    {{-- ═══════ HERO ═══════ --}}
    <section class="pt-16 pb-6 px-4">
        <div class="max-w-3xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full dark:bg-primary/10 bg-primary/5 border dark:border-primary/20 border-primary/15 mb-6">
                <span class="material-symbols-outlined text-primary text-sm">workspace_premium</span>
                <span class="text-xs font-semibold text-primary tracking-wide">SIMPLE & TRANSPARENT PRICING</span>
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold dark:text-white text-slate-900 tracking-tight mb-4">
                Choose Your <span class="bg-gradient-to-r from-primary to-teal-300 bg-clip-text text-transparent">Study Plan</span>
            </h1>
            <p class="text-lg dark:text-gray-400 text-gray-500 max-w-xl mx-auto">
                Pocket-friendly plans built for Indian students. Start free, upgrade anytime.
            </p>
        </div>
    </section>

    {{-- ═══════ PRICING CARDS ═══════ --}}
    <section class="pb-20 px-4">
        <div class="max-w-6xl mx-auto">
            @php
                $planCount = $pricingPlans->count();
                $gridCols = $planCount <= 2 ? 'lg:grid-cols-2 max-w-3xl' : ($planCount == 3 ? 'lg:grid-cols-3 max-w-5xl' : 'lg:grid-cols-4 max-w-6xl');
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 {{ $gridCols }} gap-6 mx-auto">
                @foreach($pricingPlans as $index => $plan)
                    @php
                        $features = $plan->features ?? [];
                        $featuresList = $features['features_list'] ?? $features['features'] ?? [];
                        $dailyLimits = $features['daily_limits'] ?? [];
                        $isRecommended = ($features['recommended'] ?? false);
                        $isPopular = !$isRecommended && ($plan->is_popular || ($features['popular'] ?? false));
                        $isFree = $plan->price == 0;
                        $hasWatermark = $features['watermark'] ?? false;
                        $hasAds = $features['ads'] ?? false;
                        $hasPriority = $features['priority_queue'] ?? false;
                        $historyDays = $features['history_days'] ?? 0;
                        $maxVideoLen = $features['max_video_length_seconds'] ?? 0;

                        $planColors = [
                            'free'      => ['ring' => 'ring-gray-200 dark:ring-white/10', 'badge' => 'bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-400', 'icon' => 'text-gray-400', 'btn' => 'dark:bg-white/5 bg-gray-100 dark:text-white text-gray-800 dark:hover:bg-white/10 hover:bg-gray-200'],
                            'basic'     => ['ring' => 'ring-primary/30', 'badge' => 'bg-primary/10 text-primary', 'icon' => 'text-primary', 'btn' => 'bg-primary text-white hover:bg-primary/90'],
                            'starter'   => ['ring' => 'ring-primary/30', 'badge' => 'bg-primary/10 text-primary', 'icon' => 'text-primary', 'btn' => 'bg-primary text-white hover:bg-primary/90'],
                            'pro'       => ['ring' => 'ring-secondary/40', 'badge' => 'bg-secondary/10 text-secondary', 'icon' => 'text-secondary', 'btn' => 'bg-gradient-to-r from-secondary to-orange-500 text-white hover:shadow-lg hover:shadow-secondary/25'],
                            'unlimited' => ['ring' => 'ring-purple-500/40', 'badge' => 'bg-purple-500/10 text-purple-400', 'icon' => 'text-purple-400', 'btn' => 'bg-gradient-to-r from-purple-600 to-indigo-500 text-white hover:shadow-lg hover:shadow-purple-500/25'],
                        ];
                        $slug = $plan->slug ?? 'basic';
                        $colors = $planColors[$slug] ?? $planColors['basic'];
                    @endphp

                    @php $hasBadge = $isPopular || $isRecommended; @endphp
                    <div class="plan-card animate-fade-up delay-{{ $index + 1 }} relative flex flex-col rounded-2xl border
                                dark:bg-[#0d1117] bg-white
                                {{ $isPopular ? 'plan-popular border-primary' : ($isRecommended ? 'border-secondary shadow-lg shadow-secondary/10' : 'dark:border-white/8 border-gray-200') }}
                                overflow-hidden">

                        @if($isPopular)
                            <div class="absolute top-0 left-0 right-0">
                                <div class="bg-gradient-to-r from-primary to-teal-500 text-center py-1.5">
                                    <span class="text-[11px] font-bold text-white uppercase tracking-wider">Most Popular</span>
                                </div>
                            </div>
                        @elseif($isRecommended)
                            <div class="absolute top-0 left-0 right-0">
                                <div class="bg-gradient-to-r from-secondary to-orange-500 text-center py-1.5">
                                    <span class="text-[11px] font-bold text-white uppercase tracking-wider">Best Value</span>
                                </div>
                            </div>
                        @endif

                        <div class="flex flex-col flex-1 p-5 {{ $hasBadge ? 'pt-11' : 'pt-5' }}">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg {{ $colors['badge'] }} flex items-center justify-center">
                                        @if($isFree)
                                            <span class="material-symbols-outlined text-[16px]">person</span>
                                        @elseif($slug === 'unlimited')
                                            <span class="material-symbols-outlined text-[16px]">all_inclusive</span>
                                        @elseif($slug === 'pro')
                                            <span class="material-symbols-outlined text-[16px]">diamond</span>
                                        @else
                                            <span class="material-symbols-outlined text-[16px]">bolt</span>
                                        @endif
                                    </div>
                                    <h3 class="text-base font-bold dark:text-white text-slate-900">{{ $plan->name }}</h3>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-extrabold dark:text-white text-slate-900">
                                        {{ $isFree ? 'Free' : '₹' . intval($plan->price) }}
                                    </span>
                                    @if(!$isFree)
                                        <span class="text-xs dark:text-gray-500 text-gray-400">/{{ $plan->billing_period ?? 'mo' }}</span>
                                    @endif
                                </div>
                            </div>

                            @if($plan->description)
                                <p class="text-xs dark:text-gray-500 text-gray-400 mb-3 -mt-1">{{ $plan->description }}</p>
                            @endif

                            <div class="mb-4">
                                @auth
                                    @if($isFree)
                                        <div class="w-full py-2.5 rounded-xl font-semibold text-center text-sm dark:bg-white/5 bg-gray-100 dark:text-gray-400 text-gray-500 cursor-default">
                                            Current Plan
                                        </div>
                                    @else
                                        <button id="buy-btn-{{ $plan->id }}"
                                                onclick="buyPlan({{ $plan->id }}, '{{ addslashes($plan->name) }}', {{ $plan->price }})"
                                                class="block w-full py-2.5 rounded-xl font-semibold text-center text-sm transition-all cursor-pointer {{ $colors['btn'] }}">
                                            Get {{ $plan->name }}
                                        </button>
                                    @endif
                                @else
                                    <a href="{{ route('register') }}"
                                       class="block w-full py-2.5 rounded-xl font-semibold text-center text-sm transition-all {{ $colors['btn'] }}">
                                        {{ $isFree ? 'Start Free' : 'Get ' . $plan->name }}
                                    </a>
                                @endauth
                            </div>

                            <div class="dark:border-white/5 border-gray-100 border-t mb-3"></div>

                            @if(!empty($featuresList))
                                <ul class="space-y-1.5 flex-1">
                                    @foreach($featuresList as $feature)
                                        <li class="flex items-center gap-2 py-0.5">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span class="text-xs dark:text-gray-300 text-gray-600">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="flex flex-wrap gap-1.5 mt-3 pt-3 border-t dark:border-white/5 border-gray-100">
                                @if($hasAds)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-500 text-[10px] font-medium">
                                        <span class="material-symbols-outlined text-[12px]">campaign</span> Ads
                                    </span>
                                @endif
                                @if($hasWatermark)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-500 text-[10px] font-medium">
                                        <span class="material-symbols-outlined text-[12px]">branding_watermark</span> Watermark
                                    </span>
                                @endif
                                @if($hasPriority)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary/10 text-primary text-[10px] font-medium">
                                        <span class="material-symbols-outlined text-[12px]">bolt</span> Priority
                                    </span>
                                @endif
                                @if(!$hasAds && !$isFree)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 text-[10px] font-medium">
                                        <span class="material-symbols-outlined text-[12px]">block</span> No Ads
                                    </span>
                                @endif
                                @if($historyDays == -1)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary/10 text-primary text-[10px] font-medium">
                                        <span class="material-symbols-outlined text-[12px]">all_inclusive</span> Unlimited History
                                    </span>
                                @elseif($historyDays > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full dark:bg-white/5 bg-gray-100 dark:text-gray-400 text-gray-500 text-[10px] font-medium">
                                        <span class="material-symbols-outlined text-[12px]">history</span> {{ $historyDays }}d History
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-center text-xs dark:text-gray-500 text-gray-400 mt-8">
                All daily limits reset at <strong>12:00 AM IST</strong>. Cancel anytime — no questions asked.
            </p>
        </div>
    </section>

    {{-- ═══════ COMPARISON TABLE (Desktop) ═══════ --}}
    <section class="pb-20 px-4 hidden lg:block">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-2xl font-bold dark:text-white text-slate-900 text-center mb-8">Compare Plans</h2>

            <div class="rounded-2xl border dark:border-white/8 border-gray-200 overflow-hidden dark:bg-[#0d1117] bg-white">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="dark:bg-white/3 bg-gray-50">
                            <th class="text-left px-6 py-4 dark:text-gray-400 text-gray-500 font-semibold w-1/3">Feature</th>
                            @foreach($pricingPlans as $plan)
                                <th class="text-center px-4 py-4 dark:text-white text-slate-900 font-bold">{{ $plan->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-white/5 divide-gray-100">
                        <tr>
                            <td class="px-6 py-3.5 dark:text-gray-400 text-gray-500 font-medium">Price</td>
                            @foreach($pricingPlans as $plan)
                                <td class="text-center px-4 py-3.5 font-bold dark:text-white text-slate-900">
                                    {{ $plan->price == 0 ? 'Free' : '₹' . intval($plan->price) . '/' . ($plan->billing_period ?? 'mo') }}
                                </td>
                            @endforeach
                        </tr>
                        @php
                            $compareKeys = [
                                'video_quiz_per_day' => 'Video Quizzes / Day',
                                'topic_quiz_per_day' => 'Topic Quizzes / Day',
                                'scan_solve_per_day' => 'Scan & Solve / Day',
                                'whiteboard_videos_per_day' => 'Whiteboard Videos / Day',
                                'whiteboard_videos_per_month' => 'Whiteboard Videos / Month',
                                'exam_prep_per_day' => 'Exam Prep / Day',
                                'pdf_uploads_per_month' => 'PDF Uploads / Month',
                            ];
                        @endphp
                        @foreach($compareKeys as $key => $label)
                            @php
                                $anyHas = $pricingPlans->contains(fn($p) => isset(($p->features['daily_limits'] ?? [])[$key]));
                            @endphp
                            @if($anyHas)
                                <tr>
                                    <td class="px-6 py-3.5 dark:text-gray-400 text-gray-500 font-medium">{{ $label }}</td>
                                    @foreach($pricingPlans as $plan)
                                        @php $val = ($plan->features['daily_limits'] ?? [])[$key] ?? '-'; @endphp
                                        <td class="text-center px-4 py-3.5 font-semibold {{ $val == -1 ? 'text-primary' : 'dark:text-gray-300 text-gray-600' }}">
                                            {{ $val == -1 ? 'Unlimited' : ($val === '-' ? '-' : $val) }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                        <tr>
                            <td class="px-6 py-3.5 dark:text-gray-400 text-gray-500 font-medium">Ad-Free</td>
                            @foreach($pricingPlans as $plan)
                                <td class="text-center px-4 py-3.5">
                                    @if(!($plan->features['ads'] ?? true))
                                        <span class="text-emerald-500 material-symbols-outlined text-[18px]">check_circle</span>
                                    @else
                                        <span class="dark:text-gray-600 text-gray-300 material-symbols-outlined text-[18px]">cancel</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="px-6 py-3.5 dark:text-gray-400 text-gray-500 font-medium">No Watermark</td>
                            @foreach($pricingPlans as $plan)
                                <td class="text-center px-4 py-3.5">
                                    @if(!($plan->features['watermark'] ?? true))
                                        <span class="text-emerald-500 material-symbols-outlined text-[18px]">check_circle</span>
                                    @else
                                        <span class="dark:text-gray-600 text-gray-300 material-symbols-outlined text-[18px]">cancel</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="px-6 py-3.5 dark:text-gray-400 text-gray-500 font-medium">Priority Queue</td>
                            @foreach($pricingPlans as $plan)
                                <td class="text-center px-4 py-3.5">
                                    @if($plan->features['priority_queue'] ?? false)
                                        <span class="text-emerald-500 material-symbols-outlined text-[18px]">check_circle</span>
                                    @else
                                        <span class="dark:text-gray-600 text-gray-300 material-symbols-outlined text-[18px]">cancel</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ═══════ FAQ Link ═══════ --}}
    <section class="pb-20 px-4 text-center">
        <p class="dark:text-gray-400 text-gray-600 text-sm">
            Have questions? Check our <a href="{{ route('faq') }}" class="text-primary font-semibold hover:underline">FAQ page</a> for answers.
        </p>
    </section>
@endsection

@auth
@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
function buyPlan(planId, planName, price) {
    var btn = document.getElementById('buy-btn-' + planId);
    var originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Processing...';
    btn.style.opacity = '0.7';

    fetch('/payment/create-razorpay-order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ plan_id: planId })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            alert(data.message || 'Failed to create payment order.');
            resetBtn();
            return;
        }

        var options = {
            key: data.razorpay_key,
            amount: data.amount,
            currency: data.currency,
            name: 'BlinkStudy',
            description: planName + ' Plan',
            order_id: data.order_id,
            prefill: {
                name: data.user_name || '',
                email: data.user_email || '',
                contact: data.user_phone || ''
            },
            theme: { color: '#0D9488' },
            handler: function(response) {
                btn.textContent = 'Verifying...';
                fetch('/payment/verify-razorpay', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature,
                        plan_id: planId,
                        transaction_id: data.transaction_id
                    })
                })
                .then(function(r) { return r.json(); })
                .then(function(verifyData) {
                    if (verifyData.success) {
                        btn.textContent = 'Activated!';
                        btn.classList.add('bg-green-500');
                        setTimeout(function() {
                            window.location.href = verifyData.redirect_url || '/chat';
                        }, 800);
                    } else {
                        alert(verifyData.message || 'Payment verification failed.');
                        resetBtn();
                    }
                })
                .catch(function() {
                    alert('Payment verification failed. Please contact support.');
                    resetBtn();
                });
            },
            modal: {
                ondismiss: function() { resetBtn(); }
            }
        };

        var rzp = new Razorpay(options);
        rzp.on('payment.failed', function(response) {
            alert('Payment failed: ' + (response.error.description || 'Unknown error'));
            resetBtn();
        });
        rzp.open();
    })
    .catch(function(err) {
        alert('Something went wrong. Please try again.');
        resetBtn();
    });

    function resetBtn() {
        btn.disabled = false;
        btn.textContent = originalText;
        btn.style.opacity = '1';
    }
}
</script>
@endpush
@endauth

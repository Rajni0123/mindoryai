<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plans - BlinkStudy</title>
    <meta name="description" content="Choose your study plan. Pocket-friendly plans built for Indian students.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #f8fafc; }

        .plan-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
        }
        .plan-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        .plan-best-value {
            border: 2px solid #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }
        .plan-ultimate {
            border: 2px solid #8b5cf6;
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
        }

        .feature-category {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 12px 0 6px 0;
        }
        .feature-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
            font-size: 12px;
        }
        .feature-name {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #334155;
        }
        .feature-value {
            font-weight: 500;
            color: #64748b;
            text-align: right;
            font-size: 11px;
        }
        .feature-disabled {
            color: #cbd5e1;
            text-decoration: line-through;
        }
        .unlimited-badge {
            color: #059669;
            font-weight: 600;
        }

        .toggle-btn {
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .toggle-active {
            background: #0f172a;
            color: white;
        }
        .toggle-inactive {
            background: transparent;
            color: #64748b;
        }

        .btn-choose {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .btn-free { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-lite { background: #0f172a; color: white; }
        .btn-pro { background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); color: white; }
        .btn-ultimate { background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); color: white; }

        .expanded-features {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .expanded-features.show {
            max-height: 600px;
        }

        .see-all-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-size: 12px;
            color: #3b82f6;
            cursor: pointer;
            padding: 8px;
            margin-top: 8px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .see-all-btn:hover {
            background: #eff6ff;
        }
        .see-all-btn svg {
            transition: transform 0.3s;
        }
        .see-all-btn.expanded svg {
            transform: rotate(180deg);
        }

        .pack-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .plan-current {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%) !important;
        }

        @media (max-width: 1024px) {
            .plans-grid { grid-template-columns: repeat(2, 1fr) !important; }
            .packs-grid { grid-template-columns: repeat(3, 1fr) !important; }
        }
        @media (max-width: 768px) {
            .packs-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }
        @media (max-width: 640px) {
            .plans-grid { grid-template-columns: 1fr !important; }
            .packs-grid { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000; padding: 12px;">
        <div style="max-width: 1400px; margin: 0 auto; padding: 0 16px;">
            <nav style="display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.8); border-radius: 100px; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
                <a href="/" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                    <img src="/logo.png" alt="BlinkStudy" style="width: 36px; height: 36px;">
                    <span style="font-weight: 700; font-size: 1.1rem; color: #0f172a;">BlinkStudy</span>
                </a>
                <a href="/login" style="padding: 10px 20px; background: #0f172a; color: white; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 14px;">Get Started</a>
            </nav>
        </div>
    </div>

    <div style="padding-top: 100px;">
        <!-- Header -->
        <section style="text-align: center; padding: 30px 24px 16px;">
            <div style="display: inline-block; background: #fef3c7; color: #92400e; padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 600; margin-bottom: 16px;">
                10,000+ Students Trust BlinkStudy
            </div>
            <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 10px;">
                Apni Padhai Ko <span style="color: #f59e0b;">Supercharge</span> Karo
            </h1>
            <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">
                AI tutor sirf ₹3/din se — Chai se bhi sasta ☕
            </p>

            <!-- Monthly/Annual Toggle - YEARLY DISABLED FOR NOW -->
            {{--
            <div style="display: inline-flex; background: #f1f5f9; border-radius: 50px; padding: 4px; position: relative;">
                <button id="toggle-monthly" class="toggle-btn toggle-active" onclick="toggleBilling('monthly')">Monthly</button>
                <button id="toggle-annual" class="toggle-btn toggle-inactive" onclick="toggleBilling('annual')" style="position: relative;">
                    Annual
                    <span style="position: absolute; top: -8px; right: -10px; background: #ef4444; color: white; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 700;">SAVE 30%</span>
                </button>
            </div>
            --}}
        </section>

        <!-- Plans Grid -->
        <section style="padding: 30px 24px 50px;">
            <div style="max-width: 1200px; margin: 0 auto;">
                <div class="plans-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; align-items: start;">

                    @foreach($plans as $plan)
                    @php
                        $isFree = $plan->price_monthly == 0;
                        $isRecommended = $plan->is_recommended;
                        $isUltimate = $plan->slug === 'ultimate';
                        $features = $plan->features->keyBy('feature_slug');
                        $isUserCurrentPlan = auth()->check() && ($currentPlanSlug ?? null) === $plan->slug;
                    @endphp

                    <div class="plan-card {{ $isRecommended ? 'plan-best-value' : ($isUltimate ? 'plan-ultimate' : '') }} {{ $isUserCurrentPlan ? 'plan-current' : '' }}" style="{{ $isUserCurrentPlan ? 'border: 2px solid #10b981; box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);' : '' }}">
                        @if($isUserCurrentPlan)
                        <div style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 3px 12px; border-radius: 50px; font-size: 10px; font-weight: 700; text-transform: uppercase;">✓ Your Plan</div>
                        @elseif($isRecommended)
                        <div style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #f59e0b, #f97316); color: white; padding: 3px 12px; border-radius: 50px; font-size: 10px; font-weight: 700; text-transform: uppercase;">Best Value</div>
                        @elseif($isUltimate)
                        <div style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #8b5cf6, #6366f1); color: white; padding: 3px 12px; border-radius: 50px; font-size: 10px; font-weight: 700; text-transform: uppercase;">Everything</div>
                        @endif

                        <!-- Plan Header -->
                        <div style="margin-bottom: 12px; {{ $isRecommended || $isUltimate ? 'padding-top: 6px;' : '' }}">
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: {{ $isRecommended ? '#92400e' : ($isUltimate ? '#6b21a8' : '#0f172a') }}; margin-bottom: 2px;">
                                {{ $plan->name }} @if($isUltimate) 👑 @endif
                            </h3>
                            <p style="font-size: 12px; color: #64748b;">{{ $plan->description }}</p>
                        </div>

                        <!-- Price -->
                        <div style="margin-bottom: 14px;">
                            @if($isFree)
                            <span style="font-size: 2rem; font-weight: 800; color: #0f172a;">Free</span>
                            @else
                            <span style="font-size: 12px; color: #64748b;">₹</span>
                            <span class="price-monthly" style="font-size: 2rem; font-weight: 800; color: #0f172a;">{{ $plan->price_monthly }}</span>
                            <span class="price-annual" style="font-size: 2rem; font-weight: 800; color: #0f172a; display: none;">{{ $plan->price_annual }}</span>
                            <span class="suffix-monthly" style="font-size: 12px; color: #64748b;">/mo</span>
                            <span class="suffix-annual" style="font-size: 12px; color: #64748b; display: none;">/year</span>
                            @endif
                        </div>

                        <!-- CTA Button -->
                        @auth
                            @php
                                $userPlanSlug = $currentPlanSlug ?? null;
                                $isCurrentPlan = ($plan->slug === $userPlanSlug);
                                $planOrder = ['lite' => 1, 'pro' => 2, 'ultimate' => 3];
                                $userPlanOrder = $userPlanSlug ? ($planOrder[$userPlanSlug] ?? 0) : 0;
                                $thisPlanOrder = $planOrder[$plan->slug] ?? 1;
                                $isUpgrade = $thisPlanOrder > $userPlanOrder;
                                $isDowngrade = $thisPlanOrder < $userPlanOrder;
                            @endphp

                            @if($isCurrentPlan)
                            <button class="btn-choose btn-{{ $plan->slug }}" disabled style="opacity: 0.7; cursor: not-allowed;">
                                ✓ Current Plan
                            </button>
                            @if($currentSubscription && $currentSubscription->expires_at)
                            <p style="font-size: 10px; color: #64748b; text-align: center; margin-top: 6px;">
                                Expires: {{ $currentSubscription->expires_at->format('d M Y') }}
                            </p>
                            @endif
                            @elseif($isUpgrade)
                            <button class="btn-choose btn-{{ $plan->slug }}" onclick="buyPlan('{{ $plan->slug }}', '{{ $plan->name }}')">
                                ⬆ Upgrade to {{ $plan->name }}
                            </button>
                            @elseif($isDowngrade && !$isFree)
                            <button class="btn-choose btn-free" style="background: #f1f5f9; color: #64748b;" onclick="buyPlan('{{ $plan->slug }}', '{{ $plan->name }}')">
                                Switch to {{ $plan->name }}
                            </button>
                            @elseif($isFree)
                            <button class="btn-choose btn-free" disabled style="opacity: 0.5;">
                                Free Plan
                            </button>
                            @else
                            <button class="btn-choose btn-{{ $plan->slug }}" onclick="buyPlan('{{ $plan->slug }}', '{{ $plan->name }}')">
                                Choose {{ $plan->name }}
                            </button>
                            @endif
                        @else
                        <a href="/login" class="btn-choose btn-{{ $plan->slug }}" style="display: block; text-align: center; text-decoration: none;">
                            {{ $isFree ? 'Get Started' : 'Choose ' . $plan->name }}
                        </a>
                        @endauth

                        <!-- Key Features (Always Visible) -->
                        <div style="margin-top: 14px;">
                            <div class="feature-category">AI & Solving</div>
                            @php $f = $features->get('ai_doubt'); @endphp
                            <div class="feature-row">
                                <span class="feature-name">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    AI Doubt Solving
                                </span>
                                <span class="feature-value {{ $f && $f->isUnlimited() ? 'unlimited-badge' : '' }}">
                                    @if($f && $f->isUnlimited()) Unlimited ⚡ @elseif($f) {{ $f->limit_value }}/day @endif
                                </span>
                            </div>

                            @php $f = $features->get('scan_solve'); @endphp
                            <div class="feature-row">
                                <span class="feature-name">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    Scan to Solve
                                </span>
                                <span class="feature-value">{{ $f ? $f->limit_value . '/day' : '' }}</span>
                            </div>

                            @php $f = $features->get('reasoning'); @endphp
                            <div class="feature-row">
                                <span class="feature-name">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    Reasoning Practice
                                </span>
                                <span class="feature-value {{ $f && $f->isUnlimited() ? 'unlimited-badge' : '' }}">
                                    @if($f && $f->isUnlimited()) Unlimited ⚡ @elseif($f) {{ $f->limit_value }}/day @endif
                                </span>
                            </div>

                            <div class="feature-category">Quiz & Practice</div>
                            @php $f = $features->get('quiz'); @endphp
                            <div class="feature-row">
                                <span class="feature-name">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    Quiz
                                </span>
                                <span class="feature-value {{ $f && $f->isUnlimited() ? 'unlimited-badge' : '' }}">
                                    @if($f && $f->isUnlimited()) Unlimited ⚡ @elseif($f) {{ $f->limit_value }}/day @endif
                                </span>
                            </div>

                            @php $f = $features->get('topic_quiz_gen'); @endphp
                            <div class="feature-row">
                                <span class="feature-name {{ $f && $f->limit_value == 0 ? 'feature-disabled' : '' }}">
                                    @if($f && $f->limit_value == 0)
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    @else
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    @endif
                                    Generate Quiz by Topic
                                </span>
                                <span class="feature-value {{ $f && $f->isUnlimited() ? 'unlimited-badge' : '' }}">
                                    @if($f && $f->limit_value == 0) — @elseif($f && $f->isUnlimited()) Unlimited ⚡ @elseif($f) {{ $f->limit_value }}/day @endif
                                </span>
                            </div>
                        </div>

                        <!-- Expandable Features -->
                        <div class="expanded-features" id="expanded-{{ $plan->slug }}">
                            <div class="feature-category">Content & Media</div>
                            @php $f = $features->get('whiteboard_video'); @endphp
                            <div class="feature-row">
                                <span class="feature-name">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    Whiteboard Videos
                                </span>
                                <span class="feature-value" style="font-size: 10px;">
                                    {{ $f ? $f->limit_value . '/mo' : '' }}
                                    @if($f && $f->extra_info)
                                        @if($f->extra_info['watermark'] ?? false) • WM @else • {{ $f->extra_info['quality'] ?? 'SD' }} @endif
                                    @endif
                                </span>
                            </div>

                            @php $f = $features->get('notes_diagrams'); @endphp
                            <div class="feature-row">
                                <span class="feature-name {{ $f && $f->limit_value == 0 ? 'feature-disabled' : '' }}">
                                    @if($f && $f->limit_value == 0)
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    @else
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    @endif
                                    Notes with Diagrams
                                </span>
                                <span class="feature-value">
                                    @if($f && $f->limit_value == 0) — @elseif($f && $f->limit_type === 'daily') {{ $f->limit_value }}/day @endif
                                </span>
                            </div>

                            <div class="feature-category">Social & Analytics</div>
                            @php $f = $features->get('leaderboard'); @endphp
                            <div class="feature-row">
                                <span class="feature-name">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    Leaderboard
                                </span>
                                <span class="feature-value">
                                    @if($f && ($f->extra_info['access'] ?? '') === 'full') Full Access @else View Only @endif
                                </span>
                            </div>

                            @php $f = $features->get('student_chat'); @endphp
                            <div class="feature-row">
                                <span class="feature-name {{ $f && $f->limit_value == 0 ? 'feature-disabled' : '' }}">
                                    @if($f && $f->limit_value == 0)
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    @else
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    @endif
                                    Student Chat
                                </span>
                                <span class="feature-value {{ $f && $f->isUnlimited() ? 'unlimited-badge' : '' }}">
                                    @if($f && $f->limit_value == 0) — @elseif($f && $f->isUnlimited()) Unlimited @elseif($f) {{ $f->limit_value }}/day @endif
                                </span>
                            </div>

                            <div class="feature-category">Other</div>
                            @php $f = $features->get('ads'); @endphp
                            <div class="feature-row">
                                <span class="feature-name">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    @if($f && $f->limit_value == 1) Ads @else No Ads @endif
                                </span>
                                <span class="feature-value" style="color: {{ $f && $f->limit_value == 0 ? '#10b981' : '#64748b' }};">
                                    @if($f && $f->limit_value == 1) Yes @else Clean @endif
                                </span>
                            </div>

                            @php $f = $features->get('support'); @endphp
                            <div class="feature-row">
                                <span class="feature-name {{ $f && $f->limit_value == 0 ? 'feature-disabled' : '' }}">
                                    @if($f && $f->limit_value == 0)
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    @else
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    @endif
                                    @if($f && ($f->extra_info['type'] ?? '') === 'dedicated') Dedicated Support
                                    @elseif($f && ($f->extra_info['type'] ?? '') === 'priority') Priority Support
                                    @elseif($f && ($f->extra_info['type'] ?? '') === 'email') Email Support
                                    @else Support @endif
                                </span>
                                <span class="feature-value">
                                    @if($f && $f->limit_value == 0) — @else ✓ @endif
                                </span>
                            </div>

                            <!-- Footer inside expanded -->
                            @if(!$isFree)
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0; text-align: center;">
                                <span class="daily-rate-monthly" style="font-size: 11px; color: #64748b;">
                                    ₹{{ max(1, round($plan->price_monthly / 30)) }}/din — Chai se bhi sasta ☕
                                </span>
                                <span class="daily-rate-annual" style="font-size: 11px; color: #64748b; display: none;">
                                    ₹{{ max(1, round($plan->price_annual / 365)) }}/din — Chai se bhi sasta ☕
                                </span>
                            </div>
                            @endif
                        </div>

                        <!-- See All Features Button -->
                        <div class="see-all-btn" id="toggle-btn-{{ $plan->slug }}" onclick="toggleFeatures('{{ $plan->slug }}')">
                            <span id="toggle-text-{{ $plan->slug }}">See All Features</span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </div>
                    </div>
                    @endforeach

                </div>
            </div>
        </section>

        <!-- Special Season Packs Section -->
        @if(isset($seasonalPacks) && $seasonalPacks->count() > 0 && ($settings['show_seasonal_packs'] ?? false))
        <section id="season-packs-section" style="padding: 40px 24px 60px; background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);">
            <div style="max-width: 1200px; margin: 0 auto;">
                <!-- Section Header -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 8px;">
                        <span style="font-size: 1.5rem;">🔥</span> Special Season Packs
                    </h2>
                    <p style="font-size: 14px; color: #64748b;">
                        Exam ke time limited period offers — grab karo jaldi!
                    </p>
                </div>

                <!-- Packs Grid -->
                <div class="packs-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    @foreach($seasonalPacks as $pack)
                    @php
                        $planSlug = $pack->plan->slug ?? 'lite';
                        $planName = $pack->plan->name ?? 'Lite';

                        // Color schemes based on pack type
                        $colors = [
                            'first-month-trial' => ['bg' => '#fff', 'btn' => '#10b981', 'tag' => '#10b981'],
                            'board-exam-pack' => ['bg' => '#fff', 'btn' => '#334155', 'tag' => '#3b82f6'],
                            'jee-crash-pack' => ['bg' => 'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%)', 'btn' => '#10b981', 'tag' => '#10b981'],
                            'neet-booster' => ['bg' => '#fff', 'btn' => '#f97316', 'tag' => '#f97316'],
                            'summer-study-pack' => ['bg' => '#fff', 'btn' => '#f59e0b', 'tag' => '#64748b'],
                            'topper-pack' => ['bg' => '#fff', 'btn' => '#10b981', 'tag' => '#ef4444'],
                        ];
                        $packColors = $colors[$pack->slug] ?? ['bg' => '#fff', 'btn' => '#0f172a', 'tag' => '#64748b'];
                        $isBestDeal = $pack->slug === 'topper-pack';
                    @endphp

                    <div class="pack-card" style="background: {{ $packColors['bg'] }}; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; position: relative; {{ $isBestDeal ? 'border: 2px solid #ef4444;' : '' }}">
                        <!-- Tag Badge -->
                        <div style="position: absolute; top: 12px; right: 12px; background: {{ $packColors['tag'] }}; color: white; padding: 3px 8px; border-radius: 4px; font-size: 9px; font-weight: 700; text-transform: uppercase;">
                            {{ $pack->tag }}
                        </div>

                        <!-- Icon -->
                        <div style="font-size: 2rem; margin-bottom: 10px;">{{ $pack->icon }}</div>

                        <!-- Pack Name -->
                        <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 4px;">{{ $pack->name }}</h3>

                        <!-- Description -->
                        <p style="font-size: 11px; color: #64748b; margin-bottom: 12px; min-height: 32px;">{{ $pack->description }}</p>

                        <!-- Price -->
                        <div style="margin-bottom: 12px;">
                            <span style="font-size: 11px; color: #64748b;">₹</span>
                            <span style="font-size: 1.75rem; font-weight: 800; color: #0f172a;">{{ $pack->price }}</span>
                        </div>

                        <!-- Duration & Plan Badges -->
                        <div style="display: flex; gap: 6px; margin-bottom: 14px; flex-wrap: wrap;">
                            <span style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 600;">
                                {{ $pack->duration_days }} Days
                            </span>
                            <span style="background: #f1f5f9; color: #475569; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 600;">
                                {{ $planName }} Access
                            </span>
                        </div>

                        <!-- CTA Button -->
                        @auth
                        @php
                            $userPlanSlug = $currentPlanSlug ?? null;
                            $packPlanSlug = $pack->plan->slug ?? 'lite';
                            $planOrder = ['lite' => 1, 'pro' => 2, 'ultimate' => 3];
                            $userPlanOrder = $userPlanSlug ? ($planOrder[$userPlanSlug] ?? 0) : 0;
                            $packPlanOrder = $planOrder[$packPlanSlug] ?? 2;
                            $hasHigherPlan = $userPlanOrder >= $packPlanOrder;
                        @endphp

                        @if($hasHigherPlan && $userPlanSlug)
                        <button disabled
                                style="width: 100%; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: not-allowed; border: none; background: #e2e8f0; color: #64748b; transition: all 0.2s;">
                            ✓ You have {{ ucfirst($userPlanSlug) }}
                        </button>
                        @else
                        <button onclick="buyPack('{{ $pack->slug }}', '{{ $pack->name }}')"
                                style="width: 100%; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; border: none; background: {{ $packColors['btn'] }}; color: white; transition: all 0.2s;">
                            Get This Pack
                        </button>
                        @endif
                        @else
                        <a href="/login" style="display: block; width: 100%; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 13px; text-align: center; text-decoration: none; background: {{ $packColors['btn'] }}; color: white;">
                            Get This Pack
                        </a>
                        @endauth
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif
    </div>

    <!-- Footer -->
    <footer style="padding: 30px 24px; background: #0f172a; color: #fff; text-align: center;">
        <p style="color: #64748b; font-size: 12px;">© {{ date('Y') }} BlinkStudy. All rights reserved. Made with ❤️ in India</p>
    </footer>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        const CHAT_APP_URL = @json(\App\Support\ChatSubdomainUrl::appUrl());
        let currentBilling = 'monthly';

        function toggleBilling(type) {
            currentBilling = type;
            document.getElementById('toggle-monthly').className = 'toggle-btn ' + (type === 'monthly' ? 'toggle-active' : 'toggle-inactive');
            document.getElementById('toggle-annual').className = 'toggle-btn ' + (type === 'annual' ? 'toggle-active' : 'toggle-inactive');
            // Toggle price amounts
            document.querySelectorAll('.price-monthly').forEach(el => el.style.display = type === 'monthly' ? 'inline' : 'none');
            document.querySelectorAll('.price-annual').forEach(el => el.style.display = type === 'annual' ? 'inline' : 'none');
            // Toggle price suffix (/mo or /year)
            document.querySelectorAll('.suffix-monthly').forEach(el => el.style.display = type === 'monthly' ? 'inline' : 'none');
            document.querySelectorAll('.suffix-annual').forEach(el => el.style.display = type === 'annual' ? 'inline' : 'none');
            // Toggle daily rate display
            document.querySelectorAll('.daily-rate-monthly').forEach(el => el.style.display = type === 'monthly' ? 'inline' : 'none');
            document.querySelectorAll('.daily-rate-annual').forEach(el => el.style.display = type === 'annual' ? 'inline' : 'none');
            // Hide/Show Season Packs section (only show for monthly)
            const seasonPacksSection = document.getElementById('season-packs-section');
            if (seasonPacksSection) {
                seasonPacksSection.style.display = type === 'monthly' ? 'block' : 'none';
            }
        }

        function toggleFeatures(planSlug) {
            const expanded = document.getElementById('expanded-' + planSlug);
            const btn = document.getElementById('toggle-btn-' + planSlug);
            const text = document.getElementById('toggle-text-' + planSlug);

            if (expanded.classList.contains('show')) {
                expanded.classList.remove('show');
                btn.classList.remove('expanded');
                text.textContent = 'See All Features';
            } else {
                expanded.classList.add('show');
                btn.classList.add('expanded');
                text.textContent = 'Hide Features';
            }
        }

        function buyPack(packSlug, packName) {
            const btn = event.target;
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Processing...';

            fetch('/web/razorpay/create-pack-order', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ pack_slug: packSlug })
            })
            .then(r => r.json())
            .then(data => {
                if (data.subscription) {
                    btn.textContent = 'Activated!';
                    setTimeout(() => window.location.href = CHAT_APP_URL, 800);
                    return;
                }
                if (!data.success) {
                    alert(data.message || data.errors?.pack_slug?.[0] || 'Failed to create order');
                    btn.disabled = false;
                    btn.textContent = originalText;
                    return;
                }

                const options = {
                    key: data.key,
                    amount: data.amount * 100,
                    currency: data.currency,
                    name: 'BlinkStudy',
                    description: packName,
                    order_id: data.order_id,
                    theme: { color: '#0f172a' },
                    handler: function(response) {
                        btn.textContent = 'Verifying...';
                        fetch('/web/razorpay/verify-payment', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_signature: response.razorpay_signature,
                                pack_slug: packSlug
                            })
                        })
                        .then(r => r.json())
                        .then(verifyData => {
                            if (verifyData.success) {
                                btn.textContent = 'Activated!';
                                btn.style.background = '#10b981';
                                setTimeout(() => window.location.href = CHAT_APP_URL, 800);
                            } else {
                                alert(verifyData.message || 'Verification failed');
                                btn.disabled = false;
                                btn.textContent = originalText;
                            }
                        });
                    },
                    modal: { ondismiss: () => { btn.disabled = false; btn.textContent = originalText; } }
                };

                new Razorpay(options).open();
            })
            .catch(err => {
                console.error('Payment error:', err);
                alert('Something went wrong. Please try again.');
                btn.disabled = false;
                btn.textContent = originalText;
            });
        }

        function buyPlan(planSlug, planName) {
            const btn = event.target;
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Processing...';

            fetch('/web/razorpay/create-order', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ plan_slug: planSlug, billing_cycle: currentBilling })
            })
            .then(r => r.json())
            .then(data => {
                if (data.subscription) {
                    btn.textContent = 'Activated!';
                    setTimeout(() => window.location.href = CHAT_APP_URL, 800);
                    return;
                }
                if (!data.success) {
                    alert(data.message || data.errors?.plan_slug?.[0] || 'Failed to create order');
                    btn.disabled = false;
                    btn.textContent = originalText;
                    return;
                }

                const options = {
                    key: data.key,
                    amount: data.amount * 100,
                    currency: data.currency,
                    name: 'BlinkStudy',
                    description: planName + ' Plan (' + (currentBilling === 'annual' ? 'Annual' : 'Monthly') + ')',
                    order_id: data.order_id,
                    theme: { color: '#0f172a' },
                    handler: function(response) {
                        btn.textContent = 'Verifying...';
                        fetch('/web/razorpay/verify-payment', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_signature: response.razorpay_signature,
                                plan_slug: planSlug,
                                billing_cycle: currentBilling
                            })
                        })
                        .then(r => r.json())
                        .then(verifyData => {
                            if (verifyData.success) {
                                btn.textContent = 'Activated!';
                                btn.style.background = '#10b981';
                                setTimeout(() => window.location.href = CHAT_APP_URL, 800);
                            } else {
                                alert(verifyData.message || 'Verification failed');
                                btn.disabled = false;
                                btn.textContent = originalText;
                            }
                        });
                    },
                    modal: { ondismiss: () => { btn.disabled = false; btn.textContent = originalText; } }
                };

                new Razorpay(options).open();
            })
            .catch(err => {
                console.error('Payment error:', err);
                alert('Something went wrong. Please try again.');
                btn.disabled = false;
                btn.textContent = originalText;
            });
        }
    </script>
</body>
</html>

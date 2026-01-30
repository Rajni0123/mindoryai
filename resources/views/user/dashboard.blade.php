<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#05070a">
    <title>Dashboard - {{ config('app.name', 'Mindory') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet"/>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#0df259",
                        "primary-dark": "#0ac547",
                        "background-dark": "#05070a",
                        "surface-dark": "#0d1117",
                        "surface-light": "#161b22",
                    },
                    fontFamily: {
                        "sans": ["Inter", "system-ui", "sans-serif"],
                    },
                    animation: {
                        'slide-up': 'slideUp 0.3s ease-out',
                        'slide-down': 'slideDown 0.3s ease-out',
                        'fade-in': 'fadeIn 0.2s ease-out',
                        'scale-in': 'scaleIn 0.2s ease-out',
                        'bounce-subtle': 'bounceSubtle 0.6s ease-out',
                    },
                    keyframes: {
                        slideUp: {
                            '0%': { transform: 'translateY(100%)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        slideDown: {
                            '0%': { transform: 'translateY(-100%)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        scaleIn: {
                            '0%': { transform: 'scale(0.9)', opacity: '0' },
                            '100%': { transform: 'scale(1)', opacity: '1' },
                        },
                        bounceSubtle: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-5px)' },
                        },
                    }
                }
            }
        }
    </script>

    <style>
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }

        body {
            background: #05070a;
            font-family: 'Inter', system-ui, sans-serif;
            overscroll-behavior: none;
        }

        .glass {
            background: rgba(13, 17, 23, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .glass-strong {
            background: rgba(13, 17, 23, 0.9);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .gradient-text {
            background: linear-gradient(135deg, #0df259 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .gradient-border {
            position: relative;
            background: linear-gradient(135deg, rgba(13, 242, 89, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);
            border: 1px solid transparent;
        }

        .gradient-border::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(135deg, #0df259 0%, #06b6d4 100%);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0.3;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(13, 242, 89, 0.3);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(13, 242, 89, 0.5);
        }

        /* Input focus styles */
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #0df259;
            box-shadow: 0 0 0 3px rgba(13, 242, 89, 0.1);
        }

        /* Button active state */
        button:active {
            transform: scale(0.98);
        }

        /* Smooth modal animations */
        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }

        @media (max-width: 768px) {
            .modal-content {
                max-height: 80vh;
            }
        }

        /* Toast notification */
        .toast {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
            min-width: 250px;
            max-width: 400px;
            animation: slideDown 0.3s ease-out;
        }

        @media (max-width: 640px) {
            .toast {
                left: 1rem;
                right: 1rem;
                max-width: none;
            }
        }
    </style>
</head>
<body class="text-white">
    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 z-[9999] space-y-2"></div>

    <!-- Mobile-First Bottom Navigation -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 glass-strong border-t border-white/10 safe-area-bottom">
        <div class="grid grid-cols-3 h-16">
            <a href="{{ route('chat') }}" class="flex flex-col items-center justify-center gap-0.5 text-gray-400 hover:text-primary transition-all active:scale-95">
                <span class="material-symbols-rounded text-2xl">chat_bubble</span>
                <span class="text-[10px] font-medium">Chat</span>
            </a>
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center gap-0.5 text-primary transition-all relative">
                <div class="absolute -top-1 w-12 h-1 bg-primary rounded-full"></div>
                <span class="material-symbols-rounded text-2xl" style="font-variation-settings: 'FILL' 1">dashboard</span>
                <span class="text-[10px] font-semibold">Home</span>
            </a>
            <a href="{{ route('plans') }}" class="flex flex-col items-center justify-center gap-0.5 text-gray-400 hover:text-primary transition-all active:scale-95">
                <span class="material-symbols-rounded text-2xl">workspace_premium</span>
                <span class="text-[10px] font-medium">Plans</span>
            </a>
        </div>
    </nav>

    <!-- Desktop Sidebar -->
    <aside class="hidden lg:flex fixed left-0 top-0 bottom-0 w-72 glass-strong border-r border-white/10 flex-col z-40">
        <div class="p-6 border-b border-white/10">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-cyan-500 flex items-center justify-center">
                    <span class="material-symbols-rounded text-white text-xl">school</span>
                </div>
                <span class="text-xl font-bold gradient-text">{{ config('app.name', 'Mindory') }}</span>
            </a>
        </div>

        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-primary/10 text-primary border border-primary/20 font-medium transition-all">
                <span class="material-symbols-rounded">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('chat') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-white/5 hover:text-white transition-all">
                <span class="material-symbols-rounded">chat_bubble</span>
                <span>AI Chat</span>
            </a>
            <a href="{{ route('plans') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-white/5 hover:text-white transition-all">
                <span class="material-symbols-rounded">workspace_premium</span>
                <span>Upgrade Plan</span>
            </a>
        </nav>

        <div class="p-4 border-t border-white/10 space-y-1">
            <button onclick="toggleProfile()" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-white/5 hover:text-white transition-all">
                <span class="material-symbols-rounded">person</span>
                <span>Profile Settings</span>
            </button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition-all">
                    <span class="material-symbols-rounded">logout</span>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="lg:ml-72 pb-20 lg:pb-8">
        <!-- Mobile Header -->
        <div class="lg:hidden sticky top-0 z-30 glass-strong border-b border-white/10 px-4 py-3">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold">Dashboard</h1>
                    <p class="text-xs text-gray-400">Welcome back, {{ $user->name }}</p>
                </div>
                <button onclick="toggleProfile()" class="w-10 h-10 rounded-full bg-gradient-to-br from-primary/20 to-cyan-500/20 border border-primary/30 flex items-center justify-center hover:scale-105 transition-transform active:scale-95">
                    <span class="material-symbols-rounded text-primary">person</span>
                </button>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 lg:py-8 space-y-6">

            <!-- Desktop Welcome Section -->
            <div class="hidden lg:block">
                <h1 class="text-3xl font-bold mb-1">Welcome back, {{ $user->name }}!</h1>
                <p class="text-gray-400">Here's your learning dashboard overview</p>
            </div>

            <!-- Status Card - Premium Design -->
            <div class="glass-strong rounded-2xl p-5 sm:p-6 gradient-border">
                <div class="flex items-start justify-between mb-5">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl {{ ($user->is_active && $user->plan_id) ? 'bg-gradient-to-br from-green-400 to-emerald-500' : 'bg-gradient-to-br from-yellow-400 to-orange-500' }} flex items-center justify-center shadow-lg">
                                <span class="material-symbols-rounded text-white text-3xl" style="font-variation-settings: 'FILL' 1">
                                    {{ ($user->is_active && $user->plan_id) ? 'verified' : 'schedule' }}
                                </span>
                            </div>
                            @if($user->is_active && $user->plan_id)
                            <div class="absolute -top-1 -right-1 w-5 h-5 bg-green-400 rounded-full border-2 border-background-dark animate-bounce-subtle"></div>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm text-gray-400 mb-0.5">Account Status</p>
                            <p class="text-xl sm:text-2xl font-bold {{ ($user->is_active && $user->plan_id) ? 'text-green-400' : 'text-yellow-400' }}">
                                {{ ($user->is_active && $user->plan_id) ? 'Active' : 'Inactive' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400 mb-1">Current Plan</p>
                        <p class="text-sm sm:text-base font-bold bg-gradient-to-r from-primary to-cyan-500 bg-clip-text text-transparent">
                            {{ $user->userPlan->name ?? 'Free Tier' }}
                        </p>
                    </div>
                </div>

                <!-- Token Progress -->
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-400 font-medium">Token Usage</span>
                        <span class="font-bold">
                            <span class="text-primary">{{ number_format($user->tokens_used) }}</span>
                            <span class="text-gray-500">/</span>
                            <span class="text-gray-300">{{ number_format($user->token_limit) }}</span>
                        </span>
                    </div>
                    <div class="relative w-full bg-surface-dark rounded-full h-3 overflow-hidden border border-white/5">
                        <div class="absolute inset-0 bg-gradient-to-r from-primary/20 to-cyan-500/20"></div>
                        <div class="relative bg-gradient-to-r from-primary to-cyan-500 rounded-full h-full transition-all duration-700 ease-out shadow-lg shadow-primary/20"
                             style="width: {{ $user->token_limit > 0 ? min(($user->tokens_used / $user->token_limit) * 100, 100) : 0 }}%">
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>0%</span>
                        <span>{{ $user->token_limit > 0 ? number_format(min(($user->tokens_used / $user->token_limit) * 100, 100), 1) : 0 }}% used</span>
                        <span>100%</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Grid -->
            <div>
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-3 px-1">Quick Actions</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <!-- AI Chat -->
                    <a href="{{ route('chat') }}" class="group glass rounded-2xl p-4 sm:p-5 hover:bg-white/5 transition-all active:scale-95">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-primary/20 to-primary/10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform border border-primary/20">
                            <span class="material-symbols-rounded text-primary text-2xl sm:text-3xl">chat_bubble</span>
                        </div>
                        <p class="text-sm sm:text-base font-semibold mb-0.5">AI Chat</p>
                        <p class="text-xs text-gray-400">Ask anything</p>
                    </a>

                    <!-- Upgrade Plan -->
                    <a href="{{ route('plans') }}" class="group glass rounded-2xl p-4 sm:p-5 hover:bg-white/5 transition-all active:scale-95">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-purple-500/20 to-purple-500/10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform border border-purple-500/20">
                            <span class="material-symbols-rounded text-purple-400 text-2xl sm:text-3xl">workspace_premium</span>
                        </div>
                        <p class="text-sm sm:text-base font-semibold mb-0.5">Upgrade</p>
                        <p class="text-xs text-gray-400">Premium plans</p>
                    </a>

                    <!-- Profile -->
                    <button onclick="toggleProfile()" class="group glass rounded-2xl p-4 sm:p-5 hover:bg-white/5 transition-all active:scale-95 text-left">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-orange-500/20 to-orange-500/10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform border border-orange-500/20">
                            <span class="material-symbols-rounded text-orange-400 text-2xl sm:text-3xl">person</span>
                        </div>
                        <p class="text-sm sm:text-base font-semibold mb-0.5">Profile</p>
                        <p class="text-xs text-gray-400">Your settings</p>
                    </button>
                </div>
            </div>

            <!-- AI Models Access -->
            <div class="glass rounded-2xl p-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base sm:text-lg font-bold">Available AI Models</h2>
                    <span class="text-xs text-gray-500 bg-white/5 px-2 py-1 rounded-full">
                        {{ collect([$user->can_use_gpt4, $user->can_use_claude, $user->can_use_deepseek, $user->can_use_grok])->filter()->count() }} Active
                    </span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @if($user->can_use_gpt4)
                    <div class="flex items-center gap-2.5 px-3 py-2.5 bg-green-500/10 rounded-xl border border-green-500/20 animate-fade-in">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-sm font-medium text-green-400">GPT-4</span>
                    </div>
                    @endif
                    @if($user->can_use_claude)
                    <div class="flex items-center gap-2.5 px-3 py-2.5 bg-orange-500/10 rounded-xl border border-orange-500/20 animate-fade-in">
                        <div class="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></div>
                        <span class="text-sm font-medium text-orange-400">Claude</span>
                    </div>
                    @endif
                    @if($user->can_use_deepseek)
                    <div class="flex items-center gap-2.5 px-3 py-2.5 bg-blue-500/10 rounded-xl border border-blue-500/20 animate-fade-in">
                        <div class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></div>
                        <span class="text-sm font-medium text-blue-400">DeepSeek</span>
                    </div>
                    @endif
                    @if($user->can_use_grok)
                    <div class="flex items-center gap-2.5 px-3 py-2.5 bg-purple-500/10 rounded-xl border border-purple-500/20 animate-fade-in">
                        <div class="w-2 h-2 bg-purple-400 rounded-full animate-pulse"></div>
                        <span class="text-sm font-medium text-purple-400">Grok</span>
                    </div>
                    @endif
                    @if(!$user->can_use_gpt4 && !$user->can_use_claude && !$user->can_use_deepseek && !$user->can_use_grok)
                    <div class="col-span-2 sm:col-span-3 lg:col-span-4 text-center py-8">
                        <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gray-500/10 flex items-center justify-center">
                            <span class="material-symbols-rounded text-gray-500 text-3xl">lock</span>
                        </div>
                        <p class="text-sm text-gray-400 mb-2">No AI models available</p>
                        <a href="{{ route('plans') }}" class="text-xs text-primary hover:text-primary-dark font-medium inline-flex items-center gap-1 transition-colors">
                            Get a plan to unlock
                            <span class="material-symbols-rounded text-sm">arrow_forward</span>
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Recent Payments -->
            @if($payments->count() > 0)
            <div class="glass rounded-2xl p-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base sm:text-lg font-bold">Recent Payments</h2>
                    <button onclick="togglePayments()" class="lg:hidden text-xs text-primary font-medium flex items-center gap-1">
                        <span id="toggleText">Show</span>
                        <span class="material-symbols-rounded text-sm">expand_more</span>
                    </button>
                </div>

                <div id="paymentsTable" class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-800">
                            <tr>
                                <th class="text-left py-3 text-xs text-gray-400 font-semibold uppercase tracking-wide">Date</th>
                                <th class="text-left py-3 text-xs text-gray-400 font-semibold uppercase tracking-wide">Plan</th>
                                <th class="text-left py-3 text-xs text-gray-400 font-semibold uppercase tracking-wide">Amount</th>
                                <th class="text-left py-3 text-xs text-gray-400 font-semibold uppercase tracking-wide">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/50">
                            @foreach($payments->take(5) as $payment)
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="py-3.5 text-sm text-gray-300">{{ $payment->created_at->format('M d, Y') }}</td>
                                <td class="py-3.5 text-sm font-medium">{{ $payment->plan->name ?? 'N/A' }}</td>
                                <td class="py-3.5 text-sm font-bold text-primary">₹{{ number_format($payment->amount) }}</td>
                                <td class="py-3.5">
                                    @if($payment->status === 'completed')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-500/10 text-green-400 rounded-lg text-xs font-semibold border border-green-500/20">
                                        <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                                        Paid
                                    </span>
                                    @elseif($payment->status === 'verifying')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-yellow-500/10 text-yellow-400 rounded-lg text-xs font-semibold border border-yellow-500/20">
                                        <span class="w-1.5 h-1.5 bg-yellow-400 rounded-full animate-pulse"></span>
                                        Pending
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-500/10 text-red-400 rounded-lg text-xs font-semibold border border-red-500/20">
                                        <span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span>
                                        Failed
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </main>

    <!-- Profile Modal -->
    <div id="profileModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 animate-fade-in" onclick="toggleProfile()">
        <div class="absolute bottom-0 left-0 right-0 lg:top-1/2 lg:left-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2 lg:max-w-md lg:w-full lg:bottom-auto glass-strong rounded-t-3xl lg:rounded-2xl animate-slide-up modal-content" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="sticky top-0 glass-strong border-b border-white/10 rounded-t-3xl lg:rounded-t-2xl px-6 py-4 flex items-center justify-between">
                <h2 class="text-xl font-bold">Profile</h2>
                <button onclick="toggleProfile()" class="w-9 h-9 rounded-full hover:bg-white/10 flex items-center justify-center transition-colors active:scale-95">
                    <span class="material-symbols-rounded text-gray-400">close</span>
                </button>
            </div>

            <div class="p-6 space-y-6">
                <!-- Profile Info -->
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary to-cyan-500 flex items-center justify-center">
                        <span class="text-2xl font-bold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-lg" id="displayName">{{ $user->name }}</p>
                        <p class="text-sm text-gray-400" id="displayEmail">{{ $user->email }}</p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="glass rounded-xl p-4">
                        <p class="text-xs text-gray-400 mb-1">Member Since</p>
                        <p class="text-sm font-semibold">{{ $user->created_at->format('M Y') }}</p>
                    </div>
                    <div class="glass rounded-xl p-4">
                        <p class="text-xs text-gray-400 mb-1">Class Level</p>
                        <p class="text-sm font-semibold">{{ $user->class_level ?? 'Not set' }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="space-y-2">
                    <button onclick="showEditProfile()" class="w-full flex items-center justify-center gap-2 px-4 py-3.5 bg-primary text-black rounded-xl hover:bg-primary-dark transition-all font-semibold active:scale-98">
                        <span class="material-symbols-rounded">edit</span>
                        <span>Edit Profile</span>
                    </button>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-3.5 bg-red-500/10 text-red-400 border border-red-500/20 rounded-xl hover:bg-red-500/20 transition-all font-semibold active:scale-98">
                            <span class="material-symbols-rounded">logout</span>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 animate-fade-in" onclick="toggleEditProfile()">
        <div class="absolute bottom-0 left-0 right-0 lg:top-1/2 lg:left-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2 lg:max-w-md lg:w-full lg:bottom-auto glass-strong rounded-t-3xl lg:rounded-2xl animate-slide-up modal-content" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="sticky top-0 glass-strong border-b border-white/10 rounded-t-3xl lg:rounded-t-2xl px-6 py-4 flex items-center justify-between">
                <h2 class="text-xl font-bold">Edit Profile</h2>
                <button onclick="toggleEditProfile()" class="w-9 h-9 rounded-full hover:bg-white/10 flex items-center justify-center transition-colors active:scale-95">
                    <span class="material-symbols-rounded text-gray-400">close</span>
                </button>
            </div>

            <div class="p-6 space-y-6">
                <!-- Profile Info Form -->
                <form id="profileForm" onsubmit="updateProfile(event)" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">Full Name</label>
                        <input type="text" name="name" value="{{ $user->name }}" required
                               class="w-full px-4 py-3 bg-surface-dark border border-gray-700 rounded-xl text-white placeholder-gray-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">Email Address</label>
                        <input type="email" name="email" value="{{ $user->email }}" required
                               class="w-full px-4 py-3 bg-surface-dark border border-gray-700 rounded-xl text-white placeholder-gray-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">Class Level</label>
                        <select name="class_level"
                                class="w-full px-4 py-3 bg-surface-dark border border-gray-700 rounded-xl text-white transition-all">
                            <option value="">Select your class</option>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="Class {{ $i }}" {{ $user->class_level == "Class $i" ? 'selected' : '' }}>Class {{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <button type="submit" class="w-full px-4 py-3.5 bg-primary text-black font-bold rounded-xl hover:bg-primary-dark transition-all active:scale-98 flex items-center justify-center gap-2">
                        <span class="material-symbols-rounded">check</span>
                        <span>Save Changes</span>
                    </button>
                </form>

                <!-- Change Password Section -->
                <div class="border-t border-gray-700 pt-6">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-rounded text-primary">lock</span>
                        <span>Change Password</span>
                    </h3>
                    <form id="passwordForm" onsubmit="updatePassword(event)" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2 text-gray-300">Current Password</label>
                            <input type="password" name="current_password" required
                                   class="w-full px-4 py-3 bg-surface-dark border border-gray-700 rounded-xl text-white placeholder-gray-500 transition-all"
                                   placeholder="Enter current password">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2 text-gray-300">New Password</label>
                            <input type="password" name="password" required minlength="8"
                                   class="w-full px-4 py-3 bg-surface-dark border border-gray-700 rounded-xl text-white placeholder-gray-500 transition-all"
                                   placeholder="Minimum 8 characters">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2 text-gray-300">Confirm New Password</label>
                            <input type="password" name="password_confirmation" required minlength="8"
                                   class="w-full px-4 py-3 bg-surface-dark border border-gray-700 rounded-xl text-white placeholder-gray-500 transition-all"
                                   placeholder="Re-enter new password">
                        </div>

                        <button type="submit" class="w-full px-4 py-3.5 bg-blue-500 text-white font-bold rounded-xl hover:bg-blue-600 transition-all active:scale-98 flex items-center justify-center gap-2">
                            <span class="material-symbols-rounded">key</span>
                            <span>Update Password</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toast notification function
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');

            const colors = {
                success: 'from-green-500 to-emerald-500',
                error: 'from-red-500 to-rose-500',
                info: 'from-blue-500 to-cyan-500',
                warning: 'from-yellow-500 to-orange-500'
            };

            const icons = {
                success: 'check_circle',
                error: 'error',
                info: 'info',
                warning: 'warning'
            };

            toast.className = 'glass-strong rounded-xl p-4 shadow-lg border border-white/10 flex items-center gap-3 animate-slide-down';
            toast.innerHTML = `
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br ${colors[type]} flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-rounded text-white">${icons[type]}</span>
                </div>
                <p class="flex-1 text-sm font-medium">${message}</p>
                <button onclick="this.parentElement.remove()" class="w-8 h-8 rounded-lg hover:bg-white/10 flex items-center justify-center transition-colors">
                    <span class="material-symbols-rounded text-gray-400 text-lg">close</span>
                </button>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-20px)';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        function toggleProfile() {
            const modal = document.getElementById('profileModal');
            modal.classList.toggle('hidden');
            if (!modal.classList.contains('hidden')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        function toggleEditProfile() {
            const modal = document.getElementById('editProfileModal');
            modal.classList.toggle('hidden');
            if (!modal.classList.contains('hidden')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        function showEditProfile() {
            document.getElementById('profileModal').classList.add('hidden');
            document.getElementById('editProfileModal').classList.remove('hidden');
        }

        function togglePayments() {
            const table = document.getElementById('paymentsTable');
            const text = document.getElementById('toggleText');
            table.classList.toggle('hidden');
            text.textContent = table.classList.contains('hidden') ? 'Show' : 'Hide';
        }

        async function updateProfile(event) {
            event.preventDefault();

            const form = event.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-rounded animate-spin">progress_activity</span><span>Saving...</span>';

            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            try {
                const response = await fetch('{{ route("profile.update") }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    document.getElementById('displayName').textContent = result.user.name;
                    document.getElementById('displayEmail').textContent = result.user.email;

                    showToast('Profile updated successfully!', 'success');

                    toggleEditProfile();

                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message || 'Failed to update profile', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred while updating profile', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }

        async function updatePassword(event) {
            event.preventDefault();

            const form = event.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            if (data.password !== data.password_confirmation) {
                showToast('New passwords do not match!', 'error');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-rounded animate-spin">progress_activity</span><span>Updating...</span>';

            try {
                const response = await fetch('{{ route("profile.password") }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Password updated successfully!', 'success');
                    form.reset();
                    setTimeout(() => toggleEditProfile(), 1500);
                } else {
                    showToast(result.message || 'Failed to update password', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred while updating password', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }

        // Close modals on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.getElementById('profileModal').classList.add('hidden');
                document.getElementById('editProfileModal').classList.add('hidden');
                document.body.style.overflow = '';
            }
        });

        // Prevent body scroll when modal is open
        const modals = document.querySelectorAll('[id$="Modal"]');
        modals.forEach(modal => {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        if (modal.classList.contains('hidden')) {
                            document.body.style.overflow = '';
                        }
                    }
                });
            });
            observer.observe(modal, { attributes: true });
        });
    </script>

</body>
</html>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteLogo = \App\Models\HomepageSetting::getValue('site_logo');
        $siteName = \App\Models\HomepageSetting::getValue('site_name', config('app.name', 'BlinkStudy'));
        $logoIcon = \App\Models\HomepageSetting::getValue('logo_icon', 'school');
    @endphp
    <title>Login - {{ $siteName }}</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#0D9488",
                        secondary: "#F59E0B",
                        "background-light": "#F0FDFA",
                        "background-dark": "#0F172A",
                        "primary-text": "#0F172A",
                        "accent-soft": "#99F6E4",
                    },
                    fontFamily: {
                        display: ["Outfit", "sans-serif"],
                        sans: ["Outfit", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "1rem",
                        'xl': '1.5rem',
                        '2xl': '2rem',
                    },
                },
            },
        };
    </script>
    <style>
        * {
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Outfit', sans-serif;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(13, 148, 136, 0.1);
        }

        .dark .glass-effect {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(153, 246, 228, 0.1);
        }

        .input-field {
            background: rgba(13, 148, 136, 0.05);
            border: 2px solid rgba(13, 148, 136, 0.1);
            transition: all 0.3s ease;
        }

        .input-field:focus {
            background: rgba(13, 148, 136, 0.08);
            border-color: rgba(13, 148, 136, 0.4);
            outline: none;
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
        }

        .dark .input-field {
            background: rgba(153, 246, 228, 0.03);
            border-color: rgba(153, 246, 228, 0.1);
        }

        .dark .input-field:focus {
            background: rgba(153, 246, 228, 0.05);
            border-color: rgba(153, 246, 228, 0.3);
            box-shadow: 0 0 0 4px rgba(153, 246, 228, 0.05);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0D9488 0%, #14B8A6 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(13, 148, 136, 0.3);
        }

        .btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .otp-boxes {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }

        .otp-box {
            width: 3.5rem;
            height: 3.5rem;
            font-size: 1.5rem;
            text-align: center;
            background: rgba(13, 148, 136, 0.05);
            border: 2px solid rgba(13, 148, 136, 0.15);
            border-radius: 1rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .dark .otp-box {
            background: rgba(153, 246, 228, 0.03);
            border-color: rgba(153, 246, 228, 0.1);
        }

        .otp-box:focus {
            background: rgba(13, 148, 136, 0.08);
            border-color: #0D9488;
            outline: none;
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
            transform: scale(1.05);
        }

        .dark .otp-box:focus {
            background: rgba(153, 246, 228, 0.05);
            border-color: #99F6E4;
            box-shadow: 0 0 0 4px rgba(153, 246, 228, 0.05);
        }

        @media (max-width: 640px) {
            .otp-box {
                width: 3rem;
                height: 3rem;
                font-size: 1.25rem;
            }
            .otp-boxes {
                gap: 0.5rem;
            }
        }

        .fade-in {
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-gradient {
            background: radial-gradient(circle at top right, #99F6E4 0%, transparent 50%),
                        radial-gradient(circle at bottom left, #F0FDFA 0%, transparent 50%);
        }

        .dark .hero-gradient {
            background: radial-gradient(circle at top right, rgba(13, 148, 136, 0.3) 0%, transparent 50%),
                        radial-gradient(circle at bottom left, rgba(15, 23, 42, 0.8) 0%, transparent 50%);
        }

        .social-btn {
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(13, 148, 136, 0.2);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-background-light dark:bg-background-dark text-primary-text dark:text-white transition-colors duration-300 hero-gradient">
    <div class="w-full max-w-md">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full hover:bg-white/50 dark:hover:bg-slate-800/50 transition-all text-primary-text dark:text-white text-sm font-medium">
                <span class="material-icons-round text-lg">arrow_back</span>
                <span>Back to Home</span>
            </a>
        </div>

        <!-- Login Card -->
        <div class="glass-effect rounded-3xl p-8 sm:p-10 shadow-2xl fade-in">
            <!-- Logo & Header -->
            <div id="login-header" class="text-center mb-8">
                <div class="flex items-center justify-center gap-2 mb-4">
                    @if($siteLogo)
                        <img src="{{ asset($siteLogo) }}" alt="{{ $siteName }}" class="w-12 h-12 rounded-xl object-contain">
                    @else
                        <div class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center text-white">
                            <span class="material-icons-round text-2xl">{{ $logoIcon }}</span>
                        </div>
                    @endif
                </div>
                <h1 class="text-3xl font-bold text-primary-text dark:text-white mb-2">
                    Welcome to <span class="text-primary">{{ $siteName }}</span>
                </h1>
                <p class="text-slate-600 dark:text-slate-400 text-base">Enter your mobile number to continue</p>
            </div>

            <!-- Alert Messages -->
            @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800/50 rounded-2xl text-sm text-red-600 dark:text-red-300 font-medium">
                {{ session('error') }}
            </div>
            @endif

            @if(session('success'))
            <div class="mb-6 p-4 bg-teal-50 dark:bg-teal-900/20 border-2 border-teal-200 dark:border-teal-800/50 rounded-2xl text-sm text-teal-600 dark:text-teal-300 font-medium">
                {{ session('success') }}
            </div>
            @endif

            <div id="message-container" class="hidden mb-6"></div>

            <!-- Google Login (if enabled) -->
            @if($googleLoginEnabled)
            <div id="google-login-section">
                <div class="flex justify-center mb-6">
                    <a href="{{ route('google.redirect') }}" class="social-btn w-16 h-16 rounded-2xl bg-white dark:bg-slate-800 border-2 border-teal-100 dark:border-slate-700 hover:border-primary dark:hover:border-primary flex items-center justify-center shadow-lg">
                        <svg width="28" height="28" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                    </a>
                </div>

                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t-2 border-slate-200 dark:border-slate-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-400 font-medium">OR</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Login Form Container -->
            <div id="login-container">
                <!-- Step 1: Mobile Number Input -->
                <div id="step-1" class="space-y-6 fade-in">
                    <div>
                        <label class="block text-sm font-semibold text-primary-text dark:text-white mb-3">Mobile Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-primary dark:text-accent-soft text-base font-semibold">+91</span>
                            </div>
                            <input
                                type="tel"
                                id="mobile-number"
                                placeholder="0000000000"
                                maxlength="10"
                                class="input-field w-full pl-16 pr-4 py-3.5 rounded-2xl text-primary-text dark:text-white placeholder-slate-400 text-base font-medium"
                                autocomplete="tel"
                                onkeypress="return isNumber(event)"
                                onkeydown="handleMobileKeyPress(event)"
                                autofocus
                            />
                        </div>
                        <p id="input-error" class="mt-2 text-sm text-red-500 font-medium hidden"></p>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 flex items-center gap-1">
                            <span class="material-icons-round text-sm">info</span>
                            We'll send you a 4-digit OTP
                        </p>
                    </div>

                    <!-- OTP Method Selection -->
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-primary-text dark:text-white">Get OTP via</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" id="method-sms" onclick="selectOtpMethod('sms')"
                                class="otp-method-btn flex items-center justify-center gap-2 py-3 px-4 rounded-xl border-2 border-primary bg-primary/10 text-primary font-semibold transition-all">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.2L4 17.2V4h16v12z"/><path d="M7 9h10v2H7zm0-3h10v2H7z"/></svg>
                                SMS
                            </button>
                            <button type="button" id="method-whatsapp" onclick="selectOtpMethod('whatsapp')"
                                class="otp-method-btn flex items-center justify-center gap-2 py-3 px-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 font-semibold transition-all hover:border-green-500 hover:text-green-500">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WhatsApp
                            </button>
                        </div>
                        <input type="hidden" id="otp-method" value="sms">
                    </div>

                    <button
                        type="button"
                        id="send-otp-btn"
                        onclick="sendOTP()"
                        class="btn-primary w-full py-4 px-6 rounded-2xl text-white text-base font-bold shadow-lg hover:shadow-xl transition-all"
                    >
                        Continue
                    </button>
                </div>

                <!-- Step 2: OTP Verification -->
                <div id="step-2-verify" class="space-y-6 hidden fade-in">
                    <!-- Back button -->
                    <button type="button" onclick="backToStep1()" class="inline-flex items-center gap-1.5 text-sm text-primary dark:text-accent-soft hover:underline font-medium transition-colors">
                        <span class="material-icons-round text-sm">arrow_back</span>
                        Change number
                    </button>
                    <div>
                        <label class="block text-base font-semibold text-primary-text dark:text-white mb-4 text-center">Enter OTP</label>
                        <div class="otp-boxes mb-2">
                            <input type="text" maxlength="1" class="otp-box text-primary-text dark:text-white" onkeypress="return isNumber(event)" onkeyup="moveToNext(this, event)" onkeydown="moveToPrev(this, event)" />
                            <input type="text" maxlength="1" class="otp-box text-primary-text dark:text-white" onkeypress="return isNumber(event)" onkeyup="moveToNext(this, event)" onkeydown="moveToPrev(this, event)" />
                            <input type="text" maxlength="1" class="otp-box text-primary-text dark:text-white" onkeypress="return isNumber(event)" onkeyup="moveToNext(this, event)" onkeydown="moveToPrev(this, event)" />
                            <input type="text" maxlength="1" class="otp-box text-primary-text dark:text-white" onkeypress="return isNumber(event)" onkeyup="moveToNext(this, event)" onkeydown="moveToPrev(this, event)" />
                        </div>
                        <p class="text-center text-sm text-slate-500 dark:text-slate-400">
                            Sent to +91 <span id="display-mobile" class="font-semibold"></span>
                        </p>
                    </div>

                    <button
                        type="button"
                        id="verify-otp-btn"
                        onclick="verifyOTP()"
                        class="btn-primary w-full py-4 px-6 rounded-2xl text-white text-base font-bold shadow-lg hover:shadow-xl transition-all"
                    >
                        Verify & Login
                    </button>

                    <div class="text-center">
                        <button type="button" onclick="resendOTP()" class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-accent-soft transition-colors font-medium" id="resend-btn">
                            Didn't receive code? <span class="text-primary dark:text-accent-soft font-bold">Resend</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-slate-500 dark:text-slate-400 space-x-3">
            <a href="{{ route('home') }}" class="hover:text-primary dark:hover:text-accent-soft transition-colors font-medium">Terms</a>
            <span>•</span>
            <a href="{{ route('privacy') }}" class="hover:text-primary dark:hover:text-accent-soft transition-colors font-medium">Privacy</a>
            <span>•</span>
            <button onclick="document.documentElement.classList.toggle('dark')" class="hover:text-primary dark:hover:text-accent-soft transition-colors font-medium">
                <span class="dark:hidden">Dark Mode</span>
                <span class="hidden dark:inline">Light Mode</span>
            </button>
        </div>
    </div>

    <script>
        let currentMobile = '';
        let selectedOtpMethod = 'sms';
        // Setup CSRF token for AJAX requests
        let csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // OTP Method Selection
        function selectOtpMethod(method) {
            selectedOtpMethod = method;
            document.getElementById('otp-method').value = method;

            const smsBtn = document.getElementById('method-sms');
            const whatsappBtn = document.getElementById('method-whatsapp');

            if (method === 'sms') {
                smsBtn.className = 'otp-method-btn flex items-center justify-center gap-2 py-3 px-4 rounded-xl border-2 border-primary bg-primary/10 text-primary font-semibold transition-all';
                whatsappBtn.className = 'otp-method-btn flex items-center justify-center gap-2 py-3 px-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 font-semibold transition-all hover:border-green-500 hover:text-green-500';
            } else {
                whatsappBtn.className = 'otp-method-btn flex items-center justify-center gap-2 py-3 px-4 rounded-xl border-2 border-green-500 bg-green-500/10 text-green-600 font-semibold transition-all';
                smsBtn.className = 'otp-method-btn flex items-center justify-center gap-2 py-3 px-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 font-semibold transition-all hover:border-primary hover:text-primary';
            }
        }

        // Function to refresh CSRF token
        function refreshCsrfToken() {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                csrfToken = metaTag.content;
            }
        }

        function showMessage(message, type = 'error') {
            const container = document.getElementById('message-container');
            container.className = `mb-6 p-4 border-2 rounded-2xl text-sm font-medium ${
                type === 'success'
                    ? 'bg-teal-50 dark:bg-teal-900/20 border-teal-200 dark:border-teal-800/50 text-teal-600 dark:text-teal-300'
                    : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800/50 text-red-600 dark:text-red-300'
            }`;
            container.textContent = message;
            container.classList.remove('hidden');
        }

        function hideMessage() {
            document.getElementById('message-container').classList.add('hidden');
        }

        function showError(message) {
            const errorEl = document.getElementById('input-error');
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }

        function hideError() {
            document.getElementById('input-error').classList.add('hidden');
        }

        function isNumber(evt) {
            const charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }

        function isMobile(str) {
            return /^[6-9]\d{9}$/.test(str);
        }

        function handleMobileKeyPress(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendOTP();
            }
        }

        // OTP box navigation
        function moveToNext(current, event) {
            if (current.value.length === 1) {
                const next = current.nextElementSibling;
                if (next && next.tagName === 'INPUT') {
                    next.focus();
                    next.select();
                }
            }

            // Auto-submit when all 4 boxes are filled
            const boxes = document.querySelectorAll('.otp-box');
            const allFilled = Array.from(boxes).every(box => box.value.length === 1);
            if (allFilled) {
                setTimeout(() => verifyOTP(), 300);
            }
        }

        function moveToPrev(current, event) {
            if (event.key === 'Backspace' && current.value === '') {
                const prev = current.previousElementSibling;
                if (prev && prev.tagName === 'INPUT') {
                    prev.focus();
                    prev.select();
                }
            }
        }

        function backToStep1() {
            // Show login header and Google login section
            const header = document.getElementById('login-header');
            const googleSection = document.getElementById('google-login-section');
            if (header) header.style.display = 'block';
            if (googleSection) googleSection.style.display = 'block';

            // Show step 1, hide step 2
            document.getElementById('step-1').classList.remove('hidden');
            document.getElementById('step-2-verify').classList.add('hidden');
            document.getElementById('mobile-number').value = '';
            document.querySelectorAll('.otp-box').forEach(box => box.value = '');
            hideError();
            hideMessage();
            document.getElementById('mobile-number').focus();
        }

        async function sendOTP() {
            hideError();
            hideMessage();

            const mobile = document.getElementById('mobile-number').value.trim();

            if (!mobile) {
                showError('Please enter your mobile number');
                return;
            }

            if (!isMobile(mobile)) {
                showError('Enter valid mobile number (10 digits, starting with 6-9)');
                return;
            }

            currentMobile = mobile;
            const sendBtn = document.getElementById('send-otp-btn');

            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';

            try {
                // Refresh CSRF token before request
                refreshCsrfToken();

                const response = await fetch('{{ route("otp.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin', // Include cookies in request/response
                    body: JSON.stringify({ mobile, otp_method: selectedOtpMethod })
                });

                // Handle CSRF token mismatch (419)
                if (response.status === 419) {
                    showMessage('Session expired. Please refresh the page and try again.');
                    setTimeout(() => window.location.reload(), 2000);
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    // Hide login header and Google login section
                    const header = document.getElementById('login-header');
                    const googleSection = document.getElementById('google-login-section');
                    if (header) header.style.display = 'none';
                    if (googleSection) googleSection.style.display = 'none';

                    // Show OTP verification step
                    document.getElementById('step-1').classList.add('hidden');
                    document.getElementById('step-2-verify').classList.remove('hidden');
                    document.getElementById('display-mobile').textContent = mobile;
                    showMessage(data.message, 'success');

                    setTimeout(() => {
                        document.querySelector('.otp-box').focus();
                    }, 100);
                } else {
                    showMessage(data.message);
                }
            } catch (error) {
                showMessage('Failed to send OTP. Please try again.');
            } finally {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Continue';
            }
        }

        async function verifyOTP() {
            hideMessage();
            const mobile = currentMobile;
            const boxes = document.querySelectorAll('.otp-box');
            const otp = Array.from(boxes).map(box => box.value).join('');
            const verifyBtn = document.getElementById('verify-otp-btn');

            if (!/^\d{4}$/.test(otp)) {
                showMessage('Please enter complete 4-digit OTP');
                boxes[0].focus();
                return;
            }

            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Verifying...';

            try {
                // Refresh CSRF token before request
                refreshCsrfToken();

                const response = await fetch('{{ route("otp.verify") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin', // CRITICAL: Include cookies in request/response
                    body: JSON.stringify({ mobile, otp })
                });

                // Handle CSRF token mismatch (419)
                if (response.status === 419) {
                    showMessage('Session expired. Please refresh the page and try again.');
                    setTimeout(() => window.location.reload(), 2000);
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    showMessage(data.message, 'success');

                    // Get redirect URL with fallback
                    const redirectUrl = data.redirect || '{{ route("chat") }}';

                    // Check if 2FA is required (admin only)
                    if (data.requires_2fa) {
                        setTimeout(() => {
                            window.location.replace(redirectUrl);
                        }, 1000);
                    } else {
                        // Use window.location.replace for full page reload to ensure session cookie is sent
                        setTimeout(() => {
                            window.location.replace(redirectUrl);
                        }, 1500); // Increased to 1.5 seconds for session persistence
                    }
                } else {
                    showMessage(data.message);
                    boxes.forEach(box => box.value = '');
                    boxes[0].focus();
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verify & Login';
                }
            } catch (error) {
                showMessage('Verification failed. Please try again.');
                verifyBtn.disabled = false;
                verifyBtn.textContent = 'Verify & Login';
            }
        }

        async function resendOTP() {
            document.querySelectorAll('.otp-box').forEach(box => box.value = '');
            hideMessage();
            const mobile = currentMobile;
            const resendBtn = document.getElementById('resend-btn');
            const originalText = resendBtn.innerHTML;

            resendBtn.innerHTML = 'Sending...';
            resendBtn.disabled = true;

            try {
                // Refresh CSRF token before request
                refreshCsrfToken();

                const response = await fetch('{{ route("otp.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin', // Include cookies in request/response
                    body: JSON.stringify({ mobile, otp_method: selectedOtpMethod })
                });

                // Handle CSRF token mismatch (419)
                if (response.status === 419) {
                    showMessage('Session expired. Please refresh the page and try again.');
                    setTimeout(() => window.location.reload(), 2000);
                    return;
                }

                const data = await response.json();
                if (data.success) {
                    showMessage('OTP sent successfully!', 'success');
                    setTimeout(() => {
                        document.querySelector('.otp-box').focus();
                    }, 100);
                } else {
                    showMessage(data.message);
                }
            } catch (error) {
                showMessage('Failed to resend OTP. Please try again.');
            } finally {
                resendBtn.disabled = false;
                resendBtn.innerHTML = originalText;
            }
        }

        // Auto-focus first OTP box when step 2 becomes visible
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class') {
                    const step2 = document.getElementById('step-2-verify');
                    if (!step2.classList.contains('hidden')) {
                        setTimeout(() => {
                            document.querySelector('.otp-box').focus();
                        }, 100);
                    }
                }
            });
        });

        observer.observe(document.getElementById('step-2-verify'), {
            attributes: true
        });
    </script>
</body>
</html>

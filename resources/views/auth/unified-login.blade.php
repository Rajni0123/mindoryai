<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteLogo = \App\Models\HomepageSetting::getValue('site_logo');
        $siteName = \App\Models\HomepageSetting::getValue('site_name', config('app.name', 'BlinkStudy'));
        $logoIcon = \App\Models\HomepageSetting::getValue('logo_icon', 'school');
    @endphp
    <title>Login - {{ $siteName }}</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
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
                    },
                    fontFamily: {
                        sans: ["Inter", "sans-serif"],
                    },
                },
            },
        };
    </script>
    <style>
        * { -webkit-tap-highlight-color: transparent; font-family: 'Inter', sans-serif; }

        .input-field {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        .input-field:focus {
            background: #fff;
            border-color: #0D9488;
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }
        .dark .input-field {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
        }
        .dark .input-field:focus {
            background: rgba(255,255,255,0.08);
            border-color: #0D9488;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0D9488 0%, #0f766e 100%);
            transition: all 0.2s ease;
        }
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .otp-box {
            width: 48px;
            height: 52px;
            font-size: 1.25rem;
            text-align: center;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.2s ease;
            font-weight: 600;
        }
        .otp-box:focus {
            background: #fff;
            border-color: #0D9488;
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }
        .dark .otp-box {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
        }
        .dark .otp-box:focus {
            background: rgba(255,255,255,0.08);
            border-color: #0D9488;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeIn 0.3s ease-out; }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 bg-gray-50 dark:bg-slate-900 text-slate-800 dark:text-white">
    <div class="w-full max-w-sm">

        <!-- Login Card -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-xl shadow-black/5 dark:shadow-black/20 fade-in">

            <!-- Logo & Header -->
            <div id="login-header" class="text-center mb-6">
                <div class="flex items-center justify-center mb-3">
                    @if($siteLogo)
                        <img src="{{ asset($siteLogo) }}" alt="{{ $siteName }}" class="w-10 h-10 rounded-xl object-contain">
                    @else
                        <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white">
                            <span class="material-icons-round text-xl">{{ $logoIcon }}</span>
                        </div>
                    @endif
                </div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white mb-1">
                    Welcome to {{ $siteName }}
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm">Enter mobile number to continue</p>
            </div>

            <!-- Alert Messages -->
            @if(session('error'))
            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-xl text-sm text-red-600 dark:text-red-400">
                {{ session('error') }}
            </div>
            @endif

            @if(session('success'))
            <div class="mb-4 p-3 bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800/50 rounded-xl text-sm text-teal-600 dark:text-teal-400">
                {{ session('success') }}
            </div>
            @endif

            <div id="message-container" class="hidden mb-4"></div>

            <!-- Login Form -->
            <div id="login-container">
                <!-- Step 1: Mobile Number -->
                <div id="step-1" class="fade-in">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Mobile Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-slate-500 text-sm font-medium">+91</span>
                            </div>
                            <input
                                type="tel"
                                id="mobile-number"
                                placeholder="9876543210"
                                maxlength="10"
                                class="input-field w-full pl-12 pr-4 py-3 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 text-base"
                                autocomplete="tel"
                                onkeypress="return isNumber(event)"
                                onkeydown="handleMobileKeyPress(event)"
                                autofocus
                            />
                        </div>
                        <p id="input-error" class="mt-1.5 text-xs text-red-500 hidden"></p>
                    </div>

                    <input type="hidden" id="otp-method" value="sms">

                    <button
                        type="button"
                        id="send-otp-btn"
                        onclick="sendOTP()"
                        class="btn-primary w-full py-3 rounded-xl text-white text-sm font-semibold"
                    >
                        Get OTP
                    </button>

                    <p class="mt-3 text-center text-xs text-slate-400">
                        We'll send a 4-digit code to verify
                    </p>
                </div>

                <!-- Step 2: OTP Verification -->
                <div id="step-2-verify" class="hidden fade-in">
                    <button type="button" onclick="backToStep1()" class="inline-flex items-center gap-1 text-sm text-primary hover:underline mb-4">
                        <span class="material-icons-round text-base">arrow_back</span>
                        Change number
                    </button>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3 text-center">Enter OTP</label>
                        <div class="flex gap-3 justify-center mb-2">
                            <input type="text" maxlength="1" class="otp-box text-slate-800 dark:text-white" onkeypress="return isNumber(event)" onkeyup="moveToNext(this, event)" onkeydown="moveToPrev(this, event)" />
                            <input type="text" maxlength="1" class="otp-box text-slate-800 dark:text-white" onkeypress="return isNumber(event)" onkeyup="moveToNext(this, event)" onkeydown="moveToPrev(this, event)" />
                            <input type="text" maxlength="1" class="otp-box text-slate-800 dark:text-white" onkeypress="return isNumber(event)" onkeyup="moveToNext(this, event)" onkeydown="moveToPrev(this, event)" />
                            <input type="text" maxlength="1" class="otp-box text-slate-800 dark:text-white" onkeypress="return isNumber(event)" onkeyup="moveToNext(this, event)" onkeydown="moveToPrev(this, event)" />
                        </div>
                        <p class="text-center text-xs text-slate-500">
                            Sent to +91 <span id="display-mobile" class="font-medium"></span>
                        </p>
                    </div>

                    <button
                        type="button"
                        id="verify-otp-btn"
                        onclick="verifyOTP()"
                        class="btn-primary w-full py-3 rounded-xl text-white text-sm font-semibold"
                    >
                        Verify & Login
                    </button>

                    <div class="text-center mt-3">
                        <button type="button" onclick="resendOTP()" class="text-xs text-slate-500 hover:text-primary" id="resend-btn">
                            Didn't receive? <span class="text-primary font-medium">Resend</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-4 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary">
                <span class="material-icons-round text-base">arrow_back</span>
                Back to Home
            </a>
        </div>
    </div>

    <script>
        let currentMobile = '';
        let csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function refreshCsrfToken() {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) csrfToken = metaTag.content;
        }

        function showMessage(message, type = 'error') {
            const container = document.getElementById('message-container');
            container.className = `mb-4 p-3 border rounded-xl text-sm ${
                type === 'success'
                    ? 'bg-teal-50 dark:bg-teal-900/20 border-teal-200 dark:border-teal-800/50 text-teal-600 dark:text-teal-400'
                    : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800/50 text-red-600 dark:text-red-400'
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
            return !(charCode > 31 && (charCode < 48 || charCode > 57));
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

        function moveToNext(current, event) {
            if (current.value.length === 1) {
                const next = current.nextElementSibling;
                if (next && next.tagName === 'INPUT') {
                    next.focus();
                    next.select();
                }
            }
            const boxes = document.querySelectorAll('.otp-box');
            const allFilled = Array.from(boxes).every(box => box.value.length === 1);
            if (allFilled) setTimeout(() => verifyOTP(), 300);
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
            document.getElementById('login-header').style.display = 'block';
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
                showError('Enter valid 10-digit mobile number');
                return;
            }

            currentMobile = mobile;
            const sendBtn = document.getElementById('send-otp-btn');

            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';

            try {
                refreshCsrfToken();
                const response = await fetch('{{ route("otp.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ mobile, otp_method: 'sms' })
                });

                if (response.status === 419) {
                    showMessage('Session expired. Refreshing...');
                    setTimeout(() => window.location.reload(), 1500);
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    document.getElementById('login-header').style.display = 'none';
                    document.getElementById('step-1').classList.add('hidden');
                    document.getElementById('step-2-verify').classList.remove('hidden');
                    document.getElementById('display-mobile').textContent = mobile;
                    showMessage(data.message, 'success');
                    setTimeout(() => document.querySelector('.otp-box').focus(), 100);
                } else {
                    showMessage(data.message);
                }
            } catch (error) {
                showMessage('Failed to send OTP. Please try again.');
            } finally {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Get OTP';
            }
        }

        async function verifyOTP() {
            hideMessage();
            const mobile = currentMobile;
            const boxes = document.querySelectorAll('.otp-box');
            const otp = Array.from(boxes).map(box => box.value).join('');
            const verifyBtn = document.getElementById('verify-otp-btn');

            if (!/^\d{4}$/.test(otp)) {
                showMessage('Enter complete 4-digit OTP');
                boxes[0].focus();
                return;
            }

            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Verifying...';

            try {
                refreshCsrfToken();
                const response = await fetch('{{ route("otp.verify") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ mobile, otp })
                });

                if (response.status === 419) {
                    showMessage('Session expired. Refreshing...');
                    setTimeout(() => window.location.reload(), 1500);
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    showMessage(data.message, 'success');
                    const redirectUrl = data.redirect || '{{ route("chat") }}';
                    setTimeout(() => window.location.replace(redirectUrl), 1000);
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
                refreshCsrfToken();
                const response = await fetch('{{ route("otp.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ mobile, otp_method: 'sms' })
                });

                if (response.status === 419) {
                    showMessage('Session expired. Refreshing...');
                    setTimeout(() => window.location.reload(), 1500);
                    return;
                }

                const data = await response.json();
                if (data.success) {
                    showMessage('OTP sent!', 'success');
                    setTimeout(() => document.querySelector('.otp-box').focus(), 100);
                } else {
                    showMessage(data.message);
                }
            } catch (error) {
                showMessage('Failed to resend OTP.');
            } finally {
                resendBtn.disabled = false;
                resendBtn.innerHTML = originalText;
            }
        }
    </script>
</body>
</html>

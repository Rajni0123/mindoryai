<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>

    <style>
        * {
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #000;
        }

        .login-container {
            background: rgba(20, 20, 20, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .input-field {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-field:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(13, 242, 89, 0.5);
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 242, 89, 0.1);
        }

        .btn-primary {
            background: linear-gradient(90deg, #0df259 0%, #06b6d4 50%, #0df259 100%) !important;
            background-size: 200% 100%;
            transition: all 0.3s ease;
        }

        .btn-primary:hover:not(:disabled) {
            background-position: 100% 0;
            box-shadow: 0 0 25px rgba(13, 242, 89, 0.4);
        }

        .btn-primary:active:not(:disabled) {
            transform: scale(0.98);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .back-btn {
            transition: all 0.2s ease;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .social-btn {
            transition: all 0.2s ease;
        }

        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .otp-boxes {
            display: flex;
            gap: 0.625rem;
            justify-content: center;
        }

        .otp-box {
            width: 3rem;
            height: 3rem;
            font-size: 1.25rem;
            text-align: center;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .otp-box:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(13, 242, 89, 0.5);
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 242, 89, 0.1);
        }

        @media (max-width: 640px) {
            .otp-box {
                width: 2.75rem;
                height: 2.75rem;
                font-size: 1.125rem;
            }
            .otp-boxes {
                gap: 0.5rem;
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="w-full max-w-md">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('home') }}" class="back-btn inline-flex items-center gap-2 px-3 py-2 rounded-lg text-gray-400 hover:text-white text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                <span>Back to Home</span>
            </a>
        </div>

        <!-- Login Card -->
        <div class="login-container rounded-2xl p-6 sm:p-7">
            <!-- Header -->
            <div id="login-header" class="text-center mb-6">
                <h1 class="text-xl sm:text-2xl font-semibold text-white mb-1.5">
                    Login to <span style="background: linear-gradient(90deg, #0df259 0%, #06b6d4 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Mindory</span>
                </h1>
                <p class="text-gray-400 text-sm">Enter your mobile number to continue</p>
            </div>

            <!-- Alert Messages -->
            @if(session('error'))
            <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-sm text-red-300">
                {{ session('error') }}
            </div>
            @endif

            @if(session('success'))
            <div class="mb-4 p-3 bg-green-500/10 border border-green-500/20 rounded-lg text-sm text-green-300">
                {{ session('success') }}
            </div>
            @endif

            <div id="message-container" class="hidden mb-4"></div>

            <!-- Google Login (if enabled) -->
            @if($googleLoginEnabled)
            <div id="google-login-section">
                <div class="flex justify-center mb-6">
                    <a href="{{ route('google.redirect') }}" class="social-btn w-14 h-14 rounded-full bg-white hover:bg-gray-50 flex items-center justify-center shadow-lg transition-all">
                        <svg width="24" height="24" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                    </a>
                </div>

                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-800"></div>
                    </div>
                    <div class="relative flex justify-center text-xs">
                        <span class="px-2 bg-[#141414] text-gray-500">OR</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Login Form Container -->
            <div id="login-container">
                <!-- Step 1: Mobile Number Input -->
                <div id="step-1" class="space-y-4 fade-in">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Mobile Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-sm font-medium">+91</span>
                            </div>
                            <input
                                type="tel"
                                id="mobile-number"
                                placeholder="0000000000"
                                maxlength="10"
                                class="input-field w-full pl-14 pr-4 py-2.5 rounded-lg text-white placeholder-gray-600 text-sm"
                                autocomplete="tel"
                                onkeypress="return isNumber(event)"
                                onkeydown="handleMobileKeyPress(event)"
                                autofocus
                            />
                        </div>
                        <p id="input-error" class="mt-1.5 text-xs text-red-400 hidden"></p>
                    </div>

                    <button
                        type="button"
                        id="send-otp-main-btn"
                        onclick="sendOTPDirect()"
                        class="btn-primary w-full py-2.5 px-4 rounded-lg text-white text-sm font-medium shadow-lg"
                        style="background: linear-gradient(90deg, #0df259 0%, #06b6d4 50%, #0df259 100%) !important; background-size: 200% 100%;"
                    >
                        Continue
                    </button>
                </div>

                <!-- Step 2: OTP Verification -->
                <div id="step-2-verify" class="space-y-5 hidden fade-in">
                    <!-- Back button -->
                    <button type="button" onclick="backToStep1()" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-300 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        Change number
                    </button>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-3 text-center">Enter OTP</label>
                        <div class="otp-boxes">
                            <input type="text" maxlength="1" class="otp-box text-white font-semibold" onkeypress="return isNumber(event)" onkeyup="moveToNext(this, event)" onkeydown="moveToPrev(this, event)" />
                            <input type="text" maxlength="1" class="otp-box text-white font-semibold" onkeypress="return isNumber(event)" onkeyup="moveToNext(this, event)" onkeydown="moveToPrev(this, event)" />
                            <input type="text" maxlength="1" class="otp-box text-white font-semibold" onkeypress="return isNumber(event)" onkeyup="moveToNext(this, event)" onkeydown="moveToPrev(this, event)" />
                            <input type="text" maxlength="1" class="otp-box text-white font-semibold" onkeypress="return isNumber(event)" onkeyup="moveToNext(this, event)" onkeydown="moveToPrev(this, event)" />
                        </div>
                    </div>

                    <button
                        type="button"
                        id="verify-otp-btn"
                        onclick="verifyOTP()"
                        class="btn-primary w-full py-2.5 px-4 rounded-lg text-white text-sm font-medium shadow-lg"
                        style="background: linear-gradient(90deg, #0df259 0%, #06b6d4 50%, #0df259 100%) !important; background-size: 200% 100%;"
                    >
                        Verify & Login
                    </button>

                    <div class="text-center">
                        <button type="button" onclick="resendOTP()" class="text-sm text-gray-400 hover:text-white transition-colors" id="resend-btn">
                            Didn't receive code? <span style="color: #0df259;" class="font-medium">Resend</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-xs text-gray-600">
            <a href="{{ route('home') }}" class="hover:text-gray-400 transition-colors">Terms</a>
            <span class="mx-2">•</span>
            <a href="{{ route('privacy') }}" class="hover:text-gray-400 transition-colors">Privacy</a>
        </div>
    </div>

    <script>
        let currentMobile = '';
        // Setup CSRF token for AJAX requests
        let csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Function to refresh CSRF token
        function refreshCsrfToken() {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                csrfToken = metaTag.content;
            }
        }

        function showMessage(message, type = 'error') {
            const container = document.getElementById('message-container');
            container.className = `mb-4 p-3 border rounded-lg text-sm ${
                type === 'success'
                    ? 'bg-green-500/10 border-green-500/20 text-green-300'
                    : 'bg-red-500/10 border-red-500/20 text-red-300'
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
                sendOTPDirect();
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

        async function sendOTPDirect() {
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
            const sendBtn = document.getElementById('send-otp-main-btn');

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
                    body: JSON.stringify({ mobile })
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
                    // Use window.location.replace for full page reload to ensure session cookie is sent
                    setTimeout(() => {
                        window.location.replace(data.redirect || '{{ route("chat") }}');
                    }, 1500); // Increased to 1.5 seconds for session persistence
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
                    body: JSON.stringify({ mobile })
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

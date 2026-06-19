<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteLogo = \App\Models\HomepageSetting::getValue('site_logo');
        $siteName = \App\Models\HomepageSetting::getValue('site_name', config('app.name', 'BlinkStudy'));
        $logoIcon = \App\Models\HomepageSetting::getValue('logo_icon', 'school');
    @endphp
    <title>Admin Login - {{ $siteName }}</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>

    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="bg-white dark:bg-[#0d0d0d] text-gray-900 dark:text-white min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <!-- Logo/Header -->
        <div class="text-center mb-6">
            @if($siteLogo)
                <div class="flex items-center justify-center mb-3">
                    <img src="{{ asset($siteLogo) }}" alt="{{ $siteName }}" class="w-10 h-10 rounded-lg object-contain">
                </div>
            @endif
            <h1 class="text-2xl font-semibold mb-1">Admin Access</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $siteName }} - Secure admin authentication</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white dark:bg-[#111] border border-gray-200 dark:border-[#222] rounded-xl p-6 shadow-sm">
            <!-- Alert Messages -->
            <div id="alert-message" class="hidden mb-4 rounded-lg p-3 text-sm"></div>

            <!-- Mobile Number Form (Step 1) -->
            <div id="mobile-form">
                <form id="send-otp-form" class="space-y-4">
                    @csrf
                    <div>
                        <label for="mobile" class="block text-sm font-medium mb-1.5">Mobile Number</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 text-sm bg-gray-50 dark:bg-[#0d0d0d] border border-r-0 border-gray-300 dark:border-[#333] rounded-l-lg text-gray-500 dark:text-gray-400">
                                +91
                            </span>
                            <input
                                type="text"
                                id="mobile"
                                name="mobile"
                                maxlength="10"
                                pattern="[6-9][0-9]{9}"
                                class="flex-1 px-3 py-2 bg-white dark:bg-[#0d0d0d] border border-gray-300 dark:border-[#333] rounded-r-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent"
                                placeholder="Enter 10-digit mobile"
                                required
                                autofocus
                            >
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enter your registered admin mobile number</p>
                    </div>

                    <button
                        type="submit"
                        id="send-otp-btn"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition duration-150 text-sm mt-6"
                    >
                        Send OTP
                    </button>
                </form>
            </div>

            <!-- OTP Verification Form (Step 2) -->
            <div id="otp-form" class="hidden">
                <div class="mb-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        OTP sent to <span id="mobile-display" class="font-medium text-gray-900 dark:text-white"></span>
                    </p>
                    <button
                        type="button"
                        id="change-mobile"
                        class="text-xs text-blue-600 dark:text-blue-500 hover:underline mt-1"
                    >
                        Change mobile number
                    </button>
                </div>

                <form id="verify-otp-form" class="space-y-4">
                    @csrf
                    <input type="hidden" id="mobile-hidden" name="mobile">

                    <div>
                        <label for="otp" class="block text-sm font-medium mb-1.5">Enter OTP</label>
                        <input
                            type="text"
                            id="otp"
                            name="otp"
                            maxlength="4"
                            pattern="[0-9]{4}"
                            class="w-full px-3 py-2 bg-white dark:bg-[#0d0d0d] border border-gray-300 dark:border-[#333] rounded-lg text-sm text-center text-2xl tracking-widest font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent"
                            placeholder="••••"
                            required
                        >
                        <div class="mt-2 flex items-center justify-between">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Expires in: <span id="timer" class="font-medium text-gray-900 dark:text-white">5:00</span>
                            </p>
                            <button
                                type="button"
                                id="resend-otp"
                                class="text-xs text-blue-600 dark:text-blue-500 hover:underline disabled:text-gray-400 disabled:no-underline"
                                disabled
                            >
                                Resend OTP
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        id="verify-otp-btn"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition duration-150 text-sm mt-6"
                    >
                        Verify & Login
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer Links -->
        <div class="mt-4 text-center text-sm space-y-2">
            <p class="text-gray-600 dark:text-gray-400">
                Not an admin?
                <a href="{{ route('otp.login') }}" class="text-blue-600 dark:text-blue-500 hover:underline">User Login</a>
            </p>
            <p>
                <a href="{{ route('home') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">← Back to Home</a>
            </p>
        </div>
    </div>

    <script>
        // Setup CSRF token for AJAX requests
        let csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Function to refresh CSRF token
        function refreshCsrfToken() {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                csrfToken = metaTag.content;
            }
        }
        let timerInterval;
        let secondsLeft = 300; // 5 minutes

        // Show alert message
        function showAlert(message, type = 'error') {
            const alertDiv = document.getElementById('alert-message');
            alertDiv.className = 'mb-4 rounded-lg p-3 text-sm';

            if (type === 'success') {
                alertDiv.classList.add('bg-green-50', 'dark:bg-green-500/10', 'border', 'border-green-200', 'dark:border-green-500/50', 'text-green-600', 'dark:text-green-400');
            } else {
                alertDiv.classList.add('bg-red-50', 'dark:bg-red-500/10', 'border', 'border-red-200', 'dark:border-red-500/50', 'text-red-600', 'dark:text-red-400');
            }

            alertDiv.textContent = message;
            alertDiv.classList.remove('hidden');

            // Auto hide after 5 seconds
            setTimeout(() => alertDiv.classList.add('hidden'), 5000);
        }

        // Start OTP timer
        function startTimer(seconds) {
            secondsLeft = seconds;
            const timerDisplay = document.getElementById('timer');
            const resendBtn = document.getElementById('resend-otp');

            resendBtn.disabled = true;

            timerInterval = setInterval(() => {
                secondsLeft--;

                const minutes = Math.floor(secondsLeft / 60);
                const secs = secondsLeft % 60;
                timerDisplay.textContent = `${minutes}:${secs.toString().padStart(2, '0')}`;

                if (secondsLeft <= 0) {
                    clearInterval(timerInterval);
                    timerDisplay.textContent = 'Expired';
                    resendBtn.disabled = false;
                }
            }, 1000);
        }

        // Send OTP Form
        document.getElementById('send-otp-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const mobile = document.getElementById('mobile').value;
            const btn = document.getElementById('send-otp-btn');

            // Validate mobile number
            if (!/^[6-9]\d{9}$/.test(mobile)) {
                showAlert('Please enter a valid 10-digit mobile number');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                // Refresh CSRF token before request
                refreshCsrfToken();

                const response = await fetch('{{ route("admin.otp.send") }}', {
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
                    showAlert('Session expired. Please refresh the page and try again.');
                    setTimeout(() => window.location.reload(), 2000);
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    // Show OTP form
                    document.getElementById('mobile-form').classList.add('hidden');
                    document.getElementById('otp-form').classList.remove('hidden');
                    document.getElementById('mobile-display').textContent = '+91 ' + mobile;
                    document.getElementById('mobile-hidden').value = mobile;
                    document.getElementById('otp').focus();

                    // Start timer
                    startTimer(data.expires_in || 300);

                    showAlert(data.message, 'success');
                } else {
                    showAlert(data.message);
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Send OTP';
            }
        });

        // Verify OTP Form
        document.getElementById('verify-otp-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const mobile = document.getElementById('mobile-hidden').value;
            const otp = document.getElementById('otp').value;
            const btn = document.getElementById('verify-otp-btn');

            if (!/^\d{4}$/.test(otp)) {
                showAlert('Please enter a valid 4-digit OTP');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Verifying...';

            try {
                // Refresh CSRF token before request
                refreshCsrfToken();

                const response = await fetch('{{ route("admin.otp.verify") }}', {
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
                    showAlert('Session expired. Please refresh the page and try again.');
                    setTimeout(() => window.location.reload(), 2000);
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    clearInterval(timerInterval);
                    showAlert(data.message, 'success');

                    // Redirect based on response
                    // Use window.location.replace for full page reload to ensure session cookie is sent
                    setTimeout(() => {
                        if (data.requires_2fa) {
                            window.location.replace(data.redirect);
                        } else {
                            // Full page reload to ensure session is recognized
                            window.location.replace(data.redirect || '{{ route("admin.dashboard") }}');
                        }
                    }, 2000); // Increased to 2 seconds for session persistence
                } else {
                    showAlert(data.message);
                    document.getElementById('otp').value = '';
                    document.getElementById('otp').focus();
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Verify & Login';
            }
        });

        // Change mobile number
        document.getElementById('change-mobile').addEventListener('click', () => {
            clearInterval(timerInterval);
            document.getElementById('otp-form').classList.add('hidden');
            document.getElementById('mobile-form').classList.remove('hidden');
            document.getElementById('mobile').value = '';
            document.getElementById('otp').value = '';
            document.getElementById('mobile').focus();
        });

        // Resend OTP
        document.getElementById('resend-otp').addEventListener('click', async () => {
            const mobile = document.getElementById('mobile-hidden').value;
            const btn = document.getElementById('resend-otp');

            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                // Refresh CSRF token before request
                refreshCsrfToken();

                const response = await fetch('{{ route("admin.otp.send") }}', {
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
                    showAlert('Session expired. Please refresh the page and try again.');
                    setTimeout(() => window.location.reload(), 2000);
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    startTimer(data.expires_in || 300);
                    showAlert('OTP resent successfully!', 'success');
                } else {
                    showAlert(data.message);
                    btn.disabled = false;
                }
            } catch (error) {
                showAlert('Failed to resend OTP. Please try again.');
                btn.disabled = false;
            } finally {
                btn.textContent = 'Resend OTP';
            }
        });

        // Auto-submit OTP when 4 digits entered
        document.getElementById('otp').addEventListener('input', (e) => {
            if (e.target.value.length === 4) {
                document.getElementById('verify-otp-form').dispatchEvent(new Event('submit'));
            }
        });

        // Only allow numbers in OTP field
        document.getElementById('otp').addEventListener('keypress', (e) => {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });

        // Only allow numbers in mobile field
        document.getElementById('mobile').addEventListener('keypress', (e) => {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>

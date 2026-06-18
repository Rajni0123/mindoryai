<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Two-Factor Authentication Setup - Nexova Admin</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>

    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="bg-white dark:bg-[#0d0d0d] text-gray-900 dark:text-white min-h-screen p-4">
    <div class="max-w-2xl mx-auto py-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('admin.profile.edit') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Profile
            </a>
        </div>

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold mb-2">Two-Factor Authentication</h1>
            <p class="text-gray-500 dark:text-gray-400">Add an extra layer of security to your admin account</p>
        </div>

        <!-- Alert Messages -->
        <div id="alert-message" class="hidden mb-6 rounded-lg p-4 text-sm"></div>

        @if($enabled)
            <!-- 2FA Enabled Status -->
            <div class="bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/50 rounded-lg p-6 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-300">2FA is currently enabled</h3>
                        <p class="mt-1 text-sm text-green-700 dark:text-green-400">Your account is protected with two-factor authentication</p>
                    </div>
                </div>
            </div>

            <!-- Disable 2FA Section -->
            <div class="bg-white dark:bg-[#111] border border-gray-200 dark:border-[#222] rounded-xl p-6">
                <h2 class="text-lg font-medium mb-4">Disable Two-Factor Authentication</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    This will remove 2FA protection from your account. You'll only need OTP to login.
                </p>

                <form id="disable-2fa-form" class="space-y-4">
                    @csrf
                    <div>
                        <label for="disable-password" class="block text-sm font-medium mb-2">Confirm Password</label>
                        <input
                            type="password"
                            id="disable-password"
                            name="password"
                            class="w-full max-w-md px-3 py-2 bg-white dark:bg-[#0d0d0d] border border-gray-300 dark:border-[#333] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 dark:focus:ring-red-600 focus:border-transparent"
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        id="disable-btn"
                        class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 text-sm"
                    >
                        Disable 2FA
                    </button>
                </form>
            </div>
        @else
            <!-- Setup Instructions -->
            <div class="bg-white dark:bg-[#111] border border-gray-200 dark:border-[#222] rounded-xl p-6 mb-6">
                <h2 class="text-lg font-medium mb-4">Setup Two-Factor Authentication</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                    Follow these steps to enable 2FA on your admin account:
                </p>

                <div class="space-y-6">
                    <!-- Step 1 -->
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold">
                            1
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium mb-2">Download Authenticator App</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Install Google Authenticator, Microsoft Authenticator, or any TOTP-compatible app on your phone.
                            </p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold">
                            2
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium mb-3">Scan QR Code</h3>
                            <div class="bg-white p-4 rounded-lg inline-block border border-gray-200 dark:border-[#333]">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}"
                                     alt="QR Code"
                                     class="w-48 h-48">
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                                Can't scan? Enter this code manually: <code class="bg-gray-100 dark:bg-[#0d0d0d] px-2 py-1 rounded text-blue-600 dark:text-blue-400">{{ $secret }}</code>
                            </p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold">
                            3
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium mb-3">Verify Code</h3>
                            <form id="enable-2fa-form" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="code" class="block text-sm font-medium mb-2">Enter 6-digit code from app</label>
                                    <input
                                        type="text"
                                        id="code"
                                        name="code"
                                        maxlength="6"
                                        pattern="[0-9]{6}"
                                        class="w-full max-w-xs px-4 py-2 bg-white dark:bg-[#0d0d0d] border border-gray-300 dark:border-[#333] rounded-lg text-sm text-center text-xl tracking-widest font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent"
                                        placeholder="••••••"
                                        required
                                    >
                                </div>

                                <button
                                    type="submit"
                                    id="enable-btn"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 text-sm"
                                >
                                    Enable 2FA
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="bg-yellow-50 dark:bg-yellow-500/10 border border-yellow-200 dark:border-yellow-500/50 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Important</h3>
                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-400">
                            Make sure to save your authenticator app backup codes. You won't be able to login without them if you lose access to your phone.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Show alert message
        function showAlert(message, type = 'error') {
            const alertDiv = document.getElementById('alert-message');
            alertDiv.className = 'mb-6 rounded-lg p-4 text-sm';

            if (type === 'success') {
                alertDiv.classList.add('bg-green-50', 'dark:bg-green-500/10', 'border', 'border-green-200', 'dark:border-green-500/50', 'text-green-700', 'dark:text-green-300');
            } else {
                alertDiv.classList.add('bg-red-50', 'dark:bg-red-500/10', 'border', 'border-red-200', 'dark:border-red-500/50', 'text-red-700', 'dark:text-red-300');
            }

            alertDiv.textContent = message;
            alertDiv.classList.remove('hidden');

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Only allow numbers in code field
        const codeInput = document.getElementById('code');
        if (codeInput) {
            codeInput.addEventListener('keypress', (e) => {
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        }

        // Enable 2FA Form
        const enableForm = document.getElementById('enable-2fa-form');
        if (enableForm) {
            enableForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const code = document.getElementById('code').value;
                const btn = document.getElementById('enable-btn');

                if (!/^\d{6}$/.test(code)) {
                    showAlert('Please enter a valid 6-digit code');
                    return;
                }

                btn.disabled = true;
                btn.textContent = 'Enabling...';

                try {
                    const response = await fetch('{{ route("admin.2fa.enable") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ code })
                    });

                    const data = await response.json();

                    if (data.success) {
                        showAlert(data.message, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showAlert(data.message);
                        document.getElementById('code').value = '';
                    }
                } catch (error) {
                    showAlert('An error occurred. Please try again.');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Enable 2FA';
                }
            });
        }

        // Disable 2FA Form
        const disableForm = document.getElementById('disable-2fa-form');
        if (disableForm) {
            disableForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (!confirm('Are you sure you want to disable 2FA? This will make your account less secure.')) {
                    return;
                }

                const password = document.getElementById('disable-password').value;
                const btn = document.getElementById('disable-btn');

                btn.disabled = true;
                btn.textContent = 'Disabling...';

                try {
                    const response = await fetch('{{ route("admin.2fa.disable") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ password })
                    });

                    const data = await response.json();

                    if (data.success) {
                        showAlert(data.message, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showAlert(data.message);
                        document.getElementById('disable-password').value = '';
                    }
                } catch (error) {
                    showAlert('An error occurred. Please try again.');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Disable 2FA';
                }
            });
        }
    </script>
</body>
</html>

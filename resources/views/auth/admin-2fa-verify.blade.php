<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>2FA Verification - {{ config('app.name', 'BlinkStudy') }}</title>

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
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-500/10 rounded-full mb-4">
                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-semibold mb-1.5">Two-Factor Authentication</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Enter 6-digit code from your authenticator app</p>
        </div>

        <!-- 2FA Card -->
        <div class="bg-white dark:bg-[#111] border border-gray-200 dark:border-[#222] rounded-xl p-6 shadow-sm">
            <!-- Alert Messages -->
            <div id="alert-message" class="hidden mb-4 rounded-lg p-3 text-sm"></div>

            <!-- 2FA Form -->
            <form id="verify-2fa-form" class="space-y-4">
                @csrf
                <div>
                    <label for="code" class="block text-sm font-medium mb-2">Authentication Code</label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        class="w-full px-4 py-2.5 bg-white dark:bg-[#0d0d0d] border border-gray-300 dark:border-[#333] rounded-lg text-sm text-center text-2xl tracking-widest font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent"
                        placeholder="••••••"
                        required
                        autofocus
                    >
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                        Open your authenticator app to get the code
                    </p>
                </div>

                <button
                    type="submit"
                    id="verify-btn"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition duration-150 text-sm"
                >
                    Verify & Login
                </button>
            </form>

            <!-- Help Text -->
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-[#222]">
                <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                    Lost access to your authenticator?<br>
                    Please contact administrator for assistance.
                </p>
            </div>
        </div>

        <!-- Back Link -->
        <div class="mt-4 text-center text-sm">
            <a href="{{ route('admin.login') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">← Back to Login</a>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

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
        }

        // Only allow numbers in code field
        document.getElementById('code').addEventListener('keypress', (e) => {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });

        // Auto-submit when 6 digits entered
        document.getElementById('code').addEventListener('input', (e) => {
            if (e.target.value.length === 6) {
                setTimeout(() => {
                    document.getElementById('verify-2fa-form').dispatchEvent(new Event('submit'));
                }, 100);
            }
        });

        // Verify 2FA Form
        document.getElementById('verify-2fa-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const code = document.getElementById('code').value;
            const btn = document.getElementById('verify-btn');

            if (!/^\d{6}$/.test(code)) {
                showAlert('Please enter a valid 6-digit code');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Verifying...';

            try {
                const response = await fetch('{{ route("admin.2fa.verify.submit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin', // CRITICAL: Include cookies in request/response
                    body: JSON.stringify({ code })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                    // Full page reload to ensure session is recognized
                    setTimeout(() => {
                        window.location.replace(data.redirect);
                    }, 2000); // Increased to 2 seconds for session persistence
                } else {
                    showAlert(data.message);
                    document.getElementById('code').value = '';
                    document.getElementById('code').focus();
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Verify & Login';
            }
        });
    </script>
</body>
</html>

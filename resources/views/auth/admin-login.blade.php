<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteLogo = \App\Models\HomepageSetting::getValue('site_logo');
        $siteName = \App\Models\HomepageSetting::getValue('site_name', config('app.name', 'BlinkStudy'));
    @endphp
    <title>Admin Login - {{ $siteName }}</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <style>
        * { font-family: 'Inter', sans-serif; }
        .auth-tab.active {
            color: #93c5fd;
            border-bottom-color: #3b82f6;
        }
    </style>
</head>

<body class="bg-[#0d0d0d] text-white min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            @if($siteLogo)
                <div class="flex items-center justify-center mb-3">
                    <img src="{{ asset($siteLogo) }}" alt="{{ $siteName }}" class="w-10 h-10 rounded-lg object-contain">
                </div>
            @endif
            <h1 class="text-2xl font-semibold mb-1">Admin Access</h1>
            <p class="text-sm text-gray-400">{{ $siteName }} — Sign in with email or mobile</p>
        </div>

        <div class="bg-[#111] border border-[#222] rounded-xl p-6 shadow-sm">
            @if(session('error'))
                <div class="mb-4 rounded-lg p-3 text-sm bg-red-500/10 border border-red-500/40 text-red-400">
                    {{ session('error') }}
                </div>
            @endif

            <div id="alert-message" class="hidden mb-4 rounded-lg p-3 text-sm"></div>

            <div class="flex gap-4 border-b border-[#222] mb-5">
                <button type="button" class="auth-tab active flex-1 pb-3 text-sm font-semibold border-b-2 border-transparent text-gray-400" data-tab="mobile">
                    Mobile
                </button>
                <button type="button" class="auth-tab flex-1 pb-3 text-sm font-semibold border-b-2 border-transparent text-gray-400" data-tab="email">
                    Email
                </button>
            </div>

            <form id="admin-login-form" class="space-y-4">
                @csrf
                <input type="hidden" name="login_method" id="login_method" value="mobile">

                <div id="mobile-fields">
                    <label for="mobile" class="block text-sm font-medium mb-1.5">Mobile Number</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 text-sm bg-[#0d0d0d] border border-r-0 border-[#333] rounded-l-lg text-gray-400">+91</span>
                        <input
                            type="text"
                            id="mobile"
                            name="mobile"
                            maxlength="10"
                            pattern="[6-9][0-9]{9}"
                            class="flex-1 px-3 py-2.5 bg-[#0d0d0d] border border-[#333] rounded-r-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                            placeholder="10-digit mobile"
                            autofocus
                        >
                    </div>
                </div>

                <div id="email-fields" class="hidden">
                    <label for="email" class="block text-sm font-medium mb-1.5">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="w-full px-3 py-2.5 bg-[#0d0d0d] border border-[#333] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                        placeholder="admin@example.com"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-1.5">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-3 py-2.5 bg-[#0d0d0d] border border-[#333] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                        placeholder="Enter your password"
                    >
                </div>

                <button
                    type="submit"
                    id="login-btn"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition text-sm mt-2"
                >
                    Sign In to Admin Panel
                </button>
            </form>
        </div>

        <div class="mt-4 text-center text-sm space-y-2">
            <p class="text-gray-400">
                Not an admin?
                <a href="{{ route('login') }}" class="text-blue-500 hover:underline">User Login</a>
            </p>
            <p>
                <a href="{{ route('home') }}" class="text-gray-500 hover:text-white">← Back to Home</a>
            </p>
        </div>
    </div>

    <script>
        let csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let activeTab = 'mobile';

        function showAlert(message, type = 'error') {
            const alertDiv = document.getElementById('alert-message');
            alertDiv.className = 'mb-4 rounded-lg p-3 text-sm';
            if (type === 'success') {
                alertDiv.classList.add('bg-green-500/10', 'border', 'border-green-500/40', 'text-green-400');
            } else {
                alertDiv.classList.add('bg-red-500/10', 'border', 'border-red-500/40', 'text-red-400');
            }
            alertDiv.textContent = message;
            alertDiv.classList.remove('hidden');
            setTimeout(() => alertDiv.classList.add('hidden'), 5000);
        }

        function switchTab(tab) {
            activeTab = tab;
            document.getElementById('login_method').value = tab;
            document.querySelectorAll('.auth-tab').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tab === tab);
            });
            document.getElementById('mobile-fields').classList.toggle('hidden', tab !== 'mobile');
            document.getElementById('email-fields').classList.toggle('hidden', tab !== 'email');
            if (tab === 'mobile') {
                document.getElementById('mobile').focus();
            } else {
                document.getElementById('email').focus();
            }
        }

        document.querySelectorAll('.auth-tab').forEach(btn => {
            btn.addEventListener('click', () => switchTab(btn.dataset.tab));
        });

        document.getElementById('admin-login-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = document.getElementById('login-btn');
            const password = document.getElementById('password').value;
            const payload = {
                login_method: activeTab,
                password,
            };

            if (activeTab === 'mobile') {
                const mobile = document.getElementById('mobile').value.trim();
                if (!/^[6-9]\d{9}$/.test(mobile)) {
                    showAlert('Please enter a valid 10-digit mobile number');
                    return;
                }
                payload.mobile = mobile;
            } else {
                const email = document.getElementById('email').value.trim();
                if (!email) {
                    showAlert('Please enter your email address');
                    return;
                }
                payload.email = email;
            }

            btn.disabled = true;
            btn.textContent = 'Signing in...';

            try {
                const response = await fetch('/admin/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });

                if (response.status === 419) {
                    showAlert('Session expired. Please refresh the page and try again.');
                    setTimeout(() => window.location.reload(), 2000);
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message || 'Login successful!', 'success');
                    setTimeout(() => {
                        window.location.replace(data.redirect || '/admin/dashboard');
                    }, 800);
                } else {
                    showAlert(data.message || 'Login failed');
                }
            } catch (_) {
                showAlert('An error occurred. Please try again.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Sign In to Admin Panel';
            }
        });

        ['mobile', 'password'].forEach(id => {
            document.getElementById(id)?.addEventListener('keypress', (e) => {
                if (!/[0-9]/.test(e.key) && id === 'mobile') e.preventDefault();
            });
        });
    </script>
</body>
</html>

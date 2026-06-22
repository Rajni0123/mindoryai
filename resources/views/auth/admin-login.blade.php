<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteLogo = \App\Models\HomepageSetting::getValue('site_logo');
        $siteName = \App\Models\HomepageSetting::getValue('site_name', config('app.name', 'BlinkStudy'));
        $loginMethod = old('login_method', 'mobile');
    @endphp
    <title>Admin Login - {{ $siteName }}</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <style>
        * { font-family: 'Inter', sans-serif; }
        .auth-tab.active { color: #93c5fd; border-bottom-color: #3b82f6; }
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
            <p class="text-sm text-gray-400">{{ $siteName }} — Password login (no SMS)</p>
        </div>

        <div class="bg-[#111] border border-[#222] rounded-xl p-6 shadow-sm">
            @if(session('error'))
                <div class="mb-4 rounded-lg p-3 text-sm bg-red-500/10 border border-red-500/40 text-red-400">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="mb-4 rounded-lg p-3 text-sm bg-green-500/10 border border-green-500/40 text-green-400">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-lg p-3 text-sm bg-red-500/10 border border-red-500/40 text-red-400">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="flex gap-4 border-b border-[#222] mb-5">
                <button type="button" class="auth-tab {{ $loginMethod === 'mobile' ? 'active' : '' }} flex-1 pb-3 text-sm font-semibold border-b-2 border-transparent text-gray-400" data-tab="mobile">
                    Mobile
                </button>
                <button type="button" class="auth-tab {{ $loginMethod === 'email' ? 'active' : '' }} flex-1 pb-3 text-sm font-semibold border-b-2 border-transparent text-gray-400" data-tab="email">
                    Email
                </button>
            </div>

            <form method="POST" action="/admin/login" class="space-y-4" id="admin-login-form">
                @csrf
                <input type="hidden" name="login_method" id="login_method" value="{{ $loginMethod }}">

                <div id="mobile-fields" class="{{ $loginMethod === 'email' ? 'hidden' : '' }}">
                    <label for="mobile" class="block text-sm font-medium mb-1.5">Mobile Number</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 text-sm bg-[#0d0d0d] border border-r-0 border-[#333] rounded-l-lg text-gray-400">+91</span>
                        <input
                            type="text"
                            id="mobile"
                            name="mobile"
                            value="{{ old('mobile') }}"
                            maxlength="10"
                            class="flex-1 px-3 py-2.5 bg-[#0d0d0d] border border-[#333] rounded-r-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                            placeholder="10-digit mobile"
                            {{ $loginMethod === 'mobile' ? 'required' : '' }}
                        >
                    </div>
                </div>

                <div id="email-fields" class="{{ $loginMethod === 'email' ? '' : 'hidden' }}">
                    <label for="email" class="block text-sm font-medium mb-1.5">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="w-full px-3 py-2.5 bg-[#0d0d0d] border border-[#333] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                        placeholder="admin@blinkstudy.in"
                        {{ $loginMethod === 'email' ? 'required' : '' }}
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-1.5">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-3 py-2.5 bg-[#0d0d0d] border border-[#333] rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                        placeholder="Enter your password"
                    >
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg text-sm">
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
        function switchTab(tab) {
            document.getElementById('login_method').value = tab;
            document.querySelectorAll('.auth-tab').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tab === tab);
            });
            document.getElementById('mobile-fields').classList.toggle('hidden', tab !== 'mobile');
            document.getElementById('email-fields').classList.toggle('hidden', tab !== 'email');
            document.getElementById('mobile').required = tab === 'mobile';
            document.getElementById('email').required = tab === 'email';
        }

        document.querySelectorAll('.auth-tab').forEach(btn => {
            btn.addEventListener('click', () => switchTab(btn.dataset.tab));
        });
    </script>
</body>
</html>

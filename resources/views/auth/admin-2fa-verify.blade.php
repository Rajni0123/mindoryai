<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteName = \App\Models\HomepageSetting::getValue('site_name', config('app.name', 'BlinkStudy'));
        $logoIcon = \App\Models\HomepageSetting::getValue('logo_icon', 'school');
    @endphp
    <title>2FA Verification - {{ $siteName }}</title>
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
        * { -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Outfit', sans-serif; }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(13, 148, 136, 0.1);
        }
        .dark .glass-effect {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(153, 246, 228, 0.1);
        }

        .otp-input {
            background: rgba(13, 148, 136, 0.05);
            border: 2px solid rgba(13, 148, 136, 0.15);
            transition: all 0.3s ease;
        }
        .otp-input:focus {
            background: rgba(13, 148, 136, 0.08);
            border-color: rgba(13, 148, 136, 0.5);
            outline: none;
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
        }
        .dark .otp-input {
            background: rgba(153, 246, 228, 0.03);
            border-color: rgba(153, 246, 228, 0.1);
        }
        .dark .otp-input:focus {
            background: rgba(153, 246, 228, 0.06);
            border-color: rgba(153, 246, 228, 0.3);
            box-shadow: 0 0 0 4px rgba(153, 246, 228, 0.05);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-up {
            animation: fadeUp 0.5s ease-out forwards;
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(13, 148, 136, 0.3); }
            70% { box-shadow: 0 0 0 12px rgba(13, 148, 136, 0); }
            100% { box-shadow: 0 0 0 0 rgba(13, 148, 136, 0); }
        }
        .pulse-ring { animation: pulse-ring 2s infinite; }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 transition-colors duration-300"
      :class="isDark ? 'bg-background-dark text-white' : 'bg-gradient-to-br from-background-light via-white to-accent-soft/20 text-primary-text'"
      x-data="{ isDark: localStorage.getItem('theme') === 'dark' }"
      x-init="$watch('isDark', val => { localStorage.setItem('theme', val ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', val) }); document.documentElement.classList.toggle('dark', isDark)">

    <div class="w-full max-w-md animate-fade-up">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary/10 mb-4 pulse-ring">
                <span class="material-icons-round text-primary text-3xl">shield</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight">Two-Factor Authentication</h1>
            <p class="text-sm mt-2 opacity-60">Enter the 6-digit code from your authenticator app</p>
        </div>

        {{-- Card --}}
        <div class="glass-effect rounded-2xl p-8 shadow-xl shadow-primary/5">
            {{-- Alert --}}
            <div id="alert-message" class="hidden mb-6 rounded-xl p-4 text-sm font-medium"></div>

            {{-- 2FA Form --}}
            <form id="verify-2fa-form" class="space-y-6">
                @csrf
                <div>
                    <label for="code" class="block text-sm font-semibold mb-3 opacity-80">Authentication Code</label>
                    <div class="flex gap-3 justify-center">
                        <input type="text" id="d1" maxlength="1" class="otp-input w-12 h-14 rounded-xl text-center text-2xl font-bold" inputmode="numeric" autofocus>
                        <input type="text" id="d2" maxlength="1" class="otp-input w-12 h-14 rounded-xl text-center text-2xl font-bold" inputmode="numeric">
                        <input type="text" id="d3" maxlength="1" class="otp-input w-12 h-14 rounded-xl text-center text-2xl font-bold" inputmode="numeric">
                        <span class="flex items-center text-xl opacity-30 font-bold">-</span>
                        <input type="text" id="d4" maxlength="1" class="otp-input w-12 h-14 rounded-xl text-center text-2xl font-bold" inputmode="numeric">
                        <input type="text" id="d5" maxlength="1" class="otp-input w-12 h-14 rounded-xl text-center text-2xl font-bold" inputmode="numeric">
                        <input type="text" id="d6" maxlength="1" class="otp-input w-12 h-14 rounded-xl text-center text-2xl font-bold" inputmode="numeric">
                    </div>
                    <input type="hidden" id="code" name="code">
                    <p class="mt-3 text-xs text-center opacity-50">
                        <span class="material-icons-round text-[14px] align-middle mr-0.5">info</span>
                        Open your authenticator app to get the code
                    </p>
                </div>

                <button type="submit" id="verify-btn"
                        class="w-full py-3.5 rounded-xl font-semibold text-white text-sm
                               bg-gradient-to-r from-primary to-teal-500
                               hover:shadow-lg hover:shadow-primary/25 hover:-translate-y-0.5
                               active:translate-y-0 transition-all duration-200
                               disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-none">
                    <span class="flex items-center justify-center gap-2">
                        <span class="material-icons-round text-[18px]">verified_user</span>
                        Verify & Login
                    </span>
                </button>
            </form>

            {{-- Help --}}
            <div class="mt-6 pt-6 border-t border-primary/10">
                <p class="text-xs text-center opacity-50">
                    Lost access to your authenticator?<br>
                    Please contact administrator for assistance.
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="mt-6 flex items-center justify-between px-2">
            <a href="{{ route('admin.login') }}" class="text-sm opacity-50 hover:opacity-80 transition-opacity flex items-center gap-1">
                <span class="material-icons-round text-[16px]">arrow_back</span>
                Back to Login
            </a>
            <button @click="isDark = !isDark" class="p-2 rounded-full opacity-50 hover:opacity-80 transition-opacity">
                <span x-show="!isDark" class="material-icons-round text-[18px]">dark_mode</span>
                <span x-show="isDark" class="material-icons-round text-[18px]">light_mode</span>
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const inputs = [document.getElementById('d1'), document.getElementById('d2'), document.getElementById('d3'),
                        document.getElementById('d4'), document.getElementById('d5'), document.getElementById('d6')];
        const hiddenCode = document.getElementById('code');

        function updateHiddenCode() {
            hiddenCode.value = inputs.map(i => i.value).join('');
        }

        function showAlert(message, type = 'error') {
            const alertDiv = document.getElementById('alert-message');
            alertDiv.className = 'mb-6 rounded-xl p-4 text-sm font-medium';
            if (type === 'success') {
                alertDiv.classList.add('bg-primary/10', 'text-primary', 'border', 'border-primary/20');
            } else {
                alertDiv.classList.add('bg-red-500/10', 'text-red-500', 'border', 'border-red-500/20');
            }
            alertDiv.textContent = message;
            alertDiv.classList.remove('hidden');
        }

        // OTP input handling
        inputs.forEach((input, idx) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/\D/g, '');
                e.target.value = val;
                if (val && idx < 5) inputs[idx + 1].focus();
                updateHiddenCode();
                if (hiddenCode.value.length === 6) {
                    setTimeout(() => document.getElementById('verify-2fa-form').dispatchEvent(new Event('submit')), 150);
                }
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && idx > 0) {
                    inputs[idx - 1].focus();
                    inputs[idx - 1].value = '';
                    updateHiddenCode();
                }
            });
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                paste.split('').forEach((char, i) => { if (inputs[i]) inputs[i].value = char; });
                updateHiddenCode();
                if (paste.length >= 6) {
                    inputs[5].focus();
                    setTimeout(() => document.getElementById('verify-2fa-form').dispatchEvent(new Event('submit')), 150);
                } else if (inputs[paste.length]) {
                    inputs[paste.length].focus();
                }
            });
        });

        // Verify 2FA Form
        document.getElementById('verify-2fa-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const code = hiddenCode.value;
            const btn = document.getElementById('verify-btn');

            if (!/^\d{6}$/.test(code)) {
                showAlert('Please enter a valid 6-digit code');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="flex items-center justify-center gap-2"><svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Verifying...</span>';

            try {
                const response = await fetch('{{ route("admin.2fa.verify.submit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ code })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                    btn.innerHTML = '<span class="flex items-center justify-center gap-2"><span class="material-icons-round text-[18px]">check_circle</span> Verified!</span>';
                    setTimeout(() => window.location.replace(data.redirect), 1500);
                } else {
                    showAlert(data.message);
                    inputs.forEach(i => i.value = '');
                    hiddenCode.value = '';
                    inputs[0].focus();
                    resetBtn();
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.');
                resetBtn();
            }
        });

        function resetBtn() {
            const btn = document.getElementById('verify-btn');
            btn.disabled = false;
            btn.innerHTML = '<span class="flex items-center justify-center gap-2"><span class="material-icons-round text-[18px]">verified_user</span> Verify & Login</span>';
        }
    </script>
</body>
</html>

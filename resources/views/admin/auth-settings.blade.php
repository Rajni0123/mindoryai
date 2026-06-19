<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Authentication Settings</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .card:hover { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.12); }
        .tab-btn { position: relative; }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: #3b82f6;
        }
        input[type="color"] {
            -webkit-appearance: none;
            border: none;
            width: 48px;
            height: 48px;
            cursor: pointer;
        }
        input[type="color"]::-webkit-color-swatch-wrapper {
            padding: 0;
        }
        input[type="color"]::-webkit-color-swatch {
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        @include('admin.partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-semibold text-white">Authentication Settings</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Control OTP, login screen, and social authentication</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-green-400" style="font-size: 16px;">check_circle</span>
                    <p class="text-green-300">{{ session('success') }}</p>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-red-400" style="font-size: 16px;">error</span>
                    <p class="text-red-300">{{ session('error') }}</p>
                </div>
                @endif

                <!-- Info Box -->
                <div class="mb-6 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                    <div class="flex items-start gap-3">
                        <span class="material-icons-outlined text-blue-400" style="font-size: 20px;">info</span>
                        <div class="text-xs text-blue-300">
                            <p class="font-semibold mb-1">About Authentication Settings:</p>
                            <p class="text-blue-300/90">Configure OTP provider, customize login screen appearance, and manage social login options for both web and mobile app.</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="mb-6 border-b border-gray-800/50">
                    <div class="flex gap-4 overflow-x-auto">
                        <button onclick="switchTab('otp')" class="tab-btn active px-4 py-3 text-sm font-medium text-white hover:text-blue-400 transition-colors whitespace-nowrap" id="tab-otp">
                            <span class="material-icons-outlined align-middle mr-1" style="font-size: 16px;">sms</span>
                            Mobile OTP
                        </button>
                        <button onclick="switchTab('email-otp')" class="tab-btn px-4 py-3 text-sm font-medium text-gray-400 hover:text-blue-400 transition-colors whitespace-nowrap" id="tab-email-otp">
                            <span class="material-icons-outlined align-middle mr-1" style="font-size: 16px;">email</span>
                            Email OTP
                        </button>
                        <button onclick="switchTab('login')" class="tab-btn px-4 py-3 text-sm font-medium text-gray-400 hover:text-blue-400 transition-colors whitespace-nowrap" id="tab-login">
                            <span class="material-icons-outlined align-middle mr-1" style="font-size: 16px;">login</span>
                            Login Screen
                        </button>
                        <button onclick="switchTab('social')" class="tab-btn px-4 py-3 text-sm font-medium text-gray-400 hover:text-blue-400 transition-colors whitespace-nowrap" id="tab-social">
                            <span class="material-icons-outlined align-middle mr-1" style="font-size: 16px;">share</span>
                            Social Login
                        </button>
                        <button onclick="switchTab('security')" class="tab-btn px-4 py-3 text-sm font-medium text-gray-400 hover:text-blue-400 transition-colors whitespace-nowrap" id="tab-security">
                            <span class="material-icons-outlined align-middle mr-1" style="font-size: 16px;">security</span>
                            Security
                        </button>
                    </div>
                </div>

                <!-- Tab Content: OTP Settings -->
                <div id="content-otp" class="tab-content">
                    <form action="{{ route('admin.auth-settings.update-otp') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- OTP Provider Configuration -->
                            <div class="card rounded-lg p-6">
                                <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 18px;">settings_phone</span>
                                    Provider Configuration
                                </h3>

                                <div class="space-y-4">
                                    <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="enabled" value="0">
                                            <input type="checkbox" name="enabled" value="1" class="sr-only peer" {{ $otpSettings['enabled'] ? 'checked' : '' }}>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                        </label>
                                        <div>
                                            <p class="text-sm font-medium text-white">Enable OTP Authentication</p>
                                            <p class="text-xs text-gray-500 mt-0.5">Allow users to login via OTP</p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">SMS Provider *</label>
                                        <select name="provider" required
                                                class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                                style="color-scheme: dark;">
                                            <option value="renflair" {{ $otpSettings['provider'] === 'renflair' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">Renflair (Current)</option>
                                            <option value="twilio" {{ $otpSettings['provider'] === 'twilio' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">Twilio</option>
                                            <option value="msg91" {{ $otpSettings['provider'] === 'msg91' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">MSG91</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">OTP Delivery Method *</label>
                                        <select name="method" required
                                                class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                                style="color-scheme: dark;">
                                            <option value="sms" {{ ($otpSettings['method'] ?? 'sms') === 'sms' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">📱 SMS</option>
                                            <option value="whatsapp" {{ ($otpSettings['method'] ?? 'sms') === 'whatsapp' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">💬 WhatsApp</option>
                                        </select>
                                        <p class="mt-1 text-[10px] text-gray-500">Choose how OTP will be delivered to users</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">OTP Code Length *</label>
                                        <input type="number" name="length" value="{{ $otpSettings['length'] }}" min="4" max="8" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">4 to 8 digits</p>
                                    </div>

                                    <div class="flex items-start gap-3 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="test_mode" value="0">
                                            <input type="checkbox" name="test_mode" value="1" class="sr-only peer" {{ $otpSettings['test_mode'] ? 'checked' : '' }}>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-yellow-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-600"></div>
                                        </label>
                                        <div>
                                            <p class="text-sm font-medium text-yellow-300">Test Mode</p>
                                            <p class="text-xs text-yellow-300/70 mt-0.5">Accept 123456 as valid OTP (for testing)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- OTP Behavior Settings -->
                            <div class="card rounded-lg p-6">
                                <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 18px;">tune</span>
                                    Behavior Settings
                                </h3>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">OTP Expiry Time (minutes) *</label>
                                        <input type="number" name="expiry_minutes" value="{{ $otpSettings['expiry_minutes'] }}" min="1" max="30" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">How long OTP remains valid</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Resend Cooldown (seconds) *</label>
                                        <input type="number" name="resend_cooldown" value="{{ $otpSettings['resend_cooldown'] }}" min="30" max="300" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">Wait time between OTP resend requests</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Maximum Attempts *</label>
                                        <input type="number" name="max_attempts" value="{{ $otpSettings['max_attempts'] }}" min="3" max="10" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">Max OTP verification attempts before blocking</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">SMS Template *</label>
                                        <textarea name="sms_template" rows="4" required
                                                  class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono">{{ $otpSettings['sms_template'] }}</textarea>
                                        <p class="mt-1 text-[10px] text-gray-500">Use {otp}, {minutes}, {app_name} as placeholders</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                                Save OTP Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab Content: Email OTP Settings -->
                <div id="content-email-otp" class="tab-content hidden">
                    <form action="{{ route('admin.auth-settings.update-email-otp') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Email OTP Configuration -->
                            <div class="card rounded-lg p-6">
                                <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 18px;">mail</span>
                                    Email OTP Configuration
                                </h3>

                                <div class="space-y-4">
                                    <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="email_otp_enabled" value="0">
                                            <input type="checkbox" name="email_otp_enabled" value="1" class="sr-only peer" {{ $emailOtpSettings['enabled'] ?? true ? 'checked' : '' }}>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                        </label>
                                        <div>
                                            <p class="text-sm font-medium text-white">Enable Email OTP</p>
                                            <p class="text-xs text-gray-500 mt-0.5">Allow users to receive OTP via email</p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Email OTP Code Length *</label>
                                        <input type="number" name="email_otp_length" value="{{ $emailOtpSettings['length'] ?? 6 }}" min="4" max="8" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">4 to 8 digits (recommended: 6)</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Email Subject *</label>
                                        <input type="text" name="email_otp_subject" value="{{ $emailOtpSettings['email_subject'] ?? 'Your Verification Code' }}" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Your Verification Code">
                                        <p class="mt-1 text-[10px] text-gray-500">Email subject line for OTP emails</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Email Placeholder *</label>
                                        <input type="text" name="email_placeholder" value="{{ $loginSettings['email_placeholder'] ?? 'Enter your email address' }}" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Enter your email address">
                                        <p class="mt-1 text-[10px] text-gray-500">Placeholder text in email input field</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Email OTP Behavior Settings -->
                            <div class="card rounded-lg p-6">
                                <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 18px;">tune</span>
                                    Behavior Settings
                                </h3>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">OTP Expiry Time (minutes) *</label>
                                        <input type="number" name="email_otp_expiry_minutes" value="{{ $emailOtpSettings['expiry_minutes'] ?? 5 }}" min="1" max="30" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">How long email OTP remains valid</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Resend Cooldown (seconds) *</label>
                                        <input type="number" name="email_otp_resend_cooldown" value="{{ $emailOtpSettings['resend_cooldown'] ?? 60 }}" min="30" max="300" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">Wait time between email OTP resend requests</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Maximum Attempts *</label>
                                        <input type="number" name="email_otp_max_attempts" value="{{ $emailOtpSettings['max_attempts'] ?? 3 }}" min="3" max="10" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">Max email OTP verification attempts before blocking</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Email Template *</label>
                                        <textarea name="email_otp_template" rows="5" required
                                                  class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono">{{ $emailOtpSettings['email_template'] ?? 'Your verification code is: {otp}. Valid for {minutes} minutes. - {app_name}' }}</textarea>
                                        <p class="mt-1 text-[10px] text-gray-500">Use {otp}, {minutes}, {app_name} as placeholders</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="mt-6 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                            <div class="flex items-start gap-3">
                                <span class="material-icons-outlined text-blue-400" style="font-size: 20px;">info</span>
                                <div class="text-xs text-blue-300">
                                    <p class="font-semibold mb-1">Email Configuration Required:</p>
                                    <p class="text-blue-300/90 mb-2">Make sure to configure your email settings in .env file:</p>
                                    <pre class="text-[10px] bg-black/30 p-2 rounded mt-2 text-blue-200 overflow-x-auto">MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com</pre>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                                Save Email OTP Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab Content: Login Screen -->
                <div id="content-login" class="tab-content hidden">
                    <form action="{{ route('admin.auth-settings.update-login') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Text & Content -->
                            <div class="card rounded-lg p-6">
                                <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 18px;">text_fields</span>
                                    Text & Content
                                </h3>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Login Title *</label>
                                        <input type="text" name="title" value="{{ $loginSettings['title'] }}" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Welcome Back!">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Subtitle</label>
                                        <input type="text" name="subtitle" value="{{ $loginSettings['subtitle'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Sign in to continue">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Phone Placeholder *</label>
                                        <input type="text" name="phone_placeholder" value="{{ $loginSettings['phone_placeholder'] }}" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Enter your mobile number">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Button Text *</label>
                                        <input type="text" name="button_text" value="{{ $loginSettings['button_text'] }}" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Send OTP">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Terms & Conditions Text</label>
                                        <textarea name="terms_text" rows="2"
                                                  class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                                  placeholder="By continuing, you agree...">{{ $loginSettings['terms_text'] }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Footer Text</label>
                                        <input type="text" name="footer_text" value="{{ $loginSettings['footer_text'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Need help? Contact support@...">
                                    </div>
                                </div>
                            </div>

                            <!-- Appearance & Branding -->
                            <div class="card rounded-lg p-6">
                                <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 18px;">palette</span>
                                    Appearance & Branding
                                </h3>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Login Logo</label>
                                        @if($loginSettings['logo'])
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $loginSettings['logo']) }}" alt="Current Logo" class="h-16 rounded border border-gray-800">
                                        </div>
                                        @endif
                                        <input type="file" name="logo" accept="image/*"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">Max 2MB, PNG or SVG recommended</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Background Color *</label>
                                        <div class="flex items-center gap-3">
                                            <input type="color" id="bgColorPicker" name="background_color" value="{{ $loginSettings['background_color'] }}" required>
                                            <input type="text" id="bgColorText" value="{{ $loginSettings['background_color'] }}"
                                                   class="flex-1 px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                                   pattern="^#[0-9A-Fa-f]{6}$">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Primary Color *</label>
                                        <div class="flex items-center gap-3">
                                            <input type="color" id="primaryColorPicker" name="primary_color" value="{{ $loginSettings['primary_color'] }}" required>
                                            <input type="text" id="primaryColorText" value="{{ $loginSettings['primary_color'] }}"
                                                   class="flex-1 px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                                   pattern="^#[0-9A-Fa-f]{6}$">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Text Color *</label>
                                        <div class="flex items-center gap-3">
                                            <input type="color" id="textColorPicker" name="text_color" value="{{ $loginSettings['text_color'] }}" required>
                                            <input type="text" id="textColorText" value="{{ $loginSettings['text_color'] }}"
                                                   class="flex-1 px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                                   pattern="^#[0-9A-Fa-f]{6}$">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                                Save Login Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab Content: Social Login -->
                <div id="content-social" class="tab-content hidden">
                    <form action="{{ route('admin.auth-settings.update-social') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Google Sign-In -->
                            <div class="card rounded-lg p-6">
                                <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 18px;">g_translate</span>
                                    Google Sign-In
                                </h3>

                                <div class="space-y-4">
                                    <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="google_enabled" value="0">
                                            <input type="checkbox" name="google_enabled" value="1" class="sr-only peer" {{ $socialSettings['google_enabled'] ? 'checked' : '' }}>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                        </label>
                                        <div>
                                            <p class="text-sm font-medium text-white">Enable Google Sign-In</p>
                                            <p class="text-xs text-gray-500 mt-0.5">Allow users to login with Google</p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Google Client ID</label>
                                        <input type="text" name="google_client_id" value="{{ $socialSettings['google_client_id'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="your-client-id.apps.googleusercontent.com">
                                        <p class="mt-1 text-[10px] text-gray-500">From Google Cloud Console</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Google Client Secret</label>
                                        <input type="password" name="google_client_secret" value="{{ $socialSettings['google_client_secret'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="GOCSPX-*********************">
                                        <p class="mt-1 text-[10px] text-gray-500">Keep this secret secure</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Google Redirect URL</label>
                                        <input type="text" name="google_redirect_url" value="{{ $socialSettings['google_redirect_url'] ?? url('/auth/google/callback') }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="{{ url('/auth/google/callback') }}">
                                        <p class="mt-1 text-[10px] text-gray-500">Callback URL for Google OAuth (add this in Google Console)</p>
                                    </div>

                                    <!-- Setup Instructions -->
                                    <div class="mt-4 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                                        <div class="flex items-start gap-3">
                                            <span class="material-icons-outlined text-blue-400" style="font-size: 18px;">info</span>
                                            <div class="text-xs text-blue-300">
                                                <p class="font-semibold mb-2">Google OAuth Setup:</p>
                                                <ol class="list-decimal list-inside space-y-1 text-[10px] text-blue-300/90">
                                                    <li>Go to <a href="https://console.cloud.google.com" target="_blank" class="underline hover:text-blue-200">Google Cloud Console</a></li>
                                                    <li>Create project → APIs & Services → Credentials</li>
                                                    <li>Create OAuth 2.0 Client ID (Web application)</li>
                                                    <li>Add redirect URL from above to Authorized redirect URIs</li>
                                                    <li>Copy Client ID and Secret here</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Login Method Control -->
                            <div class="card rounded-lg p-6">
                                <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 18px;">toggle_on</span>
                                    Login Method Control
                                </h3>

                                <div class="space-y-4">
                                    <!-- OTP Login Toggle -->
                                    <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="otp_login_enabled" value="0">
                                            <input type="checkbox" name="otp_login_enabled" value="1" class="sr-only peer" {{ $socialSettings['otp_login_enabled'] ?? true ? 'checked' : '' }}>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                        </label>
                                        <div>
                                            <p class="text-sm font-medium text-white">Mobile Number (OTP) Login</p>
                                            <p class="text-xs text-gray-500 mt-0.5">Allow users to login with mobile number & OTP</p>
                                        </div>
                                    </div>

                                    <!-- Email OTP Login Toggle -->
                                    <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="email_otp_login_enabled" value="0">
                                            <input type="checkbox" name="email_otp_login_enabled" value="1" class="sr-only peer" {{ $socialSettings['email_otp_login_enabled'] ?? true ? 'checked' : '' }}>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                        </label>
                                        <div>
                                            <p class="text-sm font-medium text-white">Email Address (OTP) Login</p>
                                            <p class="text-xs text-gray-500 mt-0.5">Allow users to login with email address & OTP</p>
                                        </div>
                                    </div>

                                    <!-- WhatsApp OTP Login Toggle -->
                                    <div class="flex items-start gap-3 p-4 bg-green-500/10 border border-green-500/30 rounded-lg">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="whatsapp_otp_enabled" value="0">
                                            <input type="checkbox" name="whatsapp_otp_enabled" value="1" class="sr-only peer" {{ $socialSettings['whatsapp_otp_enabled'] ?? false ? 'checked' : '' }}>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                        </label>
                                        <div>
                                            <p class="text-sm font-medium text-green-300">WhatsApp OTP Login</p>
                                            <p class="text-xs text-gray-500 mt-0.5">Send OTP via WhatsApp (Renflair API)</p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Renflair API Key</label>
                                        <input type="text" name="renflair_api_key" value="{{ $socialSettings['renflair_api_key'] ?? '' }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-green-500 transition-colors font-mono"
                                               placeholder="your-renflair-api-key">
                                        <p class="mt-1 text-[10px] text-gray-500">API key from renflair.in for WhatsApp OTP</p>
                                    </div>

                                    <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg opacity-50">
                                        <label class="relative inline-flex items-center cursor-not-allowed">
                                            <input type="checkbox" name="apple_enabled" class="sr-only peer" {{ $socialSettings['apple_enabled'] ? 'checked' : '' }} disabled>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                        </label>
                                        <div>
                                            <p class="text-sm font-medium text-white">Apple Sign-In</p>
                                            <p class="text-xs text-gray-500 mt-0.5">Coming Soon</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg opacity-50">
                                        <label class="relative inline-flex items-center cursor-not-allowed">
                                            <input type="checkbox" name="facebook_enabled" class="sr-only peer" {{ $socialSettings['facebook_enabled'] ? 'checked' : '' }} disabled>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                        </label>
                                        <div>
                                            <p class="text-sm font-medium text-white">Facebook Sign-In</p>
                                            <p class="text-xs text-gray-500 mt-0.5">Coming Soon</p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Divider Text</label>
                                        <input type="text" name="divider_text" value="{{ $socialSettings['divider_text'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="OR">
                                        <p class="mt-1 text-[10px] text-gray-500">Text shown between different login methods</p>
                                    </div>

                                    <!-- Warning -->
                                    <div class="p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                                        <div class="flex items-start gap-3">
                                            <span class="material-icons-outlined text-yellow-400" style="font-size: 18px;">warning</span>
                                            <p class="text-xs text-yellow-300">At least one login method must be enabled. Disabling all methods will prevent users from logging in!</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                                Save Social Login Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab Content: Security -->
                <div id="content-security" class="tab-content hidden">
                    <form action="{{ route('admin.auth-settings.update-security') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card rounded-lg p-6 max-w-2xl">
                            <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 18px;">shield</span>
                                Security Settings
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-2">Maximum Devices Per User *</label>
                                    <input type="number" name="max_devices" value="{{ $securitySettings['max_devices'] }}" min="1" max="10" required
                                           class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                    <p class="mt-1 text-[10px] text-gray-500">How many devices can be logged in simultaneously</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-2">Session Timeout (minutes) *</label>
                                    <input type="number" name="session_timeout" value="{{ $securitySettings['session_timeout'] }}" min="60" max="10080" required
                                           class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                    <p class="mt-1 text-[10px] text-gray-500">1440 minutes = 24 hours, 10080 = 7 days</p>
                                </div>

                                <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="auto_logout_inactive" value="0">
                                        <input type="checkbox" name="auto_logout_inactive" value="1" class="sr-only peer" {{ $securitySettings['auto_logout_inactive'] ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                    <div>
                                        <p class="text-sm font-medium text-white">Auto Logout on Inactivity</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Logout users after session timeout period</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="require_phone_verification" value="0">
                                        <input type="checkbox" name="require_phone_verification" value="1" class="sr-only peer" {{ $securitySettings['require_phone_verification'] ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                    <div>
                                        <p class="text-sm font-medium text-white">Require Phone Verification</p>
                                        <p class="text-xs text-gray-500 mt-0.5">All users must verify phone number</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                                    Save Security Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tab Switching
        function switchTab(tab) {
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Remove active from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active', 'text-white');
                btn.classList.add('text-gray-400');
            });

            // Show selected content
            document.getElementById('content-' + tab).classList.remove('hidden');

            // Set active button
            const activeBtn = document.getElementById('tab-' + tab);
            activeBtn.classList.add('active', 'text-white');
            activeBtn.classList.remove('text-gray-400');
        }

        // Color Picker Synchronization
        document.addEventListener('DOMContentLoaded', function() {
            // Background Color
            const bgPicker = document.getElementById('bgColorPicker');
            const bgText = document.getElementById('bgColorText');
            if (bgPicker && bgText) {
                bgPicker.addEventListener('input', function() {
                    bgText.value = this.value;
                });
                bgText.addEventListener('input', function() {
                    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                        bgPicker.value = this.value;
                    }
                });
            }

            // Primary Color
            const primaryPicker = document.getElementById('primaryColorPicker');
            const primaryText = document.getElementById('primaryColorText');
            if (primaryPicker && primaryText) {
                primaryPicker.addEventListener('input', function() {
                    primaryText.value = this.value;
                });
                primaryText.addEventListener('input', function() {
                    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                        primaryPicker.value = this.value;
                    }
                });
            }

            // Text Color
            const textPicker = document.getElementById('textColorPicker');
            const textText = document.getElementById('textColorText');
            if (textPicker && textText) {
                textPicker.addEventListener('input', function() {
                    textText.value = this.value;
                });
                textText.addEventListener('input', function() {
                    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                        textPicker.value = this.value;
                    }
                });
            }
        });

        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 text-sm text-white ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>
</html>

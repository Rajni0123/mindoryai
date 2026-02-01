<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mobile App Configuration</title>

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
                        <h1 class="text-base font-semibold text-white">Dynamic App Configuration</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Control your mobile app without redeploying - 36+ configs available</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ url('/api/app/configs') }}" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-lg transition-colors">
                            <span class="material-icons-outlined" style="font-size: 16px;">api</span>
                            View Public API
                        </a>
                        <a href="{{ url('DYNAMIC_APP_CONFIG_GUIDE.md') }}" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                            <span class="material-icons-outlined" style="font-size: 16px;">description</span>
                            View Guide
                        </a>
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
                            <p class="font-semibold mb-1">Dynamic App Configuration System:</p>
                            <p class="text-blue-300/90">Control 36+ aspects of your mobile app instantly without app store updates. Changes are fetched on app startup with automatic version control.</p>
                            <p class="text-blue-300/90 mt-2"><strong>API Endpoint:</strong> <code class="bg-blue-900/30 px-2 py-0.5 rounded">/api/app/configs</code> | <strong>Icon Version:</strong> v{{ $configs['icon_version'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="mb-6 border-b border-gray-800/50 overflow-x-auto">
                    <div class="flex gap-4 min-w-max">
                        <button onclick="switchTab('branding')" class="tab-btn active px-4 py-3 text-sm font-medium text-white hover:text-blue-400 transition-colors whitespace-nowrap" id="tab-branding">
                            <span class="material-icons-outlined align-middle mr-1" style="font-size: 16px;">palette</span>
                            Branding
                        </button>
                        <button onclick="switchTab('features')" class="tab-btn px-4 py-3 text-sm font-medium text-gray-400 hover:text-blue-400 transition-colors whitespace-nowrap" id="tab-features">
                            <span class="material-icons-outlined align-middle mr-1" style="font-size: 16px;">toggle_on</span>
                            Features
                        </button>
                        <button onclick="switchTab('updates')" class="tab-btn px-4 py-3 text-sm font-medium text-gray-400 hover:text-blue-400 transition-colors whitespace-nowrap" id="tab-updates">
                            <span class="material-icons-outlined align-middle mr-1" style="font-size: 16px;">system_update</span>
                            App Updates
                        </button>
                        <button onclick="switchTab('ai')" class="tab-btn px-4 py-3 text-sm font-medium text-gray-400 hover:text-blue-400 transition-colors whitespace-nowrap" id="tab-ai">
                            <span class="material-icons-outlined align-middle mr-1" style="font-size: 16px;">psychology</span>
                            AI Settings
                        </button>
                        <button onclick="switchTab('content')" class="tab-btn px-4 py-3 text-sm font-medium text-gray-400 hover:text-blue-400 transition-colors whitespace-nowrap" id="tab-content">
                            <span class="material-icons-outlined align-middle mr-1" style="font-size: 16px;">article</span>
                            Content
                        </button>
                        <button onclick="switchTab('ads')" class="tab-btn px-4 py-3 text-sm font-medium text-gray-400 hover:text-blue-400 transition-colors whitespace-nowrap" id="tab-ads">
                            <span class="material-icons-outlined align-middle mr-1" style="font-size: 16px;">ads_click</span>
                            Ads
                        </button>
                    </div>
                </div>

                <!-- Tab Content: Branding -->
                <div id="content-branding" class="tab-content">
                    <form action="{{ route('admin.mobile-app-config.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- App Information -->
                            <div class="card rounded-lg p-6">
                                <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 18px;">info</span>
                                    App Information
                                </h3>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">App Name</label>
                                        <input type="text" name="app_name" value="{{ $configs['app_name'] }}" required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Mindory AI">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">App Logo</label>
                                        @if($configs['app_logo'])
                                        <div class="mb-2">
                                            <img src="{{ $configs['app_logo'] }}" alt="Current Logo" class="h-16 rounded border border-gray-800">
                                        </div>
                                        @endif
                                        <input type="file" name="app_logo" accept="image/*"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">Max 3MB, PNG or JPG recommended</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">App Icon (512x512 PNG)</label>
                                        @if($configs['app_icon'])
                                        <div class="mb-2 flex items-center gap-3">
                                            <img src="{{ $configs['app_icon'] }}" alt="Current Icon" class="h-16 rounded-lg border border-gray-800">
                                            <div class="text-xs">
                                                <p class="text-gray-400">Current Version: <span class="text-blue-400 font-mono">v{{ $configs['icon_version'] }}</span></p>
                                                <p class="text-gray-500">Apps will auto-update on next launch</p>
                                            </div>
                                        </div>
                                        @endif
                                        <input type="file" name="app_icon" accept="image/*"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">Max 3MB, 512x512 square PNG recommended. Upload increments version automatically.</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Login Screen Logo</label>
                                        @if($configs['login_logo'])
                                        <div class="mb-2 flex items-center gap-3">
                                            <img src="{{ $configs['login_logo'] }}" alt="Login Logo" class="h-16 rounded-lg border border-gray-800 bg-gray-900 p-2">
                                            <div class="text-xs">
                                                <p class="text-gray-400">Displayed on app login screen</p>
                                                <p class="text-gray-500">Recommended: Square PNG with transparent background</p>
                                            </div>
                                        </div>
                                        @endif
                                        <input type="file" name="login_logo" accept="image/*"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">Max 3MB, PNG recommended. This logo appears on the mobile app login screen.</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Splash Screen Image</label>
                                        @if($configs['splash_screen'])
                                        <div class="mb-2 flex items-center gap-3">
                                            <img src="{{ $configs['splash_screen'] }}" alt="Splash Screen" class="h-20 rounded-lg border border-gray-800 bg-gray-900 p-1">
                                            <div class="text-xs">
                                                <p class="text-gray-400">Shown when app is loading</p>
                                                <p class="text-gray-500">Recommended: 1080x1920 PNG or JPG</p>
                                            </div>
                                        </div>
                                        @endif
                                        <input type="file" name="splash_screen" accept="image/*"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                        <p class="mt-1 text-[10px] text-gray-500">Max 3MB, 1080x1920 PNG recommended. Displayed as app splash/loading screen.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Theme Colors -->
                            <div class="card rounded-lg p-6">
                                <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 18px;">color_lens</span>
                                    Theme Colors
                                </h3>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Primary Color *</label>
                                        <div class="flex items-center gap-3">
                                            <input type="color" id="primaryColorPicker" name="primary_color" value="{{ $configs['primary_color'] }}" required>
                                            <input type="text" id="primaryColorText" value="{{ $configs['primary_color'] }}"
                                                   class="flex-1 px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                                   pattern="^#[0-9A-Fa-f]{6}$">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Secondary Color</label>
                                        <div class="flex items-center gap-3">
                                            <input type="color" id="secondaryColorPicker" name="secondary_color" value="{{ $configs['secondary_color'] }}">
                                            <input type="text" id="secondaryColorText" value="{{ $configs['secondary_color'] }}"
                                                   class="flex-1 px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                                   pattern="^#[0-9A-Fa-f]{6}$">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Background Color</label>
                                        <div class="flex items-center gap-3">
                                            <input type="color" id="backgroundColorPicker" name="background_color" value="{{ $configs['background_color'] }}">
                                            <input type="text" id="backgroundColorText" value="{{ $configs['background_color'] }}"
                                                   class="flex-1 px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                                   pattern="^#[0-9A-Fa-f]{6}$">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Default Theme *</label>
                                        <select name="theme" required
                                                class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                                style="color-scheme: dark;">
                                            <option value="light" {{ $configs['theme'] === 'light' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">Light</option>
                                            <option value="dark" {{ $configs['theme'] === 'dark' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">Dark</option>
                                            <option value="auto" {{ $configs['theme'] === 'auto' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">Auto (System)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Font Family</label>
                                        <select name="font_family"
                                                class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                                style="color-scheme: dark;">
                                            <option value="Outfit" {{ $configs['font_family'] === 'Outfit' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">Outfit</option>
                                            <option value="system" {{ $configs['font_family'] === 'system' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">System Default</option>
                                            <option value="Inter" {{ $configs['font_family'] === 'Inter' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">Inter</option>
                                            <option value="Roboto" {{ $configs['font_family'] === 'Roboto' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">Roboto</option>
                                            <option value="Poppins" {{ $configs['font_family'] === 'Poppins' ? 'selected' : '' }} style="background-color: #1a1a1a; color: #ffffff;">Poppins</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                                Save Branding Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab Content: Features -->
                <div id="content-features" class="tab-content hidden">
                    <form action="{{ route('admin.mobile-app-config.update-features') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card rounded-lg p-6">
                            <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 18px;">settings</span>
                                Feature Toggles
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="feature_chat_enabled" value="0">
                                        <input type="checkbox" name="feature_chat_enabled" value="1" class="sr-only peer" {{ $configs['features']['chat_enabled'] ?? true ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                    <div>
                                        <p class="text-sm font-medium text-white">Chat Feature</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Enable/disable AI chat</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="feature_quiz_from_image" value="0">
                                        <input type="checkbox" name="feature_quiz_from_image" value="1" class="sr-only peer" {{ $configs['features']['quiz_from_image'] ?? true ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                    <div>
                                        <p class="text-sm font-medium text-white">Quiz from Image</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Generate quizzes from images</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="feature_voice_input" value="0">
                                        <input type="checkbox" name="feature_voice_input" value="1" class="sr-only peer" {{ $configs['features']['voice_input'] ?? true ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                    <div>
                                        <p class="text-sm font-medium text-white">Voice Input</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Enable voice-to-text input</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="feature_image_upload" value="0">
                                        <input type="checkbox" name="feature_image_upload" value="1" class="sr-only peer" {{ $configs['features']['image_upload'] ?? true ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                    <div>
                                        <p class="text-sm font-medium text-white">Image Upload</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Upload images in chat</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="feature_pdf_upload" value="0">
                                        <input type="checkbox" name="feature_pdf_upload" value="1" class="sr-only peer" {{ $configs['features']['pdf_upload'] ?? true ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                    <div>
                                        <p class="text-sm font-medium text-white">PDF Upload</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Allow PDF document uploads</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="feature_notebook" value="0">
                                        <input type="checkbox" name="feature_notebook" value="1" class="sr-only peer" {{ $configs['features']['notebook'] ?? true ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                    <div>
                                        <p class="text-sm font-medium text-white">Notebook Feature</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Document scanning & notes</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="feature_referral_system" value="0">
                                        <input type="checkbox" name="feature_referral_system" value="1" class="sr-only peer" {{ $configs['features']['referral_system'] ?? true ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                    <div>
                                        <p class="text-sm font-medium text-white">Referral System</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Enable referral code system</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="feature_share_chat" value="0">
                                        <input type="checkbox" name="feature_share_chat" value="1" class="sr-only peer" {{ $configs['features']['share_chat'] ?? true ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                    <div>
                                        <p class="text-sm font-medium text-white">Share Chat</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Enable chat sharing</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                                    Save Features
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab Content: App Updates -->
                <div id="content-updates" class="tab-content hidden">
                    <form action="{{ route('admin.mobile-app-config.update-version') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card rounded-lg p-6">
                            <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 18px;">update</span>
                                App Update Settings
                            </h3>

                            <div class="space-y-6">
                                <div class="p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                                    <div class="flex items-start gap-3">
                                        <span class="material-icons-outlined text-yellow-400" style="font-size: 20px;">warning</span>
                                        <div class="text-xs text-yellow-300">
                                            <p class="font-semibold mb-1">Force Update Warning:</p>
                                            <p class="text-yellow-300/90">When enabled, users with older versions will be forced to update before using the app. Use with caution!</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Latest Version</label>
                                        <input type="text" name="latest_version" value="{{ $configs['latest_version'] }}"
                                               pattern="\d+\.\d+\.\d+"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="1.0.0">
                                        <p class="mt-1 text-[10px] text-gray-500">Current app version in stores</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Minimum Supported Version</label>
                                        <input type="text" name="min_supported_version" value="{{ $configs['min_supported_version'] }}"
                                               pattern="\d+\.\d+\.\d+"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="1.0.0">
                                        <p class="mt-1 text-[10px] text-gray-500">Oldest working version</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-4 bg-white/5 rounded-lg">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="force_update" value="0">
                                        <input type="checkbox" name="force_update" value="1" class="sr-only peer" {{ $configs['force_update'] ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                    </label>
                                    <div>
                                        <p class="text-sm font-medium text-white">Force App Update</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Block app usage until user updates</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-2">Update Message</label>
                                    <textarea name="update_message" rows="3"
                                              class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                              placeholder="A new version is available. Please update to continue.">{{ $configs['update_message'] }}</textarea>
                                    <p class="mt-1 text-[10px] text-gray-500">Message shown to users when update is required</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Android Update URL</label>
                                        <input type="url" name="update_url_android" value="{{ $configs['update_url_android'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="https://play.google.com/store/apps/details?id=com.mindory.app">
                                        <p class="mt-1 text-[10px] text-gray-500">Play Store link</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">iOS Update URL</label>
                                        <input type="url" name="update_url_ios" value="{{ $configs['update_url_ios'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="https://apps.apple.com/app/mindory">
                                        <p class="mt-1 text-[10px] text-gray-500">App Store link</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                                    Save Update Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab Content: AI Settings -->
                <div id="content-ai" class="tab-content hidden">
                    <div class="card rounded-lg p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 18px;">psychology</span>
                                AI Models Configuration
                            </h3>
                            <a href="{{ route('admin.ai-models.index') }}" class="text-xs text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                <span>Manage AI Models</span>
                                <span class="material-icons-outlined" style="font-size: 14px;">open_in_new</span>
                            </a>
                        </div>

                        <div class="p-4 bg-blue-500/10 border border-blue-500/20 rounded-lg mb-6">
                            <div class="flex gap-3">
                                <span class="material-icons-outlined text-blue-400" style="font-size: 20px;">info</span>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-300">
                                        AI models are managed from the <strong>AI Models</strong> section. Both mobile app and website use the same AI models configured there.
                                    </p>
                                    <a href="{{ route('admin.ai-models.index') }}" class="text-xs text-blue-400 hover:text-blue-300 mt-2 inline-flex items-center gap-1">
                                        Go to AI Models →
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="text-xs font-medium text-gray-400">Active AI Models</h4>
                            @php $aiModels = \App\Models\AiModel::where('is_active', true)->orderBy('order')->get(); @endphp
                            @if($aiModels->count() > 0)
                                @foreach($aiModels as $model)
                                <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg border border-white/10">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: {{ $model->color }}20;">
                                            @if($model->icon)
                                                <span style="font-size: 18px;">{{ $model->icon }}</span>
                                            @else
                                                <span class="material-icons-outlined text-white" style="font-size: 16px;">psychology</span>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-white">{{ $model->name }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $model->provider }} • Max tokens: {{ number_format($model->max_tokens) }}</p>
                                        </div>
                                    </div>
                                    @if($model->supports_vision)
                                    <span class="text-[10px] px-2 py-1 bg-green-500/20 text-green-400 rounded">Vision</span>
                                    @endif
                                </div>
                                @endforeach
                            @else
                                <div class="p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-lg text-center">
                                    <p class="text-xs text-yellow-400">No active AI models found. <a href="{{ route('admin.ai-models.index') }}" class="underline">Add AI Models</a> to get started.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Content -->
                <div id="content-content" class="tab-content hidden">
                    <form action="{{ route('admin.mobile-app-config.update-content') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card rounded-lg p-6">
                            <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 18px;">article</span>
                                Content & Legal
                            </h3>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-2">Welcome Message</label>
                                    <textarea name="welcome_message" rows="3"
                                              class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                              placeholder="Welcome to Mindory AI!">{{ $configs['welcome_message'] }}</textarea>
                                    <p class="mt-1 text-[10px] text-gray-500">First-time user greeting message</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Help Center URL</label>
                                        <input type="url" name="help_url" value="{{ $configs['help_url'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="https://mindory.in/help">
                                        <p class="mt-1 text-[10px] text-gray-500">Link to help page</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Privacy Policy URL</label>
                                        <input type="url" name="privacy_policy_url" value="{{ $configs['privacy_policy_url'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="https://mindory.in/privacy">
                                        <p class="mt-1 text-[10px] text-gray-500">Link to privacy policy</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Terms of Service URL</label>
                                        <input type="url" name="terms_url" value="{{ $configs['terms_url'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="https://mindory.in/terms">
                                        <p class="mt-1 text-[10px] text-gray-500">Link to terms of service</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                                    Save Content Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab Content: Ads -->
                <div id="content-ads" class="tab-content hidden">
                    <form action="{{ route('admin.mobile-app-config.update-ads') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            <!-- Info Box -->
                            <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <span class="material-icons-outlined text-blue-400" style="font-size: 20px;">info</span>
                                    <div class="text-sm text-blue-300">
                                        <p class="font-medium mb-1">How Ads Work</p>
                                        <p class="text-blue-400/80">Ads are shown <strong>only to free-tier users</strong>. Users on paid plans will never see ads. Configure your Google AdMob unit IDs below. Use test IDs during development.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Global Toggle -->
                            <div class="bg-white/[0.02] border border-white/5 rounded-lg p-5">
                                <h3 class="text-sm font-semibold text-blue-400 flex items-center gap-1.5 mb-4">
                                    <span class="material-icons-outlined" style="font-size: 16px;">toggle_on</span>
                                    Global Ad Control
                                </h3>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="hidden" name="ads_enabled" value="0">
                                    <input type="checkbox" name="ads_enabled" value="1" {{ $configs['ads_enabled'] ? 'checked' : '' }}
                                           class="w-5 h-5 rounded bg-white/5 border-white/20 text-blue-500 focus:ring-blue-500 focus:ring-offset-0">
                                    <div>
                                        <span class="text-sm text-white font-medium">Enable Ads</span>
                                        <p class="text-[10px] text-gray-500">Master switch - disabling this turns off ALL ads for ALL users</p>
                                    </div>
                                </label>
                            </div>

                            <!-- AdMob App IDs -->
                            <div class="bg-white/[0.02] border border-white/5 rounded-lg p-5">
                                <h3 class="text-sm font-semibold text-blue-400 flex items-center gap-1.5 mb-4">
                                    <span class="material-icons-outlined" style="font-size: 16px;">key</span>
                                    AdMob App IDs
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Android App ID</label>
                                        <input type="text" name="admob_app_id_android" value="{{ $configs['admob_app_id_android'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="ca-app-pub-XXXXXXXXXXXXXXXX~XXXXXXXXXX">
                                        <p class="mt-1 text-[10px] text-gray-500">From Google AdMob Console > Apps</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">iOS App ID</label>
                                        <input type="text" name="admob_app_id_ios" value="{{ $configs['admob_app_id_ios'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="ca-app-pub-XXXXXXXXXXXXXXXX~XXXXXXXXXX">
                                        <p class="mt-1 text-[10px] text-gray-500">From Google AdMob Console > Apps</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Banner Ads -->
                            <div class="bg-white/[0.02] border border-white/5 rounded-lg p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-semibold text-green-400 flex items-center gap-1.5">
                                        <span class="material-icons-outlined" style="font-size: 16px;">view_agenda</span>
                                        Banner Ads
                                    </h3>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="ads_banner_enabled" value="0">
                                        <input type="checkbox" name="ads_banner_enabled" value="1" {{ $configs['ads_banner_enabled'] ? 'checked' : '' }}
                                               class="w-4 h-4 rounded bg-white/5 border-white/20 text-green-500 focus:ring-green-500 focus:ring-offset-0">
                                        <span class="text-xs text-gray-400">Enabled</span>
                                    </label>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Android Banner Unit ID</label>
                                        <input type="text" name="admob_banner_id_android" value="{{ $configs['admob_banner_id_android'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="ca-app-pub-XXXX/XXXXXXXX">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">iOS Banner Unit ID</label>
                                        <input type="text" name="admob_banner_id_ios" value="{{ $configs['admob_banner_id_ios'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="ca-app-pub-XXXX/XXXXXXXX">
                                    </div>
                                </div>
                                <p class="mt-2 text-[10px] text-gray-500">Shown at bottom of Home, History, and Quiz screens</p>
                            </div>

                            <!-- Interstitial Ads -->
                            <div class="bg-white/[0.02] border border-white/5 rounded-lg p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-semibold text-yellow-400 flex items-center gap-1.5">
                                        <span class="material-icons-outlined" style="font-size: 16px;">fullscreen</span>
                                        Interstitial Ads
                                    </h3>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="ads_interstitial_enabled" value="0">
                                        <input type="checkbox" name="ads_interstitial_enabled" value="1" {{ $configs['ads_interstitial_enabled'] ? 'checked' : '' }}
                                               class="w-4 h-4 rounded bg-white/5 border-white/20 text-yellow-500 focus:ring-yellow-500 focus:ring-offset-0">
                                        <span class="text-xs text-gray-400">Enabled</span>
                                    </label>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Android Interstitial Unit ID</label>
                                        <input type="text" name="admob_interstitial_id_android" value="{{ $configs['admob_interstitial_id_android'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="ca-app-pub-XXXX/XXXXXXXX">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">iOS Interstitial Unit ID</label>
                                        <input type="text" name="admob_interstitial_id_ios" value="{{ $configs['admob_interstitial_id_ios'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="ca-app-pub-XXXX/XXXXXXXX">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-2">Show Every N Actions</label>
                                    <input type="number" name="ads_interstitial_frequency" value="{{ $configs['ads_interstitial_frequency'] }}" min="1" max="50"
                                           class="w-32 px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                    <p class="mt-1 text-[10px] text-gray-500">Show full-screen ad every N quiz completions / chat sessions</p>
                                </div>
                            </div>

                            <!-- Rewarded Ads -->
                            <div class="bg-white/[0.02] border border-white/5 rounded-lg p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-semibold text-purple-400 flex items-center gap-1.5">
                                        <span class="material-icons-outlined" style="font-size: 16px;">card_giftcard</span>
                                        Rewarded Ads
                                    </h3>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="ads_rewarded_enabled" value="0">
                                        <input type="checkbox" name="ads_rewarded_enabled" value="1" {{ $configs['ads_rewarded_enabled'] ? 'checked' : '' }}
                                               class="w-4 h-4 rounded bg-white/5 border-white/20 text-purple-500 focus:ring-purple-500 focus:ring-offset-0">
                                        <span class="text-xs text-gray-400">Enabled</span>
                                    </label>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Android Rewarded Unit ID</label>
                                        <input type="text" name="admob_rewarded_id_android" value="{{ $configs['admob_rewarded_id_android'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="ca-app-pub-XXXX/XXXXXXXX">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">iOS Rewarded Unit ID</label>
                                        <input type="text" name="admob_rewarded_id_ios" value="{{ $configs['admob_rewarded_id_ios'] }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                               placeholder="ca-app-pub-XXXX/XXXXXXXX">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-2">Credits Per Ad Watch</label>
                                    <input type="number" name="ads_rewarded_credits" value="{{ $configs['ads_rewarded_credits'] }}" min="1" max="50"
                                           class="w-32 px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                    <p class="mt-1 text-[10px] text-gray-500">Free credits earned when user watches a rewarded ad</p>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                                    Save Ad Settings
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

            // Secondary Color
            const secondaryPicker = document.getElementById('secondaryColorPicker');
            const secondaryText = document.getElementById('secondaryColorText');

            if (secondaryPicker && secondaryText) {
                secondaryPicker.addEventListener('input', function() {
                    secondaryText.value = this.value;
                });

                secondaryText.addEventListener('input', function() {
                    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                        secondaryPicker.value = this.value;
                    }
                });
            }

            // Background Color
            const backgroundPicker = document.getElementById('backgroundColorPicker');
            const backgroundText = document.getElementById('backgroundColorText');

            if (backgroundPicker && backgroundText) {
                backgroundPicker.addEventListener('input', function() {
                    backgroundText.value = this.value;
                });

                backgroundText.addEventListener('input', function() {
                    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                        backgroundPicker.value = this.value;
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

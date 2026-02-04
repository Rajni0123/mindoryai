<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Website Settings | Admin Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #09090b;
            min-height: 100vh;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="text-white">
    <div class="flex h-screen">
        <!-- Sidebar -->
        @include('admin.partials.sidebar')

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="glass-effect border-b border-white/5 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Website Settings</h2>
                        <p class="text-gray-400 text-sm mt-1">Configure your website and platform settings</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                @if(session('success'))
                <div class="bg-green-500/20 border border-green-500/50 rounded-lg p-4 flex items-center gap-3 mb-6">
                    <span class="material-icons-outlined text-green-400">check_circle</span>
                    <p class="text-green-300">{{ session('success') }}</p>
                </div>
                @endif

                <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

<!-- IP Whitelist Section -->
                    <div class="glass-effect rounded-xl p-6">
                        <h3 class="text-lg font-bold font-display text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined text-purple-400">security</span>
                            IP Whitelist Settings
                        </h3>

                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <input type="checkbox"
                                       id="ip_whitelist_enabled"
                                       name="ip_whitelist_enabled"
                                       {{ old('ip_whitelist_enabled', $settings['ip_whitelist_enabled']) == '1' ? 'checked' : '' }}
                                       class="w-5 h-5 rounded bg-gray-800/50 border-gray-700 text-blue-400 focus:ring-neon-blue focus:ring-offset-0">
                                <label for="ip_whitelist_enabled" class="text-sm text-soft-grey">
                                    Enable IP Whitelisting (Restrict access to specific IP addresses)
                                </label>
                            </div>

                            <div>
                                <label for="ip_whitelist" class="block text-sm font-semibold text-blue-400 mb-2">
                                    Whitelisted IP Addresses
                                </label>
                                <textarea id="ip_whitelist"
                                          name="ip_whitelist"
                                          rows="5"
                                          class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors font-mono text-sm"
                                          placeholder="192.168.1.1&#10;10.0.0.1&#10;172.16.0.1">{{ old('ip_whitelist', $settings['ip_whitelist']) }}</textarea>
                                <p class="mt-2 text-xs text-soft-grey/70">Enter one IP address per line. Only these IPs will be able to access the site when enabled.</p>
                            </div>
                        </div>
                    </div>

                    <!-- General Settings Section -->
                    <div class="glass-effect rounded-xl p-6">
                        <h3 class="text-lg font-bold font-display text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined text-blue-400">settings</span>
                            General Settings
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label for="site_name" class="block text-sm font-semibold text-blue-400 mb-2">
                                    Site Name
                                </label>
                                <input type="text"
                                       id="site_name"
                                       name="site_name"
                                       value="{{ old('site_name', $settings['site_name']) }}"
                                       class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="BlinkStudy">
                            </div>

                            <div class="flex items-center justify-between p-4 bg-gray-800/30 border border-gray-700 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="material-icons-outlined text-yellow-400">construction</span>
                                    <div>
                                        <p class="text-sm font-semibold text-white">Maintenance Mode</p>
                                        <p class="text-xs text-soft-grey/70">Site will be temporarily unavailable when enabled</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           id="maintenance_mode"
                                           name="maintenance_mode"
                                           {{ old('maintenance_mode', $settings['maintenance_mode']) == '1' ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-500/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-500"></div>
                                </label>
                            </div>

                            <div>
                                <label for="maintenance_message" class="block text-sm font-semibold text-yellow-400 mb-2">
                                    Maintenance Message
                                </label>
                                <textarea
                                    id="maintenance_message"
                                    name="maintenance_message"
                                    rows="3"
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-yellow-500 transition-colors resize-none"
                                    placeholder="We are currently performing scheduled maintenance. Please check back soon.">{{ old('maintenance_message', $settings['maintenance_message'] ?? 'We are currently performing scheduled maintenance. Please check back soon.') }}</textarea>
                                <p class="text-xs text-gray-500 mt-1">Message displayed to users when maintenance mode is enabled</p>
                            </div>
                        </div>
                    </div>

                    <!-- Page Sections Settings -->
                    <div class="glass-effect rounded-xl p-6">
                        <h3 class="text-lg font-bold font-display text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined text-purple-400">view_module</span>
                            Landing Page Sections
                        </h3>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-gray-800/30 border border-gray-700 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="material-icons-outlined text-blue-400">sell</span>
                                    <div>
                                        <p class="text-sm font-semibold text-white">Pricing Section</p>
                                        <p class="text-xs text-soft-grey/70">Show/hide "Simple, Transparent Pricing" section on landing page</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           id="show_pricing_section"
                                           name="show_pricing_section"
                                           {{ old('show_pricing_section', $settings['show_pricing_section'] ?? '1') == '1' ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-neon-blue/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-neon-blue"></div>
                                </label>
                            </div>

                            <div class="bg-blue-500/10 border border-blue-500/50 rounded-lg p-3 flex items-start gap-2">
                                <span class="material-icons-outlined text-blue-400 text-sm mt-0.5">info</span>
                                <p class="text-xs text-blue-300">Control which sections appear on your landing page. Disabled sections will be completely hidden from visitors.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end gap-4">
                        <button type="submit"
                                class="px-8 py-3 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg hover:shadow-[0_0_20px_0_rgba(61,220,255,0.5)] transition-all">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

</body>
</html>

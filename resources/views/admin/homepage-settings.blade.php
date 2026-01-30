<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Homepage Settings</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .card:hover { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.12); }
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
                        <h1 class="text-base font-semibold text-white">Homepage Settings</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Manage all homepage content dynamically</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button onclick="clearCache()" class="flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-lg transition-colors">
                            <span class="material-icons-outlined" style="font-size: 16px;">refresh</span>
                            Clear Cache
                        </button>
                        <button onclick="saveAllSettings()" class="flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                            <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                            Save All Changes
                        </button>
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
                <div class="mb-4 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                    <div class="flex items-start gap-3">
                        <span class="material-icons-outlined text-blue-400" style="font-size: 20px;">info</span>
                        <div class="text-xs text-blue-300">
                            <p class="font-semibold mb-1">About Homepage Settings:</p>
                            <p class="text-blue-300/90">Control all homepage content including logo, hero section, features, impact spotlight, CTA, and footer. Changes take effect immediately after cache clear.</p>
                        </div>
                    </div>
                </div>

                <form id="settingsForm" method="POST" action="{{ route('admin.homepage-settings.bulk-update') }}">
                    @csrf

                    @foreach($settings as $group => $groupSettings)
                    <!-- Section Card -->
                    <div class="card rounded-lg p-6 mb-6">
                        <h2 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined text-blue-400" style="font-size: 18px;">
                                @if($group === 'logo') image
                                @elseif($group === 'hero') rocket_launch
                                @elseif($group === 'stats') bar_chart
                                @elseif($group === 'features') apps
                                @elseif($group === 'subjects') menu_book
                                @elseif($group === 'impact') workspace_premium
                                @elseif($group === 'cta') campaign
                                @elseif($group === 'footer') view_agenda
                                @else settings
                                @endif
                            </span>
                            {{ ucfirst($group) }} Section
                        </h2>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @foreach($groupSettings as $setting)
                            <div class="@if($setting->type === 'textarea') lg:col-span-2 @endif">
                                <label class="block text-xs font-medium text-gray-400 mb-2">
                                    {{ ucwords(str_replace('_', ' ', str_replace($group . '_', '', $setting->key))) }}
                                </label>

                                @if($setting->type === 'text' || $setting->type === 'number')
                                    <input type="{{ $setting->type }}"
                                           name="settings[{{ $setting->key }}]"
                                           value="{{ $setting->value }}"
                                           class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">

                                @elseif($setting->type === 'textarea')
                                    <textarea name="settings[{{ $setting->key }}]"
                                              rows="3"
                                              class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">{{ $setting->value }}</textarea>

                                @elseif($setting->type === 'image')
                                    <div class="space-y-2">
                                        <input type="text"
                                               name="settings[{{ $setting->key }}]"
                                               value="{{ $setting->value }}"
                                               id="input-{{ $setting->key }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Image URL">
                                        <div class="flex gap-2">
                                            <button type="button"
                                                    onclick="uploadImage('{{ $setting->key }}')"
                                                    class="px-3 py-1.5 bg-blue-500/10 text-blue-400 rounded text-xs hover:bg-blue-500/20 transition-colors">
                                                Upload New Image
                                            </button>
                                            @if($setting->value)
                                            <a href="{{ $setting->value }}" target="_blank"
                                               class="px-3 py-1.5 bg-gray-500/10 text-gray-400 rounded text-xs hover:bg-gray-500/20 transition-colors">
                                                View Current
                                            </a>
                                            @endif
                                        </div>
                                        <input type="file"
                                               id="file-{{ $setting->key }}"
                                               accept="image/*"
                                               class="hidden"
                                               onchange="handleImageUpload('{{ $setting->key }}', this)">
                                    </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </form>
            </div>
        </main>
    </div>

    <script>
        function saveAllSettings() {
            document.getElementById('settingsForm').submit();
        }

        function clearCache() {
            if (!confirm('Clear all homepage settings cache?')) return;

            fetch('{{ route('admin.homepage-settings.clear-cache') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cache cleared successfully!');
                } else {
                    alert('Failed to clear cache: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to clear cache');
            });
        }

        function uploadImage(key) {
            document.getElementById('file-' + key).click();
        }

        function handleImageUpload(key, input) {
            if (!input.files || !input.files[0]) return;

            const formData = new FormData();
            formData.append('key', key);
            formData.append('image', input.files[0]);

            fetch('{{ route('admin.homepage-settings.upload-image') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('input-' + key).value = data.url;
                    alert('Image uploaded successfully!');
                } else {
                    alert('Failed to upload: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to upload image');
            });
        }
    </script>
</body>
</html>

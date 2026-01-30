<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Plan | Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        @include('admin.partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.pricing.index') }}" class="text-gray-500 hover:text-white text-xs mb-1 inline-flex items-center gap-1">
                            <span class="material-icons-outlined text-sm">arrow_back</span>
                            Back to Pricing
                        </a>
                        <h1 class="text-base font-semibold text-white">Create New Plan</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Add a new pricing plan</p>
                    </div>
                </div>
            </header>

            <div class="p-6">
                @if($errors->any())
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg">
                    <ul class="list-disc list-inside text-red-300 text-sm">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('admin.pricing.store') }}" method="POST" class="card rounded-lg p-6 max-w-4xl">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Plan Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                   placeholder="e.g., Basic, Premium">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Display Order</label>
                            <input type="number" name="order" value="{{ old('order', 0) }}" min="0"
                                   class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Price (₹) *</label>
                            <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0" required
                                   class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                   placeholder="299">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Period *</label>
                            <input type="text" name="period" value="{{ old('period') }}" required
                                   class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                   placeholder="e.g., month, year, once">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Features *</label>
                        <div id="features-container" class="space-y-2">
                            <div class="flex gap-2 feature-item">
                                <input type="text" name="features[]" value="{{ old('features.0') }}" required
                                       class="flex-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                       placeholder="Enter feature">
                                <button type="button" onclick="removeFeature(this)"
                                        class="px-3 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition-colors">
                                    <span class="material-icons-outlined text-sm">delete</span>
                                </button>
                            </div>
                        </div>
                        <button type="button" onclick="addFeature()"
                                class="mt-2 inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500/10 text-blue-400 rounded-lg hover:bg-blue-500/20 transition-colors text-sm">
                            <span class="material-icons-outlined text-sm">add</span>
                            Add Feature
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Badge (optional)</label>
                            <input type="text" name="badge" value="{{ old('badge') }}"
                                   class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                   placeholder="e.g., MOST POPULAR">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Badge Color *</label>
                            <select name="badge_color" required
                                    class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                                <option value="neon-blue" {{ old('badge_color') === 'neon-blue' ? 'selected' : '' }}>Blue</option>
                                <option value="neon-violet" {{ old('badge_color') === 'neon-violet' ? 'selected' : '' }}>Violet</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Button Text *</label>
                        <input type="text" name="button_text" value="{{ old('button_text', 'Get Started') }}" required
                               class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                               placeholder="Get Started">
                    </div>

                    <!-- Daily Feature Limits -->
                    <div class="mb-4 p-3 bg-white/5 rounded-lg">
                        <h3 class="text-xs font-medium text-gray-400 mb-1">Daily Feature Limits</h3>
                        <p class="text-[10px] text-gray-500 mb-3">Set -1 for unlimited, 0 to disable</p>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">Doubts/Day</label>
                                <input type="number" name="doubts_per_day" value="{{ old('doubts_per_day', 0) }}" min="-1"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">Video Solutions/Day</label>
                                <input type="number" name="video_solutions_per_day" value="{{ old('video_solutions_per_day', 0) }}" min="-1"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">Whiteboard Videos/Day</label>
                                <input type="number" name="whiteboard_videos_per_day" value="{{ old('whiteboard_videos_per_day', 0) }}" min="-1"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">Quizzes/Day</label>
                                <input type="number" name="quizzes_per_day" value="{{ old('quizzes_per_day', 0) }}" min="-1"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">Scans/Day</label>
                                <input type="number" name="scans_per_day" value="{{ old('scans_per_day', 0) }}" min="-1"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">Mock Tests/Month</label>
                                <input type="number" name="mock_tests_per_month" value="{{ old('mock_tests_per_month', 0) }}" min="-1"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                        </div>
                    </div>

                    <!-- Plan Extras -->
                    <div class="mb-4 p-3 bg-white/5 rounded-lg">
                        <h3 class="text-xs font-medium text-gray-400 mb-3">Plan Extras</h3>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">Video Quality</label>
                                <select name="video_quality"
                                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                                    <option value="sd" {{ old('video_quality') === 'sd' ? 'selected' : '' }}>SD (480p)</option>
                                    <option value="hd" {{ old('video_quality') === 'hd' ? 'selected' : '' }}>HD (720p)</option>
                                    <option value="4k" {{ old('video_quality') === '4k' ? 'selected' : '' }}>4K (1080p)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">Device Limit</label>
                                <input type="number" name="device_limit" value="{{ old('device_limit', 1) }}" min="1" max="10"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">Analytics Tier</label>
                                <select name="analytics_tier"
                                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                                    <option value="basic" {{ old('analytics_tier') === 'basic' ? 'selected' : '' }}>Basic</option>
                                    <option value="standard" {{ old('analytics_tier') === 'standard' ? 'selected' : '' }}>Standard</option>
                                    <option value="advanced" {{ old('analytics_tier') === 'advanced' ? 'selected' : '' }}>Advanced</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 p-3 bg-white/5 rounded-lg">
                        <h3 class="text-xs font-medium text-gray-400 mb-3">Options</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Active</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_highlighted" value="1" {{ old('is_highlighted') ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Highlighted</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_recommended" value="1" {{ old('is_recommended') ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Recommended</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-4 border-t border-gray-800/50">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                            <span class="material-icons-outlined text-sm">save</span>
                            Create Plan
                        </button>
                        <a href="{{ route('admin.pricing.index') }}"
                           class="px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-medium rounded-lg transition-colors">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function addFeature() {
            const container = document.getElementById('features-container');
            const featureItem = document.createElement('div');
            featureItem.className = 'flex gap-2 feature-item';
            featureItem.innerHTML = `
                <input type="text" name="features[]" required
                       class="flex-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                       placeholder="Enter feature">
                <button type="button" onclick="removeFeature(this)"
                        class="px-3 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition-colors">
                    <span class="material-icons-outlined text-sm">delete</span>
                </button>
            `;
            container.appendChild(featureItem);
        }

        function removeFeature(button) {
            const container = document.getElementById('features-container');
            const items = container.querySelectorAll('.feature-item');
            if (items.length > 1) {
                button.closest('.feature-item').remove();
            } else {
                alert('At least one feature is required');
            }
        }
    </script>
</body>
</html>

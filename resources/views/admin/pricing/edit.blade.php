<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Plan | Admin</title>

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
                        <h1 class="text-base font-semibold text-white">Edit Plan: {{ $plan->name }}</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Update plan details</p>
                    </div>
                </div>
            </header>

            <div class="p-6">
                <div class="max-w-4xl">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-black font-display text-transparent bg-clip-text bg-gradient-to-r from-neon-blue to-neon-violet">
                    Edit Plan: {{ $plan->name }}
                </h1>
                <p class="text-soft-grey mt-2">Update plan details - changes will sync to mobile app and website</p>
            </div>

            <!-- Form -->
            <form action="{{ route('admin.pricing.update', $plan) }}" method="POST" class="card rounded-lg p-6">
                @csrf
                @method('PUT')

                <div class="bg-gray-900/50 backdrop-blur-sm border border-gray-800 rounded-xl p-8 glow-border">
                    <!-- Basic Info -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-neon-blue mb-4">Basic Information</h3>

                        <!-- Plan Name -->
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-semibold mb-2">
                                Plan Name <span class="text-red-400">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $plan->name) }}"
                                   required
                                   class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                   placeholder="e.g., Monthly Plan, Yearly Plan">
                            @error('name')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-semibold mb-2">
                                Description
                            </label>
                            <textarea id="description"
                                      name="description"
                                      rows="3"
                                      class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                      placeholder="Plan description">{{ old('description', $plan->description) }}</textarea>
                        </div>

                        <!-- Order -->
                        <div class="mb-6">
                            <label for="order" class="block text-sm font-semibold mb-2">
                                Display Order
                            </label>
                            <input type="number"
                                   id="order"
                                   name="order"
                                   value="{{ old('order', $plan->order ?? 0) }}"
                                   min="0"
                                   class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                   placeholder="0">
                            <p class="mt-2 text-xs text-soft-grey">Lower numbers appear first (Free: 1, Monthly: 2, Yearly: 3)</p>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-neon-blue mb-4">Pricing</h3>

                        <!-- Price & Billing Period -->
                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="price" class="block text-sm font-semibold mb-2">
                                    Price (₹) <span class="text-red-400">*</span>
                                </label>
                                <input type="number"
                                       id="price"
                                       name="price"
                                       value="{{ old('price', $plan->price) }}"
                                       step="0.01"
                                       min="0"
                                       required
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="999">
                            </div>
                            <div>
                                <label for="billing_period" class="block text-sm font-semibold mb-2">
                                    Billing Period <span class="text-red-400">*</span>
                                </label>
                                <select id="billing_period"
                                        name="billing_period"
                                        required
                                        class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-neon-blue transition-colors">
                                    <option value="month" {{ old('billing_period', $plan->billing_period) === 'month' ? 'selected' : '' }}>Monthly</option>
                                    <option value="year" {{ old('billing_period', $plan->billing_period) === 'year' ? 'selected' : '' }}>Yearly</option>
                                </select>
                            </div>
                        </div>

                        <!-- Billing Description -->
                        <div class="mb-6">
                            <label for="billing_description" class="block text-sm font-semibold mb-2">
                                Billing Description
                            </label>
                            <input type="text"
                                   id="billing_description"
                                   name="billing_description"
                                   value="{{ old('billing_description', $plan->billing_description) }}"
                                   class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                   placeholder="e.g., Monthly billing, Billed yearly">
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-neon-blue mb-4">Features (for mobile app & website)</h3>

                        <!-- Feature Flags -->
                        <div class="mb-6 space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       name="popular"
                                       value="1"
                                       @if(old('popular') || ($plan->features['popular'] ?? false)) checked @endif
                                       class="w-5 h-5 bg-gray-800 border-gray-700 rounded text-neon-blue focus:ring-neon-blue">
                                <span class="text-white">Popular (shows "Most Popular" badge)</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       name="recommended"
                                       value="1"
                                       @if(old('recommended') || ($plan->features['recommended'] ?? false)) checked @endif
                                       class="w-5 h-5 bg-gray-800 border-gray-700 rounded text-neon-blue focus:ring-neon-blue">
                                <span class="text-white">Recommended</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       name="unlimited_credits"
                                       value="1"
                                       {{ old('unlimited_credits', $plan->unlimited_credits) ? 'checked' : '' }}
                                       class="w-5 h-5 bg-gray-800 border-gray-700 rounded text-neon-blue focus:ring-neon-blue">
                                <span class="text-white">Unlimited Credits (no credit deduction)</span>
                            </label>
                        </div>

                        <!-- Feature List -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold mb-2">
                                Feature List
                            </label>
                            <div id="features-container" class="space-y-3">
                                @php
                                    $featuresData = $plan->features ?? [];
                                    $featureList = $featuresData['features'] ?? [];
                                @endphp
                                @foreach(old('features', $featureList) as $index => $feature)
                                <div class="flex gap-2 feature-item">
                                    <input type="text"
                                           name="features[features][]"
                                           value="{{ htmlspecialchars($feature) }}"
                                           class="flex-1 px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                           placeholder="Enter feature">
                                    <button type="button"
                                            onclick="removeFeature(this)"
                                            class="px-4 py-3 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors">
                                        <span class="material-icons-outlined">delete</span>
                                    </button>
                                </div>
                                @endforeach
                                @if(empty(old('features')) && empty($featureList))
                                <div class="flex gap-2 feature-item">
                                    <input type="text"
                                           name="features[features][]"
                                           class="flex-1 px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                           placeholder="Enter feature">
                                    <button type="button"
                                            onclick="removeFeature(this)"
                                            class="px-4 py-3 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors">
                                        <span class="material-icons-outlined">delete</span>
                                    </button>
                                </div>
                                @endif
                            </div>
                            <button type="button"
                                    onclick="addFeature()"
                                    class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-neon-blue/20 text-neon-blue rounded-lg hover:bg-neon-blue/30 transition-colors">
                                <span class="material-icons-outlined">add</span>
                                Add Feature
                            </button>
                        </div>
                    </div>

                    <!-- Limits -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-neon-blue mb-4">Usage Limits</h3>

                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="message_tokens" class="block text-sm font-semibold mb-2">
                                    Message Tokens
                                </label>
                                <input type="number"
                                       id="message_tokens"
                                       name="message_tokens"
                                       value="{{ old('message_tokens', $plan->message_tokens) }}"
                                       min="0"
                                       required
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="50000">
                            </div>
                            <div>
                                <label for="image_credits" class="block text-sm font-semibold mb-2">
                                    Image Credits
                                </label>
                                <input type="number"
                                       id="image_credits"
                                       name="image_credits"
                                       value="{{ old('image_credits', $plan->image_credits) }}"
                                       min="0"
                                       required
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="100">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="api_calls" class="block text-sm font-semibold mb-2">
                                    API Calls
                                </label>
                                <input type="number"
                                       id="api_calls"
                                       name="api_calls"
                                       value="{{ old('api_calls', $plan->api_calls) }}"
                                       min="0"
                                       required
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="1000">
                            </div>
                            <div>
                                <label for="image_uploads" class="block text-sm font-semibold mb-2">
                                    Image Uploads
                                </label>
                                <input type="number"
                                       id="image_uploads"
                                       name="image_uploads"
                                       value="{{ old('image_uploads', $plan->image_uploads) }}"
                                       min="0"
                                       required
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="50">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="validity_days" class="block text-sm font-semibold mb-2">
                                Validity (Days)
                            </label>
                            <input type="number"
                                   id="validity_days"
                                   name="validity_days"
                                   value="{{ old('validity_days', $plan->validity_days) }}"
                                   min="1"
                                   class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                   placeholder="30">
                            <p class="mt-2 text-xs text-soft-grey">How many days the plan is valid</p>
                        </div>
                    </div>

                    <!-- Daily Feature Limits -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-neon-blue mb-4">Daily Feature Limits</h3>
                        <p class="text-xs text-gray-500 mb-4">Set -1 for unlimited, 0 to disable the feature</p>

                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="doubts_per_day" class="block text-sm font-semibold mb-2">Doubts Per Day</label>
                                <input type="number" id="doubts_per_day" name="doubts_per_day"
                                       value="{{ old('doubts_per_day', data_get($plan->features, 'daily_limits.doubts_per_day', 0)) }}"
                                       min="-1"
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="10">
                            </div>
                            <div>
                                <label for="video_solutions_per_day" class="block text-sm font-semibold mb-2">Video Solutions Per Day</label>
                                <input type="number" id="video_solutions_per_day" name="video_solutions_per_day"
                                       value="{{ old('video_solutions_per_day', data_get($plan->features, 'daily_limits.video_solutions_per_day', 0)) }}"
                                       min="-1"
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="5">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="whiteboard_videos_per_day" class="block text-sm font-semibold mb-2">Whiteboard Videos Per Day</label>
                                <input type="number" id="whiteboard_videos_per_day" name="whiteboard_videos_per_day"
                                       value="{{ old('whiteboard_videos_per_day', data_get($plan->features, 'daily_limits.whiteboard_videos_per_day', 0)) }}"
                                       min="-1"
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="5">
                            </div>
                            <div>
                                <label for="quizzes_per_day" class="block text-sm font-semibold mb-2">Quizzes Per Day</label>
                                <input type="number" id="quizzes_per_day" name="quizzes_per_day"
                                       value="{{ old('quizzes_per_day', data_get($plan->features, 'daily_limits.quizzes_per_day', 0)) }}"
                                       min="-1"
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="20">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="scans_per_day" class="block text-sm font-semibold mb-2">Scans Per Day</label>
                                <input type="number" id="scans_per_day" name="scans_per_day"
                                       value="{{ old('scans_per_day', data_get($plan->features, 'daily_limits.scans_per_day', 0)) }}"
                                       min="-1"
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="30">
                            </div>
                            <div>
                                <label for="mock_tests_per_month" class="block text-sm font-semibold mb-2">Mock Tests Per Month</label>
                                <input type="number" id="mock_tests_per_month" name="mock_tests_per_month"
                                       value="{{ old('mock_tests_per_month', data_get($plan->features, 'daily_limits.mock_tests_per_month', 0)) }}"
                                       min="-1"
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="10">
                            </div>
                        </div>
                    </div>

                    <!-- Plan Extras -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-neon-blue mb-4">Plan Extras</h3>

                        <div class="grid grid-cols-3 gap-6 mb-6">
                            <div>
                                <label for="video_quality" class="block text-sm font-semibold mb-2">Video Quality</label>
                                <select id="video_quality" name="video_quality"
                                        class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-neon-blue transition-colors">
                                    <option value="sd" {{ old('video_quality', data_get($plan->features, 'video_quality', 'sd')) === 'sd' ? 'selected' : '' }}>SD (480p)</option>
                                    <option value="hd" {{ old('video_quality', data_get($plan->features, 'video_quality', 'sd')) === 'hd' ? 'selected' : '' }}>HD (720p)</option>
                                    <option value="4k" {{ old('video_quality', data_get($plan->features, 'video_quality', 'sd')) === '4k' ? 'selected' : '' }}>4K (1080p)</option>
                                </select>
                            </div>
                            <div>
                                <label for="device_limit" class="block text-sm font-semibold mb-2">Device Limit</label>
                                <input type="number" id="device_limit" name="device_limit"
                                       value="{{ old('device_limit', data_get($plan->features, 'device_limit', 1)) }}"
                                       min="1" max="10"
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="1">
                            </div>
                            <div>
                                <label for="analytics_tier" class="block text-sm font-semibold mb-2">Analytics Tier</label>
                                <select id="analytics_tier" name="analytics_tier"
                                        class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-neon-blue transition-colors">
                                    <option value="basic" {{ old('analytics_tier', data_get($plan->features, 'analytics_tier', 'basic')) === 'basic' ? 'selected' : '' }}>Basic</option>
                                    <option value="standard" {{ old('analytics_tier', data_get($plan->features, 'analytics_tier', 'basic')) === 'standard' ? 'selected' : '' }}>Standard</option>
                                    <option value="advanced" {{ old('analytics_tier', data_get($plan->features, 'analytics_tier', 'basic')) === 'advanced' ? 'selected' : '' }}>Advanced</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- AI Model Access -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-neon-blue mb-4">AI Model Access</h3>

                        <div class="space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       name="can_use_gpt4"
                                       value="1"
                                       {{ old('can_use_gpt4', $plan->can_use_gpt4) ? 'checked' : '' }}
                                       class="w-5 h-5 bg-gray-800 border-gray-700 rounded text-neon-blue focus:ring-neon-blue">
                                <span class="text-white">GPT-4 Access</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       name="can_use_claude"
                                       value="1"
                                       {{ old('can_use_claude', $plan->can_use_claude) ? 'checked' : '' }}
                                       class="w-5 h-5 bg-gray-800 border-gray-700 rounded text-neon-blue focus:ring-neon-blue">
                                <span class="text-white">Claude Access</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       name="can_use_deepseek"
                                       value="1"
                                       {{ old('can_use_deepseek', $plan->can_use_deepseek) ? 'checked' : '' }}
                                       class="w-5 h-5 bg-gray-800 border-gray-700 rounded text-neon-blue focus:ring-neon-blue">
                                <span class="text-white">DeepSeek Access</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       name="can_use_grok"
                                       value="1"
                                       {{ old('can_use_grok', $plan->can_use_grok) ? 'checked' : '' }}
                                       class="w-5 h-5 bg-gray-800 border-gray-700 rounded text-neon-blue focus:ring-neon-blue">
                                <span class="text-white">Grok Access</span>
                            </label>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mb-6">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                                   class="w-5 h-5 bg-gray-800 border-gray-700 rounded text-neon-blue focus:ring-neon-blue">
                            <span class="text-white">Active (visible on website and mobile app)</span>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <a href="{{ route('admin.pricing.index') }}"
                       class="px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                        <span class="material-icons-outlined text-sm">save</span>
                        Update Plan
                    </button>
                </div>
            </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function addFeature() {
            const container = document.getElementById('features-container');
            const featureItem = document.createElement('div');
            featureItem.className = 'flex gap-2 feature-item';
            featureItem.innerHTML = `
                <input type="text"
                       name="features[features][]"
                       class="flex-1 px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                       placeholder="Enter feature">
                <button type="button"
                        onclick="removeFeature(this)"
                        class="px-4 py-3 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors">
                    <span class="material-icons-outlined">delete</span>
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

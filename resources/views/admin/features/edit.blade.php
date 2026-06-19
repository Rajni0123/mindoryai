<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Feature | Admin Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "neon-blue": "#3ddcff",
                        "neon-violet": "#9d5bff",
                        "soft-grey": "#d1d1d1"
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"],
                        "body": ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="font-body text-white">
    <div class="flex h-screen">
        <!-- Sidebar -->
        @include('admin.partials.sidebar')

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-gray-900/30 backdrop-blur-sm border-b border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold font-display text-white">Edit Feature</h2>
                        <p class="text-soft-grey/70 text-sm mt-1">Update feature details</p>
                    </div>
                    <a href="{{ route('admin.features.index') }}"
                       class="flex items-center gap-2 px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all">
                        <span class="material-icons-outlined">arrow_back</span>
                        Back to List
                    </a>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                <div class="max-w-3xl mx-auto">
                    <form method="POST" action="{{ route('admin.features.update', $feature) }}" class="bg-gray-900/50 backdrop-blur-sm border border-gray-800 rounded-xl p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-semibold text-neon-blue mb-2">
                                Title <span class="text-red-400">*</span>
                            </label>
                            <input type="text"
                                   id="title"
                                   name="title"
                                   value="{{ old('title', $feature->title) }}"
                                   required
                                   class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                   placeholder="e.g., Vision AI">
                            @error('title')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-semibold text-neon-blue mb-2">
                                Description <span class="text-red-400">*</span>
                            </label>
                            <textarea id="description"
                                      name="description"
                                      rows="3"
                                      required
                                      class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                      placeholder="Brief description of the feature">{{ old('description', $feature->description) }}</textarea>
                            @error('description')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Icon -->
                        <div>
                            <label for="icon" class="block text-sm font-semibold text-neon-blue mb-2">
                                Material Icon Name <span class="text-red-400">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input type="text"
                                       id="icon"
                                       name="icon"
                                       value="{{ old('icon', $feature->icon) }}"
                                       required
                                       class="flex-1 px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                       placeholder="e.g., visibility"
                                       oninput="updateIconPreview()">
                                <div id="iconPreview" class="flex items-center justify-center w-14 h-14 rounded-lg bg-neon-blue/20 border border-neon-blue/30">
                                    <span class="material-symbols-outlined text-neon-blue">{{ $feature->icon }}</span>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-soft-grey/70">Find icons at <a href="https://fonts.google.com/icons" target="_blank" class="text-neon-blue hover:underline">Google Fonts Icons</a></p>
                            @error('icon')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Color -->
                        <div>
                            <label for="color" class="block text-sm font-semibold text-neon-blue mb-2">
                                Color Theme <span class="text-red-400">*</span>
                            </label>
                            <select id="color"
                                    name="color"
                                    required
                                    class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-neon-blue transition-colors">
                                <option value="blue" {{ old('color', $feature->color) === 'blue' ? 'selected' : '' }}>Blue (Neon Blue)</option>
                                <option value="violet" {{ old('color', $feature->color) === 'violet' ? 'selected' : '' }}>Violet (Neon Violet)</option>
                            </select>
                            @error('color')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Order -->
                        <div>
                            <label for="order" class="block text-sm font-semibold text-neon-blue mb-2">
                                Display Order <span class="text-red-400">*</span>
                            </label>
                            <input type="number"
                                   id="order"
                                   name="order"
                                   value="{{ old('order', $feature->order) }}"
                                   min="0"
                                   required
                                   class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-neon-blue transition-colors"
                                   placeholder="0">
                            <p class="mt-2 text-xs text-soft-grey/70">Lower numbers appear first</p>
                            @error('order')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Is Active -->
                        <div class="flex items-center gap-3">
                            <input type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   {{ old('is_active', $feature->is_active) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded bg-gray-800/50 border-gray-700 text-neon-blue focus:ring-neon-blue focus:ring-offset-0">
                            <label for="is_active" class="text-sm text-soft-grey">
                                Active (Display on website)
                            </label>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end gap-4 pt-4">
                            <a href="{{ route('admin.features.index') }}"
                               class="px-6 py-3 text-soft-grey hover:text-white transition-colors">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="px-8 py-3 bg-gradient-to-r from-neon-blue to-neon-violet text-black font-bold rounded-lg hover:shadow-[0_0_20px_0_rgba(61,220,255,0.5)] transition-all">
                                Update Feature
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateIconPreview() {
            const iconInput = document.getElementById('icon');
            const iconPreview = document.getElementById('iconPreview').querySelector('span');
            iconPreview.textContent = iconInput.value || 'help';
        }
    </script>
</body>
</html>

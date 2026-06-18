<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit {{ $policy->title }} | Admin Dashboard</title>

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
                        <div class="flex items-center gap-2 text-sm text-gray-400 mb-1">
                            <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition-colors">Dashboard</a>
                            <span>/</span>
                            <a href="{{ route('admin.policies.index') }}" class="hover:text-white transition-colors">Policies</a>
                            <span>/</span>
                            <span class="text-white">Edit {{ $policy->title }}</span>
                        </div>
                        <h2 class="text-2xl font-bold text-white">Edit {{ $policy->title }}</h2>
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

                @error('title')
                <div class="bg-red-500/20 border border-red-500/50 rounded-lg p-4 flex items-center gap-3 mb-6">
                    <span class="material-icons-outlined text-red-400">error</span>
                    <p class="text-red-300">{{ $message }}</p>
                </div>
                @enderror

                <div class="glass-effect rounded-lg p-6">
                    <form action="{{ route('admin.policies.update', $policy->id) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="title" class="block text-sm font-semibold text-blue-400 mb-2">Title</label>
                                <input type="text"
                                       id="title"
                                       name="title"
                                       value="{{ old('title', $policy->title) }}"
                                       class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                       required>
                            </div>
                            <div>
                                <label for="order" class="block text-sm font-semibold text-blue-400 mb-2">Order</label>
                                <input type="number"
                                       id="order"
                                       name="order"
                                       value="{{ old('order', $policy->order) }}"
                                       class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                       required>
                            </div>
                            <div>
                                <label for="is_enabled" class="block text-sm font-semibold text-blue-400 mb-2">Status</label>
                                <select id="is_enabled"
                                        name="is_enabled"
                                        class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors">
                                    <option value="1" {{ $policy->is_enabled ? 'selected' : '' }}>Visible</option>
                                    <option value="0" {{ !$policy->is_enabled ? 'selected' : '' }}>Hidden</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-blue-400 mb-2">Content Sections</label>
                            <p class="text-xs text-gray-500 mb-4">Add, edit, or remove sections. Changes appear immediately in the mobile app.</p>

                            <div id="sections-container" class="space-y-4">
                                @foreach($policy->content['sections'] ?? [] as $index => $section)
                                <div class="glass-effect rounded-lg p-4 section-item">
                                    <div class="flex items-center justify-between mb-3">
                                        <h6 class="font-semibold text-white">Section {{ $index + 1 }}</h6>
                                        <button type="button" class="flex items-center gap-1 px-3 py-2 bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded text-xs font-medium transition-colors remove-section">
                                            <span class="material-icons-outlined text-sm">delete</span>
                                            Remove
                                        </button>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-300 mb-1">Section Title</label>
                                            <input type="text"
                                                   class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500 transition-colors section-title"
                                                   name="sections[{{ $index }}][title]"
                                                   value="{{ $section['title'] ?? '' }}"
                                                   required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-300 mb-1">Section Content</label>
                                            <textarea class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500 transition-colors section-content"
                                                      name="sections[{{ $index }}][content]"
                                                      rows="4"
                                                      required>{{ $section['content'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <button type="button" id="add-section" class="mt-4 flex items-center gap-2 px-4 py-2 bg-green-500/20 text-green-400 hover:bg-green-500/30 rounded text-sm font-medium transition-colors">
                                <span class="material-icons-outlined text-sm">add</span>
                                Add Section
                            </button>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-white/10">
                            <a href="{{ route('admin.policies.index') }}" class="flex items-center gap-1 px-4 py-2 bg-gray-700/50 text-gray-300 hover:bg-gray-700 rounded text-sm font-medium transition-colors">
                                <span class="material-icons-outlined text-sm">arrow_back</span>
                                Back
                            </a>
                            <button type="submit" class="flex items-center gap-2 px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg transition-colors">
                                <span class="material-icons-outlined text-sm">save</span>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        let sectionIndex = {{ count($policy->content['sections'] ?? []) }};

        document.getElementById('add-section')?.addEventListener('click', function() {
            const container = document.getElementById('sections-container');
            const sectionHtml = `
                <div class="glass-effect rounded-lg p-4 section-item">
                    <div class="flex items-center justify-between mb-3">
                        <h6 class="font-semibold text-white">Section ${sectionIndex + 1}</h6>
                        <button type="button" class="flex items-center gap-1 px-3 py-2 bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded text-xs font-medium transition-colors remove-section">
                            <span class="material-icons-outlined text-sm">delete</span>
                            Remove
                        </button>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Section Title</label>
                            <input type="text"
                                   class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500 transition-colors section-title"
                                   name="sections[${sectionIndex}][title]"
                                   required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Section Content</label>
                            <textarea class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500 transition-colors section-content"
                                      name="sections[${sectionIndex}][content]"
                                      rows="4"
                                      required></textarea>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', sectionHtml);
            sectionIndex++;
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-section')) {
                const section = e.target.closest('.section-item');
                section.remove();
            }
        });
    </script>
</body>
</html>

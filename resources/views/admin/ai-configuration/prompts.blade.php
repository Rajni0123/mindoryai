<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI System Prompts</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4 sticky top-0 z-10">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-semibold text-white">AI System Prompts</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Configure AI behavior for each feature</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6 space-y-4">
                @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-green-400" style="font-size: 16px;">check_circle</span>
                    <p class="text-green-300">{{ session('success') }}</p>
                </div>
                @endif

                <!-- Feature Cards -->
                <div class="grid grid-cols-1 gap-4">
                    @foreach($prompts as $prompt)
                    <div class="card rounded-lg p-5">
                        <form method="POST" action="{{ route('admin.ai-config.prompts.update', $prompt) }}" id="form-{{ $prompt->id }}">
                            @csrf

                            <!-- Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background: {{ $prompt->feature === 'chat' ? '#3b82f6' : ($prompt->feature === 'quiz' ? '#f59e0b' : ($prompt->feature === 'whiteboard' ? '#8b5cf6' : '#10b981')) }}20;">
                                        <span class="material-icons-outlined" style="color: {{ $prompt->feature === 'chat' ? '#3b82f6' : ($prompt->feature === 'quiz' ? '#f59e0b' : ($prompt->feature === 'whiteboard' ? '#8b5cf6' : '#10b981')) }}; font-size: 24px;">
                                            {{ $prompt->feature === 'chat' ? 'chat' : ($prompt->feature === 'quiz' ? 'quiz' : ($prompt->feature === 'whiteboard' ? 'video_library' : 'image')) }}
                                        </span>
                                    </div>
                                    <div>
                                        <input type="text" name="name" value="{{ $prompt->name }}"
                                               class="text-sm font-semibold text-white bg-transparent border-0 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1"
                                               required>
                                        <p class="text-[10px] text-gray-400 mt-1">{{ ucfirst($prompt->feature) }} Feature</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="is_active" value="1" {{ $prompt->is_active ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                        <span class="ml-2 text-xs text-gray-400">Active</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label class="block text-[10px] font-medium text-gray-400 mb-1.5">Description</label>
                                <input type="text" name="description" value="{{ $prompt->description }}"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500">
                            </div>

                            <!-- Prompt Editor -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-[10px] font-medium text-gray-400">System Prompt</label>
                                    <span class="text-[9px] text-gray-500" id="char-count-{{ $prompt->id }}">0 characters</span>
                                </div>
                                <textarea name="prompt" rows="10"
                                          class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500 font-mono"
                                          required
                                          oninput="updateCharCount({{ $prompt->id }}, this.value.length)">{{ $prompt->prompt }}</textarea>
                                <p class="mt-1.5 text-[9px] text-gray-500">
                                    <span class="material-icons-outlined align-middle" style="font-size: 10px;">info</span>
                                    This prompt defines how the AI behaves for {{ $prompt->feature }} feature
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-800/50">
                                <button type="button" onclick="previewPrompt({{ $prompt->id }})"
                                        class="px-3 py-2 bg-gray-700/50 hover:bg-gray-700 text-gray-300 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5">
                                    <span class="material-icons-outlined" style="font-size: 14px;">visibility</span>
                                    Preview
                                </button>
                                <button type="submit"
                                        class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5">
                                    <span class="material-icons-outlined" style="font-size: 14px;">save</span>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                    @endforeach
                </div>

                <!-- Info Box -->
                <div class="card rounded-lg p-4 bg-blue-500/5 border-blue-500/20">
                    <div class="flex gap-3">
                        <span class="material-icons-outlined text-blue-400" style="font-size: 18px;">info</span>
                        <div class="flex-1">
                            <h4 class="text-xs font-semibold text-blue-300 mb-2">About System Prompts</h4>
                            <ul class="space-y-1.5 text-[10px] text-gray-400">
                                <li class="flex items-start gap-1.5">
                                    <span class="material-icons-outlined text-blue-400 mt-0.5" style="font-size: 10px;">check</span>
                                    <span><strong>Tutor Mode (Chat):</strong> Controls how AI responds in regular conversations</span>
                                </li>
                                <li class="flex items-start gap-1.5">
                                    <span class="material-icons-outlined text-blue-400 mt-0.5" style="font-size: 10px;">check</span>
                                    <span><strong>Examiner Mode (Quiz):</strong> Defines quiz generation style and quality</span>
                                </li>
                                <li class="flex items-start gap-1.5">
                                    <span class="material-icons-outlined text-blue-400 mt-0.5" style="font-size: 10px;">check</span>
                                    <span><strong>Director Mode (Whiteboard):</strong> Controls video storyboard creation approach</span>
                                </li>
                                <li class="flex items-start gap-1.5">
                                    <span class="material-icons-outlined text-blue-400 mt-0.5" style="font-size: 10px;">check</span>
                                    <span><strong>Visual Artist Mode (Image):</strong> Guides educational image generation</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Update character count
        function updateCharCount(promptId, length) {
            document.getElementById(`char-count-${promptId}`).textContent = `${length.toLocaleString()} characters`;
        }

        // Initialize character counts on page load
        document.addEventListener('DOMContentLoaded', function() {
            @foreach($prompts as $prompt)
                updateCharCount({{ $prompt->id }}, {{ strlen($prompt->prompt) }});
            @endforeach
        });

        // Preview prompt (modal would go here)
        function previewPrompt(promptId) {
            const form = document.getElementById(`form-${promptId}`);
            const promptText = form.querySelector('[name="prompt"]').value;
            alert('Prompt Preview:\n\n' + promptText);
        }
    </script>
</body>
</html>

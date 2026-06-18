<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Models Management</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .card:hover { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.12); }
        .model-enabled { background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3); }
        .model-disabled { background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3); }
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
                        <h1 class="text-base font-semibold text-white">AI Models Management</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Configure and manage AI models for the platform</p>
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

                @if($errors->any())
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="material-icons-outlined text-red-400" style="font-size: 16px;">error</span>
                        <span class="text-red-300 font-semibold">Validation Errors</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="text-red-300">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- AI Models List -->
                <div class="space-y-3">
                    @forelse($aiModels as $model)
                    <div class="card rounded-lg p-4 {{ $model->is_active ? 'model-enabled' : 'model-disabled' }} transition-all">
                        <div class="flex items-start justify-between gap-4">
                            <!-- Model Info -->
                            <div class="flex items-start gap-3 flex-1">
                                <!-- Model Icon -->
                                <div class="w-12 h-12 rounded-lg flex items-center justify-center p-2" style="background: {{ $model->color ?? '#667eea' }}20;">
                                    @if($model->icon && file_exists(public_path($model->icon)))
                                        <img src="{{ asset($model->icon) }}" alt="{{ $model->name }}" class="w-full h-full object-contain">
                                    @else
                                        <span class="material-icons-outlined" style="color: {{ $model->color ?? '#667eea' }}; font-size: 24px;">psychology</span>
                                    @endif
                                </div>

                                <!-- Model Details -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-sm font-semibold text-white">{{ $model->display_name ?? $model->name }}</h3>
                                        @if($model->is_active)
                                            <span class="px-2 py-0.5 bg-green-500/20 text-green-400 text-[9px] font-semibold rounded uppercase">Active</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-red-500/20 text-red-400 text-[9px] font-semibold rounded uppercase">Disabled</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-gray-400 mb-2">{{ $model->description ?? 'No description' }}</p>

                                    <!-- Model Metadata -->
                                    <div class="flex flex-wrap items-center gap-3 text-[9px]">
                                        <div class="flex items-center gap-1">
                                            <span class="material-icons-outlined text-gray-500" style="font-size: 10px;">business</span>
                                            <span class="text-gray-400">Provider: <span class="text-white font-medium">{{ ucfirst($model->provider) }}</span></span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="material-icons-outlined text-gray-500" style="font-size: 10px;">code</span>
                                            <span class="text-gray-400">Model: <span class="text-white font-mono">{{ $model->name }}</span></span>
                                        </div>
                                        @if($model->supports_vision)
                                            <div class="flex items-center gap-1">
                                                <span class="material-icons-outlined text-blue-400" style="font-size: 10px;">visibility</span>
                                                <span class="text-blue-400 font-medium">Vision Enabled</span>
                                            </div>
                                        @endif
                                        <div class="flex items-center gap-1">
                                            <span class="material-icons-outlined text-gray-500" style="font-size: 10px;">sort</span>
                                            <span class="text-gray-400">Order: <span class="text-white font-medium">{{ $model->order }}</span></span>
                                        </div>
                                    </div>

                                    <!-- Model Config -->
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[9px]">
                                        <span class="px-2 py-1 bg-white/5 rounded border border-white/10">
                                            <span class="text-gray-400">Max Tokens:</span> <span class="text-white font-medium">{{ number_format($model->max_tokens) }}</span>
                                        </span>
                                        <span class="px-2 py-1 bg-white/5 rounded border border-white/10">
                                            <span class="text-gray-400">Temperature:</span> <span class="text-white font-medium">{{ $model->temperature }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2">
                                <!-- Toggle Active Button -->
                                <button
                                    onclick="toggleModel({{ $model->id }}, '{{ $model->name }}', {{ $model->is_active ? 'true' : 'false' }})"
                                    class="px-3 py-2 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 {{ $model->is_active ? 'bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/30' : 'bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30' }}">
                                    <span class="material-icons-outlined" style="font-size: 14px;">{{ $model->is_active ? 'toggle_on' : 'toggle_off' }}</span>
                                    {{ $model->is_active ? 'Disable' : 'Enable' }}
                                </button>

                                <!-- Edit Button -->
                                <button
                                    onclick="openEditModal({{ $model->id }})"
                                    class="px-3 py-2 bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/30 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5">
                                    <span class="material-icons-outlined" style="font-size: 14px;">edit</span>
                                    Edit
                                </button>
                            </div>
                        </div>

                        <!-- Edit Form (Hidden by default) -->
                        <div id="edit-form-{{ $model->id }}" class="hidden mt-4 pt-4 border-t border-gray-700/50">
                            <form method="POST" action="{{ route('admin.ai-models.update', $model) }}" class="space-y-3">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-medium text-gray-400 mb-1">Model Name</label>
                                        <input type="text" name="name" value="{{ $model->name }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-medium text-gray-400 mb-1">Order</label>
                                        <input type="number" name="order" value="{{ $model->order }}" min="0"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-medium text-gray-400 mb-1">Max Tokens</label>
                                        <input type="number" name="max_tokens" value="{{ $model->max_tokens }}" min="100" max="10000"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-medium text-gray-400 mb-1">Temperature</label>
                                        <input type="number" name="temperature" value="{{ $model->temperature }}" min="0" max="2" step="0.1"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500">
                                    </div>
                                </div>

                                @if($model->provider === 'google')
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-400 mb-1">API Key (Optional - uses global key if empty)</label>
                                    <input type="password" name="api_key" value="{{ $model->api_key }}"
                                           placeholder="Leave empty to use global Gemini API key"
                                           class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500">
                                </div>
                                @endif

                                <div class="flex items-center justify-end gap-2 pt-2">
                                    <button type="button" onclick="closeEditModal({{ $model->id }})"
                                            class="px-3 py-2 bg-gray-700/50 hover:bg-gray-700 text-gray-300 rounded-lg text-xs font-semibold transition-colors">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5">
                                        <span class="material-icons-outlined" style="font-size: 14px;">save</span>
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="card rounded-lg p-8 text-center">
                        <span class="material-icons-outlined text-gray-600 text-4xl mb-2">psychology_alt</span>
                        <p class="text-gray-400 text-sm">No AI models found</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleModel(modelId, modelName, currentState) {
            if (!confirm(`Are you sure you want to ${currentState ? 'disable' : 'enable'} ${modelName}?`)) {
                return;
            }

            fetch(`/admin/ai-models/${modelId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    is_active: !currentState
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                alert('Error toggling model: ' + error);
            });
        }

        function openEditModal(modelId) {
            document.getElementById(`edit-form-${modelId}`).classList.remove('hidden');
        }

        function closeEditModal(modelId) {
            document.getElementById(`edit-form-${modelId}`).classList.add('hidden');
        }
    </script>
</body>
</html>

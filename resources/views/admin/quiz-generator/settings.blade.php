<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quiz Generator Settings</title>

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
                        <h1 class="text-base font-semibold text-white">Quiz Generator Settings</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Configure AI model for quiz question generation</p>
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
                    <span class="material-icons-outlined text-red-400" style="font-size: 16px;">error</span>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li class="text-red-300">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('admin.quiz-generator.settings.update') }}" class="space-y-4">
                    @csrf

                    <!-- AI Model Selection -->
                    <div class="card rounded-lg p-4">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center">
                                <span class="material-icons-outlined text-white" style="font-size: 20px;">quiz</span>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-white">Quiz Generator</h4>
                                <p class="text-[10px] text-gray-500">AI model used for generating quiz questions and assessments</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label for="model_id" class="block text-[10px] font-medium text-gray-400 mb-1.5">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">psychology</span>
                                        AI Model
                                    </span>
                                </label>
                                <select id="model_id"
                                        name="model_id"
                                        class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500 transition-colors">
                                    @foreach($aiModels as $model)
                                        <option value="{{ $model->id }}"
                                                {{ old('model_id', $settings['model_id']) == $model->id ? 'selected' : '' }}>
                                            {{ $model->name }} ({{ ucfirst($model->provider) }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1.5 text-[9px] text-gray-500">
                                    <span class="material-icons-outlined align-middle" style="font-size: 10px;">info</span>
                                    This model will be used for generating quiz questions from text and images
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end gap-3">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors flex items-center gap-2">
                            <span class="material-icons-outlined" style="font-size: 14px;">save</span>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>

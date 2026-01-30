<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Feature-Specific AI Models - Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .card:hover { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.12); }

        .feature-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .model-select {
            width: 100%;
            padding: 12px 16px;
            background: #1a1a1a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .model-select:hover {
            border-color: rgba(255, 255, 255, 0.2);
            background: #252525;
        }

        .model-select:focus {
            outline: none;
            border-color: var(--feature-color);
            box-shadow: 0 0 0 3px rgba(var(--feature-color-rgb), 0.1);
        }

        .current-model {
            margin-top: 8px;
            font-size: 12px;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 4px;
        }
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
                        <h1 class="text-base font-semibold text-white">Feature-Specific AI Models</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Assign dedicated AI models to each feature</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                @if(session('success'))
                <div class="mb-6 p-4 bg-green-500/10 border border-green-500/30 rounded-lg flex items-center gap-3">
                    <span class="material-icons-outlined text-green-400" style="font-size: 20px;">check_circle</span>
                    <p class="text-green-300 text-sm">{{ session('success') }}</p>
                </div>
                @endif

                @if($errors->any())
                <div class="mb-6 p-4 bg-red-500/10 border border-red-500/30 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="material-icons-outlined text-red-400" style="font-size: 20px;">error</span>
                        <p class="text-red-300 text-sm font-semibold">Please fix the following errors:</p>
                    </div>
                    <ul class="text-xs text-red-300 space-y-1 ml-7">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('admin.feature-models.update') }}" class="space-y-6">
                    @csrf

                    <!-- Chat Feature -->
                    <div class="feature-card" style="--feature-color: #3b82f6; --feature-color-rgb: 59, 130, 246;">
                        <div class="flex items-start gap-4">
                            <div class="feature-icon" style="background: rgba(59, 130, 246, 0.1);">
                                <span class="material-icons-outlined" style="color: #3b82f6; font-size: 28px;">chat</span>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-base font-semibold text-white mb-1">Chat Feature</h3>
                                <p class="text-xs text-gray-400 mb-4">Model used for tutor conversations and general Q&A</p>

                                <select name="chat_model_id" class="model-select">
                                    <option value="">Select AI Model</option>
                                    @foreach($aiModels as $model)
                                        <option value="{{ $model->id }}"
                                                {{ old('chat_model_id', $currentModels['chat'] ?? '') == $model->id ? 'selected' : '' }}>
                                            {{ $model->name }} - {{ $model->provider }} ({{ $model->model_identifier }})
                                        </option>
                                    @endforeach
                                </select>

                                @if(isset($currentModels['chat']))
                                    @php
                                        $chatModel = $aiModels->firstWhere('id', $currentModels['chat']);
                                    @endphp
                                    @if($chatModel)
                                        <div class="current-model">
                                            <span class="material-icons-outlined" style="font-size: 14px;">check_circle</span>
                                            Current: <strong>{{ $chatModel->model_identifier }}</strong>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quiz Feature -->
                    <div class="feature-card" style="--feature-color: #f59e0b; --feature-color-rgb: 245, 158, 11;">
                        <div class="flex items-start gap-4">
                            <div class="feature-icon" style="background: rgba(245, 158, 11, 0.1);">
                                <span class="material-icons-outlined" style="color: #f59e0b; font-size: 28px;">quiz</span>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-base font-semibold text-white mb-1">Quiz Feature</h3>
                                <p class="text-xs text-gray-400 mb-4">Model used for generating quiz questions and assessments</p>

                                <select name="quiz_model_id" class="model-select">
                                    <option value="">Select AI Model</option>
                                    @foreach($aiModels as $model)
                                        <option value="{{ $model->id }}"
                                                {{ old('quiz_model_id', $currentModels['quiz'] ?? '') == $model->id ? 'selected' : '' }}>
                                            {{ $model->name }} - {{ $model->provider }} ({{ $model->model_identifier }})
                                        </option>
                                    @endforeach
                                </select>

                                @if(isset($currentModels['quiz']))
                                    @php
                                        $quizModel = $aiModels->firstWhere('id', $currentModels['quiz']);
                                    @endphp
                                    @if($quizModel)
                                        <div class="current-model">
                                            <span class="material-icons-outlined" style="font-size: 14px;">check_circle</span>
                                            Current: <strong>{{ $quizModel->model_identifier }}</strong>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Whiteboard Video Feature -->
                    <div class="feature-card" style="--feature-color: #8b5cf6; --feature-color-rgb: 139, 92, 246;">
                        <div class="flex items-start gap-4">
                            <div class="feature-icon" style="background: rgba(139, 92, 246, 0.1);">
                                <span class="material-icons-outlined" style="color: #8b5cf6; font-size: 28px;">video_library</span>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-base font-semibold text-white mb-1">Whiteboard Video Feature</h3>
                                <p class="text-xs text-gray-400 mb-4">Model used for creating video storyboards and scripts</p>

                                <select name="whiteboard_model_id" class="model-select">
                                    <option value="">Select AI Model</option>
                                    @foreach($aiModels as $model)
                                        <option value="{{ $model->id }}"
                                                {{ old('whiteboard_model_id', $currentModels['whiteboard'] ?? '') == $model->id ? 'selected' : '' }}>
                                            {{ $model->name }} - {{ $model->provider }} ({{ $model->model_identifier }})
                                        </option>
                                    @endforeach
                                </select>

                                @if(isset($currentModels['whiteboard']))
                                    @php
                                        $whiteboardModel = $aiModels->firstWhere('id', $currentModels['whiteboard']);
                                    @endphp
                                    @if($whiteboardModel)
                                        <div class="current-model">
                                            <span class="material-icons-outlined" style="font-size: 14px;">check_circle</span>
                                            Current: <strong>{{ $whiteboardModel->model_identifier }}</strong>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Image Generation Feature -->
                    <div class="feature-card" style="--feature-color: #10b981; --feature-color-rgb: 16, 185, 129;">
                        <div class="flex items-start gap-4">
                            <div class="feature-icon" style="background: rgba(16, 185, 129, 0.1);">
                                <span class="material-icons-outlined" style="color: #10b981; font-size: 28px;">image</span>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-base font-semibold text-white mb-1">Image Generation Feature</h3>
                                <p class="text-xs text-gray-400 mb-4">Model used for generating educational illustrations</p>

                                <select name="image_generation_model_id" class="model-select">
                                    <option value="">Select AI Model</option>
                                    @foreach($aiModels as $model)
                                        <option value="{{ $model->id }}"
                                                {{ old('image_generation_model_id', $currentModels['image_generation'] ?? '') == $model->id ? 'selected' : '' }}>
                                            {{ $model->name }} - {{ $model->provider }} ({{ $model->model_identifier }})
                                        </option>
                                    @endforeach
                                </select>

                                @if(isset($currentModels['image_generation']))
                                    @php
                                        $imageModel = $aiModels->firstWhere('id', $currentModels['image_generation']);
                                    @endphp
                                    @if($imageModel)
                                        <div class="current-model">
                                            <span class="material-icons-outlined" style="font-size: 14px;">check_circle</span>
                                            Current: <strong>{{ $imageModel->model_identifier }}</strong>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Important Notice -->
                    <div class="p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                        <div class="flex items-start gap-3">
                            <span class="material-icons-outlined text-blue-400" style="font-size: 20px;">info</span>
                            <div>
                                <h4 class="text-sm font-semibold text-blue-300 mb-2">Important Information</h4>
                                <ul class="text-xs text-blue-200 space-y-1">
                                    <li>• Each feature will use ONLY its assigned AI model</li>
                                    <li>• Chat model is used exclusively for conversations</li>
                                    <li>• Quiz model is used exclusively for quiz generation</li>
                                    <li>• Whiteboard model is used exclusively for video generation</li>
                                    <li>• Image Generation model is used exclusively for creating illustrations</li>
                                    <li>• Models must be active in AI Models section to appear here</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="submit"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors flex items-center gap-2">
                            <span class="material-icons-outlined" style="font-size: 18px;">save</span>
                            Save All Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>

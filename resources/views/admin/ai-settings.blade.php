<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Settings</title>

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
                        <h1 class="text-base font-semibold text-white">AI Settings</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Configure AI providers and models</p>
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

                <form method="POST" action="{{ route('admin.ai-settings.update') }}" class="space-y-4">
                    @csrf

                    <!-- API Settings Section -->
                    <div class="card rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-white mb-3">AI API Settings</h3>
                        <p class="text-[10px] text-gray-500 mb-4">Configure AI providers. Only enabled providers will be available to users.</p>

                        <!-- OpenAI Settings -->
                        <div class="mb-4 pb-4 border-b border-gray-800/50">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-white/5 rounded flex items-center justify-center p-1.5">
                                        @if(file_exists(public_path('images/ai-models/ChatGPT.png')))
                                            <img src="{{ asset('images/ai-models/ChatGPT.png') }}" alt="ChatGPT" class="w-full h-full object-contain">
                                        @else
                                            <span class="material-icons-outlined text-green-400" style="font-size: 14px;">smart_toy</span>
                                        @endif
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-semibold text-white">OpenAI (GPT)</h4>
                                        <p class="text-[10px] text-gray-500">ChatGPT and GPT models</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox"
                                           id="openai_enabled"
                                           name="openai_enabled"
                                           value="1"
                                           {{ old('openai_enabled', $settings['ai.openai_enabled'] ?? '0') == '1' ? 'checked' : '' }}
                                           class="w-4 h-4 rounded bg-gray-800/50 border-gray-700 text-green-500 focus:ring-green-500 focus:ring-offset-0">
                                    <label for="openai_enabled" class="text-xs font-medium text-green-400">Enable</label>
                                </div>
                            </div>

                            <div class="space-y-3 ml-10">
                                <div>
                                    <label for="openai_api_key" class="block text-[10px] font-medium text-gray-400 mb-1">API Key</label>
                                    <input type="password"
                                           id="openai_api_key"
                                           name="openai_api_key"
                                           value="{{ old('openai_api_key', $settings['ai.openai_api_key'] ?? '') }}"
                                           class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-xs placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                           placeholder="sk-...">
                                </div>

                                <div>
                                    <label for="openai_model" class="block text-[10px] font-medium text-gray-400 mb-1">Model</label>
                                    <select id="openai_model"
                                            name="openai_model"
                                            class="w-full px-3 py-2 bg-gray-900 border border-white/10 rounded text-white text-xs focus:outline-none focus:border-blue-500 transition-colors"
                                            style="color: white;">
                                        <option value="gpt-4o" {{ old('openai_model', $settings['ai.openai_model'] ?? '') === 'gpt-4o' ? 'selected' : '' }} style="background-color: #111827; color: white;">GPT-4o</option>
                                        <option value="gpt-4o-mini" {{ old('openai_model', $settings['ai.openai_model'] ?? '') === 'gpt-4o-mini' ? 'selected' : '' }} style="background-color: #111827; color: white;">GPT-4o Mini</option>
                                        <option value="gpt-4-turbo" {{ old('openai_model', $settings['ai.openai_model'] ?? '') === 'gpt-4-turbo' ? 'selected' : '' }} style="background-color: #111827; color: white;">GPT-4 Turbo</option>
                                        <option value="gpt-4" {{ old('openai_model', $settings['ai.openai_model'] ?? '') === 'gpt-4' ? 'selected' : '' }} style="background-color: #111827; color: white;">GPT-4</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Claude Settings -->
                        <div class="mb-4 pb-4 border-b border-gray-800/50">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-white/5 rounded flex items-center justify-center p-1.5">
                                        @if(file_exists(public_path('images/ai-models/Claude.png')))
                                            <img src="{{ asset('images/ai-models/Claude.png') }}" alt="Claude" class="w-full h-full object-contain">
                                        @else
                                            <span class="material-icons-outlined text-orange-400" style="font-size: 14px;">psychology</span>
                                        @endif
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-semibold text-white">Claude (Anthropic)</h4>
                                        <p class="text-[10px] text-gray-500">Claude AI assistant</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox"
                                           id="claude_enabled"
                                           name="claude_enabled"
                                           value="1"
                                           {{ old('claude_enabled', $settings['ai.claude_enabled'] ?? '0') == '1' ? 'checked' : '' }}
                                           class="w-4 h-4 rounded bg-gray-800/50 border-gray-700 text-orange-500 focus:ring-orange-500 focus:ring-offset-0">
                                    <label for="claude_enabled" class="text-xs font-medium text-orange-400">Enable</label>
                                </div>
                            </div>

                            <div class="space-y-3 ml-10">
                                <div>
                                    <label for="claude_api_key" class="block text-[10px] font-medium text-gray-400 mb-1">API Key</label>
                                    <input type="password"
                                           id="claude_api_key"
                                           name="claude_api_key"
                                           value="{{ old('claude_api_key', $settings['ai.claude_api_key'] ?? '') }}"
                                           class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-xs placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                           placeholder="sk-ant-...">
                                </div>

                                <div>
                                    <label for="claude_model" class="block text-[10px] font-medium text-gray-400 mb-1">Model</label>
                                    <select id="claude_model"
                                            name="claude_model"
                                            class="w-full px-3 py-2 bg-gray-900 border border-white/10 rounded text-white text-xs focus:outline-none focus:border-blue-500 transition-colors"
                                            style="color: white;">
                                        <option value="claude-3-5-sonnet-20241022" {{ old('claude_model', $settings['ai.claude_model'] ?? '') === 'claude-3-5-sonnet-20241022' ? 'selected' : '' }} style="background-color: #111827; color: white;">Claude 3.5 Sonnet</option>
                                        <option value="claude-3-opus-20240229" {{ old('claude_model', $settings['ai.claude_model'] ?? '') === 'claude-3-opus-20240229' ? 'selected' : '' }} style="background-color: #111827; color: white;">Claude 3 Opus</option>
                                        <option value="claude-3-sonnet-20240229" {{ old('claude_model', $settings['ai.claude_model'] ?? '') === 'claude-3-sonnet-20240229' ? 'selected' : '' }} style="background-color: #111827; color: white;">Claude 3 Sonnet</option>
                                        <option value="claude-3-haiku-20240307" {{ old('claude_model', $settings['ai.claude_model'] ?? '') === 'claude-3-haiku-20240307' ? 'selected' : '' }} style="background-color: #111827; color: white;">Claude 3 Haiku</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- DeepSeek Settings -->
                        <div class="mb-4 pb-4 border-b border-gray-800/50">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-white/5 rounded flex items-center justify-center p-1.5">
                                        @if(file_exists(public_path('images/ai-models/DeepSeek.png')))
                                            <img src="{{ asset('images/ai-models/DeepSeek.png') }}" alt="DeepSeek" class="w-full h-full object-contain">
                                        @else
                                            <span class="material-icons-outlined text-blue-400" style="font-size: 14px;">explore</span>
                                        @endif
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-semibold text-white">DeepSeek</h4>
                                        <p class="text-[10px] text-gray-500">DeepSeek AI models</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox"
                                           id="deepseek_enabled"
                                           name="deepseek_enabled"
                                           value="1"
                                           {{ old('deepseek_enabled', $settings['ai.deepseek_enabled'] ?? '0') == '1' ? 'checked' : '' }}
                                           class="w-4 h-4 rounded bg-gray-800/50 border-gray-700 text-blue-500 focus:ring-blue-500 focus:ring-offset-0">
                                    <label for="deepseek_enabled" class="text-xs font-medium text-blue-400">Enable</label>
                                </div>
                            </div>

                            <div class="space-y-3 ml-10">
                                <div>
                                    <label for="deepseek_api_key" class="block text-[10px] font-medium text-gray-400 mb-1">API Key</label>
                                    <input type="password"
                                           id="deepseek_api_key"
                                           name="deepseek_api_key"
                                           value="{{ old('deepseek_api_key', $settings['ai.deepseek_api_key'] ?? '') }}"
                                           class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-xs placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                           placeholder="sk-...">
                                </div>

                                <div>
                                    <label for="deepseek_model" class="block text-[10px] font-medium text-gray-400 mb-1">Model</label>
                                    <select id="deepseek_model"
                                            name="deepseek_model"
                                            class="w-full px-3 py-2 bg-gray-900 border border-white/10 rounded text-white text-xs focus:outline-none focus:border-blue-500 transition-colors"
                                            style="color: white;">
                                        <option value="deepseek-chat" {{ old('deepseek_model', $settings['ai.deepseek_model'] ?? '') === 'deepseek-chat' ? 'selected' : '' }} style="background-color: #111827; color: white;">DeepSeek Chat</option>
                                        <option value="deepseek-coder" {{ old('deepseek_model', $settings['ai.deepseek_model'] ?? '') === 'deepseek-coder' ? 'selected' : '' }} style="background-color: #111827; color: white;">DeepSeek Coder</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Grok Settings -->
                        <div class="mb-4 pb-4 border-b border-gray-800/50">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-purple-500/10 rounded flex items-center justify-center">
                                        <span class="material-icons-outlined text-purple-400" style="font-size: 14px;">rocket_launch</span>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-semibold text-white">Grok (xAI)</h4>
                                        <p class="text-[10px] text-gray-500">Grok AI by xAI</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox"
                                           id="grok_enabled"
                                           name="grok_enabled"
                                           value="1"
                                           {{ old('grok_enabled', $settings['ai.grok_enabled'] ?? '0') == '1' ? 'checked' : '' }}
                                           class="w-4 h-4 rounded bg-gray-800/50 border-gray-700 text-purple-500 focus:ring-purple-500 focus:ring-offset-0">
                                    <label for="grok_enabled" class="text-xs font-medium text-purple-400">Enable</label>
                                </div>
                            </div>

                            <div class="space-y-3 ml-10">
                                <div>
                                    <label for="grok_api_key" class="block text-[10px] font-medium text-gray-400 mb-1">API Key</label>
                                    <input type="password"
                                           id="grok_api_key"
                                           name="grok_api_key"
                                           value="{{ old('grok_api_key', $settings['ai.grok_api_key'] ?? '') }}"
                                           class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-xs placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                           placeholder="xai-...">
                                </div>

                                <div>
                                    <label for="grok_model" class="block text-[10px] font-medium text-gray-400 mb-1">Model</label>
                                    <select id="grok_model"
                                            name="grok_model"
                                            class="w-full px-3 py-2 bg-gray-900 border border-white/10 rounded text-white text-xs focus:outline-none focus:border-blue-500 transition-colors"
                                            style="color: white;">
                                        <option value="grok-beta" {{ old('grok_model', $settings['ai.grok_model'] ?? '') === 'grok-beta' ? 'selected' : '' }} style="background-color: #111827; color: white;">Grok Beta</option>
                                        <option value="grok-2" {{ old('grok_model', $settings['ai.grok_model'] ?? '') === 'grok-2' ? 'selected' : '' }} style="background-color: #111827; color: white;">Grok 2</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Google Gemini Settings -->
                        <div class="p-3 bg-white/5 rounded border border-gray-800/50">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-white/5 rounded flex items-center justify-center p-1.5">
                                        @if(file_exists(public_path('images/ai-models/gemini.png')))
                                            <img src="{{ asset('images/ai-models/gemini.png') }}" alt="Gemini" class="w-full h-full object-contain">
                                        @else
                                            <span class="material-icons-outlined text-white" style="font-size: 14px;">auto_awesome</span>
                                        @endif
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-semibold text-white">Google Gemini</h4>
                                        <p class="text-[10px] text-gray-500">Google's multimodal AI</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           id="gemini_enabled"
                                           name="gemini_enabled"
                                           {{ old('gemini_enabled', $settings['ai.gemini_enabled'] ?? '0') == '1' ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-700 peer-focus:outline-none peer-focus:ring-1 peer-focus:ring-purple-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-purple-600"></div>
                                </label>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <label for="gemini_api_key" class="block text-[10px] font-medium text-gray-400 mb-1">API Key</label>
                                    <input type="password"
                                           id="gemini_api_key"
                                           name="gemini_api_key"
                                           value="{{ old('gemini_api_key', $settings['ai.gemini_api_key'] ?? '') }}"
                                           class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-xs placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                           placeholder="AIza...">
                                </div>

                                <div>
                                    <label for="gemini_model" class="block text-[10px] font-medium text-gray-400 mb-1">Model</label>
                                    <select id="gemini_model"
                                            name="gemini_model"
                                            class="w-full px-3 py-2 bg-gray-900 border border-white/10 rounded text-white text-xs focus:outline-none focus:border-blue-500 transition-colors"
                                            style="color: white;">
                                        @php
                                            $geminiModels = \App\Models\AiModel::where('provider', 'google')
                                                ->where('is_active', true)
                                                ->orderBy('order')
                                                ->get();
                                        @endphp
                                        @foreach($geminiModels as $model)
                                            <option value="{{ $model->model_identifier }}"
                                                    {{ old('gemini_model', $settings['ai.gemini_model'] ?? '') === $model->model_identifier ? 'selected' : '' }}
                                                    style="background-color: #111827; color: white;">
                                                {{ $model->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Individual AI Models Management -->
                    <div class="card rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-white mb-2">Individual AI Models Management</h3>
                        <p class="text-[10px] text-gray-500 mb-4">Enable or disable specific AI models that appear in the chat interface</p>

                        @php
                            $allModels = \App\Models\AiModel::orderBy('provider')->orderBy('order')->get();
                            $groupedModels = $allModels->groupBy('provider');
                        @endphp

                        @foreach($groupedModels as $provider => $models)
                            <div class="mb-4 pb-4 border-b border-gray-800/50 last:border-0">
                                <h4 class="text-xs font-semibold text-white mb-3 capitalize">{{ ucfirst($provider) }} Models</h4>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($models as $model)
                                        <div class="flex items-center justify-between p-2 bg-white/5 rounded border border-gray-800/50 hover:border-gray-700 transition-colors">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 bg-white/5 rounded flex items-center justify-center p-1">
                                                    @if($model->icon && file_exists(public_path($model->icon)))
                                                        <img src="{{ asset($model->icon) }}" alt="{{ $model->name }}" class="w-full h-full object-contain">
                                                    @else
                                                        <span class="text-[8px] font-bold" style="color: {{ $model->color }}">{{ strtoupper(substr($model->name, 0, 2)) }}</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="text-[10px] font-medium text-white">{{ $model->name }}</p>
                                                    <p class="text-[8px] text-gray-500">{{ $model->description }}</p>
                                                </div>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox"
                                                       class="sr-only peer ai-model-toggle"
                                                       data-model-id="{{ $model->id }}"
                                                       {{ $model->is_active ? 'checked' : '' }}>
                                                <div class="w-8 h-4 bg-gray-700 peer-focus:outline-none peer-focus:ring-1 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-blue-600"></div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Content Filter Section -->
                    <div class="card rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-sm font-semibold text-white mb-1">AI Content Filter</h3>
                                <p class="text-[10px] text-gray-500">Guide AI to focus on educational topics with friendly assistance</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       id="content_filter_enabled"
                                       name="content_filter_enabled"
                                       value="1"
                                       {{ old('content_filter_enabled', $settings['ai.content_filter_enabled'] ?? '0') == '1' ? 'checked' : '' }}
                                       class="sr-only peer"
                                       onchange="updateFilterStatus(this.checked)">
                                <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </label>
                        </div>

                        <div class="space-y-3">
                            <!-- Filter Status Indicator -->
                            <div class="p-3 rounded border" id="filter-status-box">
                                <div class="flex items-start gap-2">
                                    <span class="material-icons-outlined text-lg" id="filter-icon"></span>
                                    <div>
                                        <p class="text-xs font-semibold mb-1" id="filter-title"></p>
                                        <p class="text-[10px]" id="filter-description"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Explanation Cards -->
                            <div class="grid grid-cols-2 gap-3">
                                <!-- Strict Mode Card -->
                                <div class="p-3 bg-orange-500/10 border border-orange-500/30 rounded">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="material-icons-outlined text-orange-400" style="font-size: 14px;">shield</span>
                                        <h4 class="text-xs font-semibold text-orange-300">Strict Mode (ON)</h4>
                                    </div>
                                    <ul class="text-[9px] text-orange-200/80 space-y-1 ml-5 list-disc">
                                        <li>Only educational topics allowed</li>
                                        <li>Rejects entertainment questions</li>
                                        <li>No casual conversation</li>
                                        <li>Best for schools & education</li>
                                    </ul>
                                </div>

                                <!-- Friendly Mode Card -->
                                <div class="p-3 bg-green-500/10 border border-green-500/30 rounded">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="material-icons-outlined text-green-400" style="font-size: 14px;">chat</span>
                                        <h4 class="text-xs font-semibold text-green-300">Friendly Mode (OFF)</h4>
                                    </div>
                                    <ul class="text-[9px] text-green-200/80 space-y-1 ml-5 list-disc">
                                        <li>Educational + general knowledge</li>
                                        <li>Answers all safe topics</li>
                                        <li>Conversational & helpful</li>
                                        <li>Better user experience</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Warning -->
                            <div class="bg-blue-500/10 border border-blue-500/30 rounded p-2 flex items-start gap-2">
                                <span class="material-icons-outlined text-blue-400" style="font-size: 12px;">info</span>
                                <p class="text-[9px] text-blue-300">
                                    <strong>Note:</strong> Changes take effect immediately for new conversations. Cache is automatically cleared after saving.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Educational AI Tutor System Prompt Section -->
                    <div class="card rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-white mb-3">Educational AI Tutor System Prompt</h3>

                        <div class="space-y-3">
                            <div class="bg-purple-500/10 border border-purple-500/30 rounded p-3 flex items-start gap-2">
                                <span class="material-icons-outlined text-purple-400" style="font-size: 14px;">school</span>
                                <div class="text-[10px] text-purple-300">
                                    <p class="font-medium mb-1">About the Educational Tutor Prompt:</p>
                                    <p class="text-purple-300/90">This prompt controls how the AI behaves as an educational tutor in regular chat. It defines the AI's personality, response style, and educational approach.</p>
                                </div>
                            </div>

                            <div>
                                <label for="ai_educational_system_prompt" class="block text-[10px] font-medium text-gray-400 mb-1">System Prompt</label>
                                <textarea id="ai_educational_system_prompt"
                                          name="ai_educational_system_prompt"
                                          rows="12"
                                          class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-[10px] placeholder-gray-500 focus:outline-none focus:border-purple-500 transition-colors font-mono"
                                          placeholder="You are an educational AI tutor...">{{ old('ai_educational_system_prompt', $settings['ai_educational_system_prompt'] ?? 'You are an educational AI tutor.
Your primary role is to help with studies.
If the user message is a continuation or follow-up of a study topic,
you MUST continue explaining without asking again.
If the message is unrelated, politely guide the user back to study topics.
If the user says "yes", "ok", "explain more", "give examples",
treat it as a continuation of the previous educational topic.
DO NOT refuse.
If the user greets (hello, hi),
respond politely and invite them to ask a study-related question.
Do NOT show refusal message.
Never break the conversation flow.') }}</textarea>
                                <p class="mt-1 text-[9px] text-gray-500">
                                    This prompt is used for all regular chat conversations with students.
                                </p>
                            </div>

                            <div class="bg-green-500/10 border border-green-500/30 rounded p-2 flex items-start gap-2">
                                <span class="material-icons-outlined text-green-400" style="font-size: 12px;">tips_and_updates</span>
                                <p class="text-[9px] text-green-300">
                                    <strong>Tip:</strong> Keep the tone friendly and encouraging. This prompt helps create a natural learning experience without interrupting the conversation flow.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- NCERT System Prompt Section -->
                    <div class="card rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-white mb-3">NCERT AI Tutor System Prompt</h3>

                        <div class="space-y-3">
                            <div class="bg-blue-500/10 border border-blue-500/30 rounded p-3 flex items-start gap-2">
                                <span class="material-icons-outlined text-blue-400" style="font-size: 14px;">info</span>
                                <div class="text-[10px] text-blue-300">
                                    <p class="font-medium mb-1">About the NCERT System Prompt:</p>
                                    <p class="text-blue-300/90">This prompt controls how the AI responds to student questions using NCERT materials. The AI will ONLY answer from the provided context.</p>
                                </div>
                            </div>

                            <div>
                                <label for="ai_ncert_system_prompt" class="block text-[10px] font-medium text-gray-400 mb-1">System Prompt</label>
                                <textarea id="ai_ncert_system_prompt"
                                          name="ai_ncert_system_prompt"
                                          rows="12"
                                          class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-[10px] placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                          placeholder="You are an academic tutor AI for Indian school students...">{{ old('ai_ncert_system_prompt', $settings['ai_ncert_system_prompt'] ?? '') }}</textarea>
                                <p class="mt-1 text-[9px] text-gray-500">
                                    This prompt is used when answering questions with RAG (Retrieval-Augmented Generation).
                                </p>
                            </div>

                            <div class="bg-amber-500/10 border border-amber-500/30 rounded p-2 flex items-start gap-2">
                                <span class="material-icons-outlined text-amber-400" style="font-size: 12px;">warning</span>
                                <p class="text-[9px] text-amber-300">
                                    <strong>Important:</strong> This prompt ensures the AI only answers from NCERT materials. Test thoroughly after making changes.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Thinking Level Configuration Section -->
                    <div class="card rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-sm font-semibold text-white">Thinking Level Configuration</h3>
                                <p class="text-[10px] text-gray-500 mt-0.5">Configure thinking levels for different AI features. Higher levels use more reasoning but may be slower.</p>
                            </div>
                            <span class="px-2 py-1 bg-amber-500/20 border border-amber-500/30 rounded text-[9px] font-medium text-amber-300">Coming Soon</span>
                        </div>

                        <!-- Info Banner -->
                        <div class="bg-amber-500/10 border border-amber-500/30 rounded p-3 flex items-start gap-2 mb-4">
                            <span class="material-icons-outlined text-amber-400" style="font-size: 14px;">info</span>
                            <div class="text-[10px] text-amber-300">
                                <p class="font-medium mb-1">Feature in Development</p>
                                <p class="text-amber-300/90">Thinking levels are currently being prepared for future Gemini API updates. Settings are saved but not yet applied to AI responses. This feature will be automatically enabled when Google adds thinking level support to their API.</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Chat Support -->
                            <div class="bg-white/5 rounded-lg p-3 border border-white/10">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="material-icons-outlined text-blue-400" style="font-size: 16px;">chat</span>
                                        <div>
                                            <h4 class="text-xs font-semibold text-white">Chat Support</h4>
                                            <p class="text-[9px] text-gray-400">General conversation and quick questions</p>
                                        </div>
                                    </div>
                                    <select name="thinking_level_chat" class="px-3 py-1.5 bg-gray-900 border border-white/10 rounded text-white text-xs focus:outline-none focus:border-blue-500">
                                        <option value="minimal" {{ old('thinking_level_chat', $settings['ai.thinking_level.chat'] ?? 'minimal') === 'minimal' ? 'selected' : '' }}>Minimal</option>
                                        <option value="medium" {{ old('thinking_level_chat', $settings['ai.thinking_level.chat'] ?? 'minimal') === 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ old('thinking_level_chat', $settings['ai.thinking_level.chat'] ?? 'minimal') === 'high' ? 'selected' : '' }}>High</option>
                                    </select>
                                </div>
                                <p class="text-[9px] text-gray-500 ml-6">Recommended: <span class="text-blue-400">Minimal</span> - Fast responses for casual chat</p>
                            </div>

                            <!-- PDF Solve/Explain -->
                            <div class="bg-white/5 rounded-lg p-3 border border-white/10">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="material-icons-outlined text-purple-400" style="font-size: 16px;">picture_as_pdf</span>
                                        <div>
                                            <h4 class="text-xs font-semibold text-white">PDF Solve/Explain</h4>
                                            <p class="text-[9px] text-gray-400">Document analysis and problem solving</p>
                                        </div>
                                    </div>
                                    <select name="thinking_level_pdf_solve" class="px-3 py-1.5 bg-gray-900 border border-white/10 rounded text-white text-xs focus:outline-none focus:border-blue-500">
                                        <option value="minimal" {{ old('thinking_level_pdf_solve', $settings['ai.thinking_level.pdf_solve'] ?? 'medium') === 'minimal' ? 'selected' : '' }}>Minimal</option>
                                        <option value="medium" {{ old('thinking_level_pdf_solve', $settings['ai.thinking_level.pdf_solve'] ?? 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ old('thinking_level_pdf_solve', $settings['ai.thinking_level.pdf_solve'] ?? 'medium') === 'high' ? 'selected' : '' }}>High</option>
                                    </select>
                                </div>
                                <p class="text-[9px] text-gray-500 ml-6">Recommended: <span class="text-purple-400">Medium</span> - Balanced reasoning for documents</p>
                            </div>

                            <!-- Math/Logic Reasoning -->
                            <div class="bg-white/5 rounded-lg p-3 border border-white/10">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="material-icons-outlined text-orange-400" style="font-size: 16px;">calculate</span>
                                        <div>
                                            <h4 class="text-xs font-semibold text-white">Math/Logic Reasoning</h4>
                                            <p class="text-[9px] text-gray-400">Complex problem solving and calculations</p>
                                        </div>
                                    </div>
                                    <select name="thinking_level_math_reasoning" class="px-3 py-1.5 bg-gray-900 border border-white/10 rounded text-white text-xs focus:outline-none focus:border-blue-500">
                                        <option value="minimal" {{ old('thinking_level_math_reasoning', $settings['ai.thinking_level.math_reasoning'] ?? 'high') === 'minimal' ? 'selected' : '' }}>Minimal</option>
                                        <option value="medium" {{ old('thinking_level_math_reasoning', $settings['ai.thinking_level.math_reasoning'] ?? 'high') === 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ old('thinking_level_math_reasoning', $settings['ai.thinking_level.math_reasoning'] ?? 'high') === 'high' ? 'selected' : '' }}>High</option>
                                    </select>
                                </div>
                                <p class="text-[9px] text-gray-500 ml-6">Recommended: <span class="text-orange-400">High</span> - Deep reasoning for accuracy</p>
                            </div>

                            <!-- MCQ Generation -->
                            <div class="bg-white/5 rounded-lg p-3 border border-white/10">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="material-icons-outlined text-green-400" style="font-size: 16px;">quiz</span>
                                        <div>
                                            <h4 class="text-xs font-semibold text-white">MCQ Generation</h4>
                                            <p class="text-[9px] text-gray-400">Quiz and question generation</p>
                                        </div>
                                    </div>
                                    <select name="thinking_level_mcq_generation" class="px-3 py-1.5 bg-gray-900 border border-white/10 rounded text-white text-xs focus:outline-none focus:border-blue-500">
                                        <option value="minimal" {{ old('thinking_level_mcq_generation', $settings['ai.thinking_level.mcq_generation'] ?? 'medium') === 'minimal' ? 'selected' : '' }}>Minimal</option>
                                        <option value="medium" {{ old('thinking_level_mcq_generation', $settings['ai.thinking_level.mcq_generation'] ?? 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ old('thinking_level_mcq_generation', $settings['ai.thinking_level.mcq_generation'] ?? 'medium') === 'high' ? 'selected' : '' }}>High</option>
                                    </select>
                                </div>
                                <p class="text-[9px] text-gray-500 ml-6">Recommended: <span class="text-green-400">Medium</span> - Quality quiz questions</p>
                            </div>

                            <!-- Info Box -->
                            <div class="bg-blue-500/10 border border-blue-500/30 rounded p-3 flex items-start gap-2">
                                <span class="material-icons-outlined text-blue-400" style="font-size: 14px;">info</span>
                                <div class="text-[10px] text-blue-300">
                                    <p class="font-medium mb-1">About Thinking Levels:</p>
                                    <ul class="text-blue-300/90 space-y-1 ml-3">
                                        <li>• <strong>Minimal:</strong> Fast responses with basic reasoning</li>
                                        <li>• <strong>Medium:</strong> Balanced speed and reasoning quality</li>
                                        <li>• <strong>High:</strong> Deep analysis and step-by-step reasoning (slower)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end">
                        <button type="submit"
                                class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-lg transition-all text-xs">
                            Save AI Settings
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // AI Model Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const modelToggles = document.querySelectorAll('.ai-model-toggle');

            modelToggles.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const modelId = this.dataset.modelId;
                    const isActive = this.checked;

                    fetch(`/admin/ai-models/${modelId}/toggle`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ is_active: isActive })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const message = document.createElement('div');
                            message.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50 text-xs';
                            message.textContent = `${data.model_name} ${isActive ? 'enabled' : 'disabled'} successfully`;
                            document.body.appendChild(message);
                            setTimeout(() => message.remove(), 3000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.checked = !isActive;
                        const message = document.createElement('div');
                        message.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50 text-xs';
                        message.textContent = 'Failed to update model status';
                        document.body.appendChild(message);
                        setTimeout(() => message.remove(), 3000);
                    });
                });
            });
        });

        // Content Filter Status Updater
        function updateFilterStatus(isEnabled) {
            const statusBox = document.getElementById('filter-status-box');
            const icon = document.getElementById('filter-icon');
            const title = document.getElementById('filter-title');
            const description = document.getElementById('filter-description');

            if (isEnabled) {
                // Strict Mode
                statusBox.className = 'p-3 rounded border bg-orange-500/10 border-orange-500/30';
                icon.className = 'material-icons-outlined text-lg text-orange-400';
                icon.textContent = 'shield';
                title.className = 'text-xs font-semibold mb-1 text-orange-300';
                title.textContent = 'Strict Mode: ON';
                description.className = 'text-[10px] text-orange-200/80';
                description.textContent = 'AI will only respond to educational topics. Entertainment and casual questions will be rejected.';
            } else {
                // Friendly Mode
                statusBox.className = 'p-3 rounded border bg-green-500/10 border-green-500/30';
                icon.className = 'material-icons-outlined text-lg text-green-400';
                icon.textContent = 'chat';
                title.className = 'text-xs font-semibold mb-1 text-green-300';
                title.textContent = 'Friendly Mode: OFF';
                description.className = 'text-[10px] text-green-200/80';
                description.textContent = 'AI can answer educational topics and general knowledge questions. More conversational and helpful.';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const filterToggle = document.getElementById('content_filter_enabled');
            if (filterToggle) {
                updateFilterStatus(filterToggle.checked);
            }
        });
    </script>
</body>
</html>

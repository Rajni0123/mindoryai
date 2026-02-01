<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Whiteboard Video Settings</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .card:hover { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.12); }
        select option { background: #1a1a1a; color: #fff; }
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
                        <h1 class="text-base font-semibold text-white">Whiteboard Video Settings</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Configure whiteboard video generation feature</p>
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

                <form method="POST" action="{{ route('admin.whiteboard-video.settings.update') }}" class="space-y-4">
                    @csrf

                    <!-- Feature Enable/Disable -->
                    <div class="card rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center">
                                    <span class="material-icons-outlined text-white" style="font-size: 20px;">video_library</span>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-white">Whiteboard Video Feature</h4>
                                    <p class="text-[10px] text-gray-500">Convert documents into animated whiteboard-style explainer videos</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox"
                                       id="enabled"
                                       name="enabled"
                                       value="1"
                                       {{ old('enabled', $settings['enabled']) == '1' ? 'checked' : '' }}
                                       class="w-4 h-4 rounded bg-gray-800/50 border-gray-700 text-green-500 focus:ring-green-500 focus:ring-offset-0">
                                <label for="enabled" class="text-xs font-medium text-green-400">Enable Feature</label>
                            </div>
                        </div>
                    </div>

                    <!-- Storyboard AI Selection -->
                    <div class="card rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-white mb-3">Storyboard AI (Script Generator)</h3>
                        <p class="text-[10px] text-gray-500 mb-4">Which AI generates the video storyboard, narration script, and scene breakdown</p>

                        <div class="space-y-3">
                            <div>
                                <label for="storyboard_provider" class="block text-[10px] font-medium text-gray-400 mb-1.5">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">auto_stories</span>
                                        Storyboard AI Provider
                                    </span>
                                </label>
                                <select id="storyboard_provider"
                                        name="storyboard_provider"
                                        onchange="toggleStoryboardFields()"
                                        class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500 transition-colors">
                                    <option value="gemini" {{ old('storyboard_provider', $settings['storyboard_provider'] ?? 'gemini') == 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                                    <option value="openai" {{ old('storyboard_provider', $settings['storyboard_provider'] ?? '') == 'openai' ? 'selected' : '' }}>OpenAI (GPT-4o / GPT-4)</option>
                                    <option value="deepseek" {{ old('storyboard_provider', $settings['storyboard_provider'] ?? '') == 'deepseek' ? 'selected' : '' }}>DeepSeek</option>
                                </select>
                            </div>

                            <!-- Gemini Model Selection -->
                            <div id="storyboard_gemini_fields">
                                <label for="model_id" class="block text-[10px] font-medium text-gray-400 mb-1.5">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">psychology</span>
                                        Gemini Model
                                    </span>
                                </label>
                                <select id="model_id"
                                        name="model_id"
                                        class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500 transition-colors">
                                    @foreach($aiModels as $model)
                                        <option value="{{ $model->id }}"
                                                {{ old('model_id', $settings['model_id']) == $model->id ? 'selected' : '' }}>
                                            {{ $model->display_name }} - {{ $model->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1.5 text-[9px] text-gray-500">
                                    <span class="material-icons-outlined align-middle" style="font-size: 10px;">info</span>
                                    Uses Gemini API key from AI settings. Recommended: gemini-2.0-flash
                                </p>
                            </div>

                            <!-- OpenAI Model Selection -->
                            <div id="storyboard_openai_fields" style="display:none;">
                                <label for="storyboard_openai_model" class="block text-[10px] font-medium text-gray-400 mb-1.5">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">psychology</span>
                                        OpenAI Model
                                    </span>
                                </label>
                                <select id="storyboard_openai_model"
                                        name="storyboard_openai_model"
                                        class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500 transition-colors">
                                    <option value="gpt-4o" {{ old('storyboard_openai_model', $settings['storyboard_openai_model'] ?? 'gpt-4o') == 'gpt-4o' ? 'selected' : '' }}>GPT-4o (Recommended)</option>
                                    <option value="gpt-4o-mini" {{ old('storyboard_openai_model', $settings['storyboard_openai_model'] ?? '') == 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini (Faster, Cheaper)</option>
                                    <option value="gpt-4-turbo" {{ old('storyboard_openai_model', $settings['storyboard_openai_model'] ?? '') == 'gpt-4-turbo' ? 'selected' : '' }}>GPT-4 Turbo</option>
                                </select>

                                <label for="storyboard_openai_key" class="block text-[10px] font-medium text-gray-400 mb-1.5 mt-3">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">key</span>
                                        OpenAI API Key
                                    </span>
                                </label>
                                <input type="password"
                                       id="storyboard_openai_key"
                                       name="storyboard_openai_key"
                                       value="{{ old('storyboard_openai_key', $settings['storyboard_openai_key'] ?? '') }}"
                                       class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="sk-...">
                                <p class="mt-1.5 text-[9px] text-gray-500">
                                    <span class="material-icons-outlined align-middle" style="font-size: 10px;">info</span>
                                    Get your key from platform.openai.com/api-keys
                                </p>
                            </div>

                            <!-- DeepSeek Model Selection -->
                            <div id="storyboard_deepseek_fields" style="display:none;">
                                <label for="storyboard_deepseek_model" class="block text-[10px] font-medium text-gray-400 mb-1.5">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">psychology</span>
                                        DeepSeek Model
                                    </span>
                                </label>
                                <select id="storyboard_deepseek_model"
                                        name="storyboard_deepseek_model"
                                        class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500 transition-colors">
                                    <option value="deepseek-chat" {{ old('storyboard_deepseek_model', $settings['storyboard_deepseek_model'] ?? 'deepseek-chat') == 'deepseek-chat' ? 'selected' : '' }}>DeepSeek Chat (V3)</option>
                                    <option value="deepseek-reasoner" {{ old('storyboard_deepseek_model', $settings['storyboard_deepseek_model'] ?? '') == 'deepseek-reasoner' ? 'selected' : '' }}>DeepSeek Reasoner (R1)</option>
                                </select>

                                <label for="storyboard_deepseek_key" class="block text-[10px] font-medium text-gray-400 mb-1.5 mt-3">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">key</span>
                                        DeepSeek API Key
                                    </span>
                                </label>
                                <input type="password"
                                       id="storyboard_deepseek_key"
                                       name="storyboard_deepseek_key"
                                       value="{{ old('storyboard_deepseek_key', $settings['storyboard_deepseek_key'] ?? '') }}"
                                       class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="sk-...">
                                <p class="mt-1.5 text-[9px] text-gray-500">
                                    <span class="material-icons-outlined align-middle" style="font-size: 10px;">info</span>
                                    Get your key from platform.deepseek.com
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Google Cloud TTS Configuration -->
                    <div class="card rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-white mb-3">Text-to-Speech Settings</h3>
                        <p class="text-[10px] text-gray-500 mb-4">Configure Google Cloud Text-to-Speech for video narration</p>

                        <div class="space-y-3">
                            <div>
                                <label for="tts_voice" class="block text-[10px] font-medium text-gray-400 mb-1.5">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">record_voice_over</span>
                                        Voice Selection
                                    </span>
                                </label>
                                <select id="tts_voice"
                                        name="tts_voice"
                                        class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500 transition-colors">
                                    <option value="hi-IN-Neural2-A" {{ old('tts_voice', $settings['tts_voice'] ?? 'hi-IN-Neural2-A') == 'hi-IN-Neural2-A' ? 'selected' : '' }}>Hindi Female (hi-IN-Neural2-A)</option>
                                    <option value="hi-IN-Neural2-B" {{ old('tts_voice', $settings['tts_voice'] ?? '') == 'hi-IN-Neural2-B' ? 'selected' : '' }}>Hindi Male (hi-IN-Neural2-B)</option>
                                    <option value="hi-IN-Neural2-C" {{ old('tts_voice', $settings['tts_voice'] ?? '') == 'hi-IN-Neural2-C' ? 'selected' : '' }}>Hindi Female 2 (hi-IN-Neural2-C)</option>
                                    <option value="hi-IN-Neural2-D" {{ old('tts_voice', $settings['tts_voice'] ?? '') == 'hi-IN-Neural2-D' ? 'selected' : '' }}>Hindi Male 2 (hi-IN-Neural2-D)</option>
                                    <option value="en-US-Journey-D" {{ old('tts_voice', $settings['tts_voice'] ?? '') == 'en-US-Journey-D' ? 'selected' : '' }}>English US (Journey-D)</option>
                                    <option value="en-US-Journey-F" {{ old('tts_voice', $settings['tts_voice'] ?? '') == 'en-US-Journey-F' ? 'selected' : '' }}>English US Female (Journey-F)</option>
                                </select>
                                <p class="mt-1.5 text-[9px] text-gray-500">
                                    <span class="material-icons-outlined align-middle" style="font-size: 10px;">info</span>
                                    Default: hi-IN-Neural2-A (Hindi female voice)
                                </p>
                            </div>

                            <div>
                                <label for="tts_speaking_rate" class="block text-[10px] font-medium text-gray-400 mb-1.5">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">speed</span>
                                        Speaking Rate
                                    </span>
                                </label>
                                <input type="number"
                                       id="tts_speaking_rate"
                                       name="tts_speaking_rate"
                                       value="{{ old('tts_speaking_rate', $settings['tts_speaking_rate'] ?? '1.0') }}"
                                       min="0.25"
                                       max="4.0"
                                       step="0.1"
                                       class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="1.0">
                                <p class="mt-1.5 text-[9px] text-gray-500">
                                    <span class="material-icons-outlined align-middle" style="font-size: 10px;">info</span>
                                    Range: 0.25 to 4.0 (1.0 is normal speed)
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Video Pipeline Selection -->
                    <div class="card rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-white mb-3">Video Generation Pipeline</h3>
                        <p class="text-[10px] text-gray-500 mb-4">Choose between PHP (GD + Google TTS + FFmpeg) or Python (Manim + Edge TTS) pipeline</p>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-[10px] font-medium text-gray-400 mb-2">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">route</span>
                                        Active Pipeline
                                    </span>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="pipeline" value="php"
                                               {{ old('pipeline', $settings['pipeline'] ?? 'php') === 'php' ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="p-3 border border-white/10 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-500/10 transition-all">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="material-icons-outlined text-orange-400" style="font-size: 16px;">code</span>
                                                <span class="text-xs font-semibold text-white">PHP Pipeline</span>
                                            </div>
                                            <p class="text-[9px] text-gray-400">GD Library + Google Cloud TTS + FFmpeg</p>
                                            <p class="text-[9px] text-gray-500 mt-1">Static images, Google TTS (paid)</p>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="pipeline" value="manim"
                                               {{ old('pipeline', $settings['pipeline'] ?? 'php') === 'manim' ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="p-3 border border-white/10 rounded-lg peer-checked:border-purple-500 peer-checked:bg-purple-500/10 transition-all">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="material-icons-outlined text-purple-400" style="font-size: 16px;">movie_creation</span>
                                                <span class="text-xs font-semibold text-white">Manim Pipeline</span>
                                            </div>
                                            <p class="text-[9px] text-gray-400">Python Manim + Edge TTS (free) + FFmpeg</p>
                                            <p class="text-[9px] text-gray-500 mt-1">Animated scenes, free TTS</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Image Generation Provider -->
                    <div class="card rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-sm font-semibold text-white">AI Image Generation</h3>
                                <p class="text-[10px] text-gray-500 mt-0.5">Select which AI service generates whiteboard illustrations in videos</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox"
                                       id="image_generation_enabled"
                                       name="image_generation_enabled"
                                       value="1"
                                       {{ old('image_generation_enabled', $settings['image_generation_enabled'] ?? '0') == '1' ? 'checked' : '' }}
                                       class="w-4 h-4 rounded bg-gray-800/50 border-gray-700 text-green-500 focus:ring-green-500 focus:ring-offset-0">
                                <label for="image_generation_enabled" class="text-xs font-medium text-green-400">Enable Images</label>
                            </div>
                        </div>

                        <div id="image-provider-settings" class="space-y-3">
                            <div>
                                <label for="image_provider" class="block text-[10px] font-medium text-gray-400 mb-1.5">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">image</span>
                                        Image AI Provider
                                    </span>
                                </label>
                                <select id="image_provider"
                                        name="image_provider"
                                        onchange="toggleImageApiFields()"
                                        class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500 transition-colors">
                                    <option value="gemini_imagen" {{ old('image_provider', $settings['image_provider'] ?? 'gemini_imagen') == 'gemini_imagen' ? 'selected' : '' }}>Google Imagen (via Gemini API)</option>
                                    <option value="openai_dalle" {{ old('image_provider', $settings['image_provider'] ?? '') == 'openai_dalle' ? 'selected' : '' }}>OpenAI DALL-E 3</option>
                                    <option value="openai_gpt_image" {{ old('image_provider', $settings['image_provider'] ?? '') == 'openai_gpt_image' ? 'selected' : '' }}>OpenAI GPT-Image-1 (Best Quality)</option>
                                    <option value="stability_ai" {{ old('image_provider', $settings['image_provider'] ?? '') == 'stability_ai' ? 'selected' : '' }}>Stability AI (Stable Diffusion)</option>
                                    <option value="pollinations" {{ old('image_provider', $settings['image_provider'] ?? '') == 'pollinations' ? 'selected' : '' }}>Pollinations.ai (Free - No API Key)</option>
                                </select>
                                <p class="mt-1.5 text-[9px] text-gray-500">
                                    <span class="material-icons-outlined align-middle" style="font-size: 10px;">info</span>
                                    Choose which AI generates educational diagrams and illustrations for each scene
                                </p>
                            </div>

                            <!-- API Key for selected provider -->
                            <div id="image_api_key_field">
                                <label for="image_api_key" class="block text-[10px] font-medium text-gray-400 mb-1.5">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">key</span>
                                        <span id="image_api_key_label">API Key</span>
                                    </span>
                                </label>
                                <input type="password"
                                       id="image_api_key"
                                       name="image_api_key"
                                       value="{{ old('image_api_key', $settings['image_api_key'] ?? '') }}"
                                       class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="Enter API key for selected provider">
                                <p class="mt-1.5 text-[9px] text-gray-500" id="image_api_key_hint">
                                    <span class="material-icons-outlined align-middle" style="font-size: 10px;">info</span>
                                    <span>Leave empty to use Gemini API key from AI settings</span>
                                </p>
                            </div>

                            <!-- Provider-specific info cards -->
                            <div id="provider_info_gemini" class="p-3 bg-blue-500/10 border border-blue-500/30 rounded-lg provider-info">
                                <p class="text-[10px] text-blue-300 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 14px;">info</span>
                                    <span>Uses Imagen 4.0 Fast via Gemini API. Free tier: 70 images/day. Leave API key empty to use main Gemini key.</span>
                                </p>
                            </div>
                            <div id="provider_info_openai" class="p-3 bg-green-500/10 border border-green-500/30 rounded-lg provider-info" style="display:none;">
                                <p class="text-[10px] text-green-300 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 14px;">info</span>
                                    <span>Uses DALL-E 3 for high-quality illustrations. Cost: ~$0.04/image. Requires OpenAI API key.</span>
                                </p>
                            </div>
                            <div id="provider_info_gpt_image" class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-lg provider-info" style="display:none;">
                                <p class="text-[10px] text-emerald-300 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 14px;">info</span>
                                    <span>OpenAI's newest image model (gpt-image-1). Best quality, understands complex prompts. Cost: ~$0.02-0.19/image depending on quality. Requires OpenAI API key.</span>
                                </p>
                            </div>
                            <div id="provider_info_stability" class="p-3 bg-orange-500/10 border border-orange-500/30 rounded-lg provider-info" style="display:none;">
                                <p class="text-[10px] text-orange-300 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 14px;">info</span>
                                    <span>Uses Stable Diffusion 3 via Stability API. Cost: ~25 credits/image. Requires Stability AI API key.</span>
                                </p>
                            </div>
                            <div id="provider_info_pollinations" class="p-3 bg-purple-500/10 border border-purple-500/30 rounded-lg provider-info" style="display:none;">
                                <p class="text-[10px] text-purple-300 flex items-center gap-2">
                                    <span class="material-icons-outlined" style="font-size: 14px;">info</span>
                                    <span>FREE service - no API key needed! Lower quality but unlimited usage. Good for testing.</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Manim Server Configuration -->
                    <div class="card rounded-lg p-4" id="manim-settings">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-sm font-semibold text-white">Python/Manim Server</h3>
                                <p class="text-[10px] text-gray-500 mt-0.5">Configure the Python Manim video generation server</p>
                            </div>
                            @isset($manimHealthy)
                            <div class="flex items-center gap-1.5 px-2 py-1 rounded-full text-[10px] font-medium
                                {{ $manimHealthy ? 'bg-green-500/10 text-green-400 border border-green-500/30' : 'bg-red-500/10 text-red-400 border border-red-500/30' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $manimHealthy ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                {{ $manimHealthy ? 'Server Online' : 'Server Offline' }}
                            </div>
                            @endisset
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label for="manim_server_url" class="block text-[10px] font-medium text-gray-400 mb-1.5">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">dns</span>
                                        Server URL
                                    </span>
                                </label>
                                <input type="url"
                                       id="manim_server_url"
                                       name="manim_server_url"
                                       value="{{ old('manim_server_url', $settings['manim_server_url'] ?? 'http://localhost:5000') }}"
                                       class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs placeholder-gray-500 focus:outline-none focus:border-purple-500 transition-colors"
                                       placeholder="http://localhost:5000">
                                <p class="mt-1.5 text-[9px] text-gray-500">
                                    <span class="material-icons-outlined align-middle" style="font-size: 10px;">info</span>
                                    Python Flask server URL running the Manim video generator
                                </p>
                            </div>

                            <div>
                                <label for="manim_tts_voice" class="block text-[10px] font-medium text-gray-400 mb-1.5">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">record_voice_over</span>
                                        Edge TTS Voice
                                    </span>
                                </label>
                                <select id="manim_tts_voice"
                                        name="manim_tts_voice"
                                        class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-purple-500 transition-colors">
                                    <option value="hi-IN-MadhurNeural" {{ old('manim_tts_voice', $settings['manim_tts_voice'] ?? '') == 'hi-IN-MadhurNeural' ? 'selected' : '' }}>Madhur - Hindi Male (Neural)</option>
                                    <option value="hi-IN-SwaraNeural" {{ old('manim_tts_voice', $settings['manim_tts_voice'] ?? '') == 'hi-IN-SwaraNeural' ? 'selected' : '' }}>Swara - Hindi Female (Neural)</option>
                                    <option value="en-IN-NeerjaNeural" {{ old('manim_tts_voice', $settings['manim_tts_voice'] ?? '') == 'en-IN-NeerjaNeural' ? 'selected' : '' }}>Neerja - English IN Female</option>
                                    <option value="en-IN-PrabhatNeural" {{ old('manim_tts_voice', $settings['manim_tts_voice'] ?? '') == 'en-IN-PrabhatNeural' ? 'selected' : '' }}>Prabhat - English IN Male</option>
                                    <option value="en-US-JennyNeural" {{ old('manim_tts_voice', $settings['manim_tts_voice'] ?? '') == 'en-US-JennyNeural' ? 'selected' : '' }}>Jenny - English US Female</option>
                                    <option value="en-US-GuyNeural" {{ old('manim_tts_voice', $settings['manim_tts_voice'] ?? '') == 'en-US-GuyNeural' ? 'selected' : '' }}>Guy - English US Male</option>
                                </select>
                                <p class="mt-1.5 text-[9px] text-gray-500">
                                    <span class="material-icons-outlined align-middle" style="font-size: 10px;">info</span>
                                    Edge TTS is FREE - no API key required (uses Microsoft Edge's TTS engine)
                                </p>
                            </div>

                            <div>
                                <label for="manim_tts_rate" class="block text-[10px] font-medium text-gray-400 mb-1.5">
                                    <span class="flex items-center gap-1">
                                        <span class="material-icons-outlined" style="font-size: 12px;">speed</span>
                                        Speaking Rate
                                    </span>
                                </label>
                                <select id="manim_tts_rate"
                                        name="manim_tts_rate"
                                        class="w-full px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-purple-500 transition-colors">
                                    <option value="-20%" {{ old('manim_tts_rate', $settings['manim_tts_rate'] ?? '') == '-20%' ? 'selected' : '' }}>Slow (-20%)</option>
                                    <option value="-10%" {{ old('manim_tts_rate', $settings['manim_tts_rate'] ?? '') == '-10%' ? 'selected' : '' }}>Slightly Slow (-10%)</option>
                                    <option value="+0%" {{ old('manim_tts_rate', $settings['manim_tts_rate'] ?? '+0%') == '+0%' ? 'selected' : '' }}>Normal</option>
                                    <option value="+10%" {{ old('manim_tts_rate', $settings['manim_tts_rate'] ?? '') == '+10%' ? 'selected' : '' }}>Slightly Fast (+10%)</option>
                                    <option value="+20%" {{ old('manim_tts_rate', $settings['manim_tts_rate'] ?? '') == '+20%' ? 'selected' : '' }}>Fast (+20%)</option>
                                </select>
                            </div>

                            <!-- Setup Instructions -->
                            <div class="p-3 bg-purple-500/10 border border-purple-500/30 rounded-lg mt-3">
                                <h5 class="text-[10px] font-semibold text-purple-300 mb-2">Setup Instructions</h5>
                                <ol class="space-y-1 text-[9px] text-gray-400 list-decimal list-inside">
                                    <li>Install Python 3.10+ on your server</li>
                                    <li><code class="bg-white/5 px-1 rounded">cd manim-server && pip install -r requirements.txt</code></li>
                                    <li>Copy <code class="bg-white/5 px-1 rounded">.env.example</code> to <code class="bg-white/5 px-1 rounded">.env</code> and set GEMINI_API_KEY</li>
                                    <li>Run: <code class="bg-white/5 px-1 rounded">python app.py</code> (port 5000)</li>
                                    <li>For production: <code class="bg-white/5 px-1 rounded">gunicorn -w 2 -b 0.0.0.0:5000 app:app</code></li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Feature Information -->
                    <div class="card rounded-lg p-4 bg-blue-500/5 border-blue-500/20">
                        <div class="flex gap-3">
                            <span class="material-icons-outlined text-blue-400" style="font-size: 18px;">info</span>
                            <div class="flex-1">
                                <h4 class="text-xs font-semibold text-blue-300 mb-2">About Whiteboard Video</h4>
                                <ul class="space-y-1.5 text-[10px] text-gray-400">
                                    <li class="flex items-start gap-1.5">
                                        <span class="material-icons-outlined text-blue-400 mt-0.5" style="font-size: 10px;">check</span>
                                        <span>Converts text documents into animated whiteboard-style explainer videos</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="material-icons-outlined text-blue-400 mt-0.5" style="font-size: 10px;">check</span>
                                        <span>Uses AI to generate storyboard, visuals, and voice narration</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="material-icons-outlined text-blue-400 mt-0.5" style="font-size: 10px;">check</span>
                                        <span>Powered by Gemini for content generation and Imagen for visuals</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="material-icons-outlined text-blue-400 mt-0.5" style="font-size: 10px;">check</span>
                                        <span>Videos are generated asynchronously using queue jobs</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors flex items-center gap-1.5">
                            <span class="material-icons-outlined" style="font-size: 14px;">save</span>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
        function toggleImageApiFields() {
            const provider = document.getElementById('image_provider').value;
            const apiKeyField = document.getElementById('image_api_key_field');
            const apiKeyLabel = document.getElementById('image_api_key_label');
            const apiKeyHint = document.getElementById('image_api_key_hint');

            // Hide all provider info cards
            document.querySelectorAll('.provider-info').forEach(el => el.style.display = 'none');

            // Show selected provider info
            const infoMap = {
                'gemini_imagen': 'provider_info_gemini',
                'openai_dalle': 'provider_info_openai',
                'openai_gpt_image': 'provider_info_gpt_image',
                'stability_ai': 'provider_info_stability',
                'pollinations': 'provider_info_pollinations',
            };
            const infoEl = document.getElementById(infoMap[provider]);
            if (infoEl) infoEl.style.display = 'block';

            // Update API key field
            if (provider === 'pollinations') {
                apiKeyField.style.display = 'none';
            } else {
                apiKeyField.style.display = 'block';
                const labels = {
                    'gemini_imagen': 'Gemini API Key (optional - uses main key if empty)',
                    'openai_dalle': 'OpenAI API Key',
                    'openai_gpt_image': 'OpenAI API Key',
                    'stability_ai': 'Stability AI API Key',
                };
                apiKeyLabel.textContent = labels[provider] || 'API Key';

                const hints = {
                    'gemini_imagen': 'Leave empty to use Gemini API key from AI settings',
                    'openai_dalle': 'Get your key from platform.openai.com/api-keys',
                    'openai_gpt_image': 'Get your key from platform.openai.com/api-keys (same as DALL-E key)',
                    'stability_ai': 'Get your key from platform.stability.ai/account/keys',
                };
                apiKeyHint.innerHTML = '<span class="material-icons-outlined align-middle" style="font-size: 10px;">info</span> ' + (hints[provider] || '');
            }
        }

        function toggleStoryboardFields() {
            const provider = document.getElementById('storyboard_provider').value;
            document.getElementById('storyboard_gemini_fields').style.display = provider === 'gemini' ? 'block' : 'none';
            document.getElementById('storyboard_openai_fields').style.display = provider === 'openai' ? 'block' : 'none';
            document.getElementById('storyboard_deepseek_fields').style.display = provider === 'deepseek' ? 'block' : 'none';
        }

        // Run on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleImageApiFields();
            toggleStoryboardFields();
        });
    </script>
</body>
</html>

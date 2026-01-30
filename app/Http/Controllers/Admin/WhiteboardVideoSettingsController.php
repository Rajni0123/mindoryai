<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\AiModel;
use Illuminate\Http\Request;

class WhiteboardVideoSettingsController extends Controller
{
    /**
     * Display whiteboard video settings
     */
    public function index()
    {
        $settings = [
            'enabled' => Setting::get('whiteboard_video_enabled', '1'),
            'model_id' => Setting::get('whiteboard_video_model_id', '15'),
            'credit_cost' => Setting::get('whiteboard_video_credit_cost', '50'),
            'tts_voice' => Setting::get('whiteboard_tts_voice', 'hi-IN-Neural2-A'),
            'tts_speaking_rate' => Setting::get('whiteboard_tts_speaking_rate', '1.0'),
            // Manim/Python server settings
            'pipeline' => Setting::get('whiteboard_video_pipeline', 'php'),
            'manim_server_url' => Setting::get('manim_server_url', 'http://localhost:5000'),
            'manim_tts_voice' => Setting::get('manim_tts_voice', 'hi-IN-MadhurNeural'),
            'manim_tts_rate' => Setting::get('manim_tts_rate', '+0%'),
            // Storyboard AI settings
            'storyboard_provider' => Setting::get('whiteboard_storyboard_provider', 'gemini'),
            'storyboard_openai_model' => Setting::get('whiteboard_storyboard_openai_model', 'gpt-4o'),
            'storyboard_openai_key' => Setting::get('whiteboard_storyboard_openai_key', ''),
            'storyboard_deepseek_model' => Setting::get('whiteboard_storyboard_deepseek_model', 'deepseek-chat'),
            'storyboard_deepseek_key' => Setting::get('whiteboard_storyboard_deepseek_key', ''),
            // AI Image Generation settings
            'image_generation_enabled' => Setting::get('whiteboard_image_generation_enabled', '0'),
            'image_provider' => Setting::get('whiteboard_image_provider', 'gemini_imagen'),
            'image_api_key' => Setting::get('whiteboard_image_api_key', ''),
        ];

        // Check Manim server health
        $manimHealthy = false;
        if ($settings['pipeline'] === 'manim') {
            try {
                $manimService = new \App\Services\WhiteboardVideo\ManimVideoService();
                $manimHealthy = $manimService->isHealthy();
            } catch (\Exception $e) {
                $manimHealthy = false;
            }
        }

        // Get available Edge TTS voices from Manim server
        $edgeTtsVoices = [];
        try {
            $manimService = new \App\Services\WhiteboardVideo\ManimVideoService();
            $edgeTtsVoices = $manimService->getVoices();
        } catch (\Exception $e) {
            // Server not available - use defaults
        }

        // Get AI models for selection
        $aiModels = AiModel::where('is_active', true)->get();

        return view('admin.whiteboard-video.settings', compact('settings', 'aiModels', 'manimHealthy', 'edgeTtsVoices'));
    }

    /**
     * Update whiteboard video settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'required|in:0,1',
            'model_id' => 'required|integer|exists:ai_models,id',
            'tts_voice' => 'required|string|max:50',
            'tts_speaking_rate' => 'required|numeric|min:0.25|max:4.0',
            // Manim settings
            'pipeline' => 'required|in:php,manim',
            'manim_server_url' => 'nullable|url|max:255',
            'manim_tts_voice' => 'nullable|string|max:50',
            'manim_tts_rate' => 'nullable|string|max:10',
            // Storyboard AI settings
            'storyboard_provider' => 'nullable|in:gemini,openai,deepseek',
            'storyboard_openai_model' => 'nullable|string|max:50',
            'storyboard_openai_key' => 'nullable|string|max:500',
            'storyboard_deepseek_model' => 'nullable|string|max:50',
            'storyboard_deepseek_key' => 'nullable|string|max:500',
            // Image generation settings
            'image_generation_enabled' => 'nullable|in:0,1',
            'image_provider' => 'nullable|in:gemini_imagen,openai_dalle,openai_gpt_image,stability_ai,pollinations',
            'image_api_key' => 'nullable|string|max:500',
        ]);

        Setting::set('whiteboard_video_enabled', $validated['enabled'], 'features');
        Setting::set('whiteboard_video_model_id', $validated['model_id'], 'features');
        Setting::set('whiteboard_tts_voice', $validated['tts_voice'], 'features');
        Setting::set('whiteboard_tts_speaking_rate', $validated['tts_speaking_rate'], 'features');

        // Manim settings
        Setting::set('whiteboard_video_pipeline', $validated['pipeline'], 'features');
        if ($validated['manim_server_url']) {
            Setting::set('manim_server_url', $validated['manim_server_url'], 'features');
        }
        if ($validated['manim_tts_voice']) {
            Setting::set('manim_tts_voice', $validated['manim_tts_voice'], 'features');
        }
        if ($validated['manim_tts_rate']) {
            Setting::set('manim_tts_rate', $validated['manim_tts_rate'], 'features');
        }

        // Storyboard AI settings
        if (isset($validated['storyboard_provider'])) {
            Setting::set('whiteboard_storyboard_provider', $validated['storyboard_provider'], 'features');
        }
        if (!empty($validated['storyboard_openai_model'])) {
            Setting::set('whiteboard_storyboard_openai_model', $validated['storyboard_openai_model'], 'features');
        }
        if (!empty($validated['storyboard_openai_key'])) {
            Setting::set('whiteboard_storyboard_openai_key', $validated['storyboard_openai_key'], 'features');
        }
        if (!empty($validated['storyboard_deepseek_model'])) {
            Setting::set('whiteboard_storyboard_deepseek_model', $validated['storyboard_deepseek_model'], 'features');
        }
        if (!empty($validated['storyboard_deepseek_key'])) {
            Setting::set('whiteboard_storyboard_deepseek_key', $validated['storyboard_deepseek_key'], 'features');
        }

        // Image generation settings
        Setting::set('whiteboard_image_generation_enabled', $request->input('image_generation_enabled', '0'), 'features');
        if (isset($validated['image_provider'])) {
            Setting::set('whiteboard_image_provider', $validated['image_provider'], 'features');
        }
        if (!empty($validated['image_api_key'])) {
            Setting::set('whiteboard_image_api_key', $validated['image_api_key'], 'features');
        }

        // Clear settings cache
        Setting::clearCache();

        return redirect()->route('admin.whiteboard-video.settings')
            ->with('success', 'Whiteboard video settings updated successfully!');
    }
}

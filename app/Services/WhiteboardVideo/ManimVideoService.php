<?php

namespace App\Services\WhiteboardVideo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use Exception;

/**
 * Service to communicate with the Python/Manim whiteboard video server
 */
class ManimVideoService
{
    private string $serverUrl;
    private string $ttsVoice;
    private string $ttsRate;
    private string $geminiApiKey;
    private string $geminiModel;

    public function __construct()
    {
        $this->serverUrl = rtrim(Setting::get('manim_server_url', 'http://localhost:5000'), '/');
        $this->ttsVoice = Setting::get('manim_tts_voice', 'hi-IN-MadhurNeural');
        $this->ttsRate = Setting::get('manim_tts_rate', '+0%');

        // Fetch Gemini API key from admin panel (FrontendConfig) — same key used by PHP pipeline
        $this->geminiApiKey = \App\Models\FrontendConfig::getValue('ai.gemini_api_key', '');

        // Fetch model name from whiteboard settings
        $modelId = (int) Setting::get('whiteboard_video_model_id', 33);
        $model = \App\Models\AiModel::find($modelId);
        $identifier = $model ? $model->model_identifier : 'gemini-2.0-flash';

        // Block known-invalid models that cause 404
        $invalidModels = ['gemini-nano', 'gemini-2.0-flash-exp', 'gemini-2.0-flash-thinking-exp'];
        if (in_array($identifier, $invalidModels)) {
            Log::warning("Invalid Gemini model '{$identifier}', falling back to gemini-2.0-flash");
            $identifier = 'gemini-2.0-flash';
        }
        $this->geminiModel = $identifier;
    }

    /**
     * Check if the Manim server is running
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)
                ->withOptions(['verify' => false])
                ->get("{$this->serverUrl}/health");
            return $response->successful() && ($response->json('status') === 'ok');
        } catch (Exception $e) {
            Log::warning('Manim server health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Start video generation on the Python server
     */
    public function generateVideo(string $topic, string $title, string $laravelJobId, string $language = 'en', ?string $voice = null): array
    {
        // Get video mode from settings: "simple" or "professional"
        $mode = \App\Models\Setting::get('whiteboard_video_mode', 'professional');

        // Use user-selected voice, or fall back to admin default
        $ttsVoice = $voice ?: $this->ttsVoice;

        // Storyboard AI settings
        $storyboardProvider = Setting::get('whiteboard_storyboard_provider', 'gemini');
        $storyboardOpenaiModel = Setting::get('whiteboard_storyboard_openai_model', 'gpt-4o');
        $storyboardOpenaiKey = Setting::get('whiteboard_storyboard_openai_key', '');
        $storyboardDeepseekModel = Setting::get('whiteboard_storyboard_deepseek_model', 'deepseek-chat');
        $storyboardDeepseekKey = Setting::get('whiteboard_storyboard_deepseek_key', '');

        // Image generation settings
        $imageEnabled = Setting::get('whiteboard_image_generation_enabled', '0') === '1';
        $imageProvider = Setting::get('whiteboard_image_provider', 'gemini_imagen');
        $imageApiKey = Setting::get('whiteboard_image_api_key', '');

        $response = Http::timeout(30)
            ->withOptions(['verify' => false])
            ->post("{$this->serverUrl}/generate", [
                'topic' => $topic,
                'title' => $title,
                'voice' => $ttsVoice,
                'rate' => $this->ttsRate,
                'mode' => $mode,
                'language' => $language,
                'laravel_job_id' => $laravelJobId,
                'gemini_api_key' => $this->geminiApiKey,
                'gemini_model' => $this->geminiModel,
                // Storyboard AI settings
                'storyboard_provider' => $storyboardProvider,
                'storyboard_openai_model' => $storyboardOpenaiModel,
                'storyboard_openai_key' => $storyboardOpenaiKey ?: null,
                'storyboard_deepseek_model' => $storyboardDeepseekModel,
                'storyboard_deepseek_key' => $storyboardDeepseekKey ?: null,
                // Image generation settings
                'image_generation_enabled' => $imageEnabled,
                'image_provider' => $imageProvider,
                'image_api_key' => $imageApiKey ?: null,
            ]);

        if (!$response->successful()) {
            throw new Exception('Manim server error: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Poll video generation status from Python server
     */
    public function getStatus(string $jobId): array
    {
        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->get("{$this->serverUrl}/status/{$jobId}");

            if (!$response->successful()) {
                Log::warning('Failed to get status from Manim server', [
                    'job_id' => $jobId,
                    'http_code' => $response->status(),
                ]);
                return ['status' => 'unknown', 'error' => 'HTTP ' . $response->status()];
            }

            return $response->json('data') ?? [];
        } catch (Exception $e) {
            Log::warning('Exception getting status from Manim server', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);
            return ['status' => 'unknown', 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the video download URL from Python server
     */
    public function getVideoUrl(string $jobId): string
    {
        return "{$this->serverUrl}/video/{$jobId}";
    }

    /**
     * Get the thumbnail URL from Python server
     */
    public function getThumbnailUrl(string $jobId): string
    {
        return "{$this->serverUrl}/thumbnail/{$jobId}";
    }

    /**
     * Download the video from Python server and save locally
     */
    public function downloadVideo(string $jobId, string $savePath): bool
    {
        try {
            $response = Http::timeout(120)
                ->withOptions(['verify' => false, 'stream' => true])
                ->get("{$this->serverUrl}/video/{$jobId}");

            if (!$response->successful()) {
                return false;
            }

            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($savePath, $response->body());
            return file_exists($savePath) && filesize($savePath) > 0;
        } catch (Exception $e) {
            Log::error('Failed to download video from Manim server', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Download thumbnail from Python server
     */
    public function downloadThumbnail(string $jobId, string $savePath): bool
    {
        try {
            $response = Http::timeout(30)
                ->withOptions(['verify' => false])
                ->get("{$this->serverUrl}/thumbnail/{$jobId}");

            if (!$response->successful()) {
                return false;
            }

            file_put_contents($savePath, $response->body());
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete a job from the Python server
     */
    public function deleteJob(string $jobId): bool
    {
        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->delete("{$this->serverUrl}/delete/{$jobId}");
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get available TTS voices from Python server
     */
    public function getVoices(): array
    {
        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->get("{$this->serverUrl}/voices");
            return $response->json('voices') ?? [];
        } catch (Exception $e) {
            return [];
        }
    }
}

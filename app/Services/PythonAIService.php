<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PythonAIService - Fast bridge to Python FastAPI AI microservice.
 *
 * Architecture: Laravel → HTTP → Python FastAPI → Gemini → back
 * Caching: Laravel-side cache (file/Redis) + Python-side in-memory cache (double layer)
 */
class PythonAIService
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;
    private int $cacheTtl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.python_ai.url', 'http://127.0.0.1:8100'), '/');
        $this->apiKey  = config('services.python_ai.api_key', 'mindory-ai-secret-2026');
        $this->timeout = config('services.python_ai.timeout', 15);
        $this->cacheTtl = config('services.python_ai.cache_ttl', 3600);
    }

    /**
     * Send text to AI, get answer back. Cached.
     */
    public function ask(string $text, ?string $systemPrompt = null, float $temperature = 0.7, int $maxTokens = 2048): array
    {
        $cacheKey = 'ai:' . sha1($text . '||' . ($systemPrompt ?? ''));

        // Check Laravel cache first (fastest)
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return [
                'success' => true,
                'answer'  => $cached,
                'cached'  => true,
                'source'  => 'laravel_cache',
            ];
        }

        // Call Python AI service
        try {
            $payload = [
                'text'        => $text,
                'temperature' => $temperature,
                'max_tokens'  => $maxTokens,
            ];
            if ($systemPrompt) {
                $payload['system_prompt'] = $systemPrompt;
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/ai", $payload);

            if ($response->failed()) {
                Log::error('[PythonAI] Request failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return [
                    'success' => false,
                    'answer'  => '',
                    'error'   => 'AI service returned ' . $response->status(),
                ];
            }

            $data = $response->json();
            $answer = $data['answer'] ?? '';

            // Store in Laravel cache
            if ($answer) {
                Cache::put($cacheKey, $answer, $this->cacheTtl);
            }

            return [
                'success'    => true,
                'answer'     => $answer,
                'cached'     => $data['cached'] ?? false,
                'source'     => ($data['cached'] ?? false) ? 'python_cache' : 'ai_model',
                'model'      => $data['model'] ?? '',
                'latency_ms' => $data['latency_ms'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error('[PythonAI] Exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'answer'  => '',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Batch: send multiple texts at once.
     */
    public function askBatch(array $texts, ?string $systemPrompt = null): array
    {
        $requests = [];
        foreach ($texts as $text) {
            $req = ['text' => $text];
            if ($systemPrompt) {
                $req['system_prompt'] = $systemPrompt;
            }
            $requests[] = $req;
        }

        try {
            $response = Http::timeout($this->timeout * 2)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/ai/batch", $requests);

            if ($response->failed()) {
                return ['success' => false, 'error' => 'Batch request failed'];
            }

            return [
                'success' => true,
                'results' => $response->json('results', []),
            ];

        } catch (\Exception $e) {
            Log::error('[PythonAI] Batch exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Health check on the Python service.
     */
    public function health(): array
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/health");
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'unreachable', 'error' => $e->getMessage()];
        }
    }

    /**
     * Clear Python-side cache.
     */
    public function clearCache(): array
    {
        try {
            $response = Http::timeout(3)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->delete("{$this->baseUrl}/cache");
            return $response->json();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

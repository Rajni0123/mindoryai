<?php

namespace App\Services\AIProviders;

use App\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekProvider implements AIProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.deepseek.com/v1/chat/completions';

    public function __construct(?string $model = null)
    {
        $this->apiKey = config('ai.deepseek.api_key');
        $this->model = $model ?? config('ai.deepseek.model', 'deepseek-chat');
    }

    public function chat(array $messages, array $options = []): string
    {
        try {
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(config('ai.deepseek.timeout', 30));

            // Disable SSL verification in local development
            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 2000,
            ]);

            if (!$response->successful()) {
                throw new \Exception('DeepSeek API Error: ' . $response->body());
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? '';

        } catch (\Exception $e) {
            Log::error('DeepSeek API Error', [
                'provider' => 'DeepSeek',
                'model' => $this->model,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('DeepSeek API Error: ' . $e->getMessage());
        }
    }

    public function getName(): string
    {
        return 'DeepSeek';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}

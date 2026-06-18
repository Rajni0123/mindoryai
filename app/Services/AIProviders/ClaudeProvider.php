<?php

namespace App\Services\AIProviders;

use App\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeProvider implements AIProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct(?string $model = null)
    {
        $this->apiKey = config('ai.claude.api_key');
        $this->model = $model ?? config('ai.claude.model', 'claude-3-5-sonnet-20241022');
    }

    public function chat(array $messages, array $options = []): string
    {
        try {
            // Convert OpenAI format messages to Claude format
            $claudeMessages = $this->convertMessages($messages);

            $http = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(config('ai.claude.timeout', 30));

            // Disable SSL verification in local development
            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => $claudeMessages['messages'],
                'system' => $claudeMessages['system'] ?? null,
                'max_tokens' => $options['max_tokens'] ?? 2000,
                'temperature' => $options['temperature'] ?? 0.7,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Claude API Error: ' . $response->body());
            }

            $data = $response->json();
            return $data['content'][0]['text'] ?? '';

        } catch (\Exception $e) {
            Log::error('Claude API Error', [
                'provider' => 'Claude',
                'model' => $this->model,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Claude API Error: ' . $e->getMessage());
        }
    }

    private function convertMessages(array $messages): array
    {
        $system = null;
        $claudeMessages = [];

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system = $message['content'];
            } else {
                $claudeMessages[] = [
                    'role' => $message['role'],
                    'content' => $message['content']
                ];
            }
        }

        return [
            'system' => $system,
            'messages' => $claudeMessages
        ];
    }

    public function getName(): string
    {
        return 'Claude';
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

<?php

namespace App\Services\AIProviders;

use App\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements AIProviderInterface
{
    private $client;
    private string $model;

    public function __construct(?string $model = null)
    {
        $this->model = $model ?? config('ai.openai.model', 'gpt-4o');

        // Initialize OpenAI client with SSL fix for Windows
        $this->client = \OpenAI::factory()
            ->withApiKey(config('ai.openai.api_key'))
            ->withHttpClient(new \GuzzleHttp\Client([
                'verify' => config('ai.openai.ssl_verify', false),
                'timeout' => config('ai.openai.timeout', 30),
            ]))
            ->make();
    }

    public function chat(array $messages, array $options = []): string
    {
        try {
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 2000,
            ]);

            return $response->choices[0]->message->content;

        } catch (\Exception $e) {
            Log::error('OpenAI API Error', [
                'provider' => 'OpenAI',
                'model' => $this->model,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('OpenAI API Error: ' . $e->getMessage());
        }
    }

    public function getName(): string
    {
        return 'OpenAI';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function isAvailable(): bool
    {
        return !empty(config('ai.openai.api_key'));
    }
}

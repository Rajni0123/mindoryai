<?php

namespace App\Services;

use App\Contracts\AIProviderInterface;
use App\Services\AIProviders\OpenAIProvider;
use App\Services\AIProviders\ClaudeProvider;
use App\Services\AIProviders\DeepSeekProvider;
use App\Services\AIProviders\GrokProvider;

class AIProviderFactory
{
    /**
     * Create AI provider instance based on provider name
     *
     * @param string $providerName Provider name (openai, claude, deepseek, grok)
     * @param string|null $model Optional model override
     * @return AIProviderInterface
     * @throws \Exception
     */
    public static function make(string $providerName, ?string $model = null): AIProviderInterface
    {
        $provider = match (strtolower($providerName)) {
            'openai', 'gpt', 'gpt-4', 'gpt-4o' => new OpenAIProvider($model),
            'claude', 'anthropic' => new ClaudeProvider($model),
            'deepseek' => new DeepSeekProvider($model),
            'grok', 'x.ai' => new GrokProvider($model),
            default => throw new \Exception("Unsupported AI provider: {$providerName}"),
        };

        if (!$provider->isAvailable()) {
            throw new \Exception("AI provider '{$providerName}' is not configured. Please add API key to .env file.");
        }

        return $provider;
    }

    /**
     * Get list of available/configured providers
     *
     * @return array
     */
    public static function getAvailableProviders(): array
    {
        $providers = [];

        $allProviders = [
            'openai' => OpenAIProvider::class,
            'claude' => ClaudeProvider::class,
            'deepseek' => DeepSeekProvider::class,
            'grok' => GrokProvider::class,
        ];

        foreach ($allProviders as $name => $class) {
            try {
                $provider = new $class();
                if ($provider->isAvailable()) {
                    $providers[] = [
                        'name' => $name,
                        'display_name' => $provider->getName(),
                        'model' => $provider->getModel(),
                    ];
                }
            } catch (\Exception $e) {
                // Skip unavailable providers
                continue;
            }
        }

        return $providers;
    }

    /**
     * Get default provider name from config
     *
     * @return string
     */
    public static function getDefaultProvider(): string
    {
        return config('ai.default_provider', 'openai');
    }
}

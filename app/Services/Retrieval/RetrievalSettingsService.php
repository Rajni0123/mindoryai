<?php

namespace App\Services\Retrieval;

use App\Models\FrontendConfig;

/**
 * Reads hybrid retrieval settings from admin FrontendConfig with config() fallbacks.
 */
class RetrievalSettingsService
{
    public function isHybridEnabled(): bool
    {
        return $this->flag('retrieval.hybrid_enabled', 'retrieval.enabled', false);
    }

    public function isExistingRagEnabled(): bool
    {
        return $this->flag('retrieval.existing_rag', 'retrieval.features.existing_rag', true);
    }

    public function isExaEnabled(): bool
    {
        return $this->flag('retrieval.exa_enabled', 'retrieval.features.exa_search');
    }

    public function isHybridModeEnabled(): bool
    {
        return $this->flag('retrieval.hybrid_mode', 'retrieval.features.hybrid_mode');
    }

    public function isRedisCacheEnabled(): bool
    {
        return $this->flag('retrieval.redis_cache', 'retrieval.features.redis_cache', true);
    }

    public function isTemporaryPdfEnabled(): bool
    {
        return $this->flag('retrieval.temporary_pdf', 'retrieval.features.temporary_pdf', true);
    }

    public function isAiQuizFallbackEnabled(): bool
    {
        return $this->flag('retrieval.ai_quiz_fallback', 'retrieval.features.ai_quiz_fallback', true);
    }

    public function isWebSearchEnabled(): bool
    {
        return $this->flag('retrieval.web_search', 'retrieval.features.web_search');
    }

    /**
     * @return array<string, int>
     */
    public function providerPriority(): array
    {
        $json = FrontendConfig::getValue('retrieval.provider_priority', '');

        if ($json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return config('retrieval.provider_priority', []);
    }

    /**
     * @return list<string>
     */
    public function quizPriority(): array
    {
        $json = FrontendConfig::getValue('retrieval.quiz_priority', '');

        if ($json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return array_values($decoded);
            }
        }

        return config('retrieval.quiz_priority', []);
    }

    private function flag(string $configKey, string $fallbackConfigPath, ?bool $default = null): bool
    {
        $value = FrontendConfig::getValue($configKey, null);

        if ($value !== null && $value !== '') {
            return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
        }

        if ($default !== null) {
            return (bool) config($fallbackConfigPath, $default);
        }

        return (bool) config($fallbackConfigPath, false);
    }
}

<?php

namespace App\Services\Retrieval;

use App\Services\Retrieval\DTO\IntentResult;

/**
 * Maps classified intent to retrieval strategy validation.
 */
class RetrievalRouter
{
    public function __construct(
        protected RetrievalSettingsService $settings,
    ) {}

    public function resolveRoute(IntentResult $intent): string
    {
        if (! $this->settings->isHybridEnabled()) {
            return $this->settings->isExistingRagEnabled() ? 'rag_only' : 'none';
        }

        return match ($intent->strategy) {
            'exa_only' => $this->settings->isExaEnabled() ? 'exa_only' : 'rag_only',
            'hybrid' => ($this->settings->isHybridModeEnabled() && $this->settings->isExaEnabled())
                ? 'hybrid'
                : 'rag_only',
            default => 'rag_only',
        };
    }
}

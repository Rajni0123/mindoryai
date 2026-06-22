<?php

namespace App\Services\Retrieval;

use App\Contracts\Retrieval\RetrievalProviderInterface;

/**
 * Registry for modular RAG / search providers.
 */
class ProviderRegistry
{
    /** @var array<string, RetrievalProviderInterface> */
    protected array $providers = [];

    public function register(RetrievalProviderInterface $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    public function get(string $key): ?RetrievalProviderInterface
    {
        return $this->providers[$key] ?? null;
    }

    /**
     * @return list<RetrievalProviderInterface>
     */
    public function enabledOrdered(RetrievalSettingsService $settings): array
    {
        $priority = $settings->providerPriority();
        $enabled = [];

        foreach ($this->providers as $provider) {
            if ($provider->isEnabled()) {
                $enabled[] = $provider;
            }
        }

        usort($enabled, function (RetrievalProviderInterface $a, RetrievalProviderInterface $b) use ($priority) {
            $pa = $priority[$a->key()] ?? 99;
            $pb = $priority[$b->key()] ?? 99;

            return $pa <=> $pb;
        });

        return $enabled;
    }

    /**
     * @return list<RetrievalProviderInterface>
     */
    public function ragProviders(RetrievalSettingsService $settings): array
    {
        return array_values(array_filter(
            $this->enabledOrdered($settings),
            fn (RetrievalProviderInterface $p) => $p->key() !== 'exa'
        ));
    }

    /**
     * @return array<string, RetrievalProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }
}

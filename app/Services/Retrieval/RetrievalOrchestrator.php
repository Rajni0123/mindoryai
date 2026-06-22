<?php

namespace App\Services\Retrieval;

use App\Contracts\Retrieval\IntentClassifierInterface;
use App\Services\Retrieval\DTO\RetrievalQuery;
use App\Services\Retrieval\DTO\RetrievalResult;
use Illuminate\Support\Facades\Log;

/**
 * Central orchestrator: intent → route → retrieve → merge.
 */
class RetrievalOrchestrator
{
    public function __construct(
        protected IntentClassifierInterface $intentClassifier,
        protected RetrievalRouter $router,
        protected ProviderRegistry $registry,
        protected HybridContextMerger $merger,
        protected RetrievalSettingsService $settings,
        protected ExaSearchService $exaSearchService,
    ) {}

    public function retrieve(RetrievalQuery $query): RetrievalResult
    {
        if (! $this->settings->isHybridEnabled()) {
            return $this->legacyRagOnly($query);
        }

        $forcedRoute = $query->metadata['force_route'] ?? null;
        if ($forcedRoute === 'exa_only' && $this->settings->isExaEnabled()) {
            Log::info('Hybrid retrieval route', [
                'intent' => 'web_search',
                'strategy' => 'exa_only',
                'route' => 'exa_only',
                'feature' => $query->feature,
                'forced' => true,
            ]);

            return $this->exaSearchService->search($query);
        }

        $intent = $this->intentClassifier->classify($query);
        $route = $this->router->resolveRoute($intent);

        Log::info('Hybrid retrieval route', [
            'intent' => $intent->intent,
            'strategy' => $intent->strategy,
            'route' => $route,
            'feature' => $query->feature,
        ]);

        return match ($route) {
            'exa_only' => $this->exaSearchService->search($this->withIntent($query, $intent)),
            'hybrid' => $this->retrieveHybrid($query, $intent),
            'rag_only' => $this->retrieveFromRagProviders($query),
            default => RetrievalResult::empty('none', 'Retrieval disabled.'),
        };
    }

    protected function withIntent(RetrievalQuery $query, \App\Services\Retrieval\DTO\IntentResult $intent): RetrievalQuery
    {
        return new RetrievalQuery(
            question: $query->question,
            classLevel: $query->classLevel,
            subject: $query->subject,
            exam: $query->exam,
            topic: $query->topic,
            userId: $query->userId,
            feature: $query->feature,
            metadata: array_merge($query->metadata, ['intent' => $intent->intent]),
        );
    }

    protected function legacyRagOnly(RetrievalQuery $query): RetrievalResult
    {
        $ncert = $this->registry->get('ncert');
        if ($ncert && $ncert->isEnabled()) {
            return $ncert->retrieve($query);
        }

        return RetrievalResult::empty('ncert', 'RAG not available.');
    }

    protected function retrieveHybrid(RetrievalQuery $query, \App\Services\Retrieval\DTO\IntentResult $intent): RetrievalResult
    {
        $rag = $this->retrieveFromRagProviders($query);
        $exa = $this->exaSearchService->search($this->withIntent($query, $intent));

        return $this->merger->merge([$rag, $exa]);
    }

    protected function retrieveFromRagProviders(RetrievalQuery $query): RetrievalResult
    {
        $providers = $this->registry->ragProviders($this->settings);
        $results = [];

        foreach ($providers as $provider) {
            $result = $provider->retrieve($query);
            if ($result->success) {
                $results[] = $result;
            }
        }

        if ($results === []) {
            return RetrievalResult::empty('rag', 'No RAG context found.');
        }

        return $this->merger->merge($results);
    }
}

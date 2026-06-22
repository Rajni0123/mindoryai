<?php

namespace App\Services\Retrieval\Providers;

use App\Services\RAGService;
use App\Services\Retrieval\DTO\RetrievalQuery;
use App\Services\Retrieval\DTO\RetrievalResult;
use App\Services\Retrieval\RetrievalSettingsService;

/**
 * Wraps the legacy RAGService without changing its behavior.
 */
class NcertRagProvider extends AbstractRagProvider
{
    public function __construct(
        RetrievalSettingsService $settings,
        protected RAGService $ragService,
    ) {
        parent::__construct($settings);
    }

    public function key(): string
    {
        return 'ncert';
    }

    public function label(): string
    {
        return 'NCERT RAG';
    }

    protected function fetch(RetrievalQuery $query): RetrievalResult
    {
        $rag = $this->ragService->getRelevantContext(
            $query->question,
            $query->classLevel,
            $query->subject,
            (int) config('retrieval.top_k', 5),
            (float) config('retrieval.similarity_threshold', 0.6),
        );

        return RetrievalResult::fromLegacyRag($rag, $this->key());
    }
}

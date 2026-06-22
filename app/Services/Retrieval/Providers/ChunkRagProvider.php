<?php

namespace App\Services\Retrieval\Providers;

use App\Services\RAGService;
use App\Services\Retrieval\DTO\RetrievalQuery;
use App\Services\Retrieval\DTO\RetrievalResult;
use App\Services\Retrieval\RetrievalSettingsService;

class ChunkRagProvider extends AbstractRagProvider
{
    public function __construct(
        RetrievalSettingsService $settings,
        protected RAGService $ragService,
        protected string $providerKey,
        protected string $providerLabel,
    ) {
        parent::__construct($settings);
    }

    public function key(): string
    {
        return $this->providerKey;
    }

    public function label(): string
    {
        return $this->providerLabel;
    }

    protected function fetch(RetrievalQuery $query): RetrievalResult
    {
        $rag = $this->ragService->getRelevantContextForProvider(
            $query->question,
            $this->providerKey,
            $query->classLevel,
            $query->subject,
            (int) config('retrieval.top_k', 5),
            (float) config('retrieval.similarity_threshold', 0.6),
        );

        return RetrievalResult::fromLegacyRag($rag, $this->key());
    }
}

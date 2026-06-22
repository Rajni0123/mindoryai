<?php

namespace App\Services\Retrieval\Providers;

use App\Contracts\Retrieval\RetrievalProviderInterface;
use App\Services\Retrieval\DTO\RetrievalQuery;
use App\Services\Retrieval\DTO\RetrievalResult;
use App\Services\Retrieval\ExaSearchService;
use App\Services\Retrieval\RetrievalSettingsService;

class ExaSearchProvider implements RetrievalProviderInterface
{
    public function __construct(
        protected RetrievalSettingsService $settings,
        protected ExaSearchService $exaSearchService,
    ) {}

    public function key(): string
    {
        return 'exa';
    }

    public function label(): string
    {
        return 'Exa Web Search';
    }

    public function isEnabled(): bool
    {
        return $this->settings->isExaEnabled();
    }

    public function retrieve(RetrievalQuery $query): RetrievalResult
    {
        if (! $this->isEnabled()) {
            return RetrievalResult::empty($this->key(), 'Exa disabled.');
        }

        return $this->exaSearchService->search($query);
    }
}

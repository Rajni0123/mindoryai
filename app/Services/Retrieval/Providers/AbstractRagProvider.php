<?php

namespace App\Services\Retrieval\Providers;

use App\Contracts\Retrieval\RetrievalProviderInterface;
use App\Services\Retrieval\DTO\RetrievalQuery;
use App\Services\Retrieval\DTO\RetrievalResult;
use App\Services\Retrieval\RetrievalSettingsService;

abstract class AbstractRagProvider implements RetrievalProviderInterface
{
    public function __construct(
        protected RetrievalSettingsService $settings,
    ) {}

    abstract public function key(): string;

    abstract public function label(): string;

    public function isEnabled(): bool
    {
        return $this->settings->isExistingRagEnabled();
    }

    abstract protected function fetch(RetrievalQuery $query): RetrievalResult;

    public function retrieve(RetrievalQuery $query): RetrievalResult
    {
        if (! $this->isEnabled()) {
            return RetrievalResult::empty($this->key(), 'Provider disabled.');
        }

        return $this->fetch($query);
    }
}

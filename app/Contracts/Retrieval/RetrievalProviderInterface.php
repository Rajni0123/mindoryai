<?php

namespace App\Contracts\Retrieval;

use App\Services\Retrieval\DTO\RetrievalQuery;
use App\Services\Retrieval\DTO\RetrievalResult;

interface RetrievalProviderInterface
{
    /**
     * Unique provider key (e.g. ncert, pyq, exa).
     */
    public function key(): string;

    /**
     * Human-readable label for admin UI.
     */
    public function label(): string;

    /**
     * Whether this provider is enabled via settings.
     */
    public function isEnabled(): bool;

    /**
     * Retrieve context for a query.
     */
    public function retrieve(RetrievalQuery $query): RetrievalResult;
}

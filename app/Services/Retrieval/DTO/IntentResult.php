<?php

namespace App\Services\Retrieval\DTO;

final class IntentResult
{
    public function __construct(
        public readonly string $intent,
        public readonly string $strategy, // rag_only | exa_only | hybrid
        public readonly float $confidence = 1.0,
        public readonly array $signals = [],
    ) {}
}

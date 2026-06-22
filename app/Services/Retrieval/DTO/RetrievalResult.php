<?php

namespace App\Services\Retrieval\DTO;

final class RetrievalResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $context = '',
        public readonly array $sources = [],
        public readonly string $provider = '',
        public readonly array $chunks = [],
        public readonly ?string $message = null,
    ) {}

    public static function empty(string $provider = '', ?string $message = null): self
    {
        return new self(false, '', [], $provider, [], $message);
    }

    public static function fromLegacyRag(array $ragResult, string $provider = 'ncert'): self
    {
        return new self(
            success: (bool) ($ragResult['success'] ?? false),
            context: (string) ($ragResult['context'] ?? ''),
            sources: (array) ($ragResult['sources'] ?? []),
            provider: $provider,
            message: $ragResult['message'] ?? null,
        );
    }
}

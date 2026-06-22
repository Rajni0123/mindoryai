<?php

namespace App\Services\Retrieval\DTO;

final class RetrievalQuery
{
    public function __construct(
        public readonly string $question,
        public readonly ?string $classLevel = null,
        public readonly ?string $subject = null,
        public readonly ?string $exam = null,
        public readonly ?string $topic = null,
        public readonly ?int $userId = null,
        public readonly string $feature = 'chat',
        public readonly array $metadata = [],
    ) {}

    public function normalized(): string
    {
        return mb_strtolower(trim($this->question));
    }
}

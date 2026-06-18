<?php

namespace App\Services\Cache;

class CacheDecision
{
    public function __construct(
        public readonly bool $allowed,
        public readonly string $reason,
        public readonly string $layer = ''
    ) {}

    public static function allow(string $reason): self
    {
        return new self(true, $reason);
    }

    public static function block(string $reason, string $layer = ''): self
    {
        return new self(false, $reason, $layer);
    }
}

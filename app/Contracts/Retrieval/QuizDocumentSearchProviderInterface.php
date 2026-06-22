<?php

namespace App\Contracts\Retrieval;

interface QuizDocumentSearchProviderInterface
{
  public function key(): string;

  public function label(): string;

  public function isAvailable(): bool;

  /**
   * @return list<array<string, mixed>>
   */
  public function search(string $topic, ?string $subject, int $limit): array;
}

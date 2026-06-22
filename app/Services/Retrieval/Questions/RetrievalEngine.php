<?php

namespace App\Services\Retrieval\Questions;

use App\Services\Retrieval\Questions\Search\QuizSearchRouter;

class RetrievalEngine
{
  public function __construct(
    protected QuizSearchRouter $searchRouter,
  ) {}

  /**
   * @return list<array<string, mixed>>
   */
  public function retrieveQuizDocuments(string $topic, ?string $subject, int $limit): array
  {
    return $this->searchRouter->findBestDocuments($topic, $subject, $limit);
  }
}

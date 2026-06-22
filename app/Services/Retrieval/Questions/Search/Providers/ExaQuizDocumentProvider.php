<?php

namespace App\Services\Retrieval\Questions\Search\Providers;

use App\Contracts\Retrieval\QuizDocumentSearchProviderInterface;
use App\Services\Retrieval\ExaSearchService;
use App\Services\Retrieval\Questions\Support\QuizDocumentSupport;
use App\Services\Retrieval\RetrievalSettingsService;

class ExaQuizDocumentProvider implements QuizDocumentSearchProviderInterface
{
  public function __construct(
    protected ExaSearchService $exaSearchService,
    protected RetrievalSettingsService $settings,
  ) {}

  public function key(): string
  {
    return 'exa';
  }

  public function label(): string
  {
    return 'Exa Search';
  }

  public function isAvailable(): bool
  {
    if (! $this->settings->isExaEnabled()) {
      return false;
    }

    $apiKey = \App\Models\FrontendConfig::getValue('retrieval.exa_api_key')
      ?: config('retrieval.exa.api_key');

    return ! empty($apiKey);
  }

  public function search(string $topic, ?string $subject, int $limit): array
  {
    $results = $this->exaSearchService->searchQuizDocuments($topic, $subject, $limit);

    return array_map(
      fn (array $doc) => array_merge($doc, ['search_provider' => $this->key()]),
      $results
    );
  }
}

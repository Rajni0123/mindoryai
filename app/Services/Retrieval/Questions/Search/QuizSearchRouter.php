<?php

namespace App\Services\Retrieval\Questions\Search;

use App\Contracts\Retrieval\QuizDocumentSearchProviderInterface;
use App\Services\Retrieval\Questions\Support\QuizDocumentSupport;
use App\Services\Retrieval\RetrievalCacheService;
use Illuminate\Support\Facades\Log;

/**
 * User → Search Router → providers → best PDF → extraction pipeline.
 */
class QuizSearchRouter
{
  /**
   * @param  list<QuizDocumentSearchProviderInterface>  $providers
   */
  public function __construct(
    protected iterable $providers,
    protected RetrievalCacheService $cache,
  ) {}

  /**
   * @return list<array<string, mixed>>
   */
  public function findBestDocuments(string $topic, ?string $subject, int $limit): array
  {
    $cacheKey = implode('|', [
      'quiz-search-router',
      mb_strtolower($topic),
      mb_strtolower((string) $subject),
      $limit,
    ]);

    return $this->cache->remember('search', $cacheKey, function () use ($topic, $subject, $limit) {
      return $this->searchAllProviders($topic, $subject, $limit);
    }, (int) config('retrieval.cache.ttl.search', 3600));
  }

  /**
   * @return list<array<string, mixed>>
   */
  protected function searchAllProviders(string $topic, ?string $subject, int $limit): array
  {
    $priority = config('retrieval.quiz_search.provider_priority', []);
    $ordered = $this->orderProviders($priority);
    $documents = [];

    foreach ($ordered as $provider) {
      if (! $provider->isAvailable()) {
        continue;
      }

      try {
        $results = $provider->search($topic, $subject, max($limit, 8));
        if ($results !== []) {
          Log::info('QuizSearchRouter provider hit', [
            'provider' => $provider->key(),
            'count' => count($results),
          ]);
          $documents = array_merge($documents, $results);
        }
      } catch (\Throwable $e) {
        Log::warning('QuizSearchRouter provider failed', [
          'provider' => $provider->key(),
          'error' => $e->getMessage(),
        ]);
      }
    }

    return QuizDocumentSupport::dedupeAndRank($documents, $topic, $subject, $limit);
  }

  /**
   * @param  list<string>  $priority
   * @return list<QuizDocumentSearchProviderInterface>
   */
  protected function orderProviders(array $priority): array
  {
    $byKey = [];
    foreach ($this->providers as $provider) {
      $byKey[$provider->key()] = $provider;
    }

    $ordered = [];
    foreach ($priority as $key) {
      if (isset($byKey[$key])) {
        $ordered[] = $byKey[$key];
        unset($byKey[$key]);
      }
    }

    return array_merge($ordered, array_values($byKey));
  }
}

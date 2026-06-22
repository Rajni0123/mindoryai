<?php

namespace App\Services\Retrieval\Questions\Search\Providers;

use App\Contracts\Retrieval\QuizDocumentSearchProviderInterface;
use App\Services\Retrieval\Questions\Support\QuizDocumentSupport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BraveSearchProvider implements QuizDocumentSearchProviderInterface
{
  public function key(): string
  {
    return 'brave';
  }

  public function label(): string
  {
    return 'Brave Search';
  }

  public function isAvailable(): bool
  {
    return ! empty(config('retrieval.quiz_search.brave.api_key'));
  }

  public function search(string $topic, ?string $subject, int $limit): array
  {
    $query = QuizDocumentSupport::buildQuizQuery($topic, $subject);

    try {
      $response = Http::withHeaders([
        'Accept' => 'application/json',
        'X-Subscription-Token' => (string) config('retrieval.quiz_search.brave.api_key'),
      ])
        ->timeout(20)
        ->retry(1, 300)
        ->get('https://api.search.brave.com/res/v1/web/search', [
          'q' => $query,
          'count' => min(max($limit, 5), 20),
          'search_lang' => 'en',
        ]);

      if (! $response->successful()) {
        return [];
      }

      $results = $response->json('web.results') ?? [];

      return collect($results)
        ->map(function (array $item) {
          $url = (string) ($item['url'] ?? '');
          $title = (string) ($item['title'] ?? '');

          return QuizDocumentSupport::normalizeDocument($url, $title, $this->key());
        })
        ->filter(fn (array $doc) => QuizDocumentSupport::isQuestionDocument($doc['url'], $doc['title']))
        ->values()
        ->all();
    } catch (\Throwable $e) {
      Log::warning('Brave Search quiz search failed', ['error' => $e->getMessage()]);

      return [];
    }
  }
}

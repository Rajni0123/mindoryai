<?php

namespace App\Services\Retrieval\Questions\Search\Providers;

use App\Contracts\Retrieval\QuizDocumentSearchProviderInterface;
use App\Services\Retrieval\Questions\Support\QuizDocumentSupport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BingSearchProvider implements QuizDocumentSearchProviderInterface
{
  public function key(): string
  {
    return 'bing';
  }

  public function label(): string
  {
    return 'Bing Search';
  }

  public function isAvailable(): bool
  {
    return ! empty(config('retrieval.quiz_search.bing.api_key'));
  }

  public function search(string $topic, ?string $subject, int $limit): array
  {
    $query = QuizDocumentSupport::buildQuizQuery($topic, $subject);

    try {
      $response = Http::withHeaders([
        'Ocp-Apim-Subscription-Key' => (string) config('retrieval.quiz_search.bing.api_key'),
      ])
        ->timeout(20)
        ->retry(1, 300)
        ->get('https://api.bing.microsoft.com/v7.0/search', [
          'q' => $query,
          'count' => min(max($limit, 5), 20),
          'responseFilter' => 'Webpages',
        ]);

      if (! $response->successful()) {
        return [];
      }

      $results = $response->json('webPages.value') ?? [];

      return collect($results)
        ->map(function (array $item) {
          $url = (string) ($item['url'] ?? '');
          $title = (string) ($item['name'] ?? '');

          return QuizDocumentSupport::normalizeDocument($url, $title, $this->key());
        })
        ->filter(fn (array $doc) => QuizDocumentSupport::isQuestionDocument($doc['url'], $doc['title']))
        ->values()
        ->all();
    } catch (\Throwable $e) {
      Log::warning('Bing Search quiz search failed', ['error' => $e->getMessage()]);

      return [];
    }
  }
}

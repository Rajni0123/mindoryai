<?php

namespace App\Services\Retrieval\Questions\Search\Providers;

use App\Contracts\Retrieval\QuizDocumentSearchProviderInterface;
use App\Services\Retrieval\Questions\Support\QuizDocumentSupport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCustomSearchProvider implements QuizDocumentSearchProviderInterface
{
  public function key(): string
  {
    return 'google';
  }

  public function label(): string
  {
    return 'Google Custom Search';
  }

  public function isAvailable(): bool
  {
    return ! empty(config('retrieval.quiz_search.google.api_key'))
      && ! empty(config('retrieval.quiz_search.google.cx'));
  }

  public function search(string $topic, ?string $subject, int $limit): array
  {
    $query = QuizDocumentSupport::buildQuizQuery($topic, $subject);

    try {
      $response = Http::timeout(20)
        ->retry(1, 300)
        ->get('https://www.googleapis.com/customsearch/v1', [
          'key' => config('retrieval.quiz_search.google.api_key'),
          'cx' => config('retrieval.quiz_search.google.cx'),
          'q' => $query,
          'num' => min(max($limit, 5), 10),
          'fileType' => 'pdf',
        ]);

      if (! $response->successful()) {
        return [];
      }

      $items = $response->json('items') ?? [];

      return collect($items)
        ->map(function (array $item) {
          $url = (string) ($item['link'] ?? '');
          $title = (string) ($item['title'] ?? '');

          return QuizDocumentSupport::normalizeDocument($url, $title, $this->key());
        })
        ->filter(fn (array $doc) => QuizDocumentSupport::isQuestionDocument($doc['url'], $doc['title']))
        ->values()
        ->all();
    } catch (\Throwable $e) {
      Log::warning('Google Custom Search quiz search failed', ['error' => $e->getMessage()]);

      return [];
    }
  }
}

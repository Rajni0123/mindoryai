<?php

namespace App\Services\Retrieval\Questions\Search\Providers;

use App\Contracts\Retrieval\QuizDocumentSearchProviderInterface;
use App\Services\Retrieval\Questions\Support\QuizDocumentSupport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Restricts search to official exam/board domains only.
 */
class OfficialWebsiteSearchProvider implements QuizDocumentSearchProviderInterface
{
  public function key(): string
  {
    return 'official';
  }

  public function label(): string
  {
    return 'Official Websites';
  }

  public function isAvailable(): bool
  {
    return QuizDocumentSupport::officialDomains() !== [];
  }

  public function search(string $topic, ?string $subject, int $limit): array
  {
    $domains = QuizDocumentSupport::officialDomains();
    if ($domains === []) {
      return [];
    }

    $siteFilter = collect($domains)
      ->take(6)
      ->map(fn (string $domain) => 'site:' . $domain)
      ->implode(' OR ');

    $query = trim($siteFilter . ' ' . QuizDocumentSupport::buildQuizQuery($topic, $subject));

    if ($google = $this->searchViaGoogle($query, $limit)) {
      return $google;
    }

    if ($brave = $this->searchViaBrave($query, $limit)) {
      return $brave;
    }

    return $this->searchViaBing($query, $limit);
  }

  /**
   * @return list<array<string, mixed>>
   */
  protected function searchViaGoogle(string $query, int $limit): array
  {
    if (empty(config('retrieval.quiz_search.google.api_key')) || empty(config('retrieval.quiz_search.google.cx'))) {
      return [];
    }

    try {
      $response = Http::timeout(20)->get('https://www.googleapis.com/customsearch/v1', [
        'key' => config('retrieval.quiz_search.google.api_key'),
        'cx' => config('retrieval.quiz_search.google.cx'),
        'q' => $query,
        'num' => min(max($limit, 5), 10),
      ]);

      if (! $response->successful()) {
        return [];
      }

      return $this->mapResults($response->json('items') ?? [], 'link', 'title');
    } catch (\Throwable $e) {
      Log::warning('Official Google search failed', ['error' => $e->getMessage()]);

      return [];
    }
  }

  /**
   * @return list<array<string, mixed>>
   */
  protected function searchViaBrave(string $query, int $limit): array
  {
    if (empty(config('retrieval.quiz_search.brave.api_key'))) {
      return [];
    }

    try {
      $response = Http::withHeaders([
        'Accept' => 'application/json',
        'X-Subscription-Token' => (string) config('retrieval.quiz_search.brave.api_key'),
      ])->timeout(20)->get('https://api.search.brave.com/res/v1/web/search', [
        'q' => $query,
        'count' => min(max($limit, 5), 20),
      ]);

      if (! $response->successful()) {
        return [];
      }

      $items = collect($response->json('web.results') ?? [])
        ->map(fn (array $item) => ['link' => $item['url'] ?? '', 'title' => $item['title'] ?? ''])
        ->all();

      return $this->mapResults($items, 'link', 'title');
    } catch (\Throwable $e) {
      Log::warning('Official Brave search failed', ['error' => $e->getMessage()]);

      return [];
    }
  }

  /**
   * @return list<array<string, mixed>>
   */
  protected function searchViaBing(string $query, int $limit): array
  {
    if (empty(config('retrieval.quiz_search.bing.api_key'))) {
      return [];
    }

    try {
      $response = Http::withHeaders([
        'Ocp-Apim-Subscription-Key' => (string) config('retrieval.quiz_search.bing.api_key'),
      ])->timeout(20)->get('https://api.bing.microsoft.com/v7.0/search', [
        'q' => $query,
        'count' => min(max($limit, 5), 20),
      ]);

      if (! $response->successful()) {
        return [];
      }

      $items = collect($response->json('webPages.value') ?? [])
        ->map(fn (array $item) => ['link' => $item['url'] ?? '', 'title' => $item['name'] ?? ''])
        ->all();

      return $this->mapResults($items, 'link', 'title');
    } catch (\Throwable $e) {
      Log::warning('Official Bing search failed', ['error' => $e->getMessage()]);

      return [];
    }
  }

  /**
   * @param  list<array<string, mixed>>  $items
   * @return list<array<string, mixed>>
   */
  protected function mapResults(array $items, string $urlKey, string $titleKey): array
  {
    return collect($items)
      ->map(function (array $item) use ($urlKey, $titleKey) {
        $url = (string) ($item[$urlKey] ?? '');
        $title = (string) ($item[$titleKey] ?? '');
        $doc = QuizDocumentSupport::normalizeDocument($url, $title, $this->key());
        $doc['is_official'] = true;

        return $doc;
      })
      ->filter(fn (array $doc) => QuizDocumentSupport::isOfficialUrl($doc['url']))
      ->filter(fn (array $doc) => QuizDocumentSupport::isQuestionDocument($doc['url'], $doc['title']))
      ->values()
      ->all();
  }
}

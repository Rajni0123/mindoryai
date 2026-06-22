<?php

namespace App\Services\Retrieval\Questions\Support;

class QuizDocumentSupport
{
  /**
   * @return list<string>
   */
  public static function officialDomains(): array
  {
    $domains = config('retrieval.quiz_search.official_domains', []);

    return is_array($domains) ? array_values(array_filter($domains)) : [];
  }

  public static function buildQuizQuery(string $topic, ?string $subject, bool $pdfOnly = true): string
  {
    $parts = array_filter([
      $topic,
      $subject,
      'previous year paper sample paper question bank official exam mcq',
      $pdfOnly ? 'filetype:pdf' : null,
    ]);

    return trim(implode(' ', $parts));
  }

  /**
   * @return array<string, mixed>
   */
  public static function normalizeDocument(string $url, string $title, string $provider): array
  {
    $pdfName = $title !== '' ? $title : basename(parse_url($url, PHP_URL_PATH) ?: '');

    return [
      'url' => $url,
      'title' => $title,
      'pdf_name' => $pdfName,
      'exam' => self::inferExamFromTitle($title),
      'year' => self::inferYear($title . ' ' . $url),
      'search_provider' => $provider,
      'is_official' => self::isOfficialUrl($url),
    ];
  }

  public static function isOfficialUrl(string $url): bool
  {
    $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

    foreach (self::officialDomains() as $domain) {
      $domain = mb_strtolower((string) $domain);
      if ($host === $domain || str_ends_with($host, '.' . $domain)) {
        return true;
      }
    }

    return false;
  }

  public static function isQuestionDocument(string $url, string $title): bool
  {
    $haystack = mb_strtolower($url . ' ' . $title);
    $looksPdf = str_contains(mb_strtolower($url), '.pdf') || str_contains($haystack, 'pdf');

    if (! $looksPdf && ! self::isOfficialUrl($url)) {
      return false;
    }

    foreach (['previous year', 'sample paper', 'question bank', 'exam paper', 'official', 'mcq', 'pyq', 'paper'] as $needle) {
      if (str_contains($haystack, $needle)) {
        return true;
      }
    }

    return self::isOfficialUrl($url) && $looksPdf;
  }

  /**
   * @param  list<array<string, mixed>>  $documents
   * @return list<array<string, mixed>>
   */
  public static function dedupeAndRank(array $documents, string $topic, ?string $subject, int $limit): array
  {
    $seen = [];
    $unique = [];

    foreach ($documents as $doc) {
      $url = (string) ($doc['url'] ?? '');
      if ($url === '') {
        continue;
      }

      $hash = md5(mb_strtolower($url));
      if (isset($seen[$hash])) {
        continue;
      }

      $seen[$hash] = true;
      $unique[] = $doc;
    }

    $topicNeedle = mb_strtolower($topic);
    $subjectNeedle = mb_strtolower((string) $subject);
    $providerOrder = array_flip(config('retrieval.quiz_search.provider_priority', []));

    usort($unique, function (array $a, array $b) use ($topicNeedle, $subjectNeedle, $providerOrder) {
      return self::scoreDocument($b, $topicNeedle, $subjectNeedle, $providerOrder)
        <=> self::scoreDocument($a, $topicNeedle, $subjectNeedle, $providerOrder);
    });

    return array_slice($unique, 0, $limit);
  }

  /**
   * @param  array<string, mixed>  $doc
   * @param  array<string, int>  $providerOrder
   */
  private static function scoreDocument(array $doc, string $topicNeedle, string $subjectNeedle, array $providerOrder): int
  {
    $score = 0;
    $url = mb_strtolower((string) ($doc['url'] ?? ''));
    $title = mb_strtolower((string) ($doc['title'] ?? ''));
    $provider = (string) ($doc['search_provider'] ?? '');

    if (! empty($doc['is_official'])) {
      $score += 50;
    }
    if (str_contains($url, '.pdf')) {
      $score += 25;
    }
    if (str_contains($title, 'previous year') || str_contains($url, 'pyq')) {
      $score += 20;
    }
    if ($topicNeedle !== '' && (str_contains($title, $topicNeedle) || str_contains($url, str_replace(' ', '-', $topicNeedle)))) {
      $score += 15;
    }
    if ($subjectNeedle !== '' && str_contains($title, $subjectNeedle)) {
      $score += 10;
    }
    if (isset($providerOrder[$provider])) {
      $score += max(0, 10 - (int) $providerOrder[$provider]);
    }

    return $score;
  }

  public static function inferExamFromTitle(string $text): string
  {
    $lower = mb_strtolower($text);
    foreach (['jee', 'neet', 'upsc', 'ssc', 'cbse', 'icse', 'gate', 'cat', 'nta'] as $exam) {
      if (str_contains($lower, $exam)) {
        return strtoupper($exam);
      }
    }

    return '';
  }

  public static function inferYear(string $text): ?int
  {
    if (preg_match('/\b(19|20)\d{2}\b/', $text, $m) === 1) {
      return (int) $m[0];
    }

    return null;
  }
}

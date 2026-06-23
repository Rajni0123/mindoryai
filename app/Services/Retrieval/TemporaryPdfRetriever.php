<?php

namespace App\Services\Retrieval;

use App\Models\TemporaryPdfRetrieval;
use App\Services\RAGService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

/**
 * Downloads PDFs from Exa, extracts text temporarily, and purges after TTL.
 */
class TemporaryPdfRetriever
{
    public function __construct(
        protected RetrievalCacheService $cache,
        protected RAGService $ragService,
    ) {}

    public function retrieveFromUrl(string $url, string $question): string
    {
        $cacheKey = md5($url);
        $ttlMinutes = (int) config('retrieval.temporary_pdf.ttl_minutes', 60);

        $existing = TemporaryPdfRetrieval::where('cache_key', $cacheKey)->valid()->first();
        if ($existing && $existing->extracted_text) {
            return $this->searchChunks($existing->chunks ?? [], $question, $existing->extracted_text);
        }

        return $this->cache->remember('pdf', $url, function () use ($url, $cacheKey, $ttlMinutes, $question) {
            return $this->downloadExtractAndStore($url, $cacheKey, $ttlMinutes, $question);
        }, config('retrieval.cache.ttl.pdf'));
    }

    protected function downloadExtractAndStore(string $url, string $cacheKey, int $ttlMinutes, string $question): string
    {
        try {
            $maxMb = (int) config('retrieval.temporary_pdf.max_size_mb', 15);
            $response = Http::timeout(45)
                ->retry(2, 500)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept' => 'application/pdf,application/octet-stream,*/*',
                ])
                ->get($url);

            if (! $response->successful()) {
                Log::warning('Temporary PDF download failed', ['url' => $url, 'status' => $response->status()]);

                return '';
            }

            $body = $response->body();
            if ($body === '' || ! str_starts_with($body, '%PDF')) {
                Log::warning('Temporary PDF invalid content', ['url' => $url, 'bytes' => strlen($body)]);

                return '';
            }
            if (strlen($body) > $maxMb * 1024 * 1024) {
                Log::warning('Temporary PDF too large', ['url' => $url]);

                return '';
            }

            $tempPath = 'temp-pdf/' . $cacheKey . '.pdf';
            Storage::disk('local')->put($tempPath, $body);

            $text = $this->extractPdfText(Storage::disk('local')->path($tempPath));
            Storage::disk('local')->delete($tempPath);

            if ($text === '') {
                return '';
            }

            $chunks = $this->chunkText($text);
            TemporaryPdfRetrieval::updateOrCreate(
                ['cache_key' => $cacheKey],
                [
                    'source_url' => $url,
                    'extracted_text' => $text,
                    'chunks' => $chunks,
                    'expires_at' => now()->addMinutes($ttlMinutes),
                ]
            );

            return $this->searchChunks($chunks, $question, $text);
        } catch (\Throwable $e) {
            Log::warning('Temporary PDF retrieval failed', ['url' => $url, 'error' => $e->getMessage()]);

            return '';
        }
    }

    protected function extractPdfText(string $path): string
    {
        $config = new \Smalot\PdfParser\Config();
        $config->setRetainImageContent(false);
        $parser = new Parser([], $config);
        $pdf = $parser->parseFile($path);
        $pages = $pdf->getPages();
        $text = '';

        foreach (array_slice($pages, 0, 20) as $page) {
            try {
                $text .= $page->getText() . "\n";
            } catch (\Throwable) {
                continue;
            }
        }

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /**
     * @return list<string>
     */
    protected function chunkText(string $text, int $size = 1200): array
    {
        $chunks = [];
        $length = strlen($text);

        for ($offset = 0; $offset < $length; $offset += $size) {
            $chunks[] = substr($text, $offset, $size);
        }

        return $chunks;
    }

  /**
   * @param  list<string>  $chunks
   */
    protected function searchChunks(array $chunks, string $question, string $fallbackText): string
    {
        if ($chunks === []) {
            return substr($fallbackText, 0, 4000);
        }

        $keywords = preg_split('/\s+/', mb_strtolower($question), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $scored = [];

        foreach ($chunks as $chunk) {
            $lower = mb_strtolower($chunk);
            $score = 0;
            foreach ($keywords as $word) {
                if (strlen($word) > 3 && str_contains($lower, $word)) {
                    $score++;
                }
            }
            $scored[] = ['chunk' => $chunk, 'score' => $score];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $top = array_slice($scored, 0, 3);

        return implode("\n\n", array_map(fn ($item) => $item['chunk'], $top));
    }
}

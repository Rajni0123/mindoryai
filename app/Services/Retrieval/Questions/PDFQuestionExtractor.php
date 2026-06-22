<?php

namespace App\Services\Retrieval\Questions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;

class PDFQuestionExtractor
{
    public function extractFromUrl(string $url): string
    {
        try {
            $response = Http::timeout((int) config('retrieval.exa.timeout', 30))->retry(1, 300)->get($url);
            if (! $response->successful()) {
                return '';
            }

            $body = $response->body();
            if ($body === '' || strlen($body) > (20 * 1024 * 1024)) {
                return '';
            }

            $hash = md5($url);
            $tempPath = "temp-pdf/quiz-{$hash}.pdf";
            Storage::disk('local')->put($tempPath, $body);
            $absolute = Storage::disk('local')->path($tempPath);

            $config = new Config();
            $config->setRetainImageContent(false);
            $parser = new Parser([], $config);
            $pdf = $parser->parseFile($absolute);

            $text = '';
            foreach (array_slice($pdf->getPages(), 0, 80) as $page) {
                try {
                    $text .= "\n" . $page->getText();
                } catch (\Throwable) {
                    // skip bad pages
                }
            }

            Storage::disk('local')->delete($tempPath);

            return trim((string) preg_replace("/\r\n?/", "\n", $text));
        } catch (\Throwable $e) {
            Log::warning('PDFQuestionExtractor failed', ['url' => $url, 'error' => $e->getMessage()]);

            return '';
        }
    }
}


<?php

namespace App\Services\Retrieval;

use App\Services\Retrieval\DTO\RetrievalResult;

/**
 * Enriches chat retrieval with PDF text from Exa document hits (PYQ / exam papers).
 */
class ChatDocumentRetrievalService
{
    public function __construct(
        protected ExaSearchService $exaSearchService,
        protected TemporaryPdfRetriever $temporaryPdfRetriever,
        protected RetrievalSettingsService $settings,
    ) {}

    public function isDocumentRequest(string $question): bool
    {
        $lower = mb_strtolower($question);

        foreach ([
            'previous year',
            'last year paper',
            'last year',
            'pyq',
            'question paper',
            'sample paper',
            'exam paper',
            'past paper',
            'old paper',
            'paper pdf',
            'prelims paper',
            'mains paper',
        ] as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{context: string, sources: list<string>}
     */
    public function enrich(string $question, RetrievalResult $base): array
    {
        $parts = [];
        $sources = $base->sources;

        if ($base->success && $base->context !== '') {
            $parts[] = $base->context;
        }

        if ($this->isDocumentRequest($question) && $this->settings->isExaEnabled()) {
            $docs = $this->exaSearchService->searchQuizDocuments($question, null, 6);

            foreach (array_slice($docs, 0, 3) as $doc) {
                $url = (string) ($doc['url'] ?? '');
                $title = (string) ($doc['title'] ?? $doc['pdf_name'] ?? 'Exam document');

                if ($url === '') {
                    continue;
                }

                $sources[] = $title . ' (' . $url . ')';

                if ($this->settings->isTemporaryPdfEnabled()) {
                    $extracted = $this->temporaryPdfRetriever->retrieveFromUrl($url, $question);
                    if ($extracted !== '') {
                        $parts[] = "[EXTRACTED PDF CONTENT — {$title}]\nSource: {$url}\n\n{$extracted}";

                        continue;
                    }
                }

                $parts[] = "[PDF REFERENCE — {$title}]\nSource: {$url}";
            }
        }

        return [
            'context' => trim(implode("\n\n---\n\n", array_filter($parts))),
            'sources' => array_values(array_unique($sources)),
        ];
    }
}

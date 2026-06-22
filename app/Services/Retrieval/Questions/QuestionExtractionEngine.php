<?php

namespace App\Services\Retrieval\Questions;

class QuestionExtractionEngine
{
    public function __construct(
        protected PDFQuestionExtractor $pdfExtractor,
        protected MCQDetector $mcqDetector,
        protected QuestionParser $questionParser,
        protected QuestionNormalizer $normalizer,
        protected DuplicateRemover $duplicateRemover,
        protected QuestionRanker $ranker,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $documents
     * @return list<array<string, mixed>>
     */
    public function extractQuestions(array $documents, string $topic, ?string $subject, int $limit): array
    {
        $questions = [];

        foreach ($documents as $doc) {
            $url = (string) ($doc['url'] ?? '');
            if ($url === '') {
                continue;
            }

            $text = $this->pdfExtractor->extractFromUrl($url);
            if ($text === '') {
                continue;
            }

            $blocks = $this->mcqDetector->detectBlocks($text);
            foreach ($blocks as $block) {
                $parsed = $this->questionParser->parse($block);
                if (! $parsed) {
                    continue;
                }

                $normalized = $this->normalizer->normalize([
                    ...$parsed,
                    'source_url' => $url,
                    'pdf_name' => (string) ($doc['pdf_name'] ?? ''),
                    'exam' => (string) ($doc['exam'] ?? ''),
                    'year' => $doc['year'] ?? null,
                    'topic' => $topic,
                    'difficulty' => 'medium',
                ]);

                if ($normalized['question'] !== '' && count($normalized['options']) >= 2) {
                    $questions[] = $normalized;
                }
            }
        }

        $questions = $this->duplicateRemover->remove($questions);
        $questions = $this->ranker->rank($questions, $topic, $subject);

        return array_slice($questions, 0, $limit);
    }
}


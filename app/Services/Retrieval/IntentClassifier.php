<?php

namespace App\Services\Retrieval;

use App\Contracts\Retrieval\IntentClassifierInterface;
use App\Services\Retrieval\DTO\IntentResult;
use App\Services\Retrieval\DTO\RetrievalQuery;

/**
 * Rule-based intent classifier. Extensible to LLM classifier later.
 */
class IntentClassifier implements IntentClassifierInterface
{
    public function classify(RetrievalQuery $query): IntentResult
    {
        $text = $query->normalized();
        $signals = [];
        $intent = 'tutor';
        $bestScore = 0;

        foreach (config('retrieval.intents', []) as $name => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($text, mb_strtolower($keyword))) {
                    $score++;
                    $signals[] = $keyword;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $intent = $name;
            }
        }

        $strategy = $this->resolveStrategy($intent, $text);

        return new IntentResult(
            intent: $intent,
            strategy: $strategy,
            confidence: $bestScore > 0 ? min(1.0, 0.5 + ($bestScore * 0.15)) : 0.6,
            signals: array_values(array_unique($signals)),
        );
    }

    private function resolveStrategy(string $intent, string $text): string
    {
        $exaIntents = ['current_affairs', 'government', 'exam_update', 'general_search'];
        $ragIntents = ['tutor', 'revision', 'scan'];

        $wantsHybrid = str_contains($text, 'latest')
            || str_contains($text, 'pyq')
            || str_contains($text, 'previous year')
            || (str_contains($text, 'explain') && str_contains($text, 'and'));

        if ($wantsHybrid || $intent === 'pyq') {
            return 'hybrid';
        }

        if (in_array($intent, $exaIntents, true)) {
            return 'exa_only';
        }

        if (in_array($intent, $ragIntents, true)) {
            return 'rag_only';
        }

        if ($intent === 'quiz') {
            return 'rag_only';
        }

        return 'rag_only';
    }
}

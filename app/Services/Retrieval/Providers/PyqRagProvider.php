<?php

namespace App\Services\Retrieval\Providers;

use App\Models\ExamQuestion;
use App\Services\Retrieval\DTO\RetrievalQuery;
use App\Services\Retrieval\DTO\RetrievalResult;
use App\Services\Retrieval\RetrievalSettingsService;

class PyqRagProvider extends AbstractRagProvider
{
    public function __construct(
        RetrievalSettingsService $settings,
    ) {
        parent::__construct($settings);
    }

    public function key(): string
    {
        return 'pyq';
    }

    public function label(): string
    {
        return 'Previous Year Questions';
    }

    protected function fetch(RetrievalQuery $query): RetrievalResult
    {
        $builder = ExamQuestion::query()->active();

        if ($subject) {
            $builder->where('subject', 'like', '%' . $subject . '%');
        }

        $keywords = preg_split('/\s+/', $query->normalized(), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $topic = $query->topic ?: $query->question;
        $builder->where(function ($q) use ($query, $keywords, $topic) {
            $q->where('topic', 'like', '%' . $topic . '%')
                ->orWhere('subtopic', 'like', '%' . $topic . '%')
                ->orWhere('question_text', 'like', '%' . $query->question . '%');

            foreach (array_slice($keywords, 0, 5) as $word) {
                if (strlen($word) >= 4) {
                    $q->orWhere('question_text', 'like', '%' . $word . '%');
                }
            }
        });

        $questions = $builder->limit(5)->get();

        if ($questions->isEmpty()) {
            return RetrievalResult::empty($this->key(), 'No PYQ matches found.');
        }

        $parts = [];
        $sources = [];

        foreach ($questions as $index => $question) {
            $sources[] = trim(($question->subject ?? 'PYQ') . ' ' . ($question->year ?? ''));
            $options = is_array($question->options) ? json_encode($question->options) : (string) $question->options;
            $parts[] = '[PYQ ' . ($index + 1) . "]\n"
                . $question->question_text . "\nOptions: " . $options;
        }

        return new RetrievalResult(
            success: true,
            context: implode("\n\n---\n\n", $parts),
            sources: array_values(array_unique($sources)),
            provider: $this->key(),
        );
    }
}

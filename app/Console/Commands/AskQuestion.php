<?php

namespace App\Console\Commands;

use App\Services\AnswerBankService;
use App\Models\QuestionBank;
use App\Models\AnswerBankStats;
use Illuminate\Console\Command;

class AskQuestion extends Command
{
    protected $signature = 'ask:question
                            {question? : The question to ask}
                            {--exam= : Exam type (JEE/NEET/CBSE/UPSC)}
                            {--stats : Show answer bank statistics}
                            {--interactive : Interactive mode - keep asking questions}
                            {--top=10 : Show top cached questions}';

    protected $description = 'Ask a question - serves from cache if available, otherwise calls AI API';

    protected AnswerBankService $service;

    public function __construct(AnswerBankService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        // Show stats
        if ($this->option('stats')) {
            return $this->showStats();
        }

        // Show top cached questions
        if ($this->option('top')) {
            return $this->showTopQuestions((int) $this->option('top'));
        }

        // Interactive mode
        if ($this->option('interactive')) {
            return $this->interactiveMode();
        }

        // Single question
        $question = $this->argument('question');
        if (!$question) {
            $question = $this->ask('Enter your question');
        }

        if (!$question) {
            $this->error('No question provided!');
            return 1;
        }

        $this->processQuestion($question);
        return 0;
    }

    protected function processQuestion(string $question): void
    {
        $examType = $this->option('exam') ?? 'JEE/NEET';

        $this->newLine();
        $this->info("Searching answer bank...");

        $startTime = microtime(true);

        $result = $this->service->getAnswer($question, [
            'exam_type' => $examType,
        ]);

        $elapsed = round((microtime(true) - $startTime) * 1000);

        $this->newLine();

        if ($result['source'] === 'cache') {
            $this->components->twoColumnDetail(
                'Source',
                "<fg=green;options=bold>CACHE HIT</> (saved ~Rs.0.15)"
            );
            $this->components->twoColumnDetail(
                'Response Time',
                "<fg=green>{$elapsed}ms</>"
            );
            $this->components->twoColumnDetail(
                'Times Served',
                "{$result['hit_count']} times"
            );
        } elseif ($result['source'] === 'api') {
            $this->components->twoColumnDetail(
                'Source',
                "<fg=yellow;options=bold>API CALL</> (answer cached for future)"
            );
            $this->components->twoColumnDetail(
                'Response Time',
                "<fg=yellow>{$elapsed}ms</>"
            );
        } else {
            $this->components->twoColumnDetail(
                'Source',
                "<fg=red;options=bold>ERROR</>"
            );
        }

        if ($result['subject']) {
            $this->components->twoColumnDetail('Subject', ucfirst($result['subject']));
        }
        if ($result['topic']) {
            $this->components->twoColumnDetail('Topic', str_replace('_', ' ', ucfirst($result['topic'])));
        }

        $this->newLine();
        $this->line("================================================");
        $this->newLine();
        $this->line($result['answer']);
        $this->newLine();
        $this->line("================================================");
    }

    protected function interactiveMode(): int
    {
        $this->newLine();
        $this->info("BlinkStudy Answer Bank - Interactive Mode");
        $this->info("Type 'quit' to exit, 'stats' for statistics");
        $this->line("================================================");

        while (true) {
            $this->newLine();
            $question = $this->ask('Your question');

            if (!$question || strtolower($question) === 'quit' || strtolower($question) === 'exit') {
                $this->info('Bye! Keep studying!');
                return 0;
            }

            if (strtolower($question) === 'stats') {
                $this->showStats();
                continue;
            }

            $this->processQuestion($question);
        }
    }

    protected function showStats(): int
    {
        $stats = AnswerBankStats::getSummary();
        $totalQuestions = QuestionBank::count();
        $totalHits = QuestionBank::sum('hit_count');

        $this->newLine();
        $this->info("BlinkStudy Answer Bank Statistics");
        $this->line("================================================");

        $this->newLine();
        $this->info("Database:");
        $this->components->twoColumnDetail('Total Questions', number_format($totalQuestions));
        $this->components->twoColumnDetail('Total Cache Hits', number_format($totalHits));

        $this->newLine();
        $this->info("All Time:");
        $this->components->twoColumnDetail('Total Queries', number_format($stats['all_time']['total_queries']));
        $this->components->twoColumnDetail('Cache Hits', number_format($stats['all_time']['cache_hits']));
        $this->components->twoColumnDetail('API Calls', number_format($stats['all_time']['api_calls']));
        $this->components->twoColumnDetail('Cache Hit Rate', $stats['all_time']['hit_rate'] . '%');
        $this->components->twoColumnDetail('Money Saved', $stats['all_time']['money_saved']);

        $this->newLine();
        $this->info("Today:");
        $this->components->twoColumnDetail('Queries', number_format($stats['today']['total_queries']));
        $this->components->twoColumnDetail('Cache Hits', number_format($stats['today']['cache_hits']));
        $this->components->twoColumnDetail('API Calls', number_format($stats['today']['api_calls']));
        $this->components->twoColumnDetail('Hit Rate', $stats['today']['hit_rate'] . '%');

        // Subject breakdown
        $this->newLine();
        $this->info("Subject Breakdown:");
        $subjects = QuestionBank::selectRaw('subject, COUNT(*) as count, SUM(hit_count) as total_hits')
            ->whereNotNull('subject')
            ->groupBy('subject')
            ->orderByDesc('count')
            ->get();

        foreach ($subjects as $s) {
            $this->components->twoColumnDetail(
                ucfirst($s->subject),
                "{$s->count} questions | {$s->total_hits} hits"
            );
        }

        $this->newLine();
        return 0;
    }

    protected function showTopQuestions(int $limit): int
    {
        $top = QuestionBank::orderByDesc('hit_count')
            ->limit($limit)
            ->get(['id', 'original_question', 'subject', 'hit_count', 'api_cost_saved']);

        $this->newLine();
        $this->info("Top {$limit} Most Served Questions");
        $this->line("================================================");

        $this->table(
            ['#', 'Question', 'Subject', 'Hits', 'Cost Saved'],
            $top->map(fn($q, $i) => [
                $i + 1,
                \Illuminate\Support\Str::limit($q->original_question, 60),
                ucfirst($q->subject ?? '-'),
                number_format($q->hit_count),
                'Rs.' . number_format($q->api_cost_saved / 100, 2),
            ])->toArray()
        );

        $this->newLine();
        return 0;
    }
}

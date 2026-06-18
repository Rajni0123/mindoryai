<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AnswerBank;
use App\Services\Cache\SmartGreetingFilter;
use App\Services\Cache\FollowUpDetector;
use App\Services\Cache\MessageQualityGate;
use Illuminate\Support\Facades\DB;

class CachePurgeGarbageCommand extends Command
{
    protected $signature = 'cache:purge-garbage
                            {--dry-run : Show what would be purged without actually purging}
                            {--verbose : Show each purged entry}';

    protected $description = 'Purge garbage entries from cache (greetings, follow-ups, low-quality)';

    private SmartGreetingFilter $greetingFilter;
    private FollowUpDetector $followUpDetector;
    private MessageQualityGate $qualityGate;

    public function __construct(
        SmartGreetingFilter $greetingFilter,
        FollowUpDetector $followUpDetector,
        MessageQualityGate $qualityGate
    ) {
        parent::__construct();
        $this->greetingFilter = $greetingFilter;
        $this->followUpDetector = $followUpDetector;
        $this->qualityGate = $qualityGate;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $verbose = $this->option('verbose');

        $this->info('🗑️  Smart Cache Garbage Purge');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN - No data will be purged');
            $this->newLine();
        }

        $this->info('Scanning answer_bank for garbage entries...');
        $this->newLine();

        $stats = [
            'greeting' => 0,
            'followup' => 0,
            'quality' => 0,
            'total' => 0,
        ];

        $garbageIds = [];

        // Process in batches to avoid memory issues
        $batchSize = 100;
        $processed = 0;

        try {
            $total = AnswerBank::count();
            $this->line("Total entries to scan: {$total}");
            $this->newLine();

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            AnswerBank::chunk($batchSize, function ($entries) use (&$stats, &$garbageIds, $verbose, $bar) {
                foreach ($entries as $entry) {
                    $question = $entry->original_question;
                    $isGarbage = false;
                    $reason = '';

                    // Check greeting filter
                    $greetingResult = $this->greetingFilter->shouldSkipCache($question);
                    if ($greetingResult['skip']) {
                        $isGarbage = true;
                        $reason = 'greeting';
                        $stats['greeting']++;
                    }

                    // Check follow-up detector
                    if (!$isGarbage && $this->followUpDetector->isFollowUp($question, false)) {
                        $isGarbage = true;
                        $reason = 'followup';
                        $stats['followup']++;
                    }

                    // Check quality gate
                    if (!$isGarbage && !$this->qualityGate->passes($question)) {
                        $isGarbage = true;
                        $reason = 'quality';
                        $stats['quality']++;
                    }

                    if ($isGarbage) {
                        $garbageIds[] = $entry->id;
                        $stats['total']++;

                        if ($verbose) {
                            $this->newLine();
                            $this->line("  [{$reason}] ID:{$entry->id} - \"" . mb_substr($question, 0, 50) . "...\"");
                        }
                    }

                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine();
            $this->newLine();

        } catch (\Exception $e) {
            $this->error('Error scanning entries: ' . $e->getMessage());
            return 1;
        }

        // Summary
        $this->info('📊 Scan Results:');
        $this->newLine();

        $this->table(
            ['Category', 'Count'],
            [
                ['Greetings', $stats['greeting']],
                ['Follow-ups', $stats['followup']],
                ['Low Quality', $stats['quality']],
                ['─────────', '─────'],
                ['Total Garbage', $stats['total']],
            ]
        );

        if ($stats['total'] === 0) {
            $this->info('✅ No garbage entries found!');
            return 0;
        }

        // Purge
        if (!$dryRun) {
            $this->newLine();
            $this->info('🗑️  Purging garbage entries...');

            try {
                // Delete in batches
                $deleted = 0;
                foreach (array_chunk($garbageIds, 100) as $batch) {
                    $deleted += AnswerBank::whereIn('id', $batch)->delete();
                }

                $this->info("✅ Purged {$deleted} garbage entries");
            } catch (\Exception $e) {
                $this->error('Error purging entries: ' . $e->getMessage());
                return 1;
            }
        } else {
            $this->newLine();
            $this->warn("Would purge {$stats['total']} entries. Run without --dry-run to actually purge.");
        }

        return 0;
    }
}

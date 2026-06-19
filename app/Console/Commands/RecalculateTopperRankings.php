<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TopperRankingService;

class RecalculateTopperRankings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'toppers:recalculate
                            {--user= : Recalculate for specific user ID}
                            {--show-leaderboard : Display current leaderboard after recalculation}';

    /**
     * The console command description.
     */
    protected $description = 'Recalculate topper rankings based on performance scores. Top 10 users automatically become toppers.';

    /**
     * Execute the console command.
     */
    public function handle(TopperRankingService $rankingService): int
    {
        $this->info('🏆 Starting Topper Ranking Recalculation...');
        $this->newLine();

        try {
            $userId = $this->option('user');

            if ($userId) {
                // Single user recalculation
                $user = \App\Models\User::find($userId);

                if (!$user) {
                    $this->error("User with ID {$userId} not found!");
                    return Command::FAILURE;
                }

                $this->info("Recalculating score for: {$user->name}");
                $result = $rankingService->updateUserRanking($user);

                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Old Score', $result['old_score']],
                        ['New Score', $result['new_score']],
                        ['Change', ($result['change'] >= 0 ? '+' : '') . $result['change']],
                        ['Is Topper', $result['is_topper'] ? '✅ Yes' : '❌ No'],
                        ['Rank Position', $result['rank'] ?? 'N/A'],
                    ]
                );

            } else {
                // Full recalculation
                $this->info('Processing all active users...');
                $stats = $rankingService->recalculateAllRankings();

                $this->newLine();
                $this->info('✅ Recalculation Complete!');
                $this->newLine();

                $this->table(
                    ['Statistic', 'Count'],
                    [
                        ['Users Processed', $stats['users_processed']],
                        ['New Toppers', $stats['new_toppers']],
                        ['Demoted Users', $stats['demoted']],
                    ]
                );

                // Show top 10
                if (!empty($stats['top_10'])) {
                    $this->newLine();
                    $this->info('🏅 Current Top 10 Toppers:');

                    $leaderboardData = [];
                    foreach ($stats['top_10'] as $topper) {
                        $leaderboardData[] = [
                            $topper['rank'],
                            $topper['name'],
                            $topper['score'],
                            $topper['doubts_solved'],
                        ];
                    }

                    $this->table(
                        ['Rank', 'Name', 'Score', 'Doubts Solved'],
                        $leaderboardData
                    );
                }
            }

            // Show leaderboard if requested
            if ($this->option('show-leaderboard')) {
                $this->newLine();
                $this->info('📊 Full Leaderboard:');

                $leaderboard = $rankingService->getLeaderboard();

                if (empty($leaderboard)) {
                    $this->warn('No toppers yet! Users need minimum 3 doubts solved and 50 score.');
                } else {
                    $tableData = [];
                    foreach ($leaderboard as $entry) {
                        $tableData[] = [
                            $entry['rank'],
                            $entry['name'],
                            $entry['score'],
                            $entry['doubts_solved'],
                            $entry['rating'] ? number_format($entry['rating'], 1) . '⭐' : 'N/A',
                            $entry['response_time'] ? $this->formatResponseTime($entry['response_time']) : 'N/A',
                            $entry['is_available'] ? '🟢' : '🔴',
                        ];
                    }

                    $this->table(
                        ['Rank', 'Name', 'Score', 'Doubts', 'Rating', 'Avg Response', 'Available'],
                        $tableData
                    );
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error during recalculation: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Format response time in human readable format
     */
    private function formatResponseTime(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "{$minutes}m";
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return "{$hours}h {$minutes}m";
        }
    }
}

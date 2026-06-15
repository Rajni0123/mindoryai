<?php

namespace App\Console\Commands;

use App\Models\StudyBattleRoom;
use App\Services\StudyBattleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupStudyBattles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'study-battle:cleanup';

    /**
     * The console command description.
     */
    protected $description = 'Cleanup expired and abandoned study battle rooms';

    /**
     * Execute the console command.
     */
    public function handle(StudyBattleService $battleService): int
    {
        $this->info('Starting Study Battle cleanup...');

        try {
            // 1. Cleanup disconnected participants and check for abandoned rooms
            $cleanedUp = $battleService->cleanup();
            $this->info("Cleaned up {$cleanedUp} disconnected participants");

            // 2. Mark expired waiting rooms as cancelled
            $expiredCount = StudyBattleRoom::expired()
                ->update([
                    'status' => 'cancelled',
                    'battle_ended_at' => now(),
                ]);
            $this->info("Cancelled {$expiredCount} expired rooms");

            // 3. Auto-end battles stuck in_progress for more than 30 minutes
            $stuckBattles = StudyBattleRoom::where('status', 'in_progress')
                ->where('battle_started_at', '<', now()->subMinutes(30))
                ->get();

            foreach ($stuckBattles as $room) {
                $room->update([
                    'status' => 'abandoned',
                    'battle_ended_at' => now(),
                ]);

                // Update all participants
                $room->participants()
                    ->whereIn('status', ['playing', 'joined', 'ready'])
                    ->update([
                        'status' => 'disconnected',
                        'finished_at' => now(),
                    ]);
            }
            $this->info("Ended {$stuckBattles->count()} stuck battles");

            // 4. Delete old completed/cancelled rooms (older than 7 days)
            $deletedCount = StudyBattleRoom::whereIn('status', ['completed', 'cancelled', 'abandoned'])
                ->where('updated_at', '<', now()->subDays(7))
                ->delete();
            $this->info("Deleted {$deletedCount} old rooms");

            Log::info('Study Battle cleanup completed', [
                'disconnected_participants' => $cleanedUp,
                'expired_rooms' => $expiredCount,
                'stuck_battles' => $stuckBattles->count(),
                'deleted_old_rooms' => $deletedCount,
            ]);

            $this->info('Study Battle cleanup completed successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            Log::error('Study Battle cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}

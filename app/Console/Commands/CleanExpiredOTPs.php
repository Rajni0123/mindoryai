<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanExpiredOTPs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired OTP verification records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning expired OTP records...');

        // Delete expired OTPs
        $expiredCount = DB::table('otp_verifications')
            ->where('expires_at', '<', now())
            ->delete();

        // Delete old verified OTPs (older than 24 hours)
        $verifiedCount = DB::table('otp_verifications')
            ->where('verified', true)
            ->where('created_at', '<', now()->subDay())
            ->delete();

        $this->info("✅ Deleted {$expiredCount} expired OTP records");
        $this->info("✅ Deleted {$verifiedCount} old verified OTP records");
        $this->info('OTP cleanup completed successfully!');

        return Command::SUCCESS;
    }
}

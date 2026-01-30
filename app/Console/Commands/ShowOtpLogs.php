<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowOtpLogs extends Command
{
    protected $signature = 'otp:show {--mobile= : Filter by mobile number} {--latest : Show only latest OTP}';
    protected $description = 'Show OTP codes from database (Development Mode)';

    public function handle()
    {
        $this->info('🔐 OTP Logs - Development Mode');
        $this->newLine();

        $query = DB::table('otp_verifications')
            ->orderBy('created_at', 'desc');

        if ($this->option('mobile')) {
            $query->where('mobile', $this->option('mobile'));
        }

        if ($this->option('latest')) {
            $query->limit(1);
        } else {
            $query->limit(10);
        }

        $otps = $query->get();

        if ($otps->isEmpty()) {
            $this->warn('No OTP records found.');
            return 0;
        }

        $headers = ['Mobile', 'OTP Code', 'Created At', 'Expires At', 'Verified', 'Attempts'];
        $rows = [];

        foreach ($otps as $otp) {
            $rows[] = [
                $otp->mobile,
                $otp->otp_code,
                $otp->created_at,
                $otp->expires_at,
                $otp->verified ? '✓ Yes' : '✗ No',
                $otp->attempts
            ];
        }

        $this->table($headers, $rows);

        if ($this->option('latest') && !$otps->isEmpty()) {
            $latest = $otps->first();
            $this->newLine();
            $this->info("📱 Latest OTP for {$latest->mobile}: {$latest->otp_code}");
            $this->line("   Created: {$latest->created_at}");
            $this->line("   Expires: {$latest->expires_at}");
        }

        return 0;
    }
}

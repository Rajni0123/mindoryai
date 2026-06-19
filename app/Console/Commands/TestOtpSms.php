<?php

namespace App\Console\Commands;

use App\Services\RenflairOTPService;
use Illuminate\Console\Command;

class TestOtpSms extends Command
{
    protected $signature = 'otp:test {mobile}';
    protected $description = 'Test OTP SMS sending to a mobile number';

    public function handle()
    {
        $mobile = $this->argument('mobile');

        $this->info('🧪 Testing OTP SMS Service');
        $this->newLine();
        $this->line("Mobile Number: {$mobile}");
        $this->newLine();

        // Validate mobile format
        if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            $this->error('Invalid mobile number format!');
            $this->line('Mobile number must be 10 digits and start with 6-9');
            return 1;
        }

        $this->line('Sending OTP...');

        $otpService = new RenflairOTPService();
        // Use server IP or placeholder for CLI testing
        $serverIp = gethostbyname(gethostname());
        $result = $otpService->sendOTP($mobile, $serverIp, 'CLI Test');

        $this->newLine();

        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
            $this->newLine();
            $this->line('📱 Check your phone for the OTP SMS');
            $this->line('📋 Or run: php artisan otp:show --latest');
        } else {
            $this->error('❌ ' . $result['message']);
        }

        $this->newLine();
        $this->line('📄 Check logs for details:');
        $this->line('   tail -20 storage/logs/laravel.log');

        return 0;
    }
}

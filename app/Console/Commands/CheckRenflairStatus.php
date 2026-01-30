<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckRenflairStatus extends Command
{
    protected $signature = 'renflair:status';
    protected $description = 'Check Renflair API account status and credits';

    public function handle()
    {
        $apiKey = config('services.renflair.api_key');

        if (!$apiKey) {
            $this->error('RENFLAIR_API_KEY not set in .env file!');
            return 1;
        }

        $this->info('🔍 Checking Renflair Account Status');
        $this->newLine();

        $this->line("API Key: " . substr($apiKey, 0, 10) . '...' . substr($apiKey, -5));
        $this->newLine();

        // Test API connectivity with a sample request
        $this->line('Testing API connectivity...');

        try {
            $url = 'https://sms.renflair.in/V1.php';
            $testMobile = '9999999999'; // Invalid test number
            $testOTP = '0000';

            $response = Http::withoutVerifying()
                ->timeout(30)
                ->get($url, [
                    'API' => $apiKey,
                    'PHONE' => $testMobile,
                    'OTP' => $testOTP,
                ]);

            $this->newLine();
            $this->info('API Response:');
            $this->line("Status Code: " . $response->status());
            $this->line("Response Body: " . $response->body());
            $this->newLine();

            if ($response->successful()) {
                $body = json_decode($response->body(), true);

                if (isset($body['status'])) {
                    if ($body['status'] === 'SUCCESS') {
                        $this->info('✅ API is working correctly');
                    } else {
                        $this->warn('⚠️  API Status: ' . $body['status']);
                    }
                }

                if (isset($body['message'])) {
                    $this->line('Message: ' . $body['message']);
                }

                if (isset($body['return'])) {
                    $this->line('Return: ' . ($body['return'] ? 'true' : 'false'));
                }
            } else {
                $this->error('❌ API returned HTTP ' . $response->status());
            }

            $this->newLine();
            $this->line('📋 Next Steps:');
            $this->line('1. Login to your Renflair dashboard at: https://renflair.in');
            $this->line('2. Check your SMS credits/balance');
            $this->line('3. Verify your account is in PRODUCTION mode (not TEST mode)');
            $this->line('4. Check if your sender ID is approved');
            $this->line('5. Ensure the mobile number is not on DND (Do Not Disturb) registry');
            $this->newLine();
            $this->line('📱 If the API says "SUCCESS" but SMS not received:');
            $this->line('   - Contact Renflair support with the request_id from logs');
            $this->line('   - Check if your Renflair account is in test/sandbox mode');
            $this->line('   - Verify sufficient credits in your account');

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

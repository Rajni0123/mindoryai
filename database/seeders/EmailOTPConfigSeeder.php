<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FrontendConfig;

class EmailOTPConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds default email OTP authentication settings
     */
    public function run(): void
    {
        $configs = [
            // Email OTP Settings
            [
                'config_key' => 'auth.email_otp.enabled',
                'config_value' => 'true',
                'value_type' => 'boolean',
                'description' => 'Enable/disable email OTP login'
            ],
            [
                'config_key' => 'auth.email_otp.length',
                'config_value' => '4',
                'value_type' => 'number',
                'description' => 'Email OTP code length (4-8 digits)'
            ],
            [
                'config_key' => 'auth.email_otp.expiry_minutes',
                'config_value' => '5',
                'value_type' => 'number',
                'description' => 'Email OTP expiry time in minutes'
            ],
            [
                'config_key' => 'auth.email_otp.resend_cooldown',
                'config_value' => '60',
                'value_type' => 'number',
                'description' => 'Email OTP resend cooldown in seconds'
            ],
            [
                'config_key' => 'auth.email_otp.max_attempts',
                'config_value' => '3',
                'value_type' => 'number',
                'description' => 'Maximum email OTP verification attempts'
            ],
            [
                'config_key' => 'auth.email_otp.email_subject',
                'config_value' => 'Your Verification Code',
                'value_type' => 'string',
                'description' => 'Email OTP email subject line'
            ],
            [
                'config_key' => 'auth.email_otp.email_template',
                'config_value' => 'Your verification code is: {otp}. Valid for {minutes} minutes. - {app_name}',
                'value_type' => 'string',
                'description' => 'Email OTP email template (supports {otp}, {minutes}, {app_name})'
            ],

            // Email OTP Login Enable/Disable
            [
                'config_key' => 'auth.email_otp_login_enabled',
                'config_value' => 'true',
                'value_type' => 'boolean',
                'description' => 'Show email OTP login option in login screen'
            ],

            // Login Screen Email Placeholder
            [
                'config_key' => 'auth.login.email_placeholder',
                'config_value' => 'Enter your email address',
                'value_type' => 'string',
                'description' => 'Email input placeholder text'
            ],
        ];

        foreach ($configs as $config) {
            FrontendConfig::updateOrCreate(
                ['config_key' => $config['config_key']],
                [
                    'config_value' => $config['config_value'],
                    'value_type' => $config['value_type'],
                    'description' => $config['description'] ?? null
                ]
            );
        }

        // Clear cache
        FrontendConfig::clearCache();

        $this->command->info('✅ Email OTP configuration seeded successfully!');
    }
}

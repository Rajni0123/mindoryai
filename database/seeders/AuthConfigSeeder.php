<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FrontendConfig;

class AuthConfigSeeder extends Seeder
{
    /**
     * Seed authentication configuration settings
     */
    public function run()
    {
        $configs = [
            // =============================================
            // OTP SETTINGS
            // =============================================
            [
                'config_key' => 'auth.otp.provider',
                'config_value' => 'renflair',
                'value_type' => 'string',
                'description' => 'OTP SMS provider (renflair, twilio, msg91)',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.otp.enabled',
                'config_value' => 'true',
                'value_type' => 'boolean',
                'description' => 'Enable OTP authentication',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.otp.length',
                'config_value' => '4',
                'value_type' => 'number',
                'description' => 'OTP code length',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.otp.expiry_minutes',
                'config_value' => '5',
                'value_type' => 'number',
                'description' => 'OTP expiry time in minutes',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.otp.resend_cooldown',
                'config_value' => '60',
                'value_type' => 'number',
                'description' => 'Cooldown between OTP resends (seconds)',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.otp.max_attempts',
                'config_value' => '3',
                'value_type' => 'number',
                'description' => 'Maximum OTP verification attempts',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.otp.sms_template',
                'config_value' => 'Your verification code is: {otp}. Valid for {minutes} minutes. - {app_name}',
                'value_type' => 'string',
                'description' => 'SMS template for OTP (use {otp}, {minutes}, {app_name})',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.otp.test_mode',
                'config_value' => 'false',
                'value_type' => 'boolean',
                'description' => 'Test mode - always accept 123456 as valid OTP',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.otp.method',
                'config_value' => 'sms',
                'value_type' => 'string',
                'description' => 'OTP delivery method: sms or whatsapp',
                'is_active' => true
            ],

            // =============================================
            // LOGIN SCREEN CUSTOMIZATION
            // =============================================
            [
                'config_key' => 'auth.login.title',
                'config_value' => 'Welcome Back!',
                'value_type' => 'string',
                'description' => 'Login screen title text',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.login.subtitle',
                'config_value' => 'Sign in to continue to your AI assistant',
                'value_type' => 'string',
                'description' => 'Login screen subtitle text',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.login.logo',
                'config_value' => '',
                'value_type' => 'string',
                'description' => 'Login screen logo path',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.login.background_color',
                'config_value' => '#0a0a0a',
                'value_type' => 'string',
                'description' => 'Login screen background color',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.login.primary_color',
                'config_value' => '#0df259',
                'value_type' => 'string',
                'description' => 'Login screen primary button color',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.login.text_color',
                'config_value' => '#ffffff',
                'value_type' => 'string',
                'description' => 'Login screen text color',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.login.phone_placeholder',
                'config_value' => 'Enter your mobile number',
                'value_type' => 'string',
                'description' => 'Phone number input placeholder',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.login.button_text',
                'config_value' => 'Send OTP',
                'value_type' => 'string',
                'description' => 'Login button text',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.login.terms_text',
                'config_value' => 'By continuing, you agree to our Terms of Service and Privacy Policy',
                'value_type' => 'string',
                'description' => 'Terms and conditions text',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.login.footer_text',
                'config_value' => 'Need help? Contact support@' . env('MAIN_DOMAIN', 'example.com'),
                'value_type' => 'string',
                'description' => 'Login screen footer text',
                'is_active' => true
            ],

            // =============================================
            // SOCIAL LOGIN SETTINGS
            // =============================================
            [
                'config_key' => 'auth.social.google.enabled',
                'config_value' => 'false',
                'value_type' => 'boolean',
                'description' => 'Enable Google Sign-In',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.social.google.client_id',
                'config_value' => '',
                'value_type' => 'string',
                'description' => 'Google OAuth Client ID',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.social.google.client_secret',
                'config_value' => '',
                'value_type' => 'string',
                'description' => 'Google OAuth Client Secret',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.social.apple.enabled',
                'config_value' => 'false',
                'value_type' => 'boolean',
                'description' => 'Enable Apple Sign-In (Coming Soon)',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.social.facebook.enabled',
                'config_value' => 'false',
                'value_type' => 'boolean',
                'description' => 'Enable Facebook Sign-In (Coming Soon)',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.social.divider_text',
                'config_value' => 'OR',
                'value_type' => 'string',
                'description' => 'Divider text between social and OTP login',
                'is_active' => true
            ],

            // =============================================
            // SECURITY SETTINGS
            // =============================================
            [
                'config_key' => 'auth.security.max_devices',
                'config_value' => '5',
                'value_type' => 'number',
                'description' => 'Maximum devices per user',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.security.session_timeout',
                'config_value' => '1440',
                'value_type' => 'number',
                'description' => 'Session timeout in minutes (default 24 hours)',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.security.auto_logout_inactive',
                'config_value' => 'false',
                'value_type' => 'boolean',
                'description' => 'Auto logout after inactivity',
                'is_active' => true
            ],
            [
                'config_key' => 'auth.security.require_phone_verification',
                'config_value' => 'true',
                'value_type' => 'boolean',
                'description' => 'Require phone number verification',
                'is_active' => true
            ],
        ];

        foreach ($configs as $config) {
            FrontendConfig::updateOrCreate(
                ['config_key' => $config['config_key']],
                $config
            );
        }

        $this->command->info('✅ Auth configurations seeded successfully!');
    }
}

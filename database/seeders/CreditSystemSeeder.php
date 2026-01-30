<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreditSystemSeeder extends Seeder
{
    /**
     * Seed the credit system with default configuration
     *
     * This sets up:
     * 1. Feature flags for credit system controls
     * 2. Default credit costs for actions
     * 3. Daily limits configuration
     * 4. Frontend config for mobile app
     */
    public function run(): void
    {
        // =============================================
        // FEATURE FLAGS
        // =============================================
        $featureFlags = [
            [
                'feature_key' => 'credits_enabled',
                'feature_name' => 'Credit System',
                'description' => 'Enable/disable the entire credit system',
                'is_enabled' => true,
                'config' => json_encode([
                    'signup_bonus' => 50, // Free credits on signup
                    'daily_login_bonus' => 5, // Credits for daily login
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'feature_key' => 'chat_credits_enabled',
                'feature_name' => 'Chat Credit Deduction',
                'description' => 'Deduct credits for chat messages',
                'is_enabled' => true,
                'config' => json_encode([
                    'cost_per_message' => 1, // 1 credit per message
                    'cost_with_image' => 2, // 2 credits if image attached
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'feature_key' => 'image_generation_enabled',
                'feature_name' => 'Image Generation',
                'description' => 'Enable/disable image generation feature',
                'is_enabled' => true,
                'config' => json_encode([
                    'cost_per_image' => 5, // 5 credits per image
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'feature_key' => 'daily_limits_enabled',
                'feature_name' => 'Daily Usage Limits',
                'description' => 'Apply daily limits when unlimited mode is OFF',
                'is_enabled' => true,
                'config' => json_encode([
                    'max_messages_per_day' => 100,
                    'max_images_per_day' => 10,
                    'max_credits_per_day' => 200,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'feature_key' => 'voice_input_enabled',
                'feature_name' => 'Voice Input',
                'description' => 'Enable/disable voice transcription',
                'is_enabled' => true,
                'config' => json_encode([
                    'cost_per_minute' => 2, // 2 credits per minute
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($featureFlags as $flag) {
            DB::table('feature_flags')->updateOrInsert(
                ['feature_key' => $flag['feature_key']],
                $flag
            );
        }

        // =============================================
        // FRONTEND CONFIG FOR MOBILE APP
        // =============================================
        $frontendConfigs = [
            // Credit System Display
            [
                'config_key' => 'credits.show_balance',
                'config_value' => 'true',
                'value_type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'config_key' => 'credits.low_balance_warning',
                'config_value' => '10',
                'value_type' => 'number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'config_key' => 'credits.show_transaction_history',
                'config_value' => 'true',
                'value_type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Profile Completion
            [
                'config_key' => 'profile.require_name',
                'config_value' => 'true',
                'value_type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'config_key' => 'profile.require_mobile_for_email_login',
                'config_value' => 'false',
                'value_type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'config_key' => 'profile.show_upgrade_option',
                'config_value' => 'true',
                'value_type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Rate Limiting
            [
                'config_key' => 'rate_limit.messages_per_minute',
                'config_value' => '10',
                'value_type' => 'number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'config_key' => 'rate_limit.images_per_hour',
                'config_value' => '20',
                'value_type' => 'number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($frontendConfigs as $config) {
            DB::table('frontend_configs')->updateOrInsert(
                ['config_key' => $config['config_key']],
                $config
            );
        }

        $this->command->info('✅ Credit system seeded successfully!');
        $this->command->info('   - Feature flags created');
        $this->command->info('   - Frontend config added');
        $this->command->info('   - Default costs configured');
    }
}

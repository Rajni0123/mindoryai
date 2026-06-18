<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureFlagSeeder extends Seeder
{
    /**
     * Seed feature flags for credit system and feature toggles
     */
    public function run()
    {
        $flags = [
            // Credit System
            [
                'feature_key' => 'credits_enabled',
                'feature_name' => 'Credit System',
                'is_enabled' => true,
                'config' => json_encode([
                    'signup_bonus' => 50,
                    'low_balance_warning' => 10,
                    'show_transaction_history' => true,
                ]),
                'description' => 'Enable/disable credit system',
            ],

            // Chat Credits
            [
                'feature_key' => 'chat_credits_enabled',
                'feature_name' => 'Chat Message Credits',
                'is_enabled' => true,
                'config' => json_encode([
                    'cost_per_message' => 1,
                    'cost_with_image' => 2,
                ]),
                'description' => 'Credit cost for chat messages',
            ],

            // Image Generation Credits
            [
                'feature_key' => 'image_generation_enabled',
                'feature_name' => 'Image Generation Credits',
                'is_enabled' => true,
                'config' => json_encode([
                    'cost_per_image' => 5,
                ]),
                'description' => 'Credit cost for AI image generation',
            ],

            // Voice Input Credits
            [
                'feature_key' => 'voice_input_enabled',
                'feature_name' => 'Voice Input Credits',
                'is_enabled' => true,
                'config' => json_encode([
                    'cost_per_minute' => 2,
                ]),
                'description' => 'Credit cost for voice transcription',
            ],

            // Daily Limits
            [
                'feature_key' => 'daily_limits_enabled',
                'feature_name' => 'Daily Usage Limits',
                'is_enabled' => false, // Disabled by default
                'config' => json_encode([
                    'max_messages_per_day' => 100,
                    'max_images_per_day' => 10,
                    'max_credits_per_day' => 200,
                ]),
                'description' => 'Daily usage limits for free users',
            ],
        ];

        foreach ($flags as $flag) {
            $exists = DB::table('feature_flags')
                ->where('feature_key', $flag['feature_key'])
                ->exists();

            $flag['created_at'] = now();
            $flag['updated_at'] = now();

            if ($exists) {
                DB::table('feature_flags')
                    ->where('feature_key', $flag['feature_key'])
                    ->update($flag);
            } else {
                DB::table('feature_flags')->insert($flag);
            }
        }

        $this->command->info('✅ Feature flags seeded successfully!');
    }
}

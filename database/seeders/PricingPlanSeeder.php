<?php

namespace Database\Seeders;

use App\Models\PricingPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PricingPlanSeeder extends Seeder
{
    /**
     * OFFICIAL PRICING - DO NOT CHANGE WITHOUT APPROVAL
     * Free: ₹0 | Lite: ₹79/mo, ₹649/yr | Pro: ₹249/mo, ₹1999/yr | Ultimate: ₹799/mo, ₹6499/yr
     *
     * NOTE: This is a LEGACY seeder. Use UserPlanSeeder or SubscriptionPlansSeeder instead.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0.00,
                'billing_period' => 'month',
                'description' => 'Padhai Shuru Karo',
                'features' => [
                    '10 AI Chat Messages / Day',
                    '3 Scan & Solve / Day',
                    '2 Whiteboard Videos / Month',
                    'Ad-Supported',
                ],
                'order' => 1,
                'is_active' => true,
                'message_tokens' => 0,
                'image_credits' => 0,
            ],
            [
                'name' => 'Lite',
                'slug' => 'lite',
                'price' => 79.00,
                'billing_period' => 'month',
                'description' => 'Regular Padhai',
                'features' => [
                    'Unlimited AI Chat',
                    '10 Scan & Solve / Day',
                    '5 Whiteboard Videos / Month',
                    'No Ads',
                ],
                'order' => 2,
                'is_active' => true,
                'message_tokens' => 50000,
                'image_credits' => 10,
                'can_use_gpt4' => true,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 249.00,
                'billing_period' => 'month',
                'description' => 'Serious Padhai',
                'features' => [
                    'Unlimited AI Chat',
                    '30 Scan & Solve / Day',
                    '15 Whiteboard Videos / Month',
                    'Priority Processing',
                    'HD Video Quality',
                ],
                'order' => 3,
                'is_active' => true,
                'message_tokens' => 200000,
                'image_credits' => 50,
                'can_use_gpt4' => true,
            ],
            [
                'name' => 'Ultimate',
                'slug' => 'ultimate',
                'price' => 799.00,
                'billing_period' => 'month',
                'description' => 'Topper Mode',
                'features' => [
                    'Everything Unlimited',
                    '100 Scan & Solve / Day',
                    '30 Whiteboard Videos / Month',
                    'Priority Processing',
                    'HD Video Quality',
                    '5 Devices',
                    'Advanced Analytics',
                ],
                'order' => 4,
                'is_active' => true,
                'unlimited_credits' => true,
                'can_use_gpt4' => true,
                'can_use_claude' => true,
            ],
        ];

        foreach ($plans as $plan) {
            PricingPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('✅ Pricing plans seeded!');
        $this->command->info('   Free: ₹0 | Lite: ₹79/mo | Pro: ₹249/mo | Ultimate: ₹799/mo');
    }
}

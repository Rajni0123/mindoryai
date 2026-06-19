<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PricingPlansSeeder extends Seeder
{
    /**
     * OFFICIAL PRICING - DO NOT CHANGE WITHOUT APPROVAL
     * Lite: ₹79/mo, ₹649/yr | Pro: ₹249/mo, ₹1999/yr | Ultimate: ₹799/mo, ₹6499/yr
     *
     * NOTE: This is a LEGACY seeder. Use UserPlanSeeder or SubscriptionPlansSeeder instead.
     * No free plan — paid subscriptions only.
     */
    public function run(): void
    {
        DB::table('pricing_plans')->where('slug', 'free')->delete();

        $plans = [
            [
                'name' => 'Lite',
                'slug' => 'lite',
                'description' => 'Regular Padhai',
                'price' => 79.00,
                'yearly_price' => 649.00,
                'period' => 'monthly',
                'credits_per_month' => 500,
                'unlimited_credits' => false,
                'max_messages_per_day' => null,
                'max_images_per_day' => 10,
                'is_active' => true,
                'order' => 1,
                'features' => json_encode([
                    'features' => [
                        'Unlimited AI Chat',
                        '10 Scan & Solve / Day',
                        '5 Whiteboard Videos / Month',
                        'No Ads',
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Serious Padhai',
                'price' => 249.00,
                'yearly_price' => 1999.00,
                'period' => 'monthly',
                'credits_per_month' => 0,
                'unlimited_credits' => false,
                'max_messages_per_day' => null,
                'max_images_per_day' => 30,
                'is_active' => true,
                'order' => 2,
                'features' => json_encode([
                    'features' => [
                        'Unlimited AI Chat',
                        '30 Scan & Solve / Day',
                        '15 Whiteboard Videos / Month',
                        'Priority Processing',
                        'HD Video Quality',
                    ],
                    'recommended' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ultimate',
                'slug' => 'ultimate',
                'description' => 'Topper Mode',
                'price' => 799.00,
                'yearly_price' => 6499.00,
                'period' => 'monthly',
                'credits_per_month' => 0,
                'unlimited_credits' => true,
                'max_messages_per_day' => null,
                'max_images_per_day' => null,
                'is_active' => true,
                'order' => 3,
                'features' => json_encode([
                    'features' => [
                        'Everything Unlimited',
                        '100 Scan & Solve / Day',
                        '30 Whiteboard Videos / Month',
                        'Priority Processing',
                        'HD Video Quality',
                        '5 Devices',
                        'Advanced Analytics',
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('pricing_plans')->updateOrInsert(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('✅ Pricing plans seeded successfully!');
        $this->command->info('   - Lite: ₹79/month, ₹649/year');
        $this->command->info('   - Pro: ₹249/month, ₹1999/year');
        $this->command->info('   - Ultimate: ₹799/month, ₹6499/year');
    }
}

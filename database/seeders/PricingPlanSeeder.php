<?php

namespace Database\Seeders;

use App\Models\PricingPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PricingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'price' => 299.00,
                'billing_period' => 'month',
                'description' => 'Perfect for getting started',
                'features' => [
                    'Text Questions',
                    'Image Analysis',
                    '50 requests/day',
                    'Email Support',
                ],
                'order' => 1,
                'is_active' => true,
                'message_tokens' => 50000,
                'image_credits' => 10,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'price' => 599.00,
                'billing_period' => 'month',
                'description' => 'Most popular choice',
                'features' => [
                    'Everything in Basic',
                    'Unlimited requests',
                    'Priority Support',
                    'Advanced Features',
                ],
                'order' => 2,
                'is_active' => true,
                'message_tokens' => 200000,
                'image_credits' => 50,
                'can_use_gpt4' => true,
            ],
            [
                'name' => 'Lifetime',
                'slug' => 'lifetime',
                'price' => 4999.00,
                'billing_period' => 'once',
                'description' => 'Best value - pay once',
                'features' => [
                    'Everything in Premium',
                    'Lifetime Access',
                    'Premium Support Forever',
                    'All Future Updates',
                ],
                'order' => 3,
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

        $this->command->info('Pricing plans seeded successfully!');
    }
}

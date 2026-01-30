<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PricingPlansSeeder extends Seeder
{
    /**
     * Seed pricing plans with credit-based features
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Perfect for trying out our AI assistant',
                'price' => 0.00,
                'yearly_price' => 0.00,
                'period' => 'monthly',
                'credits_per_month' => 50,
                'unlimited_credits' => false,
                'max_messages_per_day' => 20,
                'max_images_per_day' => 5,
                'is_active' => true,
                'order' => 1,
                'features' => json_encode([
                    'features' => [
                        '50 credits per month',
                        '20 messages per day',
                        '5 image generations per day',
                        'Access to GPT-4o Mini',
                        'Email support',
                        'Basic features',
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For regular users who need more',
                'price' => 199.00,
                'yearly_price' => 1999.00, // ~17% discount
                'period' => 'monthly',
                'credits_per_month' => 500,
                'unlimited_credits' => false,
                'max_messages_per_day' => 200,
                'max_images_per_day' => 50,
                'is_active' => true,
                'order' => 2,
                'features' => json_encode([
                    'features' => [
                        '500 credits per month',
                        '200 messages per day',
                        '50 image generations per day',
                        'Access to all AI models',
                        'Priority support',
                        'Voice input',
                        'File upload',
                        'Chat history export',
                    ],
                    'popular' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Unlimited access for power users',
                'price' => 499.00,
                'yearly_price' => 4999.00, // ~17% discount
                'period' => 'monthly',
                'credits_per_month' => 0, // Unlimited
                'unlimited_credits' => true,
                'max_messages_per_day' => null, // Unlimited
                'max_images_per_day' => null, // Unlimited
                'is_active' => true,
                'order' => 3,
                'features' => json_encode([
                    'features' => [
                        '✨ Unlimited credits',
                        '✨ Unlimited messages',
                        '✨ Unlimited image generations',
                        'Access to all AI models',
                        'Priority support (24/7)',
                        'Voice input',
                        'File upload',
                        'Chat history export',
                        'API access',
                        'Custom AI instructions',
                        'Early access to new features',
                    ],
                    'recommended' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Custom solutions for teams and businesses',
                'price' => 2999.00,
                'yearly_price' => 29999.00,
                'period' => 'monthly',
                'credits_per_month' => 0, // Unlimited
                'unlimited_credits' => true,
                'max_messages_per_day' => null, // Unlimited
                'max_images_per_day' => null, // Unlimited
                'is_active' => true,
                'order' => 4,
                'features' => json_encode([
                    'features' => [
                        'Everything in Premium',
                        'Multi-user support (up to 50 users)',
                        'Team collaboration features',
                        'Custom AI model fine-tuning',
                        'Dedicated account manager',
                        'SLA guarantee',
                        'Custom integrations',
                        'Advanced analytics',
                        'White-label option',
                    ],
                    'contact' => true,
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
        $this->command->info('   - Free: 50 credits/month (₹0)');
        $this->command->info('   - Pro: 500 credits/month (₹199/month, ₹1999/year)');
        $this->command->info('   - Premium: Unlimited (₹499/month, ₹4999/year)');
        $this->command->info('   - Enterprise: Unlimited (₹2999/month, ₹29999/year)');
    }
}

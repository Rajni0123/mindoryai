<?php

namespace Database\Seeders;

use App\Models\UserPlan;
use Illuminate\Database\Seeder;

class UserPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Plans matching website: Free, Weekly, Starter, Pro (all monthly billing, no yearly)
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Start Your Learning Journey',
                'price' => 0,
                'billing_period' => 'month',
                'message_tokens' => 3000,      // ~3 quizzes per day
                'image_credits' => 0,
                'api_calls' => 90,             // 3 quizzes x 30 days
                'image_uploads' => 0,
                'can_use_gpt4' => false,
                'can_use_claude' => false,
                'can_use_deepseek' => true,
                'can_use_grok' => false,
                'validity_days' => 30,
                'is_active' => true,
                'order' => 1,
                'features' => json_encode([
                    '3 AI Quizzes per day',
                    'Simple Text-to-Quiz',
                    'Basic Performance Stats',
                    'Community Support',
                    'Ad-Supported Experience'
                ]),
            ],
            [
                'name' => 'Weekly',
                'slug' => 'weekly',
                'description' => 'Quick Exam Boost - Perfect for Last Week',
                'price' => 29,
                'billing_period' => 'week',
                'message_tokens' => 15000,     // 15 quizzes
                'image_credits' => 15,
                'api_calls' => 15,
                'image_uploads' => 15,
                'can_use_gpt4' => true,
                'can_use_claude' => false,
                'can_use_deepseek' => true,
                'can_use_grok' => false,
                'validity_days' => 7,
                'is_active' => true,
                'order' => 2,
                'features' => json_encode([
                    '15 AI Quizzes / Week',
                    'Photo-to-Quiz',
                    'Ad-Free Experience',
                    'Save Quiz History',
                    'Perfect for Last Minute Prep'
                ]),
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for Exam Month - The Exam Booster',
                'price' => 49,
                'billing_period' => 'month',
                'message_tokens' => 60000,     // 60 quizzes
                'image_credits' => 60,
                'api_calls' => 60,
                'image_uploads' => 60,
                'can_use_gpt4' => true,
                'can_use_claude' => false,
                'can_use_deepseek' => true,
                'can_use_grok' => false,
                'validity_days' => 30,
                'is_active' => true,
                'order' => 3,
                'features' => json_encode([
                    '60 AI Quizzes / Month',
                    'Photo-to-Quiz (Image Upload)',
                    'Ad-Free Experience',
                    'Save Quiz History',
                    'PDF Export for Revision'
                ]),
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Master Any Subject with AI - The Topper\'s Secret',
                'price' => 199,
                'billing_period' => 'month',
                'message_tokens' => 500000,    // 500 quizzes
                'image_credits' => 500,
                'api_calls' => 500,
                'image_uploads' => 500,
                'can_use_gpt4' => true,
                'can_use_claude' => true,
                'can_use_deepseek' => true,
                'can_use_grok' => true,
                'validity_days' => 30,
                'is_active' => true,
                'order' => 4,
                'features' => json_encode([
                    '500 AI Quizzes / Month',
                    'AI Doubt Solver (Chat with Quiz)',
                    'Detailed Step-by-Step Explanations',
                    'Priority Server Speed (No Waiting)',
                    'Multi-Language Support (Hindi/English)',
                    'VIP Community Access'
                ]),
            ],
        ];

        // Delete old plans that are not in the new list
        UserPlan::whereNotIn('slug', ['free', 'weekly', 'starter', 'pro'])->delete();

        foreach ($plans as $plan) {
            UserPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}

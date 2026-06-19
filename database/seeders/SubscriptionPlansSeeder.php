<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\SeasonalPack;

class SubscriptionPlansSeeder extends Seeder
{
    public function run(): void
    {
        // Create plans
        $plans = $this->createPlans();

        // Create plan features
        $this->createPlanFeatures($plans);

        // Create seasonal packs
        $this->createSeasonalPacks($plans);
    }

    /**
     * OFFICIAL PRICING - DO NOT CHANGE WITHOUT APPROVAL
     * Lite: ₹79/mo, ₹649/yr | Pro: ₹249/mo, ₹1999/yr | Ultimate: ₹799/mo, ₹6499/yr
     * No free plan — paid subscriptions only.
     */
    private function createPlans(): array
    {
        Plan::where('slug', 'free')->delete();

        $plansData = [
            [
                'name' => 'Lite',
                'slug' => 'lite',
                'description' => 'Regular Padhai',
                'price_monthly' => 79,
                'price_annual' => 649,
                'is_active' => true,
                'is_popular' => false,
                'is_recommended' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Serious Padhai',
                'price_monthly' => 249,
                'price_annual' => 1999,
                'is_active' => true,
                'is_popular' => false,
                'is_recommended' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Ultimate',
                'slug' => 'ultimate',
                'description' => 'Topper Mode',
                'price_monthly' => 799,
                'price_annual' => 6499,
                'is_active' => true,
                'is_popular' => false,
                'is_recommended' => false,
                'sort_order' => 3,
            ],
        ];

        $plans = [];
        foreach ($plansData as $planData) {
            $plans[$planData['slug']] = Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        return $plans;
    }

    private function createPlanFeatures(array $plans): void
    {
        // Feature limits per plan matching the image exactly
        // Format: [feature_slug => [lite, pro, ultimate]]
        // Where each value is [limit_type, limit_value, extra_info]
        $features = [
            // AI & SOLVING
            'ai_doubt' => [
                'lite' => ['unlimited', -1, null],
                'pro' => ['unlimited', -1, null],
                'ultimate' => ['unlimited', -1, null],
            ],
            'scan_solve' => [
                'free' => ['daily', 2, null],
                'lite' => ['daily', 10, null],
                'pro' => ['daily', 30, null],
                'ultimate' => ['daily', 100, null],
            ],
            'reasoning' => [
                'free' => ['daily', 2, null],
                'lite' => ['daily', 5, null],
                'pro' => ['unlimited', -1, null],
                'ultimate' => ['unlimited', -1, null],
            ],

            // QUIZ & PRACTICE
            'quiz' => [
                'free' => ['daily', 3, null],
                'lite' => ['daily', 10, null],
                'pro' => ['unlimited', -1, null],
                'ultimate' => ['unlimited', -1, null],
            ],
            'topic_quiz_gen' => [
                'free' => ['boolean', 0, null],
                'lite' => ['daily', 5, null],
                'pro' => ['unlimited', -1, null],
                'ultimate' => ['unlimited', -1, null],
            ],

            // CONTENT & MEDIA
            'whiteboard_video' => [
                'free' => ['monthly', 2, ['quality' => 'SD', 'watermark' => true]],
                'lite' => ['monthly', 10, ['quality' => 'SD', 'watermark' => false]],
                'pro' => ['monthly', 30, ['quality' => 'HD', 'watermark' => false]],
                'ultimate' => ['monthly', 50, ['quality' => 'HD', 'watermark' => false]],
            ],
            'notes_diagrams' => [
                'free' => ['boolean', 0, null],
                'lite' => ['boolean', 0, null],
                'pro' => ['boolean', 0, null],
                'ultimate' => ['daily', 30, null],
            ],

            // SOCIAL & ANALYTICS
            'leaderboard' => [
                'free' => ['boolean', 1, ['access' => 'view_only']],
                'lite' => ['boolean', 1, ['access' => 'view_only']],
                'pro' => ['boolean', 2, ['access' => 'full']],
                'ultimate' => ['boolean', 2, ['access' => 'full']],
            ],
            'student_chat' => [
                'free' => ['boolean', 0, null],
                'lite' => ['boolean', 0, null],
                'pro' => ['daily', 5, null],
                'ultimate' => ['unlimited', -1, null],
            ],

            // OTHER
            'ads' => [
                'free' => ['boolean', 1, null],  // 1 = show ads
                'lite' => ['boolean', 0, null],  // 0 = no ads
                'pro' => ['boolean', 0, null],
                'ultimate' => ['boolean', 0, null],
            ],
            'support' => [
                'free' => ['boolean', 0, null],
                'lite' => ['boolean', 1, ['type' => 'email']],
                'pro' => ['boolean', 2, ['type' => 'priority']],
                'ultimate' => ['boolean', 3, ['type' => 'dedicated']],
            ],

            // Additional features (for API/backend use)
            'exam_prep' => [
                'free' => ['boolean', 1, ['access' => '1_subject']],
                'lite' => ['unlimited', -1, ['access' => 'all_subjects']],
                'pro' => ['unlimited', -1, ['access' => 'all_subjects', 'mock_tests' => true]],
                'ultimate' => ['unlimited', -1, ['access' => 'all_subjects', 'mock_tests' => true, 'ai_analysis' => true]],
            ],
            'daily_challenge' => [
                'free' => ['unlimited', -1, null],
                'lite' => ['unlimited', -1, null],
                'pro' => ['unlimited', -1, null],
                'ultimate' => ['unlimited', -1, null],
            ],
            'pdf_analysis_pages' => [
                'free' => ['daily', 2, null],
                'lite' => ['daily', 10, null],
                'pro' => ['daily', 30, null],
                'ultimate' => ['daily', 100, null],
            ],
            'chat_history_days' => [
                'free' => ['boolean', 0, null],
                'lite' => ['boolean', 7, null],
                'pro' => ['boolean', 30, null],
                'ultimate' => ['unlimited', -1, null],
            ],
            'analytics' => [
                'free' => ['boolean', 0, null],
                'lite' => ['boolean', 1, ['level' => 'basic']],
                'pro' => ['boolean', 2, ['level' => 'detailed']],
                'ultimate' => ['boolean', 3, ['level' => 'advanced', 'parent_dashboard' => true]],
            ],
            'video_quality' => [
                'free' => ['boolean', 1, ['quality' => 'SD']],
                'lite' => ['boolean', 1, ['quality' => 'SD']],
                'pro' => ['boolean', 2, ['quality' => 'HD']],
                'ultimate' => ['boolean', 2, ['quality' => 'HD']],
            ],
            'video_watermark' => [
                'free' => ['boolean', 1, null],  // 1 = has watermark
                'lite' => ['boolean', 0, null],  // 0 = no watermark
                'pro' => ['boolean', 0, null],
                'ultimate' => ['boolean', 0, null],
            ],
            'study_recommendations' => [
                'free' => ['boolean', 0, null],
                'lite' => ['boolean', 0, null],
                'pro' => ['boolean', 1, null],
                'ultimate' => ['boolean', 1, null],
            ],
            'weekly_reports' => [
                'free' => ['boolean', 0, null],
                'lite' => ['boolean', 0, null],
                'pro' => ['boolean', 0, null],
                'ultimate' => ['boolean', 1, null],
            ],
            'parent_dashboard' => [
                'free' => ['boolean', 0, null],
                'lite' => ['boolean', 0, null],
                'pro' => ['boolean', 0, null],
                'ultimate' => ['boolean', 1, null],
            ],
            'custom_study_schedule' => [
                'free' => ['boolean', 0, null],
                'lite' => ['boolean', 0, null],
                'pro' => ['boolean', 0, null],
                'ultimate' => ['boolean', 1, null],
            ],
            'download_chats_pdf' => [
                'free' => ['boolean', 0, null],
                'lite' => ['boolean', 0, null],
                'pro' => ['boolean', 1, null],
                'ultimate' => ['boolean', 1, null],
            ],
        ];

        foreach ($features as $featureSlug => $planLimits) {
            foreach ($planLimits as $planSlug => $limits) {
                if ($planSlug === 'free' || ! isset($plans[$planSlug])) {
                    continue;
                }

                PlanFeature::updateOrCreate(
                    [
                        'plan_id' => $plans[$planSlug]->id,
                        'feature_slug' => $featureSlug,
                    ],
                    [
                        'limit_type' => $limits[0],
                        'limit_value' => $limits[1],
                        'extra_info' => $limits[2],
                    ]
                );
            }
        }
    }

    private function createSeasonalPacks(array $plans): void
    {
        $packs = [
            [
                'name' => 'First Month Trial',
                'slug' => 'first-month-trial',
                'price' => 29,
                'duration_days' => 30,
                'plan_id' => $plans['lite']->id,
                'description' => 'Pehli baar try karo, sirf ₹29 me!',
                'tag' => 'NEW USERS',
                'icon' => '🎁',
                'is_active' => true,
            ],
            [
                'name' => 'Board Exam Pack',
                'slug' => 'board-exam-pack',
                'price' => 99,
                'duration_days' => 30,
                'plan_id' => $plans['lite']->id,
                'description' => 'Class 10 & 12 Board prep ke liye perfect',
                'tag' => 'CLASS 10 & 12',
                'icon' => '📝',
                'is_active' => true,
            ],
            [
                'name' => 'JEE Crash Pack',
                'slug' => 'jee-crash-pack',
                'price' => 149,
                'duration_days' => 30,
                'plan_id' => $plans['pro']->id,
                'description' => 'JEE ke last 30 din — full power mode',
                'tag' => 'JEE SPECIAL',
                'icon' => '⚡',
                'is_active' => true,
            ],
            [
                'name' => 'NEET Booster',
                'slug' => 'neet-booster',
                'price' => 149,
                'duration_days' => 30,
                'plan_id' => $plans['pro']->id,
                'description' => 'NEET aspirants ke liye 30 din Pro access',
                'tag' => 'NEET SPECIAL',
                'icon' => '🔬',
                'is_active' => true,
            ],
            [
                'name' => 'Summer Study Pack',
                'slug' => 'summer-study-pack',
                'price' => 399,
                'duration_days' => 90,
                'plan_id' => $plans['pro']->id,
                'description' => 'Summer vacation me aage niklo sabse',
                'tag' => '90 DAYS',
                'icon' => '☀️',
                'is_active' => true,
            ],
            [
                'name' => 'Topper Pack',
                'slug' => 'topper-pack',
                'price' => 499,
                'duration_days' => 90,
                'plan_id' => $plans['ultimate']->id,
                'description' => '3 mahine Ultimate — sabse best deal',
                'tag' => 'BEST DEAL',
                'icon' => '👑',
                'is_active' => true,
            ],
        ];

        foreach ($packs as $packData) {
            SeasonalPack::updateOrCreate(
                ['slug' => $packData['slug']],
                $packData
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\UserPlan;
use Illuminate\Database\Seeder;

class UserPlanSeeder extends Seeder
{
    /**
     * OFFICIAL PRICING PLANS - DO NOT CHANGE WITHOUT APPROVAL
     * Lite: ₹79/mo, ₹649/yr | Pro: ₹249/mo, ₹1999/yr | Ultimate: ₹799/mo, ₹6499/yr
     * NOTE: No free plan — users must subscribe to a paid plan.
     */
    public function run(): void
    {
        // Remove legacy free plan if it exists
        UserPlan::where('slug', 'free')->delete();

        $plans = [
            // ── LITE – ₹79/month, ₹649/year ──
            [
                'name' => 'Lite',
                'slug' => 'lite',
                'description' => 'Regular Padhai',
                'price' => 79,
                'billing_period' => 'month',
                'billing_description' => '₹79 / month',
                'validity_days' => 30,
                'message_tokens' => 0,
                'image_credits' => 0,
                'api_calls' => 0,
                'image_uploads' => 0,
                'can_use_gpt4' => true,
                'can_use_claude' => false,
                'can_use_deepseek' => false,
                'can_use_grok' => false,
                'unlimited_credits' => false,
                'is_active' => true,
                'order' => 1,
                'features' => json_encode([
                    'popular' => false,
                    'recommended' => false,
                    'price_annual' => 649,
                    'daily_limits' => [
                        'chat_messages_per_day' => -1,
                        'video_quiz_per_day' => 5,
                        'whiteboard_videos_per_month' => 5,
                        'topic_quiz_per_day' => 10,
                        'exam_prep_per_day' => 3,
                        'scan_solve_per_day' => 5,
                        'pdf_uploads_per_month' => 5,
                    ],
                    'max_video_length_seconds' => 60,
                    'frames_per_video' => 8,
                    'pages_per_pdf' => 15,
                    'history_days' => 7,
                    'watermark' => false,
                    'ads' => false,
                    'priority_queue' => false,
                    'video_quality' => 'sd',
                    'device_limit' => 2,
                    'analytics_tier' => 'basic',
                    'features_list' => [
                        'Unlimited AI Chat',
                        '5 Video Quizzes / Day',
                        '5 Whiteboard Videos / Month',
                        '10 Topic Quizzes / Day',
                        '3 Exam Prep / Day',
                        '5 Scan & Solve / Day',
                        '5 PDF Uploads / Month (15 pages)',
                        '7 Days History',
                        'No Watermark',
                        'Ad-Free Experience',
                    ],
                ]),
            ],

            // ── PRO – ₹249/month, ₹1999/year ⭐ RECOMMENDED ──
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Serious Padhai',
                'price' => 249,
                'billing_period' => 'month',
                'billing_description' => '₹249 / month',
                'validity_days' => 30,
                'message_tokens' => 0,
                'image_credits' => 0,
                'api_calls' => 0,
                'image_uploads' => 0,
                'can_use_gpt4' => true,
                'can_use_claude' => false,
                'can_use_deepseek' => false,
                'can_use_grok' => false,
                'unlimited_credits' => false,
                'is_active' => true,
                'order' => 2,
                'features' => json_encode([
                    'popular' => false,
                    'recommended' => true,
                    'price_annual' => 1999,
                    'daily_limits' => [
                        'chat_messages_per_day' => -1,
                        'video_quiz_per_day' => 15,
                        'whiteboard_videos_per_month' => 15,
                        'topic_quiz_per_day' => -1,
                        'exam_prep_per_day' => -1,
                        'scan_solve_per_day' => 30,
                        'pdf_uploads_per_month' => 20,
                    ],
                    'max_video_length_seconds' => 180,
                    'frames_per_video' => 15,
                    'pages_per_pdf' => 30,
                    'history_days' => 30,
                    'watermark' => false,
                    'ads' => false,
                    'priority_queue' => true,
                    'video_quality' => 'hd',
                    'device_limit' => 3,
                    'analytics_tier' => 'standard',
                    'features_list' => [
                        'Unlimited AI Chat',
                        '15 Video Quizzes / Day',
                        '15 Whiteboard Videos / Month',
                        'Unlimited Topic Quizzes',
                        'Unlimited Exam Prep',
                        '30 Scan & Solve / Day',
                        '20 PDF Uploads / Month (30 pages)',
                        '30 Days History',
                        'Priority Processing',
                        'HD Video Quality',
                        'No Watermark',
                        'Ad-Free Experience',
                    ],
                ]),
            ],

            // ── ULTIMATE – ₹799/month, ₹6499/year ──
            [
                'name' => 'Ultimate',
                'slug' => 'ultimate',
                'description' => 'Topper Mode',
                'price' => 799,
                'billing_period' => 'month',
                'billing_description' => '₹799 / month',
                'validity_days' => 30,
                'message_tokens' => 0,
                'image_credits' => 0,
                'api_calls' => 0,
                'image_uploads' => 0,
                'can_use_gpt4' => true,
                'can_use_claude' => false,
                'can_use_deepseek' => false,
                'can_use_grok' => false,
                'unlimited_credits' => false,
                'is_active' => true,
                'order' => 3,
                'features' => json_encode([
                    'popular' => false,
                    'recommended' => false,
                    'price_annual' => 6499,
                    'daily_limits' => [
                        'chat_messages_per_day' => -1,
                        'video_quiz_per_day' => -1,
                        'whiteboard_videos_per_month' => 30,
                        'topic_quiz_per_day' => -1,
                        'exam_prep_per_day' => -1,
                        'scan_solve_per_day' => 100,
                        'pdf_uploads_per_month' => 50,
                    ],
                    'max_video_length_seconds' => 600,
                    'frames_per_video' => 40,
                    'pages_per_pdf' => 50,
                    'history_days' => -1,
                    'watermark' => false,
                    'ads' => false,
                    'priority_queue' => true,
                    'video_quality' => 'hd',
                    'device_limit' => 5,
                    'analytics_tier' => 'advanced',
                    'features_list' => [
                        'Unlimited AI Chat',
                        'Unlimited Video Quizzes',
                        '30 Whiteboard Videos / Month',
                        'Unlimited Topic Quizzes',
                        'Unlimited Exam Prep',
                        '100 Scan & Solve / Day',
                        '50 PDF Uploads / Month (50 pages)',
                        'Unlimited History',
                        'Priority Processing',
                        'HD Video Quality',
                        'Up to 5 Devices',
                        'Advanced Analytics',
                        'No Watermark',
                        'Ad-Free Experience',
                    ],
                ]),
            ],
        ];

        foreach ($plans as $plan) {
            UserPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}

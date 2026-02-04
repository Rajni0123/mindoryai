<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Delete all old plans
        DB::table('user_plans')->delete();

        $now = now();

        // ════════════════════════════════════════════════════════════════
        //  BLINKSTUDY AI — OPTIMIZED PRICING FOR ₹10 CRORE REVENUE 2026
        // ════════════════════════════════════════════════════════════════
        //
        //  Revenue Target: ₹10,00,00,000 (11 months: Feb–Dec 2026)
        //  Revenue Mix: Subscriptions 60% + Ads 25% + Referrals/IAP 15%
        //  Target Users by Dec: 12,00,000 total | 72,000 paying (6%)
        //  Weighted ARPU: ₹332/month
        //  Profit Margin Target: 50%+
        //
        // ════════════════════════════════════════════════════════════════

        // ──────────────────────────────────────────────
        // PLAN 1: FREE – ₹0/month
        // Cost/user: ~₹1-3/month (Gemini Flash only)
        // Revenue: Ad-supported → ₹0.50-1.00/DAU/day
        // Target: ₹2.5 crore/year from 2.85L DAU ads
        // ──────────────────────────────────────────────
        DB::table('user_plans')->insert([
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Start Your AI Learning Journey — Free Forever',
            'price' => 0,
            'billing_period' => 'month',
            'billing_description' => 'Free Forever',
            'validity_days' => 0,
            'message_tokens' => 0,
            'image_credits' => 0,
            'api_calls' => 0,
            'image_uploads' => 0,
            'can_use_gpt4' => false,
            'can_use_claude' => false,
            'can_use_deepseek' => false,
            'can_use_grok' => false,
            'unlimited_credits' => false,
            'is_active' => true,
            'order' => 1,
            'features' => json_encode([
                'popular' => false,
                'recommended' => false,
                'savings' => null,
                'daily_limits' => [
                    'chat_messages_per_day' => 10,
                    'video_quiz_per_day' => 2,
                    'whiteboard_videos_per_month' => 2,
                    'topic_quiz_per_day' => 3,
                    'exam_prep_per_day' => 1,
                    'scan_solve_per_day' => 3,
                    'pdf_uploads_per_month' => 1,
                ],
                'max_video_length_seconds' => 30,
                'frames_per_video' => 5,
                'pages_per_pdf' => 5,
                'history_days' => 3,
                'watermark' => true,
                'ads' => true,
                'priority_queue' => false,
                'video_quality' => 'sd',
                'device_limit' => 1,
                'analytics_tier' => 'basic',
                'features_list' => [
                    '10 AI Chat Messages / Day',
                    '2 Video Quizzes / Day',
                    '2 Whiteboard Videos / Month',
                    '3 Topic Quizzes / Day',
                    '1 Exam Prep Session / Day',
                    '3 Scan & Solve / Day',
                    '1 PDF Upload / Month (5 pages)',
                    '3 Days History',
                    'Gemini Flash AI',
                    'Watermark on Videos',
                    'Ad-Supported',
                ],
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ──────────────────────────────────────────────
        // PLAN 2: STARTER – ₹149/month
        // Cost/user: ~₹20-35/month | Margin: ~76-86%
        // Key upgrade: Unlimited chat, GPT-4o Mini, Ad-free
        // Revenue target: 35% of paying users
        // ──────────────────────────────────────────────
        DB::table('user_plans')->insert([
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Supercharge Your Exam Preparation',
            'price' => 149,
            'billing_period' => 'month',
            'billing_description' => '₹149 / month',
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
                'recommended' => false,
                'savings' => null,
                'daily_limits' => [
                    'chat_messages_per_day' => -1,
                    'video_quiz_per_day' => 5,
                    'whiteboard_videos_per_month' => 5,
                    'topic_quiz_per_day' => 10,
                    'exam_prep_per_day' => 3,
                    'scan_solve_per_day' => 10,
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
                    'Gemini Flash + GPT-4o Mini',
                    '5 Video Quizzes / Day',
                    '5 Whiteboard Videos / Month',
                    '10 Topic Quizzes / Day',
                    '3 Exam Prep Sessions / Day',
                    '10 Scan & Solve / Day',
                    '5 PDF Uploads / Month (15 pages)',
                    '7 Days History',
                    'No Watermark',
                    'Ad-Free Experience',
                ],
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ──────────────────────────────────────────────
        // PLAN 3: PRO – ₹399/month ⭐ MOST POPULAR
        // Cost/user: ~₹60-100/month | Margin: ~75-85%
        // Key upgrade: HD video, unlimited quizzes, priority
        // Revenue target: 35% of paying users
        // ──────────────────────────────────────────────
        DB::table('user_plans')->insert([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'The Topper\'s Secret Weapon — Most Popular',
            'price' => 399,
            'billing_period' => 'month',
            'billing_description' => '₹399 / month',
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
                'popular' => true,
                'recommended' => true,
                'savings' => null,
                'daily_limits' => [
                    'chat_messages_per_day' => -1,
                    'video_quiz_per_day' => 20,
                    'whiteboard_videos_per_month' => 20,
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
                    'Gemini Flash + GPT-4o Mini',
                    '20 Video Quizzes / Day',
                    '20 Whiteboard Videos / Month',
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
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ──────────────────────────────────────────────
        // PLAN 4: ULTIMATE – ₹899/month
        // Cost/user: ~₹120-200/month | Margin: ~77-87%
        // Key upgrade: Unlimited everything, advanced analytics
        // Revenue target: 10% of paying users
        // ──────────────────────────────────────────────
        DB::table('user_plans')->insert([
            'name' => 'Ultimate',
            'slug' => 'ultimate',
            'description' => 'Unlimited Power for Serious Achievers',
            'price' => 899,
            'billing_period' => 'month',
            'billing_description' => '₹899 / month',
            'validity_days' => 30,
            'message_tokens' => 0,
            'image_credits' => 0,
            'api_calls' => 0,
            'image_uploads' => 0,
            'can_use_gpt4' => true,
            'can_use_claude' => false,
            'can_use_deepseek' => false,
            'can_use_grok' => false,
            'unlimited_credits' => true,
            'is_active' => true,
            'order' => 4,
            'features' => json_encode([
                'popular' => false,
                'recommended' => false,
                'savings' => null,
                'daily_limits' => [
                    'chat_messages_per_day' => -1,
                    'video_quiz_per_day' => -1,
                    'whiteboard_videos_per_month' => 50,
                    'topic_quiz_per_day' => -1,
                    'exam_prep_per_day' => -1,
                    'scan_solve_per_day' => -1,
                    'pdf_uploads_per_month' => 100,
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
                    'Gemini Flash + GPT-4o Mini',
                    'Unlimited Video Quizzes',
                    '50 Whiteboard Videos / Month',
                    'Unlimited Topic Quizzes',
                    'Unlimited Exam Prep',
                    'Unlimited Scan & Solve',
                    '100 PDF Uploads / Month (50 pages)',
                    'Unlimited History',
                    'Priority Processing',
                    'HD Video Quality',
                    'Up to 5 Devices',
                    'Advanced Analytics',
                    'No Watermark',
                    'Ad-Free Experience',
                ],
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ──────────────────────────────────────────────
        // PLAN 5: ANNUAL PASS – ₹2,999/year
        // Equivalent to ₹250/month (37% savings vs Pro ₹399)
        // Pro-level features + Ultimate perks for committed users
        // Cost/user: ~₹1000-1600/year | Margin: ~47-67%
        // Revenue target: 20% of paying users (upfront cash)
        // ──────────────────────────────────────────────
        DB::table('user_plans')->insert([
            'name' => 'Annual Pass',
            'slug' => 'annual',
            'description' => 'Best Value — Pro Features for 12 Months at 37% Off',
            'price' => 2999,
            'billing_period' => 'year',
            'billing_description' => '₹2,999 / year (Save 37%)',
            'validity_days' => 365,
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
            'order' => 5,
            'features' => json_encode([
                'popular' => false,
                'recommended' => false,
                'savings' => '37%',
                'daily_limits' => [
                    'chat_messages_per_day' => -1,
                    'video_quiz_per_day' => -1,
                    'whiteboard_videos_per_month' => 30,
                    'topic_quiz_per_day' => -1,
                    'exam_prep_per_day' => -1,
                    'scan_solve_per_day' => 50,
                    'pdf_uploads_per_month' => 30,
                ],
                'max_video_length_seconds' => 300,
                'frames_per_video' => 20,
                'pages_per_pdf' => 40,
                'history_days' => -1,
                'watermark' => false,
                'ads' => false,
                'priority_queue' => true,
                'video_quality' => 'hd',
                'device_limit' => 3,
                'analytics_tier' => 'standard',
                'features_list' => [
                    'Everything in Pro Plan',
                    'Unlimited AI Chat',
                    'Gemini Flash + GPT-4o Mini',
                    'Unlimited Video Quizzes',
                    '30 Whiteboard Videos / Month',
                    'Unlimited Topic Quizzes',
                    'Unlimited Exam Prep',
                    '50 Scan & Solve / Day',
                    '30 PDF Uploads / Month (40 pages)',
                    'Unlimited History',
                    'Priority Processing',
                    'HD Video Quality',
                    'No Watermark',
                    'Ad-Free Experience',
                    '37% Savings vs Monthly Pro',
                ],
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('user_plans')
            ->whereIn('slug', ['free', 'starter', 'pro', 'ultimate', 'annual'])
            ->delete();
    }
};

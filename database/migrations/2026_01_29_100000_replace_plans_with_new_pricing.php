<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Delete all old plans (they are being replaced)
        DB::table('user_plans')->delete();

        // Insert new plans
        $now = now();

        // FREE – ₹0
        DB::table('user_plans')->insert([
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Start Your Learning Journey',
            'price' => 0,
            'billing_period' => 'month',
            'validity_days' => 30,
            'message_tokens' => 0,
            'image_credits' => 0,
            'api_calls' => 0,
            'image_uploads' => 0,
            'can_use_gpt4' => false,
            'can_use_claude' => false,
            'can_use_deepseek' => true,
            'can_use_grok' => false,
            'unlimited_credits' => false,
            'is_active' => true,
            'order' => 1,
            'features' => json_encode([
                'daily_limits' => [
                    'video_quiz_per_day' => 2,
                    'whiteboard_videos_per_month' => 3,
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
                'features_list' => [
                    '2 Video Quizzes / Day',
                    '3 Whiteboard Videos / Month',
                    '3 Topic Quizzes / Day',
                    '1 Exam Prep / Day',
                    '3 Scan to Solve / Day',
                    '1 PDF Upload / Month (5 pages)',
                    '3 Days History',
                    'Watermark on Videos',
                    'Ad-Supported',
                ],
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // BASIC – ₹149/month
        DB::table('user_plans')->insert([
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => 'Boost Your Learning Power',
            'price' => 149,
            'billing_period' => 'month',
            'validity_days' => 30,
            'message_tokens' => 0,
            'image_credits' => 0,
            'api_calls' => 0,
            'image_uploads' => 0,
            'can_use_gpt4' => true,
            'can_use_claude' => false,
            'can_use_deepseek' => true,
            'can_use_grok' => true,
            'unlimited_credits' => false,
            'is_active' => true,
            'order' => 2,
            'features' => json_encode([
                'daily_limits' => [
                    'video_quiz_per_day' => 10,
                    'whiteboard_videos_per_day' => 5,
                    'topic_quiz_per_day' => 15,
                    'exam_prep_per_day' => 5,
                    'scan_solve_per_day' => 15,
                    'pdf_uploads_per_month' => 10,
                ],
                'max_video_length_seconds' => 120,
                'frames_per_video' => 10,
                'pages_per_pdf' => 20,
                'history_days' => 7,
                'watermark' => false,
                'ads' => false,
                'priority_queue' => false,
                'features_list' => [
                    '10 Video Quizzes / Day',
                    '5 Whiteboard Videos / Day',
                    '15 Topic Quizzes / Day',
                    '5 Exam Prep / Day',
                    '15 Scan to Solve / Day',
                    '10 PDF Uploads / Month (20 pages)',
                    '7 Days History',
                    'No Watermark',
                    'Ad-Free Experience',
                ],
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // PRO – ₹999/month
        DB::table('user_plans')->insert([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Unlimited Learning - The Topper\'s Secret',
            'price' => 999,
            'billing_period' => 'month',
            'validity_days' => 30,
            'message_tokens' => 0,
            'image_credits' => 0,
            'api_calls' => 0,
            'image_uploads' => 0,
            'can_use_gpt4' => true,
            'can_use_claude' => true,
            'can_use_deepseek' => true,
            'can_use_grok' => true,
            'unlimited_credits' => false,
            'is_active' => true,
            'order' => 3,
            'features' => json_encode([
                'daily_limits' => [
                    'video_quiz_per_day' => 20,
                    'whiteboard_videos_per_day' => -1, // unlimited
                    'topic_quiz_per_day' => -1, // unlimited
                    'exam_prep_per_day' => -1, // unlimited
                    'scan_solve_per_day' => 100,
                    'pdf_uploads_per_month' => 150,
                ],
                'max_video_length_seconds' => 600,
                'frames_per_video' => 40,
                'pages_per_pdf' => 100,
                'history_days' => -1, // unlimited
                'watermark' => false,
                'ads' => false,
                'priority_queue' => true,
                'features_list' => [
                    '20 Video Quizzes / Day',
                    'Unlimited Whiteboard Videos',
                    'Unlimited Topic Quizzes',
                    'Unlimited Exam Prep',
                    '100 Scan to Solve / Day',
                    '150 PDF Uploads / Month (100 pages)',
                    'Unlimited History',
                    'Fast Queue (Priority Processing)',
                    'No Watermark',
                    'Ad-Free Experience',
                ],
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Remove new plans
        DB::table('user_plans')->whereIn('slug', ['free', 'basic', 'pro'])
            ->where('price', '>=', 0)
            ->where('created_at', '>=', '2026-01-29 10:00:00')
            ->delete();

        // Reactivate old plans
        DB::table('user_plans')->update(['is_active' => true]);
    }
};

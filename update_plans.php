<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Delete all existing plans
DB::table('user_plans')->delete();
echo "Deleted all old plans\n\n";

$now = now();

// ⭐ LITE - ₹149/mo
DB::table('user_plans')->insert([
    'name' => 'Lite',
    'slug' => 'lite',
    'description' => 'Perfect for regular learners',
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
    'order' => 1,
    'features' => json_encode([
        'icon' => '⭐',
        'popular' => false,
        'daily_limits' => [
            'chat_messages_per_day' => -1,
            'topic_quiz_per_day' => 15,
            'video_quiz_per_day' => 8,
            'scan_solve_per_day' => 25,
            'whiteboard_videos_per_day' => 1,
            'whiteboard_videos_per_month' => 30,
            'pdf_uploads_per_month' => 10,
            'exam_prep_per_day' => 6,
            'offline_downloads_per_month' => 5,
        ],
        'pages_per_pdf' => 20,
        'history_days' => 30,
        'ads' => false,
        'watermark' => false,
        'priority_queue' => false,
        'support' => 'email',
        'parent_report' => false,
        'features_list' => [
            '💬 Unlimited AI Chat',
            '📝 15 Topic Quiz / Day',
            '📹 8 Video Quiz / Day',
            '📸 25 Scan & Solve / Day',
            '🎬 1 WB Video / Day (30/mo)',
            '📄 10 PDF / Month (20 pages)',
            '🎯 6 Exam Prep / Day',
            '🔇 No Ads',
            '🚫 No Watermark',
            '📞 Email Support',
            '📱 5 Offline / Month',
            '⏰ 30 Days History',
        ],
    ]),
    'created_at' => $now,
    'updated_at' => $now,
]);
echo "✅ ⭐ Lite - ₹149/mo\n";

// 💎 PRO - ₹299/mo (POPULAR)
DB::table('user_plans')->insert([
    'name' => 'Pro',
    'slug' => 'pro',
    'description' => 'Most popular for serious students',
    'price' => 299,
    'billing_period' => 'month',
    'billing_description' => '₹299 / month',
    'validity_days' => 30,
    'message_tokens' => 0,
    'image_credits' => 0,
    'api_calls' => 0,
    'image_uploads' => 0,
    'can_use_gpt4' => true,
    'can_use_claude' => true,
    'can_use_deepseek' => false,
    'can_use_grok' => false,
    'unlimited_credits' => false,
    'is_active' => true,
    'order' => 2,
    'features' => json_encode([
        'icon' => '💎',
        'popular' => true,
        'daily_limits' => [
            'chat_messages_per_day' => -1,
            'topic_quiz_per_day' => 40,
            'video_quiz_per_day' => 15,
            'scan_solve_per_day' => 50,
            'whiteboard_videos_per_day' => 1,
            'whiteboard_videos_per_month' => 30,
            'pdf_uploads_per_month' => 25,
            'exam_prep_per_day' => 12,
            'offline_downloads_per_month' => 20,
        ],
        'pages_per_pdf' => 50,
        'history_days' => 90,
        'ads' => false,
        'watermark' => false,
        'priority_queue' => true,
        'priority_level' => 'fast',
        'support' => 'chat',
        'parent_report' => 'weekly',
        'features_list' => [
            '💬 Unlimited AI Chat',
            '📝 40 Topic Quiz / Day',
            '📹 15 Video Quiz / Day',
            '📸 50 Scan & Solve / Day',
            '🎬 1 WB Video / Day (30/mo)',
            '📄 25 PDF / Month (50 pages)',
            '🎯 12 Exam Prep / Day',
            '🔇 No Ads',
            '🚫 No Watermark',
            '⚡ Fast Priority',
            '📞 Chat Support',
            '👨‍👩‍👧 Weekly Parent Report',
            '📱 20 Offline / Month',
            '⏰ 90 Days History',
        ],
    ]),
    'created_at' => $now,
    'updated_at' => $now,
]);
echo "✅ 💎 Pro - ₹299/mo (POPULAR)\n";

// 👑 ULTIMATE - ₹999/mo
DB::table('user_plans')->insert([
    'name' => 'Ultimate',
    'slug' => 'ultimate',
    'description' => 'Maximum power for toppers',
    'price' => 999,
    'billing_period' => 'month',
    'billing_description' => '₹999 / month',
    'validity_days' => 30,
    'message_tokens' => 0,
    'image_credits' => 0,
    'api_calls' => 0,
    'image_uploads' => 0,
    'can_use_gpt4' => true,
    'can_use_claude' => true,
    'can_use_deepseek' => true,
    'can_use_grok' => true,
    'unlimited_credits' => true,
    'is_active' => true,
    'order' => 3,
    'features' => json_encode([
        'icon' => '👑',
        'popular' => false,
        'daily_limits' => [
            'chat_messages_per_day' => -1,
            'topic_quiz_per_day' => -1,
            'video_quiz_per_day' => -1,
            'scan_solve_per_day' => -1,
            'whiteboard_videos_per_day' => -1,
            'whiteboard_videos_per_month' => -1,
            'pdf_uploads_per_month' => -1,
            'exam_prep_per_day' => -1,
            'offline_downloads_per_month' => -1,
        ],
        'pages_per_pdf' => -1,
        'history_days' => -1,
        'ads' => false,
        'watermark' => false,
        'priority_queue' => true,
        'priority_level' => 'instant',
        'support' => 'dedicated',
        'parent_report' => 'realtime',
        'features_list' => [
            '💬 Unlimited AI Chat',
            '📝 Unlimited Topic Quiz',
            '📹 Unlimited Video Quiz',
            '📸 Unlimited Scan & Solve',
            '🎬 Unlimited WB Video',
            '📄 Unlimited PDF Upload',
            '🎯 Unlimited Exam Prep',
            '🔇 No Ads',
            '🚫 No Watermark',
            '⚡ Instant Priority',
            '📞 Dedicated Support',
            '👨‍👩‍👧 Real-time Parent Report',
            '📱 Unlimited Offline',
            '⏰ Unlimited History',
        ],
    ]),
    'created_at' => $now,
    'updated_at' => $now,
]);
echo "✅ 👑 Ultimate - ₹999/mo\n";

echo "\n=== PLANS UPDATED ===\n";
$all = DB::table('user_plans')->orderBy('order')->get();
foreach($all as $p) {
    $f = json_decode($p->features);
    $icon = $f->icon ?? '';
    $popular = $f->popular ? ' ⭐POPULAR' : '';
    echo "{$icon} {$p->name}: ₹{$p->price}/mo{$popular}\n";
}
echo "\nTotal: " . count($all) . " plans\n";

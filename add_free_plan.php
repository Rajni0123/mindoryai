<?php
/**
 * Add FREE Plan with correct limits
 * Run: php add_free_plan.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$now = now();

// Check if FREE plan exists
$exists = DB::table('user_plans')->where('slug', 'free')->first();
if ($exists) {
    echo "FREE plan already exists. Updating...\n";
    DB::table('user_plans')->where('slug', 'free')->delete();
}

// Create FREE plan with correct limits as specified
DB::table('user_plans')->insert([
    'name' => 'Free',
    'slug' => 'free',
    'description' => 'Get started with basic features',
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
    'order' => 0,
    'features' => json_encode([
        'icon' => '🆓',
        'popular' => false,
        'daily_limits' => [
            'chat_messages_per_day' => 50,
            'topic_quiz_per_day' => 3,
            'video_quiz_per_day' => 1,
            'scan_solve_per_day' => 5,
            'whiteboard_videos_per_day' => 0,
            'whiteboard_videos_per_month' => 0,
            'pdf_uploads_per_month' => 1,
            'exam_prep_per_day' => 1,
            'offline_downloads_per_month' => 0,
        ],
        'pages_per_pdf' => 5,
        'history_days' => 3,
        'ads' => true,
        'watermark' => true,
        'priority_queue' => false,
        'support' => 'none',
        'parent_report' => false,
        'features_list' => [
            '💬 50 AI Chat / Day',
            '📝 3 Topic Quiz / Day',
            '📹 1 Video Quiz / Day',
            '📸 5 Scan & Solve / Day',
            '🎬 No WB Video',
            '📄 1 PDF / Month (5 pages)',
            '🎯 1 Exam Prep / Day',
            '❌ Ads Shown',
            '💧 Watermark on Videos',
        ],
    ]),
    'created_at' => $now,
    'updated_at' => $now,
]);

echo "✅ FREE plan created with correct limits:\n";
echo "   - Chat: 50/day\n";
echo "   - Topic Quiz: 3/day\n";
echo "   - Video Quiz: 1/day\n";
echo "   - Scan & Solve: 5/day\n";
echo "   - WB Video: 0 (disabled)\n";
echo "   - PDF: 1/month (5 pages)\n";
echo "   - Exam Prep: 1/day\n";
echo "   - Ads: YES\n";
echo "   - Watermark: YES\n";

echo "\n=== ALL PLANS ===\n";
$plans = DB::table('user_plans')->orderBy('order')->get();
foreach ($plans as $p) {
    $f = json_decode($p->features);
    $icon = $f->icon ?? '';
    echo "{$icon} {$p->name}: ₹{$p->price}\n";
}

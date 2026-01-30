<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_usage_limits', function (Blueprint $table) {
            // New feature-specific columns (some may already exist from previous migration)
            if (!Schema::hasColumn('daily_usage_limits', 'video_quiz_used')) {
                $table->integer('video_quiz_used')->default(0)->after('credits_spent');
            }
            if (!Schema::hasColumn('daily_usage_limits', 'whiteboard_videos_used')) {
                // Already exists from earlier migration, skip
            } else {
                // Reset column to ensure it's properly tracked
            }
            if (!Schema::hasColumn('daily_usage_limits', 'topic_quiz_used')) {
                $table->integer('topic_quiz_used')->default(0)->after('quizzes_used');
            }
            if (!Schema::hasColumn('daily_usage_limits', 'exam_prep_used')) {
                $table->integer('exam_prep_used')->default(0)->after('topic_quiz_used');
            }
            if (!Schema::hasColumn('daily_usage_limits', 'pdf_uploads_used')) {
                $table->integer('pdf_uploads_used')->default(0)->after('scans_used');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_usage_limits', function (Blueprint $table) {
            $table->dropColumn([
                'video_quiz_used',
                'topic_quiz_used',
                'exam_prep_used',
                'pdf_uploads_used',
            ]);
        });
    }
};

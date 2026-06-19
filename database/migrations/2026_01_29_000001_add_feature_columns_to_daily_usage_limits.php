<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_usage_limits', function (Blueprint $table) {
            $table->integer('doubts_used')->default(0)->after('credits_spent');
            $table->integer('video_solutions_used')->default(0)->after('doubts_used');
            $table->integer('whiteboard_videos_used')->default(0)->after('video_solutions_used');
            $table->integer('quizzes_used')->default(0)->after('whiteboard_videos_used');
            $table->integer('scans_used')->default(0)->after('quizzes_used');
            $table->integer('mock_tests_used')->default(0)->after('scans_used');
        });
    }

    public function down(): void
    {
        Schema::table('daily_usage_limits', function (Blueprint $table) {
            $table->dropColumn([
                'doubts_used',
                'video_solutions_used',
                'whiteboard_videos_used',
                'quizzes_used',
                'scans_used',
                'mock_tests_used',
            ]);
        });
    }
};

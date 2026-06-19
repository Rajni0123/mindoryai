<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds columns for AI personality personalization
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Study tracking
            $table->integer('current_streak')->default(0)->after('last_activity_at');
            $table->date('last_study_date')->nullable()->after('current_streak');
            $table->integer('questions_answered')->default(0)->after('last_study_date');

            // Preferences
            $table->string('favorite_subject', 50)->nullable()->after('questions_answered');
            $table->string('target_exam', 30)->nullable()->after('favorite_subject');
            $table->date('exam_date')->nullable()->after('target_exam');
            $table->string('gender', 10)->nullable()->after('exam_date');

            // Session tracking
            $table->timestamp('session_started_at')->nullable()->after('gender');
            $table->integer('session_message_count')->default(0)->after('session_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'current_streak',
                'last_study_date',
                'questions_answered',
                'favorite_subject',
                'target_exam',
                'exam_date',
                'gender',
                'session_started_at',
                'session_message_count',
            ]);
        });
    }
};

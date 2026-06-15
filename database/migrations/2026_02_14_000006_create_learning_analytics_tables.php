<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates comprehensive learning analytics tables
     */
    public function up(): void
    {
        // User Learning Analytics - Track learning style preferences
        Schema::create('user_learning_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('learning_style', ['visual', 'verbal', 'practical', 'theoretical'])->nullable();
            $table->integer('diagram_requests')->default(0);
            $table->integer('example_requests')->default(0);
            $table->integer('practical_questions')->default(0);
            $table->integer('theory_questions')->default(0);
            // Performance tracking
            $table->integer('total_questions')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->decimal('overall_accuracy', 5, 2)->default(0);
            $table->integer('last_10_correct')->default(0);
            $table->decimal('last_10_accuracy', 5, 2)->default(0);
            // Language preferences
            $table->string('preferred_language', 20)->default('english');
            $table->integer('hindi_messages')->default(0);
            $table->integer('hinglish_messages')->default(0);
            $table->integer('english_messages')->default(0);
            $table->timestamps();

            $table->unique('user_id');
            $table->index('learning_style');
        });

        // Topic Performance Tracking - Track per-topic performance
        Schema::create('topic_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('topic', 100);
            $table->string('subject', 50);
            $table->integer('attempts')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('total_time_spent')->default(0); // seconds
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->timestamp('last_attempt')->nullable();
            $table->timestamp('first_learned_at')->nullable();
            $table->boolean('needs_revision')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'topic', 'subject']);
            $table->index(['user_id', 'difficulty_level']);
            $table->index(['user_id', 'needs_revision']);
        });

        // Study Sessions - Track each study session
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->default(0); // minutes
            $table->integer('questions_asked')->default(0);
            $table->json('topics_covered')->nullable();
            $table->decimal('focus_score', 3, 1)->default(0);
            $table->enum('productivity_rating', ['low', 'medium', 'high'])->default('medium');
            $table->integer('xp_earned')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // User Achievements - Gamification achievements
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('achievement_key', 50);
            $table->string('achievement_name', 100);
            $table->string('achievement_icon', 10);
            $table->string('category', 30)->default('general');
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['user_id', 'achievement_key']);
            $table->index(['user_id', 'category']);
        });

        // Add XP and level columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->integer('total_xp')->default(0)->after('questions_answered');
            $table->integer('current_level')->default(1)->after('total_xp');
            $table->string('learning_style', 20)->nullable()->after('current_level');
            $table->decimal('overall_accuracy', 5, 2)->default(0)->after('learning_style');
            $table->integer('avg_response_time')->default(0)->after('overall_accuracy'); // seconds
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('study_sessions');
        Schema::dropIfExists('topic_performance');
        Schema::dropIfExists('user_learning_analytics');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'total_xp',
                'current_level',
                'learning_style',
                'overall_accuracy',
                'avg_response_time',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates AI Feedback System tables
     */
    public function up(): void
    {
        // AI Response Feedback - Store individual feedback
        Schema::create('ai_response_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('conversation_id', 100)->nullable();
            $table->text('question');
            $table->text('ai_response');
            $table->enum('feedback_type', ['like', 'dislike', 'report']);
            $table->string('feedback_reason', 255)->nullable();
            $table->text('user_comment')->nullable();
            $table->tinyInteger('response_rating')->nullable(); // 1-5 stars
            $table->boolean('was_helpful')->nullable();
            $table->string('topic', 100)->nullable();
            $table->string('subject', 50)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('feedback_type');
            $table->index('created_at');
            $table->index(['user_id', 'feedback_type']);
        });

        // AI Learned Preferences - Per-user preferences learned from feedback
        Schema::create('ai_learned_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Style preferences
            $table->boolean('prefers_simple_language')->default(false);
            $table->boolean('prefers_detailed_explanations')->default(false);
            $table->boolean('prefers_examples')->default(true);
            $table->boolean('prefers_step_by_step')->default(true);

            // Content preferences
            $table->boolean('likes_real_world_examples')->default(true);
            $table->boolean('likes_diagrams_descriptions')->default(true);
            $table->boolean('likes_mnemonics')->default(true);

            // Communication preferences
            $table->boolean('prefers_hinglish')->default(true);
            $table->boolean('prefers_english')->default(false);
            $table->boolean('prefers_formal_tone')->default(false);
            $table->boolean('prefers_friendly_tone')->default(true);

            // Learning preferences (JSON arrays)
            $table->json('struggles_with_topics')->nullable(); // ["Organic Chemistry", "Calculus"]
            $table->json('favorite_topics')->nullable(); // ["Physics Mechanics"]
            $table->json('preferred_explanation_styles')->nullable(); // ["analogy", "step-by-step"]

            // Calculated metrics
            $table->integer('total_feedbacks')->default(0);
            $table->integer('positive_feedbacks')->default(0);
            $table->integer('negative_feedbacks')->default(0);
            $table->decimal('feedback_score', 3, 2)->default(0.50); // 0.00 to 1.00

            // Feedback analysis
            $table->integer('too_complex_count')->default(0);
            $table->integer('too_simple_count')->default(0);
            $table->integer('wrong_answer_count')->default(0);
            $table->integer('missing_steps_count')->default(0);
            $table->integer('needs_examples_count')->default(0);

            $table->timestamps();

            $table->unique('user_id');
        });

        // Topic Feedback Analytics - Track feedback per topic
        Schema::create('topic_feedback_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('topic', 100);
            $table->string('subject', 50)->nullable();

            $table->integer('total_questions')->default(0);
            $table->integer('liked_responses')->default(0);
            $table->integer('disliked_responses')->default(0);

            // Common issues tracking
            $table->integer('too_complex_count')->default(0);
            $table->integer('too_simple_count')->default(0);
            $table->integer('wrong_answer_count')->default(0);
            $table->integer('missing_steps_count')->default(0);

            $table->decimal('satisfaction_rate', 5, 2)->default(50.00);
            $table->timestamp('last_feedback_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'topic']);
            $table->index(['user_id', 'satisfaction_rate']);
        });

        // Successful Response Templates - Store high-performing responses
        Schema::create('successful_response_templates', function (Blueprint $table) {
            $table->id();
            $table->string('question_pattern', 255);
            $table->string('topic', 100)->nullable();
            $table->string('subject', 50)->nullable();
            $table->text('response_template');
            $table->integer('success_count')->default(0);
            $table->integer('usage_count')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->timestamps();

            $table->index('topic');
            $table->index('success_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('successful_response_templates');
        Schema::dropIfExists('topic_feedback_analytics');
        Schema::dropIfExists('ai_learned_preferences');
        Schema::dropIfExists('ai_response_feedback');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mobile_chat_id')->nullable()->constrained('mobile_chats')->onDelete('set null');

            // Quiz details
            $table->string('title');
            $table->string('exam')->nullable();
            $table->string('subject')->nullable();
            $table->string('topic')->nullable();
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            $table->string('language')->default('english');
            $table->integer('duration_minutes')->nullable();

            // Performance metrics
            $table->integer('total_questions')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('wrong_answers')->default(0);
            $table->integer('skipped_questions')->default(0);
            $table->decimal('score', 5, 2)->default(0); // Percentage score
            $table->integer('time_taken_seconds')->nullable(); // Actual time taken

            // Status tracking
            $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Additional data
            $table->json('questions_data')->nullable(); // Store question details
            $table->json('answers_data')->nullable(); // Store user answers

            $table->timestamps();

            // Indexes for better query performance
            $table->index('user_id');
            $table->index('status');
            $table->index(['user_id', 'created_at']);
            $table->index('score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};

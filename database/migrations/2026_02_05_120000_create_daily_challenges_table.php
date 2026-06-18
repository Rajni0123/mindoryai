<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_challenges', function (Blueprint $table) {
            $table->id();
            $table->date('challenge_date')->unique();
            $table->string('title');
            $table->string('subject');
            $table->string('difficulty')->default('mixed'); // easy, medium, hard, mixed
            $table->json('questions'); // Array of {question, options[], correct_answer, explanation}
            $table->integer('time_limit_seconds')->default(300);
            $table->integer('reward_credits')->default(2);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_ai_generated')->default(false);
            $table->timestamps();

            $table->index('challenge_date');
            $table->index('is_active');
        });

        Schema::create('daily_challenge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('daily_challenge_id')->constrained()->onDelete('cascade');
            $table->integer('score')->default(0);
            $table->integer('total_questions');
            $table->integer('time_taken_seconds')->nullable();
            $table->json('answers')->nullable(); // User's answers
            $table->boolean('completed')->default(false);
            $table->integer('credits_earned')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'daily_challenge_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_challenge_attempts');
        Schema::dropIfExists('daily_challenges');
    }
};

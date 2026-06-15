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
        Schema::create('study_battle_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('study_battle_rooms')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('study_battle_participants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->integer('question_index');
            $table->string('selected_answer', 1)->nullable(); // A, B, C, D, or null (timeout)
            $table->boolean('is_correct')->default(false);
            $table->integer('response_time_ms')->default(0);
            $table->integer('points_earned')->default(0);
            $table->integer('bonus_points')->default(0);

            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            // Unique: one answer per user per question per room
            $table->unique(['room_id', 'user_id', 'question_index'], 'unique_battle_answer');
            $table->index(['room_id', 'question_index']);
            $table->index('participant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_battle_answers');
    }
};

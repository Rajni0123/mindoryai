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
        Schema::create('study_battle_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('study_battle_rooms')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Participant status
            $table->enum('status', [
                'joined',
                'ready',
                'playing',
                'finished',
                'disconnected',
                'left'
            ])->default('joined');

            $table->boolean('is_host')->default(false);

            // Scoring
            $table->integer('total_score')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('wrong_answers')->default(0);
            $table->integer('questions_answered')->default(0);
            $table->integer('total_time_ms')->default(0);
            $table->integer('current_streak')->default(0);
            $table->integer('best_streak')->default(0);

            // Connection tracking
            $table->timestamp('last_poll_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('rank')->nullable();

            $table->timestamps();

            // Unique constraint: one entry per user per room
            $table->unique(['room_id', 'user_id']);
            $table->index('user_id');
            $table->index(['room_id', 'status']);
            $table->index('last_poll_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_battle_participants');
    }
};

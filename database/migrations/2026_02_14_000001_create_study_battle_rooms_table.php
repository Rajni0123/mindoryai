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
        Schema::create('study_battle_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_code', 6)->unique();
            $table->foreignId('host_user_id')->constrained('users')->onDelete('cascade');
            $table->string('title')->default('Study Battle');
            $table->string('topic')->nullable();
            $table->string('subject')->nullable();
            $table->string('exam_type')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'mixed'])->default('medium');
            $table->enum('status', [
                'waiting',
                'starting',
                'in_progress',
                'completed',
                'cancelled',
                'abandoned'
            ])->default('waiting');

            // Battle configuration
            $table->integer('max_players')->default(4);
            $table->integer('min_players')->default(2);
            $table->integer('question_count')->default(10);
            $table->integer('time_per_question')->default(15);
            $table->integer('current_question_index')->default(0);

            // Timing
            $table->timestamp('question_started_at')->nullable();
            $table->timestamp('battle_started_at')->nullable();
            $table->timestamp('battle_ended_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Questions data (stored as JSON after generation)
            $table->json('questions_data')->nullable();
            $table->json('battle_settings')->nullable();

            $table->timestamps();

            // Indexes for polling efficiency
            $table->index('room_code');
            $table->index('status');
            $table->index(['status', 'updated_at']);
            $table->index('host_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_battle_rooms');
    }
};

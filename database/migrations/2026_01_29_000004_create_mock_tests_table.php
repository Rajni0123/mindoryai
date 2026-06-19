<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mock_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->string('title');
            $table->json('question_ids');
            $table->integer('total_questions');
            $table->integer('duration_minutes');
            $table->json('config')->nullable(); // marking_scheme, negative_marking, etc.
            $table->enum('status', ['pending', 'in_progress', 'completed', 'abandoned'])->default('pending');
            $table->integer('score')->nullable();
            $table->integer('correct_answers')->nullable();
            $table->integer('wrong_answers')->nullable();
            $table->integer('unanswered')->nullable();
            $table->integer('time_taken_seconds')->nullable();
            $table->json('answers')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'exam_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mock_tests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->string('subject');
            $table->string('topic')->nullable();
            $table->string('subtopic')->nullable();
            $table->integer('year')->nullable();
            $table->enum('type', ['mcq', 'numerical', 'assertion_reason', 'true_false'])->default('mcq');
            $table->text('question_text');
            $table->json('options')->nullable(); // [{label: "A", text: "..."}, ...]
            $table->string('correct_answer');
            $table->text('explanation')->nullable();
            $table->text('solution_steps')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->string('language')->default('english');
            $table->json('tags')->nullable(); // ["pyq","2024","shift-1"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['exam_id', 'subject', 'topic']);
            $table->index(['exam_id', 'year']);
            $table->index(['exam_id', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};

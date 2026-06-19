<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions_bank', function (Blueprint $table) {
            $table->id();
            $table->text('original_question');
            $table->text('normalized_question'); // lowercase, cleaned version for matching
            $table->longText('answer');
            $table->string('subject', 50)->nullable()->index(); // physics, chemistry, biology, math
            $table->string('topic', 100)->nullable()->index(); // newton_laws, thermodynamics, etc
            $table->string('exam_type', 30)->nullable()->index(); // JEE, NEET, CBSE, UPSC
            $table->string('difficulty', 20)->nullable(); // easy, medium, hard
            $table->string('question_hash', 64)->unique(); // SHA256 hash for exact match
            $table->json('keywords')->nullable(); // extracted keywords for fuzzy matching
            $table->unsignedInteger('hit_count')->default(0); // how many times served from cache
            $table->unsignedInteger('api_cost_saved')->default(0); // estimated cost saved in paisa
            $table->string('ai_model', 50)->default('gemini'); // which model generated the answer
            $table->timestamps();

            // Fulltext index for searching
            $table->fullText(['original_question', 'normalized_question']);
        });

        // Stats tracking table
        Schema::create('answer_bank_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('total_queries')->default(0);
            $table->unsignedInteger('cache_hits')->default(0);
            $table->unsignedInteger('api_calls')->default(0);
            $table->unsignedInteger('cost_saved_paisa')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_bank_stats');
        Schema::dropIfExists('questions_bank');
    }
};

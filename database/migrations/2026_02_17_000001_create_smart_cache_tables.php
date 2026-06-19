<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates all 7 tables for the Smart Cache System
     */
    public function up(): void
    {
        // 1. Answer Bank - Main Q&A Cache
        Schema::create('answer_bank', function (Blueprint $table) {
            $table->id();
            $table->string('question_hash', 64)->index();
            $table->text('original_question');
            $table->text('normalized_question');
            $table->longText('answer');
            $table->string('subject', 50)->nullable()->index();
            $table->string('topic', 100)->nullable();
            $table->string('class_level', 10)->nullable();
            $table->string('exam_type', 20)->nullable()->index();
            $table->string('source_type', 30)->index();
            $table->string('language', 10)->default('hinglish');
            $table->unsignedInteger('hit_count')->default(0);
            $table->unsignedInteger('token_count_saved')->default(0);
            $table->decimal('cost_saved', 10, 6)->default(0);
            $table->float('quality_score')->default(1.0);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();

            $table->unique(['question_hash', 'source_type']);
        });

        // Add FULLTEXT index for MySQL
        DB::statement('ALTER TABLE answer_bank ADD FULLTEXT INDEX answer_bank_fulltext (original_question, normalized_question)');

        // 2. Video Cache - Whiteboard storyboard cache
        Schema::create('video_cache', function (Blueprint $table) {
            $table->id();
            $table->string('content_hash', 64)->unique();
            $table->string('topic', 200)->nullable();
            $table->string('subject', 50)->nullable();
            $table->string('style', 50)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->longText('storyboard_json')->nullable();
            $table->longText('script_text')->nullable();
            $table->string('audio_path', 500)->nullable();
            $table->string('video_path', 500)->nullable();
            $table->unsignedInteger('hit_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Image Cache - Video frame cache
        Schema::create('image_cache', function (Blueprint $table) {
            $table->id();
            $table->string('image_hash', 64)->unique();
            $table->string('topic', 200)->nullable();
            $table->string('style', 50)->nullable();
            $table->unsignedInteger('frame_number')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->unsignedInteger('file_size')->default(0);
            $table->unsignedInteger('hit_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Formula Bank - Pre-seeded formulas
        Schema::create('formula_bank', function (Blueprint $table) {
            $table->id();
            $table->string('content_hash', 64)->unique();
            $table->string('name', 200);
            $table->text('formula_latex')->nullable();
            $table->text('formula_unicode')->nullable();
            $table->text('explanation')->nullable();
            $table->string('subject', 50)->index();
            $table->string('chapter', 100)->nullable();
            $table->string('class_level', 10)->nullable();
            $table->text('keywords')->nullable();
            $table->unsignedInteger('hit_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add FULLTEXT index for formula_bank
        DB::statement('ALTER TABLE formula_bank ADD FULLTEXT INDEX formula_bank_fulltext (name, keywords)');

        // 5. NCERT Cache - Chapter content
        Schema::create('ncert_cache', function (Blueprint $table) {
            $table->id();
            $table->string('content_hash', 64)->unique();
            $table->string('subject', 50)->nullable();
            $table->string('class_level', 10)->nullable();
            $table->string('book_name', 100)->nullable();
            $table->unsignedInteger('chapter_number')->nullable();
            $table->string('chapter_name', 200)->nullable();
            $table->string('content_type', 50)->nullable(); // summary, explanation, exercise, etc.
            $table->longText('content')->nullable();
            $table->string('page_numbers', 50)->nullable();
            $table->unsignedInteger('hit_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add FULLTEXT index for ncert_cache
        DB::statement('ALTER TABLE ncert_cache ADD FULLTEXT INDEX ncert_cache_fulltext (chapter_name, content)');

        // 6. Cache Analytics - Daily stats per source type
        Schema::create('cache_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('source_type', 50);
            $table->unsignedInteger('total_requests')->default(0);
            $table->unsignedInteger('cache_hits')->default(0);
            $table->unsignedInteger('cache_misses')->default(0);
            $table->unsignedInteger('greeting_filtered')->default(0);
            $table->unsignedInteger('followup_filtered')->default(0);
            $table->unsignedInteger('quality_filtered')->default(0);
            $table->unsignedInteger('tokens_saved')->default(0);
            $table->decimal('cost_saved', 10, 6)->default(0);
            $table->decimal('avg_lookup_ms', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['date', 'source_type']);
        });

        // 7. Greeting Patterns - Configurable filter patterns
        Schema::create('greeting_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('pattern', 100)->unique();
            $table->string('category', 30)->default('greeting'); // greeting, farewell, thanks, filler, meta
            $table->string('language', 10)->default('en'); // en, hi
            $table->unsignedTinyInteger('priority')->default(1); // 1=highest, 3=lowest
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('greeting_patterns');
        Schema::dropIfExists('cache_analytics');
        Schema::dropIfExists('ncert_cache');
        Schema::dropIfExists('formula_bank');
        Schema::dropIfExists('image_cache');
        Schema::dropIfExists('video_cache');
        Schema::dropIfExists('answer_bank');
    }
};

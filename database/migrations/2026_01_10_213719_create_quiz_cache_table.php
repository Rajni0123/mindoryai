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
        Schema::create('quiz_cache', function (Blueprint $table) {
            $table->id();
            $table->string('image_hash', 64)->unique(); // SHA-256 hash of image for deduplication
            $table->text('quiz_data'); // JSON encoded quiz data
            $table->string('quiz_type')->default('mcq'); // mcq, true_false, short_answer
            $table->integer('difficulty')->default(1); // 1-5 scale
            $table->string('subject')->nullable(); // Math, Science, etc.
            $table->text('topics')->nullable(); // JSON array of topics
            $table->integer('question_count')->default(5);
            $table->unsignedBigInteger('usage_count')->default(1); // Track how many times used
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Indexes for faster lookups
            $table->index('image_hash');
            $table->index('quiz_type');
            $table->index('subject');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_cache');
    }
};

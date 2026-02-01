<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add topic_hash to whiteboard_videos for video caching
        Schema::table('whiteboard_videos', function (Blueprint $table) {
            $table->string('topic_hash', 64)->nullable()->after('document_content')->index();
            $table->unsignedBigInteger('cached_from_id')->nullable()->after('topic_hash');
        });

        // Create doubt_cache table for image/pdf doubt caching
        Schema::create('doubt_cache', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['image', 'pdf']);
            $table->string('content_hash', 64)->index();
            $table->string('question_hash', 64)->nullable()->index();
            $table->text('question_text')->nullable();
            $table->string('subject')->nullable();
            $table->longText('solution');
            $table->unsignedInteger('tokens_used')->default(0);
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'content_hash', 'question_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('whiteboard_videos', function (Blueprint $table) {
            $table->dropColumn(['topic_hash', 'cached_from_id']);
        });

        Schema::dropIfExists('doubt_cache');
    }
};

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
        Schema::create('whiteboard_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('job_id')->unique()->index();
            $table->string('title')->nullable();
            $table->string('document_path')->nullable();
            $table->text('document_content')->nullable();
            $table->enum('status', ['pending', 'processing', 'generating_storyboard', 'generating_assets', 'stitching_video', 'completed', 'failed'])->default('pending');
            $table->json('storyboard')->nullable();
            $table->string('final_video_url')->nullable();
            $table->string('final_video_path')->nullable();
            $table->integer('total_scenes')->default(0);
            $table->integer('processed_scenes')->default(0);
            $table->integer('duration_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whiteboard_videos');
    }
};

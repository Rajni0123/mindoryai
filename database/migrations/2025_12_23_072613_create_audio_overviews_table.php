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
        Schema::create('audio_overviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notebook_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('audio_file_path')->nullable();
            $table->string('transcript_file_path')->nullable();
            $table->longText('script')->nullable(); // Dialogue script for podcast
            $table->integer('duration_seconds')->nullable();
            $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('notebook_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_overviews');
    }
};

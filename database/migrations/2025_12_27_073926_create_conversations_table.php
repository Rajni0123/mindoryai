<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates conversations table to store chat conversation threads
     * Each user can have multiple conversations (like ChatGPT)
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            // Foreign key to users table
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Conversation title (auto-generated from first message or custom)
            $table->string('title')->nullable();

            // AI Model used in this conversation
            $table->foreignId('ai_model_id')->nullable()->constrained('ai_models')->onDelete('set null');

            // Track last activity for sorting
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};

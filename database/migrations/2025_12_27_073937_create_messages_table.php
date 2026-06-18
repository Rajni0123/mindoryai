<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates messages table to store individual chat messages
     * Each message belongs to a conversation and has a role (user/assistant)
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Foreign key to conversations table
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');

            // Message role: 'user' or 'assistant' (matches OpenAI API format)
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');

            // Message content (the actual text)
            $table->text('content');

            // Optional: Store token count for this specific message
            $table->integer('tokens')->nullable();

            // Optional: Metadata (can store image URLs, attachments, etc.)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index('conversation_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

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
        Schema::create('mobile_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobile_chat_id')->constrained('mobile_chats')->onDelete('cascade');
            $table->enum('sender', ['user', 'assistant', 'system']);
            $table->longText('content');
            $table->json('image')->nullable(); // Store image data as JSON
            $table->json('metadata')->nullable(); // Store model info, tokens, etc.
            $table->timestamps();

            $table->index('mobile_chat_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_chat_messages');
    }
};

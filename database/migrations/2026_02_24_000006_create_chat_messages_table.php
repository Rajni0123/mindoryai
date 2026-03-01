<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->enum('sender_type', ['student', 'topper']);
            $table->text('content');
            $table->string('image_url')->nullable();
            $table->enum('message_type', ['text', 'image', 'latex', 'voice'])->default('text');
            $table->integer('tokens_charged')->default(0); // 0 for topper, tokens for student
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            // Anti-cheat fields
            $table->integer('typing_time_ms')->nullable(); // How long it took to type
            $table->integer('char_count')->default(0);
            $table->boolean('was_pasted')->default(false);
            $table->decimal('ai_detection_score', 3, 2)->nullable(); // 0.00 to 1.00
            $table->boolean('flagged_for_review')->default(false);

            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index('sender_id');
            $table->index('flagged_for_review');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};

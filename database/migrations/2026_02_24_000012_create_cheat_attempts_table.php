<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheat_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('conversation_id')->nullable()->constrained('chat_conversations')->onDelete('cascade');
            $table->foreignId('message_id')->nullable()->constrained('chat_messages')->onDelete('cascade');
            $table->enum('type', [
                'paste_detected',
                'ai_content_detected',
                'typing_speed_anomaly',
                'external_link_shared',
                'contact_info_shared',
                'abusive_content'
            ]);
            $table->text('details');
            $table->integer('severity'); // 1-5
            $table->enum('action_taken', ['warned', 'message_blocked', 'conversation_ended', 'account_suspended', 'none'])->default('none');
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('created_at');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheat_attempts');
    }
};

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
        // Web conversations table
        Schema::create('web_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->json('tags')->nullable();
            $table->json('settings')->nullable();
            $table->integer('message_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('last_message_at');
            $table->index(['user_id', 'is_pinned']);
        });

        // Web messages table
        Schema::create('web_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('web_conversations')->onDelete('cascade');
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('content');
            $table->integer('tokens_used')->nullable();
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->index(['conversation_id', 'created_at']);
        });

        // Add web_chat_settings column to users table if not exists
        if (!Schema::hasColumn('users', 'web_chat_settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('web_chat_settings')->nullable()->after('notification_preferences');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_messages');
        Schema::dropIfExists('web_conversations');

        if (Schema::hasColumn('users', 'web_chat_settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('web_chat_settings');
            });
        }
    }
};

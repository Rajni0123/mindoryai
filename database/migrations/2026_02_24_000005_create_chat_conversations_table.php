<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_request_id')->constrained('chat_requests')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('topper_id')->constrained('users')->onDelete('cascade');
            $table->string('subject');
            $table->enum('status', ['active', 'resolved', 'closed', 'disputed'])->default('active');
            $table->integer('tokens_used')->default(0);
            $table->integer('message_count')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('student_rating')->nullable(); // 1-5
            $table->text('student_feedback')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['topper_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};

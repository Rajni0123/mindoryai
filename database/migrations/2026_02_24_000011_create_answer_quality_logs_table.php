<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answer_quality_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->onDelete('cascade');
            $table->foreignId('topper_id')->constrained('users')->onDelete('cascade');
            $table->decimal('ai_detection_score', 3, 2); // 0.00 to 1.00
            $table->integer('typing_speed_wpm')->nullable(); // Words per minute
            $table->boolean('seems_copied')->default(false);
            $table->boolean('seems_ai_generated')->default(false);
            $table->text('analysis_notes')->nullable();
            $table->enum('review_status', ['pending', 'approved', 'flagged', 'banned'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['topper_id', 'review_status']);
            $table->index('ai_detection_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_quality_logs');
    }
};

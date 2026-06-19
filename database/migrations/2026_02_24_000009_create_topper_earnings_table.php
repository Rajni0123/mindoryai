<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topper_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topper_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('conversation_id')->constrained('chat_conversations')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->integer('tokens_received');
            $table->decimal('gross_amount', 10, 2); // Total in INR (tokens / 10)
            $table->decimal('platform_fee', 10, 2); // 30% platform cut
            $table->decimal('net_amount', 10, 2); // 70% topper gets
            $table->enum('status', ['pending', 'processed', 'paid', 'disputed'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['topper_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topper_earnings');
    }
};

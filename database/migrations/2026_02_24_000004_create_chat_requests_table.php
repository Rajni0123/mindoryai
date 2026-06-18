<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('topper_id')->constrained('users')->onDelete('cascade');
            $table->string('subject'); // physics, chemistry, etc.
            $table->text('doubt_preview'); // First 200 chars of doubt
            $table->integer('student_token_balance'); // Snapshot at request time
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['topper_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index('expires_at');
            $table->index('student_token_balance'); // For priority sorting
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_requests');
    }
};

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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['info', 'success', 'warning', 'error'])->default('info');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_global')->default(false); // true = sent to all users
            $table->timestamp('read_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'is_read']);
            $table->index(['is_global', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

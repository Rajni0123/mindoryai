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
        Schema::create('usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action_type'); // 'api_call', 'message', 'image_upload', 'image_generation'
            $table->string('ai_model')->nullable(); // gpt4, claude, deepseek, grok
            $table->integer('tokens_consumed')->default(0);
            $table->integer('credits_consumed')->default(0);
            $table->string('ip_address')->nullable();
            $table->text('request_data')->nullable(); // JSON data about the request
            $table->text('response_data')->nullable(); // JSON data about the response
            $table->string('status')->default('success'); // success, failed, error
            $table->timestamps();

            $table->index('user_id');
            $table->index('action_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_logs');
    }
};

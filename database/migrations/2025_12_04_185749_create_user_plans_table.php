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
        Schema::create('user_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Basic, Pro, etc.
            $table->string('slug')->unique();
            $table->integer('message_tokens')->default(0); // Message token limit
            $table->integer('image_credits')->default(0); // Image generation credits
            $table->integer('api_calls')->default(0); // API call limit
            $table->integer('image_uploads')->default(0); // Image upload limit
            $table->boolean('can_use_gpt4')->default(false);
            $table->boolean('can_use_claude')->default(false);
            $table->boolean('can_use_deepseek')->default(false);
            $table->boolean('can_use_grok')->default(false);
            $table->integer('validity_days')->nullable(); // Plan validity in days
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_plans');
    }
};

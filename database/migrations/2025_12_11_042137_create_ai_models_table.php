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
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "GPT-4", "Claude Sonnet", "Gemini Pro"
            $table->string('provider'); // e.g., "openai", "anthropic", "google"
            $table->string('model_identifier'); // e.g., "gpt-4o", "claude-3-sonnet-20240229"
            $table->string('api_key')->nullable();
            $table->string('api_url')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // Icon name or emoji
            $table->string('color')->default('#3ddcff'); // Brand color
            $table->boolean('is_active')->default(true);
            $table->boolean('supports_vision')->default(false);
            $table->integer('max_tokens')->default(1000);
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->integer('order')->default(0); // Display order
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};

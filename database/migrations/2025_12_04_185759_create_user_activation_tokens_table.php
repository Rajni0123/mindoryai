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
        Schema::create('user_activation_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash')->unique(); // Hashed token for security
            $table->string('raw_token')->nullable(); // Store temporarily for display to admin
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('user_plans')->onDelete('cascade');
            $table->boolean('is_used')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index('token_hash');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activation_tokens');
    }
};

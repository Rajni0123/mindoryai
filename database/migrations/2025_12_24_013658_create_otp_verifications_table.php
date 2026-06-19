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
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 15);
            $table->string('otp_code', 4);
            $table->timestamp('expires_at');
            $table->boolean('verified')->default(false);
            $table->integer('attempts')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index('mobile');
            $table->index(['mobile', 'verified']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('event'); // purchase, renewal, upgrade, downgrade, expired, grace_period, cancelled
            $table->json('data')->nullable(); // Additional event data
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'event']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_logs');
    }
};

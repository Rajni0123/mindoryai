<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fixes type mismatch: ai_model_id was unsignedInteger but ai_models.id is bigInteger
     */
    public function up(): void
    {
        Schema::table('mobile_chats', function (Blueprint $table) {
            // Change from unsignedInteger to unsignedBigInteger to match ai_models.id
            $table->unsignedBigInteger('ai_model_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobile_chats', function (Blueprint $table) {
            $table->unsignedInteger('ai_model_id')->nullable()->change();
        });
    }
};

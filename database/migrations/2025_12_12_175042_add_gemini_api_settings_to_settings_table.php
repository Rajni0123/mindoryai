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
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('gemini_enabled')->default(false);
            $table->string('gemini_api_key')->nullable();
            $table->string('gemini_model')->default('gemini-pro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['gemini_enabled', 'gemini_api_key', 'gemini_model']);
        });
    }
};

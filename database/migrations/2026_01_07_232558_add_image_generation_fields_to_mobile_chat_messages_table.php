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
        Schema::table('mobile_chat_messages', function (Blueprint $table) {
            $table->text('image_url')->nullable()->after('image');
            $table->boolean('is_image_generation')->default(false)->after('image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobile_chat_messages', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'is_image_generation']);
        });
    }
};

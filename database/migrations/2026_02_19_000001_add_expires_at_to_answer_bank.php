<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add expires_at column for TTL enforcement in cache.
     */
    public function up(): void
    {
        Schema::table('answer_bank', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('last_hit_at');
            $table->index('expires_at'); // Index for efficient TTL queries
        });

        // Set default expires_at for existing entries (90 days from created_at)
        \DB::statement('UPDATE answer_bank SET expires_at = DATE_ADD(created_at, INTERVAL 90 DAY) WHERE expires_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answer_bank', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};

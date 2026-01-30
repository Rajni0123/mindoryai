<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds composite indexes for better query performance on shared hosting
     */
    public function up(): void
    {
        Schema::table('image_analyses', function (Blueprint $table) {
            // Composite index for cleanup queries (is_deleted + created_at)
            // Used by: images:cleanup command
            $table->index(['is_deleted', 'created_at'], 'idx_cleanup');
        });

        Schema::table('usage_logs', function (Blueprint $table) {
            // Note: user_id and created_at indexes already exist from create_usage_logs_table
            // Only add composite index for user activity queries
            $table->index(['user_id', 'created_at'], 'idx_user_activity');
        });

        Schema::table('users', function (Blueprint $table) {
            // Index on token_activated for filtering active users
            if (!Schema::hasIndex('users', ['token_activated'])) {
                $table->index('token_activated');
            }

            // Index on email for faster lookups
            if (!Schema::hasIndex('users', ['email'])) {
                $table->index('email');
            }
        });

        Schema::table('user_activation_tokens', function (Blueprint $table) {
            // Index on is_used for filtering available tokens
            $table->index('is_used');

            // Index on is_active for active token queries
            $table->index('is_active');

            // Composite index for token validation
            $table->index(['is_used', 'is_active', 'expires_at'], 'idx_token_validation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('image_analyses', function (Blueprint $table) {
            $table->dropIndex('idx_cleanup');
        });

        Schema::table('usage_logs', function (Blueprint $table) {
            // Only drop the composite index we added
            $table->dropIndex('idx_user_activity');
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasIndex('users', ['token_activated'])) {
                $table->dropIndex(['token_activated']);
            }
            if (Schema::hasIndex('users', ['email'])) {
                $table->dropIndex(['email']);
            }
        });

        Schema::table('user_activation_tokens', function (Blueprint $table) {
            $table->dropIndex(['is_used']);
            $table->dropIndex(['is_active']);
            $table->dropIndex('idx_token_validation');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Performance optimization: Add composite indexes for frequently used queries
     */
    public function up(): void
    {
        // Add composite index for OTP verification queries (mobile + created_at DESC)
        Schema::table('otp_verifications', function (Blueprint $table) {
            // Composite index for "ORDER BY created_at DESC WHERE mobile = ?" queries
            if (!$this->hasIndex('otp_verifications', 'otp_verifications_mobile_created_at_index')) {
                $table->index(['mobile', 'created_at'], 'otp_verifications_mobile_created_at_index');
            }
        });

        // Add index for users mobile lookup (if not exists)
        Schema::table('users', function (Blueprint $table) {
            if (!$this->hasIndex('users', 'users_mobile_index')) {
                $table->index('mobile', 'users_mobile_index');
            }
        });

        // Add index for sessions user_id (for session-based queries)
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            Schema::table('sessions', function (Blueprint $table) {
                if (!$this->hasIndex('sessions', 'sessions_user_id_index')) {
                    $table->index('user_id', 'sessions_user_id_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropIndex('otp_verifications_mobile_created_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            if ($this->hasIndex('users', 'users_mobile_index')) {
                $table->dropIndex('users_mobile_index');
            }
        });

        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                if ($this->hasIndex('sessions', 'sessions_user_id_index')) {
                    $table->dropIndex('sessions_user_id_index');
                }
            });
        }
    }

    /**
     * Check if index exists on table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        // For MySQL
        if ($connection->getDriverName() === 'mysql') {
            $indexes = $connection->select(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$indexName]
            );
            return count($indexes) > 0;
        }

        // For SQLite - indexes are in sqlite_master
        if ($connection->getDriverName() === 'sqlite') {
            $indexes = $connection->select(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND name = ?",
                [$indexName]
            );
            return count($indexes) > 0;
        }

        return false;
    }
};

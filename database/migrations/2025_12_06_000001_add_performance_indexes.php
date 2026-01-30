<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Add performance indexes
     */
    public function up(): void
    {
        // Add composite indexes for faster queries
        Schema::table('settings', function (Blueprint $table) {
            $table->index(['group', 'key'], 'settings_group_key_index');
        });

        Schema::table('image_analyses', function (Blueprint $table) {
            $table->index(['is_deleted', 'created_at'], 'image_analyses_deleted_created_index');
        });

        Schema::table('usage_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'usage_logs_user_created_index');
            $table->index(['action_type', 'created_at'], 'usage_logs_action_created_index');
        });

        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->index(['is_active', 'order'], 'pricing_plans_active_order_index');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->index(['is_active', 'order'], 'features_active_order_index');
        });

        // Add indexes to users table for faster lookups
        Schema::table('users', function (Blueprint $table) {
            $table->index('is_active', 'users_is_active_index');
            $table->index('created_at', 'users_created_at_index');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex('settings_group_key_index');
        });

        Schema::table('image_analyses', function (Blueprint $table) {
            $table->dropIndex('image_analyses_deleted_created_index');
        });

        Schema::table('usage_logs', function (Blueprint $table) {
            $table->dropIndex('usage_logs_user_created_index');
            $table->dropIndex('usage_logs_action_created_index');
        });

        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropIndex('pricing_plans_active_order_index');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->dropIndex('features_active_order_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_is_active_index');
            $table->dropIndex('users_created_at_index');
        });
    }
};

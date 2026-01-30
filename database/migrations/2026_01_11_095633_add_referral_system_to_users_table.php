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
        Schema::table('users', function (Blueprint $table) {
            // Referral system fields
            $table->string('referral_code', 20)->unique()->nullable()->after('plan_id');
            $table->unsignedBigInteger('referred_by')->nullable()->after('referral_code');
            $table->timestamp('referral_applied_at')->nullable()->after('referred_by');
            $table->boolean('referral_reward_given')->default(false)->after('referral_applied_at');
            $table->integer('referral_count')->default(0)->after('referral_reward_given');

            // Foreign key for referred_by
            $table->foreign('referred_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('referral_code');
            $table->index('referred_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropIndex(['referred_by']);
            $table->dropIndex(['referral_code']);
            $table->dropColumn(['referral_code', 'referred_by', 'referral_applied_at', 'referral_reward_given', 'referral_count']);
        });
    }
};

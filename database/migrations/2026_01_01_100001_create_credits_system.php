<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates comprehensive credit system:
     * - user_credits: Track current credit balance
     * - credit_transactions: Audit trail of all credit changes
     * - feature_flags: Toggle features without app update
     * - daily_usage_limits: Track daily usage when unlimited mode is OFF
     */
    public function up(): void
    {
        // =============================================
        // USER CREDITS TABLE
        // =============================================
        // Stores current credit balance for each user
        Schema::create('user_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('available_credits')->default(0); // Current balance
            $table->integer('total_earned')->default(0); // Lifetime earned credits
            $table->integer('total_spent')->default(0); // Lifetime spent credits
            $table->boolean('unlimited_mode')->default(false); // Bypass credit checks
            $table->timestamp('unlimited_until')->nullable(); // Expiry for unlimited mode
            $table->timestamps();

            // Ensure one record per user
            $table->unique('user_id');

            // Indexes for fast lookups
            $table->index('unlimited_mode');
        });

        // =============================================
        // CREDIT TRANSACTIONS TABLE
        // =============================================
        // Audit trail of all credit additions/deductions
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['earn', 'spend', 'refund', 'admin_adjustment']); // Transaction type
            $table->integer('amount'); // Positive for earn, negative for spend
            $table->integer('balance_after'); // Balance after this transaction
            $table->string('reason', 100); // e.g., 'chat_message', 'image_generation', 'signup_bonus'
            $table->string('description')->nullable(); // Additional details
            $table->string('reference_type')->nullable(); // Model type (Chat, Message, etc.)
            $table->unsignedBigInteger('reference_id')->nullable(); // Model ID
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Indexes for fast queries
            $table->index(['user_id', 'created_at']);
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
        });

        // =============================================
        // FEATURE FLAGS TABLE
        // =============================================
        // Toggle features without app update
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key', 100)->unique(); // e.g., 'credits_enabled', 'image_generation_enabled'
            $table->string('feature_name'); // Human-readable name
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable(); // Additional configuration (cost per action, etc.)
            $table->timestamps();

            $table->index('is_enabled');
        });

        // =============================================
        // DAILY USAGE LIMITS TABLE
        // =============================================
        // Track daily usage when unlimited mode is OFF
        // Resets automatically based on daily limits
        Schema::create('daily_usage_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('usage_date'); // Date of usage
            $table->integer('messages_sent')->default(0); // Chat messages sent
            $table->integer('images_generated')->default(0); // Images generated
            $table->integer('credits_spent')->default(0); // Total credits spent today
            $table->timestamps();

            // Ensure one record per user per day
            $table->unique(['user_id', 'usage_date']);

            // Index for cleanup queries
            $table->index('usage_date');
        });

        // =============================================
        // ADD CREDIT COLUMNS TO USERS TABLE
        // =============================================
        // Add initial_credits column to track signup bonus
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'initial_credits_given')) {
                $table->boolean('initial_credits_given')->default(false)->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'initial_credits_given')) {
                $table->dropColumn('initial_credits_given');
            }
        });

        Schema::dropIfExists('daily_usage_limits');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('user_credits');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'trial_used_at')) {
                $table->timestamp('trial_used_at')->nullable()->after('plan_expires_at');
            }
            if (! Schema::hasColumn('users', 'razorpay_subscription_id')) {
                $table->string('razorpay_subscription_id')->nullable()->after('trial_used_at');
            }
        });

        if (! Schema::hasTable('autopay_trials')) {
            Schema::create('autopay_trials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('razorpay_subscription_id')->unique();
                $table->string('status', 32)->default('pending_auth');
                $table->string('plan_slug', 32)->default('lite');
                $table->unsignedInteger('trial_price')->default(1);
                $table->unsignedInteger('renewal_price')->default(79);
                $table->timestamp('trial_starts_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('next_billing_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (Schema::hasTable('user_subscriptions')) {
            Schema::table('user_subscriptions', function (Blueprint $table) {
                if (! Schema::hasColumn('user_subscriptions', 'razorpay_subscription_id')) {
                    $table->string('razorpay_subscription_id')->nullable()->after('transaction_id');
                }
                if (! Schema::hasColumn('user_subscriptions', 'is_trial')) {
                    $table->boolean('is_trial')->default(false)->after('auto_renew');
                }
                if (! Schema::hasColumn('user_subscriptions', 'trial_ends_at')) {
                    $table->timestamp('trial_ends_at')->nullable()->after('is_trial');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('autopay_trials');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'trial_used_at')) {
                $table->dropColumn('trial_used_at');
            }
            if (Schema::hasColumn('users', 'razorpay_subscription_id')) {
                $table->dropColumn('razorpay_subscription_id');
            }
        });

        if (Schema::hasTable('user_subscriptions')) {
            Schema::table('user_subscriptions', function (Blueprint $table) {
                foreach (['razorpay_subscription_id', 'is_trial', 'trial_ends_at'] as $col) {
                    if (Schema::hasColumn('user_subscriptions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

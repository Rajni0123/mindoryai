<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $freeUserPlan = DB::table('user_plans')->where('slug', 'free')->first();
        if ($freeUserPlan) {
            DB::table('users')->where('plan_id', $freeUserPlan->id)->update([
                'plan_id' => null,
                'plan_expires_at' => null,
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('user_subscriptions')) {
                DB::table('user_subscriptions')->where('plan_id', $freeUserPlan->id)->delete();
            }

            if (Schema::hasTable('payment_orders')) {
                DB::table('payment_orders')->where('plan_id', $freeUserPlan->id)->delete();
            }

            DB::table('user_plans')->where('slug', 'free')->delete();
        }

        if (Schema::hasTable('plans')) {
            $freePlan = DB::table('plans')->where('slug', 'free')->first();
            if ($freePlan && Schema::hasTable('plan_features')) {
                DB::table('plan_features')->where('plan_id', $freePlan->id)->delete();
            }
            DB::table('plans')->where('slug', 'free')->delete();
        }

        if (Schema::hasTable('pricing_plans')) {
            DB::table('pricing_plans')->where('slug', 'free')->delete();
        }

        Cache::forget('user_plan_free');
        Cache::forget('free_plan');
    }

    public function down(): void
    {
        // Free plan intentionally not restored — paid-only product policy
    }
};

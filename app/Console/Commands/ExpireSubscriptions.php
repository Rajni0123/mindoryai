<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'Downgrade users whose paid plan has expired to free plan';

    public function handle(): int
    {
        $expiredUsers = DB::table('users')
            ->whereNotNull('plan_id')
            ->whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '<', now())
            ->where('role', '!=', 'admin')
            ->get(['id', 'name', 'plan_id', 'plan_expires_at']);

        if ($expiredUsers->isEmpty()) {
            $this->info('No expired subscriptions found.');
            return 0;
        }

        $count = 0;
        foreach ($expiredUsers as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'plan_id' => null,
                    'plan_expires_at' => null,
                    'updated_at' => now(),
                ]);

            $count++;
            $this->line("  Expired: {$user->name} (ID: {$user->id}) - plan was #{$user->plan_id}, expired {$user->plan_expires_at}");
        }

        $this->info("Downgraded {$count} expired users to free plan.");
        Log::info("Subscription expiry: {$count} users downgraded to free plan.");

        return 0;
    }
}

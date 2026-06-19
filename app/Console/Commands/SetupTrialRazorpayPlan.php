<?php

namespace App\Console\Commands;

use App\Services\TrialAutopayService;
use Illuminate\Console\Command;

class SetupTrialRazorpayPlan extends Command
{
    protected $signature = 'trial:setup-razorpay-plan';

    protected $description = 'Create Razorpay monthly plan for ₹1 trial autopay (Lite ₹79/mo after 2 days)';

    public function handle(TrialAutopayService $trialService): int
    {
        if (config('trial.razorpay_plan_id')) {
            $this->info('RAZORPAY_LITE_MONTHLY_PLAN_ID already set: ' . config('trial.razorpay_plan_id'));
            return self::SUCCESS;
        }

        try {
            $planId = $trialService->ensureRazorpayPlan();
        } catch (\Exception $e) {
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Razorpay plan created successfully!');
        $this->line('Add this to your .env file:');
        $this->newLine();
        $this->line("RAZORPAY_LITE_MONTHLY_PLAN_ID={$planId}");
        $this->newLine();
        $this->comment('Then configure Razorpay webhooks for: subscription.authenticated, subscription.charged, subscription.cancelled, subscription.halted');

        return self::SUCCESS;
    }
}

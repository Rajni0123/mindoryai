<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display comprehensive admin dashboard
     */
    public function index()
    {
        // User Statistics
        $userStats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'new_users_week' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'new_users_month' => User::where('created_at', '>=', now()->subMonth())->count(),
        ];

        // Credit Statistics
        $creditStats = [
            'total_credits_earned' => DB::table('user_credits')->sum('total_earned'),
            'total_credits_spent' => DB::table('user_credits')->sum('total_spent'),
            'total_credits_available' => DB::table('user_credits')->sum('available_credits'),
            'credits_used_today' => DB::table('credit_transactions')
                ->where('type', 'spend')
                ->whereDate('created_at', today())
                ->sum(DB::raw('ABS(amount)')),
            'credits_used_week' => DB::table('credit_transactions')
                ->where('type', 'spend')
                ->where('created_at', '>=', now()->subDays(7))
                ->sum(DB::raw('ABS(amount)')),
        ];

        // Plan Distribution
        $planDistribution = DB::table('user_subscriptions')
            ->where('status', 'active')
            ->select('plan_id', DB::raw('COUNT(*) as count'))
            ->groupBy('plan_id')
            ->pluck('count', 'plan_id')
            ->toArray();

        $plans = DB::table('user_plans')
            ->whereIn('id', array_keys($planDistribution))
            ->get()
            ->map(function ($plan) use ($planDistribution) {
                $plan->users_count = $planDistribution[$plan->id] ?? 0;
                return $plan;
            });

        // Chat Activity
        $chatStats = [
            'total_chats' => DB::table('mobile_chats')->count(),
            'chats_today' => DB::table('mobile_chats')->whereDate('created_at', today())->count(),
            'chats_week' => DB::table('mobile_chats')->where('created_at', '>=', now()->subDays(7))->count(),
            'messages_sent_today' => DB::table('mobile_chat_messages')->whereDate('created_at', today())->count(),
        ];

        // Recent Activity (Last 10 actions) - Fixed N+1 by using JOIN
        $recentActivities = DB::table('credit_transactions')
            ->leftJoin('users', 'credit_transactions.user_id', '=', 'users.id')
            ->select(
                'credit_transactions.id',
                'credit_transactions.type',
                'credit_transactions.amount',
                'credit_transactions.reason',
                'credit_transactions.created_at',
                'users.name as user_name',
                'users.mobile as user_mobile'
            )
            ->orderBy('credit_transactions.created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'reason' => $transaction->reason,
                    'user_name' => $transaction->user_name ?? 'Unknown',
                    'user_mobile' => $transaction->user_mobile ?? 'N/A',
                    'created_at' => $transaction->created_at,
                ];
            });

        // User Growth Chart Data (Last 30 days) - Fixed N+1 by using single GROUP BY query
        $startDate = now()->subDays(29)->startOfDay();
        $growthData = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('count', 'date')
            ->toArray();

        $userGrowth = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $userGrowth[] = [
                'date' => $date,
                'count' => $growthData[$date] ?? 0,
            ];
        }

        // Credit Usage Chart Data (Last 7 days)
        $creditUsage = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $spent = DB::table('credit_transactions')
                ->where('type', 'spend')
                ->whereDate('created_at', $date)
                ->sum(DB::raw('ABS(amount)'));
            $creditUsage[] = [
                'date' => $date,
                'amount' => $spent,
            ];
        }

        // Top Active Users (Last 7 days) - Fixed N+1 by using JOIN
        $topActiveUsers = DB::table('credit_transactions')
            ->join('users', 'credit_transactions.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.mobile',
                DB::raw('COUNT(*) as activity_count'),
                DB::raw('SUM(ABS(credit_transactions.amount)) as total_spent')
            )
            ->where('credit_transactions.created_at', '>=', now()->subDays(7))
            ->groupBy('users.id', 'users.name', 'users.mobile')
            ->orderByDesc('activity_count')
            ->limit(10)
            ->get()
            ->map(function ($data) {
                return [
                    'id' => $data->id,
                    'name' => $data->name,
                    'mobile' => $data->mobile,
                    'activity_count' => $data->activity_count,
                    'total_spent' => $data->total_spent,
                ];
            });

        // Combine stats for the view
        $stats = array_merge($userStats, [
            'total_plans' => \App\Models\PricingPlan::count(),
            'active_plans' => \App\Models\PricingPlan::where('is_active', true)->count(),
            'total_features' => \App\Models\Feature::count(),
            'active_features' => \App\Models\Feature::where('is_active', true)->count(),
        ]);

        return view('admin.dashboard', compact(
            'stats',
            'userStats',
            'creditStats',
            'plans',
            'chatStats',
            'recentActivities',
            'userGrowth',
            'creditUsage',
            'topActiveUsers'
        ));
    }

    /**
     * Get total sessions (placeholder - implement based on your session tracking)
     */
    private function getTotalSessions()
    {
        // This is a placeholder - implement based on your actual session tracking
        return 0;
    }

    /**
     * Display settings page
     */
    public function settings()
    {
        $settings = \App\Models\FrontendConfig::all()->pluck('config_value', 'config_key');

        // Add homepage settings to avoid undefined key errors
        $homepageSettings = \App\Models\HomepageSetting::all()->pluck('value', 'key');
        $settings = $settings->merge($homepageSettings);

        // Add default values for any missing keys to prevent undefined array key errors
        $defaults = [
            'hero_title' => 'Unlock Your',
            'hero_subtitle' => 'with AI',
            'hero_description' => 'Master any subject with BlinkStudy.',
            'hero_cta_text' => 'Get Started Free',
            'feature_section_title' => 'Everything you need to study smarter',
            'feature_section_description' => 'BlinkStudy combines advanced AI models with intuitive design.',
            'site_logo' => '',
            'logo_width' => '120',
            'logo_height' => '40',
            'chat_sidebar_logo' => '',
            'chat_sidebar_logo_height' => '32',
            'ip_whitelist_enabled' => '0',
            'ip_whitelist' => '',
            'site_name' => 'BlinkStudy',
            'maintenance_mode' => '0',
            'maintenance_message' => 'We are currently performing scheduled maintenance. Please check back soon.',
            'show_pricing_section' => '1',
            'show_seasonal_packs' => '0',
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }

        // Chat page enable/disable from DynamicAppConfig
        $settings['chat_enabled'] = \App\Models\DynamicAppConfig::getValue('features.chat_enabled', true) ? '1' : '0';

        return view('admin.settings', compact('settings'));
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        try {
            // Handle chat enable/disable (stored in DynamicAppConfig)
            \App\Models\DynamicAppConfig::setValue('features.chat_enabled', $request->boolean('chat_enabled'), 'boolean');

            $data = $request->except(['_token', '_method', 'chat_enabled']);

            // List of homepage settings keys
            $homepageKeys = [
                'logo_text', 'logo_highlight',
                'hero_badge_text', 'hero_title', 'hero_title_highlight', 'hero_subtitle', 'hero_description',
                'hero_cta_primary', 'hero_cta_secondary', 'hero_cta_text', 'hero_student_count',
                'features_badge', 'features_title', 'features_description',
                'feature_section_title', 'feature_section_description',
                'feature1_title', 'feature1_description', 'feature1_icon',
                'feature2_title', 'feature2_description', 'feature2_icon',
                'feature3_title', 'feature3_description', 'feature3_icon',
                'impact_badge', 'impact_title', 'impact_title_highlight', 'impact_description1',
                'impact_quote', 'impact_student_name', 'impact_student_title', 'impact_image', 'impact_quote_badge',
                'cta_title', 'cta_description', 'cta_button_primary', 'cta_button_secondary',
                'footer_description', 'footer_copyright', 'footer_newsletter_text'
            ];

            foreach ($data as $key => $value) {
                // Check if this is a homepage setting
                if (in_array($key, $homepageKeys)) {
                    \App\Models\HomepageSetting::where('key', $key)->update(['value' => $value]);
                } else {
                    // Otherwise, it's a FrontendConfig setting
                    \App\Models\FrontendConfig::updateOrCreate(
                        ['config_key' => $key],
                        ['config_value' => $value]
                    );
                }
            }

            // Clear cache
            \App\Models\HomepageSetting::clearCache();

            return redirect()->back()->with('success', 'Settings updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    /**
     * AI Settings page
     */
    public function aiSettings()
    {
        $settings = \App\Models\FrontendConfig::all()->pluck('config_value', 'config_key');

        // Add homepage settings to avoid undefined key errors
        $homepageSettings = \App\Models\HomepageSetting::all()->pluck('value', 'key');
        $settings = $settings->merge($homepageSettings);

        // Add default values for any missing keys
        $defaults = [
            'hero_title' => 'Unlock Your',
            'hero_subtitle' => 'with AI',
            'hero_description' => 'Master any subject with BlinkStudy.',
            'hero_cta_text' => 'Get Started Free',
            'feature_section_title' => 'Everything you need to study smarter',
            'feature_section_description' => 'BlinkStudy combines advanced AI models with intuitive design.',
            'site_logo' => '',
            'logo_width' => '120',
            'logo_height' => '40',
            'chat_sidebar_logo' => '',
            'chat_sidebar_logo_height' => '32',
            'ip_whitelist_enabled' => '0',
            'ip_whitelist' => '',
            'site_name' => 'BlinkStudy',
            'maintenance_mode' => '0',
            'maintenance_message' => 'We are currently performing scheduled maintenance. Please check back soon.',
            'show_pricing_section' => '1',
            'show_seasonal_packs' => '0',
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }

        $aiModels = \App\Models\AiModel::all();
        return view('admin.ai-settings', compact('aiModels', 'settings'));
    }

    /**
     * Update AI settings
     */
    public function updateAiSettings(Request $request)
    {
        // AI Provider Settings
        $aiSettings = [
            'openai_enabled' => $request->boolean('openai_enabled') ? '1' : '0',
            'openai_api_key' => $request->input('openai_api_key', ''),
            'openai_model' => $request->input('openai_model', 'gpt-4o'),

            'claude_enabled' => $request->boolean('claude_enabled') ? '1' : '0',
            'claude_api_key' => $request->input('claude_api_key', ''),
            'claude_model' => $request->input('claude_model', 'claude-3-5-sonnet-20241022'),

            'deepseek_enabled' => $request->boolean('deepseek_enabled') ? '1' : '0',
            'deepseek_api_key' => $request->input('deepseek_api_key', ''),
            'deepseek_model' => $request->input('deepseek_model', 'deepseek-chat'),

            'grok_enabled' => $request->boolean('grok_enabled') ? '1' : '0',
            'grok_api_key' => $request->input('grok_api_key', ''),
            'grok_model' => $request->input('grok_model', 'grok-beta'),

            'gemini_enabled' => $request->boolean('gemini_enabled') ? '1' : '0',
            'gemini_api_key' => $request->input('gemini_api_key', ''),
            'gemini_model' => $request->input('gemini_model', 'gemini-pro'),
        ];

        // Content Filter Settings
        $contentFilterSettings = [
            'ai.content_filter_enabled' => $request->boolean('content_filter_enabled') ? '1' : '0',
            'ai_educational_system_prompt' => $request->input('ai_educational_system_prompt', ''),
            'ai_ncert_system_prompt' => $request->input('ai_ncert_system_prompt', ''),
        ];

        // Thinking Level Settings
        $thinkingLevelSettings = [
            'ai.thinking_level.chat' => $request->input('thinking_level_chat', 'minimal'),
            'ai.thinking_level.pdf_solve' => $request->input('thinking_level_pdf_solve', 'medium'),
            'ai.thinking_level.math_reasoning' => $request->input('thinking_level_math_reasoning', 'high'),
            'ai.thinking_level.mcq_generation' => $request->input('thinking_level_mcq_generation', 'medium'),
        ];

        // Save all settings
        foreach ($aiSettings as $key => $value) {
            \App\Models\FrontendConfig::setValue('ai.' . $key, $value, 'string');
        }

        foreach ($contentFilterSettings as $key => $value) {
            \App\Models\FrontendConfig::setValue($key, $value, 'string');
        }

        foreach ($thinkingLevelSettings as $key => $value) {
            \App\Models\FrontendConfig::setValue($key, $value, 'string');
        }

        // Clear all related caches to ensure settings persist
        \Cache::forget('mobile_app_config');
        \Cache::forget('ai_config');
        \Cache::forget(\App\Models\FrontendConfig::CACHE_KEY);

        // Clear Laravel config cache
        try {
            \Artisan::call('config:clear');
        } catch (\Exception $e) {
            // Config clear may fail in some environments, that's OK
            \Log::warning('Config clear failed: ' . $e->getMessage());
        }

        // Force FrontendConfig to refresh
        \App\Models\FrontendConfig::clearCache();

        \Log::info('AI Settings Updated', [
            'admin_id' => auth()->id(),
            'openai_enabled' => $aiSettings['openai_enabled'],
            'gemini_enabled' => $aiSettings['gemini_enabled'],
        ]);

        return redirect()->back()->with('success', 'AI settings updated successfully');
    }

    /**
     * Payment gateways settings
     */
    public function paymentGateways()
    {
        $gateways = \App\Models\PaymentGateway::all();
        return view('admin.payment-gateways', compact('gateways'));
    }

    /**
     * Update payment gateway settings
     */
    public function updatePaymentGateways(Request $request)
    {
        $request->validate([
            'gateway_id' => 'required|exists:payment_gateways,id',
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
            'merchant_id' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'is_test_mode' => 'boolean',
        ]);

        $gateway = \App\Models\PaymentGateway::findOrFail($request->gateway_id);
        $gateway->update([
            'api_key' => $request->api_key,
            'api_secret' => $request->api_secret,
            'merchant_id' => $request->merchant_id,
            'webhook_secret' => $request->webhook_secret,
            'is_test_mode' => $request->boolean('is_test_mode'),
        ]);

        \Log::info('Payment Gateway Updated', [
            'gateway' => $gateway->name,
            'admin_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', $gateway->display_name . ' settings updated successfully!');
    }

    /**
     * Toggle payment gateway
     */
    public function togglePaymentGateway(Request $request, $gateway)
    {
        $gateway = \App\Models\PaymentGateway::findOrFail($gateway);
        $gateway->update([
            'is_enabled' => !$gateway->is_enabled,
        ]);

        \Log::info('Payment Gateway Toggled', [
            'gateway' => $gateway->name,
            'enabled' => $gateway->is_enabled,
            'admin_id' => auth()->id(),
        ]);

        $status = $gateway->is_enabled ? 'enabled' : 'disabled';
        $message = $gateway->display_name . ' has been ' . $status . '!';

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'is_enabled' => $gateway->is_enabled,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\HomepageSetting;
use App\Models\Page;
use App\Models\Plan;
use App\Models\PricingPlan;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    /**
     * Display the landing page
     *
     * @return \Illuminate\View\View
     */
    public function landing()
    {
        $chatHost = env('CHAT_SUBDOMAIN');
        if ($chatHost && request()->getHost() === $chatHost) {
            return app(ChatSubdomainController::class)->handleRoot(request());
        }

        // Use new Plan model and transform to match view expectations
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->with('features')
            ->get();

        // Transform to match the view's expected format
        $pricingPlans = $plans->map(function ($plan) {
            // Get feature descriptions for display - key features only
            $featuresList = [];
            $keyFeatures = ['ai_doubt', 'scan_solve', 'quiz', 'reasoning', 'whiteboard_video', 'pdf_analysis_pages', 'chat_history_days', 'support', 'ads'];

            foreach ($plan->features as $feature) {
                if (in_array($feature->feature_slug, $keyFeatures)) {
                    if ($feature->feature_slug === 'ads') {
                        $featuresList[] = $feature->limit_value == 0 ? 'No Ads - Clean Experience' : 'With Ads';
                    } elseif ($feature->isUnlimited()) {
                        $featuresList[] = $feature->getDisplayName() . ': Unlimited';
                    } elseif ($feature->limit_value > 0) {
                        $featuresList[] = $feature->getDisplayName() . ': ' . $feature->getLimitDescription();
                    }
                }
            }

            return (object) [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price' => $plan->price_monthly,
                'price_annual' => $plan->price_annual,
                'billing_period' => 'month',
                'is_popular' => $plan->is_recommended, // Pro is recommended = popular
                'is_recommended' => $plan->is_recommended,
                'features' => [
                    'popular' => $plan->is_recommended,
                    'recommended' => $plan->is_recommended,
                    'features_list' => $featuresList,
                ],
            ];
        });

        $features = Feature::getActiveFeatures();

        // Optimized: Batch load all settings at once
        $settingKeys = [
            'hero_title', 'hero_subtitle', 'hero_description', 'hero_cta_text',
            'feature_section_title', 'feature_section_description',
            'site_name', 'site_logo', 'logo_width', 'logo_height',
            'show_pricing_section'
        ];

        $defaults = [
            'hero_title' => 'Your AI Doubt Solver',
            'hero_subtitle' => 'For Students',
            'hero_description' => 'Get instant, step-by-step explanations for Math, Science, Literature, and more.',
            'hero_cta_text' => 'Solve',
            'feature_section_title' => 'Powered by Intelligence',
            'feature_section_description' => 'Experience cutting-edge AI technology designed to understand, analyze, and illuminate your questions with unparalleled clarity and precision.',
            'site_name' => 'BlinkStudy',
            'site_logo' => '',
            'logo_width' => '150',
            'logo_height' => '50',
            'show_pricing_section' => '1',
        ];

        $settings = Setting::getMany($settingKeys, $defaults);

        // Homepage dynamic settings (from admin panel) - CACHED for performance
        $allHomepageSettings = HomepageSetting::getAllCached();

        return view('pages.landing', compact('pricingPlans', 'features', 'settings', 'allHomepageSettings'));
    }

    /**
     * Display the pricing plans page
     *
     * @return \Illuminate\View\View
     */
    public function plans()
    {
        // Use new subscription Plan model with features
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->with('features')
            ->get();

        // Get active seasonal packs with their plan info (handle missing table gracefully)
        $seasonalPacks = collect();
        try {
            if (\Schema::hasTable('seasonal_packs')) {
                $seasonalPacks = \App\Models\SeasonalPack::available()
                    ->with('plan')
                    ->get();
            }
        } catch (\Exception $e) {
            // Log but don't fail - seasonal packs are optional
            \Log::warning('SeasonalPack query failed: ' . $e->getMessage());
        }

        // Get user's current subscription info
        $currentSubscription = null;
        $currentPlan = null;
        $currentPlanSlug = null;

        if (auth()->check()) {
            try {
                $currentSubscription = \App\Models\UserSubscription::where('user_id', auth()->id())
                    ->active()
                    ->with('plan')
                    ->first();

                if ($currentSubscription && $currentSubscription->plan) {
                    $currentPlan = $currentSubscription->plan;
                    $currentPlanSlug = $currentPlan->slug;
                }
            } catch (\Exception $e) {
                \Log::warning('UserSubscription query failed: ' . $e->getMessage());
            }
        }

        $settingKeys = ['site_name'];
        $defaults = ['site_name' => 'BlinkStudy'];
        $settings = Setting::getMany($settingKeys, $defaults);

        // Get show_seasonal_packs from FrontendConfig (admin settings) - handle missing table
        try {
            if (\Schema::hasTable('frontend_configs')) {
                $settings['show_seasonal_packs'] = \App\Models\FrontendConfig::where('config_key', 'show_seasonal_packs')->value('config_value') ?? '0';
            } else {
                $settings['show_seasonal_packs'] = '0';
            }
        } catch (\Exception $e) {
            $settings['show_seasonal_packs'] = '0';
        }

        return view('pages.plans', compact('plans', 'seasonalPacks', 'settings', 'currentSubscription', 'currentPlan', 'currentPlanSlug'));
    }

    /**
     * Display a dynamic page by slug
     *
     * @param  string  $slug
     * @return \Illuminate\View\View
     */
    public function show($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $settingKeys = ['site_name'];
        $defaults = ['site_name' => 'BlinkStudy'];
        $settings = Setting::getMany($settingKeys, $defaults);

        return view('pages.dynamic', compact('page', 'settings'));
    }

    // ==================== ADMIN METHODS ====================

    /**
     * Display a listing of all pages (Admin)
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $pages = Page::orderBy('order')->orderBy('title')->get();
        return view('admin.pages.index', compact('pages'));
    }

    /**
     * Show the form for creating a new page (Admin)
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.pages.create');
    }

    /**
     * Store a newly created page (Admin)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'boolean',
            'order' => 'integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['title']);

        // Ensure unique slug
        $slug = $validated['slug'];
        $count = Page::where('slug', 'LIKE', $slug . '%')->count();
        if ($count > 0) {
            $validated['slug'] = $slug . '-' . ($count + 1);
        }

        Page::create($validated);

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page created successfully.');
    }

    /**
     * Show the form for editing the specified page (Admin)
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\View\View
     */
    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    /**
     * Update the specified page (Admin)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'boolean',
            'order' => 'integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        // Update slug only if title changed
        if ($page->title !== $validated['title']) {
            $validated['slug'] = Str::slug($validated['title']);

            // Ensure unique slug (excluding current page)
            $slug = $validated['slug'];
            $count = Page::where('slug', 'LIKE', $slug . '%')
                ->where('id', '!=', $page->id)
                ->count();
            if ($count > 0) {
                $validated['slug'] = $slug . '-' . ($count + 1);
            }
        }

        $page->update($validated);

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page updated successfully.');
    }

    /**
     * Remove the specified page (Admin)
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page deleted successfully.');
    }

    /**
     * Toggle page active status (Admin)
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Page $page)
    {
        $page->update(['is_active' => !$page->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $page->is_active
        ]);
    }
}

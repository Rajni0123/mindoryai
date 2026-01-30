<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\HomepageSetting;
use App\Models\Page;
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
        $pricingPlans = PricingPlan::getActivePlans();
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
            'site_name' => 'Mindory',
            'site_logo' => '',
            'logo_width' => '150',
            'logo_height' => '50',
            'show_pricing_section' => '1',
        ];

        $settings = Setting::getMany($settingKeys, $defaults);

        // Homepage dynamic settings (from admin panel)
        $allHomepageSettings = HomepageSetting::all()->pluck('value', 'key')->toArray();
        $h = function ($key, $default = '') use ($allHomepageSettings) {
            return $allHomepageSettings[$key] ?? $default;
        };

        return view('pages.landing', compact('pricingPlans', 'features', 'settings', 'allHomepageSettings'));
    }

    /**
     * Display the pricing plans page
     *
     * @return \Illuminate\View\View
     */
    public function plans()
    {
        $pricingPlans = PricingPlan::getActivePlans();

        $settingKeys = ['site_name'];
        $defaults = ['site_name' => 'Mindory'];
        $settings = Setting::getMany($settingKeys, $defaults);

        return view('pages.plans', compact('pricingPlans', 'settings'));
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
        $defaults = ['site_name' => 'Mindory'];
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

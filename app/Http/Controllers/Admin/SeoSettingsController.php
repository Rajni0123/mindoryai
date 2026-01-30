<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrontendConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SeoSettingsController extends Controller
{
    /**
     * Display SEO settings page
     */
    public function index()
    {
        $seoSettings = [
            'site_title' => FrontendConfig::getValue('seo.site_title', 'Mindory - AI Knowledge Website'),
            'meta_description' => FrontendConfig::getValue('seo.meta_description', ''),
            'meta_keywords' => FrontendConfig::getValue('seo.meta_keywords', ''),
            'google_site_verification' => FrontendConfig::getValue('seo.google_site_verification', ''),
            'google_analytics_id' => FrontendConfig::getValue('seo.google_analytics_id', ''),
            'favicon_url' => FrontendConfig::getValue('seo.favicon_url', '/favicon.ico'),
            'og_image' => FrontendConfig::getValue('seo.og_image', ''),
            'twitter_handle' => FrontendConfig::getValue('seo.twitter_handle', ''),
        ];

        return view('admin.seo-settings', compact('seoSettings'));
    }

    /**
     * Update SEO settings
     */
    public function update(Request $request)
    {
        // Debug log
        \Log::info('SEO Settings Update Request', [
            'all_data' => $request->except(['favicon', 'og_image']),
            'has_favicon' => $request->hasFile('favicon'),
            'has_og_image' => $request->hasFile('og_image'),
        ]);

        $validator = Validator::make($request->all(), [
            'site_title' => 'required|string|max:255',
            'meta_description' => 'required|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'google_site_verification' => 'nullable|string|max:255',
            'google_analytics_id' => 'nullable|string|max:50',
            'twitter_handle' => 'nullable|string|max:50',
            'favicon' => 'nullable|file|mimes:ico,png,jpg,jpeg|max:512',
            'og_image' => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::error('SEO Settings Validation Failed', [
                'errors' => $validator->errors()->toArray(),
            ]);

            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your inputs.');
        }

        try {
            // Update basic SEO settings
            FrontendConfig::setValue('seo.site_title', $request->site_title);
            FrontendConfig::setValue('seo.meta_description', $request->meta_description);
            FrontendConfig::setValue('seo.meta_keywords', $request->meta_keywords ?? '');
            FrontendConfig::setValue('seo.google_site_verification', $request->google_site_verification ?? '');
            FrontendConfig::setValue('seo.google_analytics_id', $request->google_analytics_id ?? '');
            FrontendConfig::setValue('seo.twitter_handle', $request->twitter_handle ?? '');

            // Handle favicon upload - Save to icons directory
            if ($request->hasFile('favicon')) {
                $file = $request->file('favicon');
                $extension = $file->getClientOriginalExtension();
                $fileName = 'favicon_' . time() . '.' . $extension;

                // Ensure icons directory exists
                $iconsPath = public_path('icons');
                if (!file_exists($iconsPath)) {
                    mkdir($iconsPath, 0755, true);
                }

                $file->move($iconsPath, $fileName);
                FrontendConfig::setValue('seo.favicon_url', 'icons/' . $fileName);

                \Log::info('Favicon uploaded successfully', [
                    'file_name' => $fileName,
                    'path' => $iconsPath . '/' . $fileName,
                ]);
            }

            // Handle OG image upload - Save to images directory
            if ($request->hasFile('og_image')) {
                $file = $request->file('og_image');
                $extension = $file->getClientOriginalExtension();
                $fileName = 'og_image_' . time() . '.' . $extension;

                // Ensure images directory exists
                $imagesPath = public_path('images');
                if (!file_exists($imagesPath)) {
                    mkdir($imagesPath, 0755, true);
                }

                $file->move($imagesPath, $fileName);
                FrontendConfig::setValue('seo.og_image', 'images/' . $fileName);

                \Log::info('OG Image uploaded successfully', [
                    'file_name' => $fileName,
                    'path' => $imagesPath . '/' . $fileName,
                ]);
            }

            return redirect()->back()->with('success', 'SEO settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update SEO settings: ' . $e->getMessage());
        }
    }
}

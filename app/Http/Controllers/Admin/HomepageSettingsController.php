<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class HomepageSettingsController extends Controller
{
    /**
     * Display homepage settings management page
     */
    public function index()
    {
        $settings = HomepageSetting::getAllGrouped();

        return view('admin.homepage-settings', compact('settings'));
    }

    /**
     * Update a specific setting
     */
    public function update(Request $request, HomepageSetting $setting)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'type' => 'required|in:text,textarea,image,number',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $value = $request->value;

            // Handle image upload
            if ($request->type === 'image' && $request->hasFile('value')) {
                $file = $request->file('value');
                $filename = $setting->key . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/homepage'), $filename);
                $value = '/uploads/homepage/' . $filename;

                // Delete old image if exists
                if ($setting->value) {
                    $oldFile = public_path(ltrim($setting->value, '/'));
                    if (file_exists($oldFile) && str_contains($setting->value, '/uploads/')) {
                        @unlink($oldFile);
                    }
                }
            }

            $setting->update([
                'value' => $value,
                'type' => $request->type,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully!',
                'value' => $value
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $settings = $request->input('settings', []);

            foreach ($settings as $key => $value) {
                HomepageSetting::where('key', $key)->update(['value' => $value]);
            }

            HomepageSetting::clearCache();

            return redirect()->back()->with('success', 'Settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update settings: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Upload image for a setting
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = HomepageSetting::where('key', $request->key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }

            // Upload new image to public/uploads/homepage/
            $file = $request->file('image');
            $filename = $request->key . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/homepage'), $filename);
            $url = '/uploads/homepage/' . $filename;

            // Delete old image if exists
            if ($setting->value) {
                $oldFile = public_path(ltrim($setting->value, '/'));
                if (file_exists($oldFile) && str_contains($setting->value, '/uploads/')) {
                    @unlink($oldFile);
                }
            }

            // Update setting
            $setting->update(['value' => $url]);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully!',
                'url' => $url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all settings cache
     */
    public function clearCache()
    {
        try {
            HomepageSetting::clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrontendConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FrontendConfigController extends Controller
{
    /**
     * Display frontend configs management page
     */
    public function index()
    {
        $configs = FrontendConfig::orderBy('config_key')->paginate(20);

        return view('admin.frontend-configs', compact('configs'));
    }

    /**
     * Store or update a config
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'config_key' => 'required|string|max:255|regex:/^[a-z0-9_]+$/',
            'config_value' => 'required',
            'value_type' => 'required|in:boolean,string,number,json',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ], [
            'config_key.regex' => 'Config key can only contain lowercase letters, numbers, and underscores'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Validate JSON if type is json
            if ($request->value_type === 'json') {
                $decoded = json_decode($request->config_value);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    return redirect()->back()
                        ->with('error', 'Invalid JSON format')
                        ->withInput();
                }
            }

            // Sanitize value to prevent XSS
            $sanitizedValue = $this->sanitizeValue($request->config_value, $request->value_type);

            FrontendConfig::updateOrCreate(
                ['config_key' => $request->config_key],
                [
                    'config_value' => $sanitizedValue,
                    'value_type' => $request->value_type,
                    'description' => $request->description,
                    'is_active' => $request->has('is_active') ? true : false
                ]
            );

            return redirect()->back()->with('success', 'Configuration saved successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to save configuration: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update a specific config
     */
    public function update(Request $request, FrontendConfig $config)
    {
        $validator = Validator::make($request->all(), [
            'config_value' => 'required',
            'value_type' => 'required|in:boolean,string,number,json',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validate JSON if type is json
            if ($request->value_type === 'json') {
                $decoded = json_decode($request->config_value);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid JSON format'
                    ], 422);
                }
            }

            // Sanitize value to prevent XSS
            $sanitizedValue = $this->sanitizeValue($request->config_value, $request->value_type);

            $config->update([
                'config_value' => $sanitizedValue,
                'value_type' => $request->value_type,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle config active status
     */
    public function toggle(Request $request, FrontendConfig $config)
    {
        try {
            $config->update([
                'is_active' => $request->input('is_active', !$config->is_active)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Config status updated',
                'is_active' => $config->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a config
     */
    public function destroy(FrontendConfig $config)
    {
        try {
            $config->delete();

            return response()->json([
                'success' => true,
                'message' => 'Configuration deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all config cache
     */
    public function clearCache()
    {
        try {
            FrontendConfig::clearCache();

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

    /**
     * Sanitize value to prevent XSS
     */
    private function sanitizeValue($value, $type)
    {
        if ($type === 'json') {
            // For JSON, just validate structure, don't modify
            return $value;
        }

        if ($type === 'string') {
            // Strip HTML tags to prevent XSS
            return strip_tags($value);
        }

        return $value;
    }
}

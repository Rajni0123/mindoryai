<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorageSetting;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StorageSettingsController extends Controller
{
    protected FileStorageService $fileStorageService;

    public function __construct(FileStorageService $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;
    }

    /**
     * Display storage settings page
     */
    public function index()
    {
        $storages = StorageSetting::orderBy('created_at', 'desc')->get();
        $activeStorage = StorageSetting::getActive();

        return view('admin.storage-settings.index', compact('storages', 'activeStorage'));
    }

    /**
     * Show the form for creating new storage
     */
    public function create()
    {
        return view('admin.storage-settings.create');
    }

    /**
     * Store new storage configuration
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|in:r2,s3,local',
            'key' => 'nullable|string|max:255',
            'secret' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:100',
            'bucket' => 'nullable|string|max:255',
            'endpoint' => 'nullable|url|max:500',
            'cdn_url' => 'nullable|url|max:500',
            'url_prefix' => 'nullable|string|max:255',
            'max_file_size' => 'nullable|integer|min:1|max:104857600', // Max 100MB
            'allowed_extensions' => 'nullable|array',
            'allowed_extensions.*' => 'string|max:10',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Handle allowed extensions
        if (isset($validated['allowed_extensions'])) {
            $validated['allowed_extensions'] = $validated['allowed_extensions'];
        } else {
            // Default extensions
            $validated['allowed_extensions'] = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        }

        // Set default max file size if not provided (10MB)
        if (!isset($validated['max_file_size'])) {
            $validated['max_file_size'] = 10485760;
        }

        $validated['is_active'] = false; // New storage is inactive by default

        StorageSetting::create($validated);

        return redirect()
            ->route('admin.storage-settings.index')
            ->with('success', 'Storage configuration created successfully!');
    }

    /**
     * Show the form for editing storage
     */
    public function edit(StorageSetting $storageSetting)
    {
        return view('admin.storage-settings.edit', compact('storageSetting'));
    }

    /**
     * Update storage configuration
     */
    public function update(Request $request, StorageSetting $storageSetting)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|in:r2,s3,local',
            'key' => 'nullable|string|max:255',
            'secret' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:100',
            'bucket' => 'nullable|string|max:255',
            'endpoint' => 'nullable|url|max:500',
            'cdn_url' => 'nullable|url|max:500',
            'url_prefix' => 'nullable|string|max:255',
            'max_file_size' => 'nullable|integer|min:1|max:104857600',
            'allowed_extensions' => 'nullable|array',
            'allowed_extensions.*' => 'string|max:10',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Handle allowed extensions
        if (isset($validated['allowed_extensions'])) {
            $validated['allowed_extensions'] = $validated['allowed_extensions'];
        } else {
            // Keep existing or set default
            $validated['allowed_extensions'] = $storageSetting->allowed_extensions ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        }

        // Keep existing is_active state unless specifically changed
        if ($request->has('is_active') && $request->is_active) {
            // Activate this storage and deactivate all others
            $storageSetting->activate();
        }

        $storageSetting->update($validated);

        return redirect()
            ->route('admin.storage-settings.index')
            ->with('success', 'Storage configuration updated successfully!');
    }

    /**
     * Remove storage configuration
     */
    public function destroy(StorageSetting $storageSetting)
    {
        if ($storageSetting->isActive()) {
            return redirect()
                ->route('admin.storage-settings.index')
                ->with('error', 'Cannot delete active storage configuration. Please activate another storage first.');
        }

        $storageSetting->delete();

        return redirect()
            ->route('admin.storage-settings.index')
            ->with('success', 'Storage configuration deleted successfully!');
    }

    /**
     * Activate storage configuration
     */
    public function activate(StorageSetting $storageSetting)
    {
        // Test connection first
        try {
            $storageSetting->activate();

            // Reinitialize the file storage service with new active storage
            $this->fileStorageService->__construct();

            return redirect()
                ->route('admin.storage-settings.index')
                ->with('success', 'Storage activated successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.storage-settings.index')
                ->with('error', 'Failed to activate storage: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate storage configuration
     */
    public function deactivate(StorageSetting $storageSetting)
    {
        $storageSetting->deactivate();

        // Reinitialize the file storage service
        $this->fileStorageService->__construct();

        return redirect()
            ->route('admin.storage-settings.index')
            ->with('success', 'Storage deactivated successfully!');
    }

    /**
     * Test storage connection with actual upload test
     */
    public function testConnection(StorageSetting $storageSetting)
    {
        try {
            // Temporarily activate this storage for testing
            $wasActive = $storageSetting->isActive();
            $currentActive = null;

            if (!$wasActive) {
                $currentActive = StorageSetting::getActive();
                $storageSetting->activate();
            }

            // Create a test file service
            $testService = new FileStorageService();

            if (!$testService->isActive()) {
                // Restore previous active storage
                if (!$wasActive && $currentActive) {
                    $currentActive->activate();
                }
                throw new \Exception('Storage service failed to initialize. Check credentials and endpoint.');
            }

            // Actually test upload + delete
            $testKey = '_connection_test_' . time() . '.txt';
            $testBody = 'Mindory connection test ' . now()->toIso8601String();

            // Use reflection to access the r2Put method for a real upload test
            $reflection = new \ReflectionMethod($testService, 'r2Put');
            $reflection->setAccessible(true);
            $uploadOk = $reflection->invoke($testService, $testKey, $testBody, 'text/plain');

            if (!$uploadOk) {
                if (!$wasActive && $currentActive) {
                    $currentActive->activate();
                }
                throw new \Exception('Upload test failed. Check credentials and bucket permissions.');
            }

            // Clean up test file
            $deleteReflection = new \ReflectionMethod($testService, 'r2Delete');
            $deleteReflection->setAccessible(true);
            $deleteReflection->invoke($testService, $testKey);

            // Restore previous active storage
            if (!$wasActive && $currentActive) {
                $currentActive->activate();
            }

            return response()->json([
                'success' => true,
                'message' => 'Connection successful! Upload and delete verified.'
            ]);

        } catch (\Exception $e) {
            Log::error('Storage connection test failed', [
                'storage_id' => $storageSetting->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get storage status (AJAX)
     */
    public function getStatus()
    {
        $isActive = $this->fileStorageService->isActive();
        $activeStorage = $this->fileStorageService->getActiveStorage();
        $validationRules = $this->fileStorageService->getValidationRules();

        return response()->json([
            'is_active' => $isActive,
            'storage' => $activeStorage ? [
                'id' => $activeStorage->id,
                'name' => $activeStorage->name,
                'driver' => $activeStorage->driver,
                'bucket' => $activeStorage->bucket,
                'endpoint' => $activeStorage->endpoint,
            ] : null,
            'validation' => $validationRules,
        ]);
    }
}

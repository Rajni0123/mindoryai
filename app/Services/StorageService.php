<?php

namespace App\Services;

use App\Models\StorageSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class StorageService
{
    protected ?StorageSetting $activeStorage = null;

    public function __construct()
    {
        $this->loadActiveStorage();
    }

    /**
     * Load the active storage configuration
     */
    protected function loadActiveStorage(): void
    {
        $this->activeStorage = StorageSetting::getActive();

        if ($this->activeStorage) {
            $this->configureDisk($this->activeStorage);
        }
    }

    /**
     * Configure a storage disk dynamically
     */
    protected function configureDisk(StorageSetting $storage): void
    {
        $config = [
            'driver' => $storage->driver,
            'key' => $storage->key,
            'secret' => $storage->secret,
            'region' => $storage->region,
            'bucket' => $storage->bucket,
        ];

        // Add endpoint for custom S3-compatible services
        if ($storage->endpoint) {
            $config['endpoint'] = $storage->endpoint;
        }

        // Add URL prefix if configured
        if ($storage->url_prefix) {
            $config['url_prefix'] = $storage->url_prefix;
        }

        // Dynamically set the disk configuration
        config(["filesystems.disks.{$storage->driver}" => $config]);
    }

    /**
     * Get the active storage disk instance
     */
    public function disk()
    {
        if (!$this->activeStorage) {
            Log::warning('No active storage configured, falling back to default');
            return Storage::disk(config('filesystems.default', 'local'));
        }

        return Storage::disk($this->activeStorage->driver);
    }

    /**
     * Upload a file to the active storage
     */
    public function upload(string $path, $contents, string $diskName = null): string|false
    {
        try {
            $disk = $diskName ? Storage::disk($diskName) : $this->disk();

            $result = $disk->put($path, $contents);

            if ($result) {
                Log::info('File uploaded successfully', [
                    'path' => $path,
                    'disk' => $diskName ?? $this->activeStorage?->driver,
                ]);

                return $this->getUrl($path, $diskName);
            }

            return false;
        } catch (Exception $e) {
            Log::error('File upload failed', [
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Upload a file with a specific name
     */
    public function uploadFile(string $path, string $filePath, string $diskName = null): string|false
    {
        try {
            $disk = $diskName ? Storage::disk($diskName) : $this->disk();

            $result = $disk->putFile($path, $filePath);

            if ($result) {
                Log::info('File uploaded successfully', [
                    'path' => $path,
                    'file' => $filePath,
                    'disk' => $diskName ?? $this->activeStorage?->driver,
                ]);

                return $this->getUrl($result, $diskName);
            }

            return false;
        } catch (Exception $e) {
            Log::error('File upload failed', [
                'path' => $path,
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get the public URL for a file
     */
    public function getUrl(string $path, string $diskName = null): string
    {
        $disk = $diskName ? Storage::disk($diskName) : $this->disk();

        // Check if CDN URL is configured
        if ($this->activeStorage && $this->activeStorage->cdn_url) {
            return rtrim($this->activeStorage->cdn_url, '/') . '/' . ltrim($path, '/');
        }

        return $disk->url($path);
    }

    /**
     * Delete a file from storage
     */
    public function delete(string $path, string $diskName = null): bool
    {
        try {
            $disk = $diskName ? Storage::disk($diskName) : $this->disk();
            $result = $disk->delete($path);

            if ($result) {
                Log::info('File deleted successfully', [
                    'path' => $path,
                    'disk' => $diskName ?? $this->activeStorage?->driver,
                ]);
            }

            return $result;
        } catch (Exception $e) {
            Log::error('File deletion failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if a file exists
     */
    public function exists(string $path, string $diskName = null): bool
    {
        $disk = $diskName ? Storage::disk($diskName) : $this->disk();
        return $disk->exists($path);
    }

    /**
     * Get file contents
     */
    public function get(string $path, string $diskName = null): string|false
    {
        try {
            $disk = $diskName ? Storage::disk($diskName) : $this->disk();
            return $disk->get($path);
        } catch (Exception $e) {
            Log::error('Failed to get file contents', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get active storage information
     */
    public function getActiveStorageInfo(): ?array
    {
        if (!$this->activeStorage) {
            return null;
        }

        return [
            'id' => $this->activeStorage->id,
            'name' => $this->activeStorage->name,
            'driver' => $this->activeStorage->driver,
            'bucket' => $this->activeStorage->bucket,
            'region' => $this->activeStorage->region,
            'is_active' => $this->activeStorage->is_active,
            'max_file_size' => $this->activeStorage->max_file_size,
            'allowed_extensions' => $this->activeStorage->getAllowedExtensions(),
        ];
    }

    /**
     * Test the active storage connection
     */
    public function testConnection(): array
    {
        if (!$this->activeStorage) {
            return [
                'success' => false,
                'message' => 'No active storage configured',
            ];
        }

        try {
            $disk = $this->disk();

            // Try to list files (or check if we can access the bucket)
            $disk->files('/');

            return [
                'success' => true,
                'message' => 'Storage connection successful',
                'driver' => $this->activeStorage->driver,
                'bucket' => $this->activeStorage->bucket,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Storage connection failed: ' . $e->getMessage(),
                'driver' => $this->activeStorage->driver,
            ];
        }
    }

    /**
     * Validate file before upload
     */
    public function validateFile(\Illuminate\Http\UploadedFile $file): array
    {
        if (!$this->activeStorage) {
            return [
                'valid' => false,
                'message' => 'No active storage configured',
            ];
        }

        // Check file size
        $maxSize = $this->activeStorage->getMaxFileSize();
        if ($file->getSize() > $maxSize) {
            return [
                'valid' => false,
                'message' => "File size exceeds maximum allowed size of {$maxSize} bytes",
            ];
        }

        // Check file extension
        $allowedExtensions = $this->activeStorage->getAllowedExtensions();
        if (!empty($allowedExtensions)) {
            $extension = $file->getClientOriginalExtension();
            if (!in_array(strtolower($extension), $allowedExtensions)) {
                return [
                    'valid' => false,
                    'message' => "File extension '{$extension}' is not allowed. Allowed: " . implode(', ', $allowedExtensions),
                ];
            }
        }

        return ['valid' => true];
    }
}

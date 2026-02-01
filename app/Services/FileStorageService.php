<?php

namespace App\Services;

use App\Models\StorageSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class FileStorageService
{
    protected ?StorageSetting $activeStorage = null;
    protected bool $r2Ready = false;

    // Cloudflare account ID (extracted from R2 endpoint URL)
    protected ?string $cfAccountId = null;

    public function __construct()
    {
        $this->initializeActiveStorage();
    }

    /**
     * Initialize the active storage configuration
     */
    protected function initializeActiveStorage(): void
    {
        $this->activeStorage = StorageSetting::getActive();

        if ($this->activeStorage && $this->activeStorage->isActive()) {
            $this->initializeR2();
        }
    }

    /**
     * Initialize R2 connection using Cloudflare REST API
     */
    protected function initializeR2(): void
    {
        if (!$this->activeStorage) {
            return;
        }

        // Need key (CF email) and secret (CF Global API Key) for REST API auth
        if (empty($this->activeStorage->key) || empty($this->activeStorage->secret)) {
            \Log::warning('FileStorage: Credentials not configured', [
                'storage_id' => $this->activeStorage->id,
            ]);
            return;
        }

        if (empty($this->activeStorage->endpoint) || empty($this->activeStorage->bucket)) {
            \Log::warning('FileStorage: Endpoint or bucket not configured');
            return;
        }

        // Extract account ID from R2 endpoint URL
        // Format: https://{account_id}.r2.cloudflarestorage.com
        if (preg_match('/https?:\/\/([a-f0-9]+)\.r2\.cloudflarestorage\.com/', $this->activeStorage->endpoint, $m)) {
            $this->cfAccountId = $m[1];
        } else {
            // Fallback: use endpoint as-is for S3-compatible storage
            $this->cfAccountId = null;
        }

        $this->r2Ready = true;
        \Log::info('FileStorage R2 initialized', [
            'storage_id' => $this->activeStorage->id,
            'bucket' => $this->activeStorage->bucket,
            'account_id' => $this->cfAccountId ? substr($this->cfAccountId, 0, 8) . '...' : 'none',
        ]);
    }

    /**
     * Upload to R2 via Cloudflare REST API
     */
    protected function r2Put(string $key, string $body, string $contentType): bool
    {
        if (!$this->cfAccountId) {
            \Log::error('R2 account ID not available');
            return false;
        }

        $url = "https://api.cloudflare.com/client/v4/accounts/{$this->cfAccountId}/r2/buckets/{$this->activeStorage->bucket}/objects/{$key}";

        $response = Http::timeout(120)->withHeaders([
            'X-Auth-Email' => $this->activeStorage->key,
            'X-Auth-Key' => $this->activeStorage->secret,
            'Content-Type' => $contentType,
        ])->withBody($body, $contentType)->put($url);

        if ($response->successful()) {
            return true;
        }

        \Log::error('R2 upload failed', [
            'key' => $key,
            'status' => $response->status(),
            'error' => substr($response->body(), 0, 300),
        ]);
        return false;
    }

    /**
     * Delete from R2 via Cloudflare REST API
     */
    protected function r2Delete(string $key): bool
    {
        if (!$this->cfAccountId) {
            return false;
        }

        $url = "https://api.cloudflare.com/client/v4/accounts/{$this->cfAccountId}/r2/buckets/{$this->activeStorage->bucket}/objects/{$key}";

        $response = Http::withHeaders([
            'X-Auth-Email' => $this->activeStorage->key,
            'X-Auth-Key' => $this->activeStorage->secret,
        ])->delete($url);

        return $response->successful();
    }

    /**
     * Upload a file to R2 storage
     */
    public function upload($file, string $path = ''): ?array
    {
        if (!$this->activeStorage || !$this->activeStorage->isActive()) {
            \Log::error('No active storage configured');
            return null;
        }

        // Validate file size
        $maxSize = $this->activeStorage->getMaxFileSize();
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size of ' . $this->formatBytes($maxSize));
        }

        // Validate file extension
        $allowedExtensions = $this->activeStorage->getAllowedExtensions();
        $extension = strtolower($file->getClientOriginalExtension());
        if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions)) {
            throw new \Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions));
        }

        try {
            $filename = $this->generateFileName($file);
            $fullPath = $path ? trim($path, '/') . '/' . $filename : $filename;

            $body = file_get_contents($file->getRealPath());
            $ok = $this->r2Put($fullPath, $body, $file->getMimeType());

            if (!$ok) {
                throw new \Exception('R2 upload failed');
            }

            $fileUrl = $this->getFileUrl($fullPath);

            \Log::info('File uploaded successfully', [
                'file' => $filename,
                'path' => $fullPath,
                'url' => $fileUrl,
                'size' => $file->getSize()
            ]);

            return [
                'file_name' => $filename,
                'file_url' => $fileUrl,
                'file_path' => $fullPath,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'extension' => $extension,
            ];

        } catch (\Exception $e) {
            \Log::error('File upload error: ' . $e->getMessage());
            throw new \Exception('Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Upload file from base64 data
     */
    public function uploadFromBase64(string $base64Data, string $filename, string $mimeType, string $path = ''): ?array
    {
        if (!$this->activeStorage || !$this->activeStorage->isActive()) {
            \Log::error('No active storage configured');
            return null;
        }

        try {
            $fileData = base64_decode($base64Data);
            if ($fileData === false) {
                throw new \Exception('Invalid base64 data');
            }

            $fileSize = strlen($fileData);

            $maxSize = $this->activeStorage->getMaxFileSize();
            if ($fileSize > $maxSize) {
                throw new \Exception('File size exceeds maximum allowed size of ' . $this->formatBytes($maxSize));
            }

            $allowedExtensions = $this->activeStorage->getAllowedExtensions();
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions)) {
                throw new \Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions));
            }

            $uniqueFilename = $this->generateUniqueFileName($filename);
            $fullPath = $path ? trim($path, '/') . '/' . $uniqueFilename : $uniqueFilename;

            $ok = $this->r2Put($fullPath, $fileData, $mimeType);

            if (!$ok) {
                throw new \Exception('R2 upload failed');
            }

            $fileUrl = $this->getFileUrl($fullPath);

            \Log::info('Base64 file uploaded successfully', [
                'file' => $uniqueFilename,
                'path' => $fullPath,
                'url' => $fileUrl,
                'size' => $fileSize
            ]);

            return [
                'file_name' => $uniqueFilename,
                'file_url' => $fileUrl,
                'file_path' => $fullPath,
                'file_type' => $mimeType,
                'file_size' => $fileSize,
                'extension' => $extension,
            ];

        } catch (\Exception $e) {
            \Log::error('Base64 file upload error: ' . $e->getMessage());
            throw new \Exception('Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Upload a file from a local filesystem path to R2 storage
     */
    public function uploadFromPath(string $localFilePath, string $remotePath, ?string $contentType = null): ?array
    {
        if (!$this->activeStorage || !$this->activeStorage->isActive() || !$this->r2Ready) {
            \Log::error('uploadFromPath: No active storage configured');
            return null;
        }

        if (!file_exists($localFilePath)) {
            \Log::error('uploadFromPath: Local file not found', ['path' => $localFilePath]);
            return null;
        }

        try {
            $fileContent = file_get_contents($localFilePath);
            $fileSize = filesize($localFilePath);
            $extension = strtolower(pathinfo($localFilePath, PATHINFO_EXTENSION));

            if (!$contentType) {
                $contentType = mime_content_type($localFilePath) ?: 'application/octet-stream';
            }

            $ok = $this->r2Put($remotePath, $fileContent, $contentType);

            if (!$ok) {
                \Log::error('uploadFromPath: R2 PUT failed', ['path' => $remotePath]);
                return null;
            }

            $fileUrl = $this->getFileUrl($remotePath);

            \Log::info('File uploaded from path successfully', [
                'local_path' => $localFilePath,
                'remote_path' => $remotePath,
                'url' => $fileUrl,
                'size' => $fileSize,
            ]);

            return [
                'file_name' => basename($remotePath),
                'file_url' => $fileUrl,
                'file_path' => $remotePath,
                'file_type' => $contentType,
                'file_size' => $fileSize,
                'extension' => $extension,
            ];

        } catch (\Exception $e) {
            \Log::error('uploadFromPath error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete a file from storage
     */
    public function delete(string $filePath): bool
    {
        if (!$this->activeStorage || !$this->r2Ready) {
            return false;
        }

        try {
            $ok = $this->r2Delete($filePath);
            if ($ok) {
                \Log::info('File deleted successfully', ['path' => $filePath]);
            }
            return $ok;
        } catch (\Exception $e) {
            \Log::error('Failed to delete file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the public URL for a file
     */
    protected function getFileUrl(string $filePath): string
    {
        // Use CDN URL if configured (custom domain or r2.dev public URL)
        if ($this->activeStorage->cdn_url) {
            return rtrim($this->activeStorage->cdn_url, '/') . '/' . $filePath;
        }

        // Fallback: endpoint/bucket/path (won't be publicly accessible for R2)
        if ($this->activeStorage->endpoint) {
            return rtrim($this->activeStorage->endpoint, '/') . '/' . $this->activeStorage->bucket . '/' . $filePath;
        }

        return 'https://' . $this->activeStorage->bucket . '.s3.amazonaws.com/' . $filePath;
    }

    /**
     * Download file content from R2 using Cloudflare REST API
     */
    public function downloadFile(string $fileUrl): string
    {
        if (!$this->activeStorage || !$this->r2Ready) {
            throw new \Exception('No active storage configured');
        }

        // Extract file path from URL
        $parsedUrl = parse_url($fileUrl);
        $path = ltrim($parsedUrl['path'] ?? '', '/');

        // Remove bucket name from path if present
        $bucketName = $this->activeStorage->bucket;
        if (strpos($path, $bucketName . '/') === 0) {
            $path = substr($path, strlen($bucketName) + 1);
        }

        // If the URL is a CDN/public URL, just fetch it directly
        if ($this->activeStorage->cdn_url && strpos($fileUrl, $this->activeStorage->cdn_url) === 0) {
            $response = Http::timeout(30)->get($fileUrl);
            if ($response->successful()) {
                return $response->body();
            }
        }

        // Use Cloudflare API to get the object
        $url = "https://api.cloudflare.com/client/v4/accounts/{$this->cfAccountId}/r2/buckets/{$bucketName}/objects/{$path}";

        $response = Http::withHeaders([
            'X-Auth-Email' => $this->activeStorage->key,
            'X-Auth-Key' => $this->activeStorage->secret,
        ])->get($url);

        if ($response->successful()) {
            return $response->body();
        }

        throw new \Exception('Failed to download file from R2: HTTP ' . $response->status());
    }

    /**
     * Generate unique filename
     */
    protected function generateFileName($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = now()->format('Y-m-d-His');
        $random = Str::random(6);

        return "{$timestamp}-{$basename}-{$random}.{$extension}";
    }

    /**
     * Generate unique filename from original filename
     */
    protected function generateUniqueFileName(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $basename = Str::slug(pathinfo($filename, PATHINFO_FILENAME));
        $timestamp = now()->format('Y-m-d-His');
        $random = Str::random(6);

        return "{$timestamp}-{$basename}-{$random}.{$extension}";
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Check if storage is active and configured
     */
    public function isActive(): bool
    {
        return $this->activeStorage !== null && $this->activeStorage->isActive() && $this->r2Ready;
    }

    /**
     * Get active storage configuration
     */
    public function getActiveStorage(): ?StorageSetting
    {
        return $this->activeStorage;
    }

    /**
     * Get validation rules for file upload
     */
    public function getValidationRules(): array
    {
        if (!$this->activeStorage) {
            return [
                'file' => 'required|file|max:10240',
                'allowed' => 'jpg,jpeg,png,gif,webp,pdf'
            ];
        }

        $maxSizeKB = $this->activeStorage->getMaxFileSize() / 1024;
        $allowed = implode(',', $this->activeStorage->getAllowedExtensions());

        return [
            'max_size_kb' => $maxSizeKB,
            'allowed_extensions' => $allowed,
        ];
    }
}

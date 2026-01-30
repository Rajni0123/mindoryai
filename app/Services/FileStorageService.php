<?php

namespace App\Services;

use App\Models\StorageSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class FileStorageService
{
    protected ?S3Client $s3Client = null;
    protected ?StorageSetting $activeStorage = null;

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
            $this->initializeS3Client();
        }
    }

    /**
     * Initialize S3 Client for R2/S3 compatible storage
     */
    protected function initializeS3Client(): void
    {
        if (!$this->activeStorage) {
            \Log::warning('initializeS3Client: No active storage configured');
            return;
        }

        // Check if credentials are properly configured
        if (empty($this->activeStorage->key) || empty($this->activeStorage->secret)) {
            \Log::warning('initializeS3Client: S3 credentials not configured', [
                'storage_id' => $this->activeStorage->id,
                'has_key' => !empty($this->activeStorage->key),
                'has_secret' => !empty($this->activeStorage->secret),
            ]);
            $this->s3Client = null;
            return;
        }

        // Check if endpoint and bucket are configured
        if (empty($this->activeStorage->endpoint) || empty($this->activeStorage->bucket)) {
            \Log::warning('initializeS3Client: S3 endpoint or bucket not configured', [
                'storage_id' => $this->activeStorage->id,
                'has_endpoint' => !empty($this->activeStorage->endpoint),
                'has_bucket' => !empty($this->activeStorage->bucket),
            ]);
            $this->s3Client = null;
            return;
        }

        try {
            \Log::info('Initializing S3Client', [
                'storage_id' => $this->activeStorage->id,
                'name' => $this->activeStorage->name,
                'driver' => $this->activeStorage->driver,
                'endpoint' => $this->activeStorage->endpoint,
                'bucket' => $this->activeStorage->bucket,
                'region' => $this->activeStorage->region,
                'has_key' => !empty($this->activeStorage->key),
                'has_secret' => !empty($this->activeStorage->secret),
                'is_active' => $this->activeStorage->isActive(),
            ]);

            $this->s3Client = new S3Client([
                'version' => 'latest',
                'region' => $this->activeStorage->region ?? 'auto',
                'endpoint' => $this->activeStorage->endpoint,
                'credentials' => [
                    'key' => $this->activeStorage->key,
                    'secret' => $this->activeStorage->secret,
                ],
                'use_path_style_endpoint' => true, // Required for R2
                'http' => [
                    'verify' => false, // Set to true in production
                ],
            ]);

            \Log::info('S3Client initialized successfully', [
                'storage_id' => $this->activeStorage->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to initialize S3 client', [
                'error' => $e->getMessage(),
                'storage_id' => $this->activeStorage->id,
                'trace' => $e->getTraceAsString()
            ]);
            $this->s3Client = null;
        }
    }

    /**
     * Upload a file to R2/S3 storage
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $path Optional custom path
     * @return array|null Returns file info array or null on failure
     */
    public function upload($file, string $path = ''): ?array
    {
        // Check if active storage exists
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
            // Generate unique filename
            $filename = $this->generateFileName($file);
            $fullPath = $path ? trim($path, '/') . '/' . $filename : $filename;

            // Upload file
            $result = $this->s3Client->putObject([
                'Bucket' => $this->activeStorage->bucket,
                'Key' => $fullPath,
                'SourceFile' => $file->getRealPath(),
                'ContentType' => $file->getMimeType(),
                'ACL' => 'public-read', // Make file publicly accessible
            ]);

            // Get the file URL
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

        } catch (AwsException $e) {
            \Log::error('AWS S3/R2 upload error: ' . $e->getMessage());
            throw new \Exception('Failed to upload file: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('File upload error: ' . $e->getMessage());
            throw new \Exception('Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Upload file from base64 data
     *
     * @param string $base64Data
     * @param string $filename
     * @param string $mimeType
     * @param string $path
     * @return array|null
     */
    public function uploadFromBase64(string $base64Data, string $filename, string $mimeType, string $path = ''): ?array
    {
        // Check if active storage exists
        if (!$this->activeStorage || !$this->activeStorage->isActive()) {
            \Log::error('No active storage configured');
            return null;
        }

        try {
            // Decode base64 data
            $fileData = base64_decode($base64Data);
            if ($fileData === false) {
                throw new \Exception('Invalid base64 data');
            }

            $fileSize = strlen($fileData);

            // Validate file size
            $maxSize = $this->activeStorage->getMaxFileSize();
            if ($fileSize > $maxSize) {
                throw new \Exception('File size exceeds maximum allowed size of ' . $this->formatBytes($maxSize));
            }

            // Validate file extension
            $allowedExtensions = $this->activeStorage->getAllowedExtensions();
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions)) {
                throw new \Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions));
            }

            // Generate unique filename
            $uniqueFilename = $this->generateUniqueFileName($filename);
            $fullPath = $path ? trim($path, '/') . '/' . $uniqueFilename : $uniqueFilename;

            // Upload file
            $result = $this->s3Client->putObject([
                'Bucket' => $this->activeStorage->bucket,
                'Key' => $fullPath,
                'Body' => $fileData,
                'ContentType' => $mimeType,
                'ACL' => 'public-read',
            ]);

            // Get the file URL
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

        } catch (AwsException $e) {
            \Log::error('AWS S3/R2 upload error: ' . $e->getMessage());
            throw new \Exception('Failed to upload file: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Base64 file upload error: ' . $e->getMessage());
            throw new \Exception('Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Upload a file from a local filesystem path to R2/S3 storage
     *
     * @param string $localFilePath Absolute path to local file
     * @param string $remotePath Remote path/key in the bucket
     * @param string|null $contentType MIME type (auto-detected if null)
     * @return array|null Returns file info array or null on failure
     */
    public function uploadFromPath(string $localFilePath, string $remotePath, ?string $contentType = null): ?array
    {
        if (!$this->activeStorage || !$this->activeStorage->isActive() || !$this->s3Client) {
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

            $this->s3Client->putObject([
                'Bucket' => $this->activeStorage->bucket,
                'Key' => $remotePath,
                'Body' => $fileContent,
                'ContentType' => $contentType,
                'ACL' => 'public-read',
            ]);

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

        } catch (AwsException $e) {
            \Log::error('uploadFromPath AWS error: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            \Log::error('uploadFromPath error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete a file from storage
     *
     * @param string $filePath
     * @return bool
     */
    public function delete(string $filePath): bool
    {
        if (!$this->activeStorage || !$this->s3Client) {
            return false;
        }

        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->activeStorage->bucket,
                'Key' => $filePath,
            ]);

            \Log::info('File deleted successfully', ['path' => $filePath]);
            return true;

        } catch (AwsException $e) {
            \Log::error('Failed to delete file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the public URL for a file
     *
     * @param string $filePath
     * @return string
     */
    protected function getFileUrl(string $filePath): string
    {
        // If CDN URL is configured, use it
        if ($this->activeStorage->cdn_url) {
            return rtrim($this->activeStorage->cdn_url, '/') . '/' . $filePath;
        }

        // Otherwise, use R2/S3 endpoint with bucket
        if ($this->activeStorage->endpoint) {
            return rtrim($this->activeStorage->endpoint, '/') . '/' . $this->activeStorage->bucket . '/' . $filePath;
        }

        // Fallback to public URL pattern
        return 'https://' . $this->activeStorage->bucket . '.' . ($this->activeStorage->endpoint ?? 'r2.cloudflarestorage.com') . '/' . $filePath;
    }

    /**
     * Download file content from R2/S3 using authenticated request
     *
     * @param string $fileUrl Full URL to the file
     * @return string Binary file content
     */
    public function downloadFile(string $fileUrl): string
    {
        if (!$this->activeStorage || !$this->s3Client) {
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

        try {
            \Log::info('Downloading file from R2/S3', [
                'url' => $fileUrl,
                'extracted_path' => $path,
                'bucket' => $bucketName
            ]);

            $result = $this->s3Client->getObject([
                'Bucket' => $bucketName,
                'Key' => $path,
            ]);

            $content = (string) $result['Body'];

            \Log::info('File downloaded successfully', [
                'size' => strlen($content),
                'path' => $path
            ]);

            return $content;

        } catch (AwsException $e) {
            \Log::error('Failed to download file from R2/S3', [
                'error' => $e->getMessage(),
                'path' => $path,
                'bucket' => $bucketName
            ]);
            throw new \Exception('Failed to download file: ' . $e->getMessage());
        }
    }

    /**
     * Generate unique filename
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
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
     *
     * @param string $filename
     * @return string
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
     *
     * @param int $bytes
     * @param int $precision
     * @return string
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
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->activeStorage !== null && $this->activeStorage->isActive() && $this->s3Client !== null;
    }

    /**
     * Get active storage configuration
     *
     * @return StorageSetting|null
     */
    public function getActiveStorage(): ?StorageSetting
    {
        return $this->activeStorage;
    }

    /**
     * Get validation rules for file upload
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        if (!$this->activeStorage) {
            return [
                'file' => 'required|file|max:10240', // Default 10MB
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

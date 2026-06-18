<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\FrontendConfig;

/**
 * Sync Mobile App Icon Command
 *
 * Syncs the app icon uploaded via admin panel to the mobile-app/assets folder
 * so it can be used in production builds.
 *
 * Usage:
 *   php artisan mobile:sync-icon
 *   php artisan mobile:sync-icon --force
 */
class SyncMobileAppIcon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mobile:sync-icon
                            {--force : Force overwrite even if icon exists}
                            {--path= : Custom path to mobile-app directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync admin-uploaded app icon to mobile-app assets folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Syncing mobile app icon...');

        // Get icon URL from config
        $iconUrl = FrontendConfig::getValue('mobile.app_icon');
        $iconVersion = FrontendConfig::getValue('mobile.icon_version', 1);

        if (!$iconUrl) {
            $this->warn('⚠️  No icon URL found in config.');
            $this->info('Please upload an icon via the admin panel first:');
            $this->info('  Admin Panel → Mobile App Configuration → Branding → App Icon');
            return self::FAILURE;
        }

        $this->info("📱 Icon URL: {$iconUrl}");
        $this->info("🔢 Icon Version: v{$iconVersion}");

        // Determine mobile-app path
        $mobileAppPath = $this->option('path');

        // If no path provided, use default (sibling of current project)
        if (!$mobileAppPath) {
            $mobileAppPath = dirname(base_path()) . '/mobile-app';
        }

        // Convert to absolute path for reliable file operations
        $realPath = realpath($mobileAppPath);

        // If realpath failed but directory exists, use the provided path
        if ($realPath === false && is_dir($mobileAppPath)) {
            $realPath = $mobileAppPath;
        }

        // For Windows paths, normalize
        $realPath = str_replace('\\', '/', $realPath);
        $realPath = rtrim($realPath, '/');

        $this->info("📂 Mobile app path: {$realPath}");

        if (!is_dir($realPath)) {
            $this->error("❌ Mobile app directory not found: {$realPath}");
            $this->info('Please specify the correct path using --path option');
            $this->info('Example: php artisan mobile:sync-icon --path=/path/to/mobile-app');
            return self::FAILURE;
        }

        $targetPath = $realPath . '/assets/icon.png';
        $this->info("📝 Target path: {$targetPath}");

        // Check if file exists and --force not used
        if (file_exists($targetPath) && !$this->option('force')) {
            $this->warn('⚠️  Icon file already exists at:');
            $this->warn("  {$targetPath}");
            if (!$this->confirm('Do you want to overwrite it?')) {
                $this->info('❌ Cancelled.');
                return self::SUCCESS;
            }
        }

        // Extract file path from URL
        $storagePath = $this->extractStoragePath($iconUrl);

        if (!$storagePath) {
            $this->error('❌ Could not determine storage path from icon URL.');
            return self::FAILURE;
        }

        // Check if file exists in storage
        if (!Storage::disk('public')->exists($storagePath)) {
            $this->error("❌ Icon file not found in storage: {$storagePath}");
            return self::FAILURE;
        }

        // Read from storage
        $this->info('📂 Reading from storage...');
        $fileContent = Storage::disk('public')->get($storagePath);

        // Validate it's a PNG
        $imageInfo = getimagesizefromstring($fileContent);
        if ($imageInfo === false) {
            $this->error('❌ Invalid image file');
            return self::FAILURE;
        }

        $this->info("📐 Image size: {$imageInfo[0]}x{$imageInfo[1]}, MIME: {$imageInfo['mime']}");

        if ($imageInfo['mime'] !== 'image/png') {
            $this->warn('⚠️  Image is not PNG format, but continuing...');
        }

        // Write to mobile-app assets
        $this->info('💾 Writing to mobile-app assets...');
        File::ensureDirectoryExists(dirname($targetPath));

        $bytesWritten = File::put($targetPath, $fileContent);

        if ($bytesWritten === false) {
            $this->error('❌ Failed to write icon file');
            return self::FAILURE;
        }

        $sizeInKb = round($bytesWritten / 1024, 2);
        $this->info("✅ Icon synced successfully!");
        $this->info("   Target: {$targetPath}");
        $this->info("   Size: {$sizeInKb} KB");
        $this->info("   Version: v{$iconVersion}");

        // Show additional info
        $this->newLine();
        $this->info('📱 Next steps:');
        $this->info('   1. The icon is now in mobile-app/assets/icon.png');
        $this->info('   2. Build your APK: cd mobile-app && eas build --platform android --profile production');
        $this->info('   3. Or test locally: cd mobile-app && expo start');

        return self::SUCCESS;
    }

    /**
     * Extract storage path from URL
     *
     * @param string $url
     * @return string|null
     */
    private function extractStoragePath($url)
    {
        // URL format: https://yourdomain.com/storage/icons/xxx.png
        // or: /storage/icons/xxx.png
        // or relative: icons/xxx.png

        $this->info("🔍 Parsing URL: {$url}");

        // If it's a full URL, extract the path
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '';

            $this->info("📂 Extracted path: {$path}");

            // Remove /storage/ prefix if present
            if (str_starts_with($path, '/storage/')) {
                $result = substr($path, strlen('/storage/')); // Use strlen for clarity
                $this->info("✂️  Removed /storage/, result: {$result}");
                return $result;
            }

            // Remove just the leading slash
            if (str_starts_with($path, '/')) {
                $result = substr($path, 1);
                $this->info("✂️  Removed leading slash, result: {$result}");
                return $result;
            }

            $this->info("✅ Using path as-is: {$path}");
            return $path;
        }

        // If it's already a storage path
        if (str_starts_with($url, 'icons/') || str_starts_with($url, 'logos/')) {
            $this->info("✅ Already a storage path: {$url}");
            return $url;
        }

        // If it starts with /storage/
        if (str_starts_with($url, '/storage/')) {
            $result = substr($url, strlen('/storage/'));
            $this->info("✂️  Removed /storage/, result: {$result}");
            return $result;
        }

        $this->warn("⚠️  Could not parse URL: {$url}");
        return null;
    }
}

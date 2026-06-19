<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageService
{
    /**
     * Compress and save image - OPTIMIZED FOR SHARED HOSTING
     *
     * Reduces max dimensions and quality to minimize memory usage
     * Perfect for shared hosting with limited resources
     *
     * @param UploadedFile $image
     * @param string $filename
     * @return string|null Saved image path or null on failure
     */
    public function compressAndSave(UploadedFile $image, string $filename): ?string
    {
        $imageResource = null;

        try {
            // Create uploads directory if it doesn't exist
            $directory = 'uploads';
            Storage::makeDirectory($directory);

            $path = $directory . '/' . $filename;
            $fullPath = Storage::path($path);

            // SHARED HOSTING OPTIMIZATION: Use lower max width and quality
            // Max width: 1600px instead of 2048px (saves 35% memory)
            // Quality: 80% instead of 85% (saves disk space, minimal quality loss)
            $maxWidth = config('app.max_image_width', 1600);
            $quality = config('app.image_quality', 80);

            // Load image
            $imageResource = Image::make($image->getRealPath());

            // Resize if image is too large
            if ($imageResource->width() > $maxWidth) {
                $imageResource->resize($maxWidth, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Also check height (prevent very tall images)
            $maxHeight = config('app.max_image_height', 1600);
            if ($imageResource->height() > $maxHeight) {
                $imageResource->resize(null, $maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Compress and save
            $imageResource->save($fullPath, $quality);

            // IMPORTANT: Free memory immediately after saving
            $imageResource->destroy();
            $imageResource = null;

            // Force garbage collection on shared hosting
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

            return $path;

        } catch (\Exception $e) {
            Log::error('Image compression failed: ' . $e->getMessage(), [
                'exception' => $e,
                'filename' => $filename
            ]);

            return null;
        } finally {
            // Always free memory even if exception occurs
            if ($imageResource !== null) {
                try {
                    $imageResource->destroy();
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            }
        }
    }

    /**
     * Delete image from storage
     *
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        try {
            if (Storage::exists($path)) {
                Storage::delete($path);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Image deletion failed: ' . $e->getMessage(), [
                'exception' => $e,
                'path' => $path
            ]);

            return false;
        }
    }
}

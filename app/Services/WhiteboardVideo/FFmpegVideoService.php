<?php

namespace App\Services\WhiteboardVideo;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class FFmpegVideoService
{
    private string $ffmpegPath;
    private string $ffprobePath;

    public function __construct()
    {
        // Get FFmpeg paths from config or use default
        $this->ffmpegPath = config('services.ffmpeg.path', 'ffmpeg');
        $this->ffprobePath = config('services.ffmpeg.ffprobe_path', 'ffprobe');

        // Verify FFmpeg is available
        $this->verifyFFmpeg();
    }

    /**
     * Verify FFmpeg installation
     *
     * @throws Exception
     */
    private function verifyFFmpeg(): void
    {
        $command = "{$this->ffmpegPath} -version";
        $output = shell_exec($command);

        if (empty($output) || strpos($output, 'ffmpeg version') === false) {
            throw new Exception('FFmpeg is not installed or not accessible');
        }

        Log::info('FFmpeg verified', ['version' => substr($output, 0, 100)]);
    }

    /**
     * Stitch video from scenes (images + audio)
     *
     * @param array $scenes Storyboard scenes
     * @param array $imagePaths Paths to generated images
     * @param array $audioPaths Paths to generated audio files
     * @param string $outputDirectory
     * @param string $jobId
     * @return string Path to final video
     * @throws Exception
     */
    public function stitchVideo(
        array $scenes,
        array $imagePaths,
        array $audioPaths,
        string $outputDirectory,
        string $jobId
    ): string {
        try {
            Log::info('Starting video stitching', [
                'job_id' => $jobId,
                'total_scenes' => count($scenes),
            ]);

            // Step 1: Generate individual video segments for each scene
            $segmentPaths = $this->generateSegments($scenes, $imagePaths, $audioPaths, $outputDirectory);

            // Step 2: Concatenate all segments into final video
            $finalVideoPath = $this->concatenateSegments($segmentPaths, $outputDirectory, $jobId);

            Log::info('Video stitching completed', [
                'job_id' => $jobId,
                'output_path' => $finalVideoPath,
            ]);

            return $finalVideoPath;

        } catch (Exception $e) {
            Log::error('Video stitching failed', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate individual video segments
     *
     * @param array $scenes
     * @param array $imagePaths
     * @param array $audioPaths
     * @param string $outputDirectory
     * @return array Paths to generated segments
     * @throws Exception
     */
    private function generateSegments(
        array $scenes,
        array $imagePaths,
        array $audioPaths,
        string $outputDirectory
    ): array {
        $segmentPaths = [];

        foreach ($scenes as $scene) {
            $sceneNumber = $scene['scene_number'];

            if (!isset($imagePaths[$sceneNumber]) || !isset($audioPaths[$sceneNumber])) {
                throw new Exception("Missing assets for scene {$sceneNumber}");
            }

            $imagePath = storage_path("app/{$imagePaths[$sceneNumber]}");
            $audioPath = storage_path("app/{$audioPaths[$sceneNumber]['path']}");
            $duration = $audioPaths[$sceneNumber]['duration'];

            $segmentPath = $this->generateSingleSegment(
                $imagePath,
                $audioPath,
                $duration,
                $sceneNumber,
                $outputDirectory
            );

            $segmentPaths[] = $segmentPath;

            Log::info("Generated segment for scene {$sceneNumber}", [
                'path' => $segmentPath,
            ]);
        }

        return $segmentPaths;
    }

    /**
     * Generate single video segment (image + audio + fade effect)
     *
     * @param string $imagePath
     * @param string $audioPath
     * @param float $duration
     * @param int $sceneNumber
     * @param string $outputDirectory
     * @return string Path to generated segment
     * @throws Exception
     */
    private function generateSingleSegment(
        string $imagePath,
        string $audioPath,
        float $duration,
        int $sceneNumber,
        string $outputDirectory
    ): string {
        $outputPath = storage_path("app/{$outputDirectory}/segments/segment_{$sceneNumber}.mp4");

        // Create segments directory if it doesn't exist
        $segmentsDir = dirname($outputPath);
        if (!file_exists($segmentsDir)) {
            mkdir($segmentsDir, 0755, true);
        }

        // FFmpeg command with fade-in effect and zoom animation (whiteboard drawing effect)
        $command = sprintf(
            '%s -loop 1 -t %.2f -i "%s" -i "%s" ' .
            '-filter_complex "[0:v]scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2,' .
            'fade=in:0:30,fade=out:st=%.2f:d=1[v]" ' .
            '-map "[v]" -map 1:a -c:v libx264 -preset fast -pix_fmt yuv420p -c:a aac -b:a 192k -shortest ' .
            '-y "%s" 2>&1',
            $this->ffmpegPath,
            $duration,
            $imagePath,
            $audioPath,
            max(0, $duration - 1),
            $outputPath
        );

        $output = shell_exec($command);

        if (!file_exists($outputPath)) {
            throw new Exception("Failed to generate segment {$sceneNumber}: " . $output);
        }

        return $outputPath;
    }

    /**
     * Concatenate all segments into final video
     *
     * @param array $segmentPaths
     * @param string $outputDirectory
     * @param string $jobId
     * @return string Path to final video
     * @throws Exception
     */
    private function concatenateSegments(
        array $segmentPaths,
        string $outputDirectory,
        string $jobId
    ): string {
        $concatListPath = storage_path("app/{$outputDirectory}/concat_list.txt");
        $finalOutputPath = storage_path("app/{$outputDirectory}/final_video.mp4");

        // Create concat list file
        $concatList = [];
        foreach ($segmentPaths as $segmentPath) {
            $concatList[] = "file '" . $segmentPath . "'";
        }

        file_put_contents($concatListPath, implode("\n", $concatList));

        // Concatenate all segments using FFmpeg concat demuxer
        $command = sprintf(
            '%s -f concat -safe 0 -i "%s" -c copy -y "%s" 2>&1',
            $this->ffmpegPath,
            $concatListPath,
            $finalOutputPath
        );

        $output = shell_exec($command);

        if (!file_exists($finalOutputPath)) {
            throw new Exception('Failed to concatenate segments: ' . $output);
        }

        // Get final video duration and size
        $videoDuration = $this->getVideoDuration($finalOutputPath);
        $videoSize = filesize($finalOutputPath);

        Log::info('Final video created', [
            'path' => $finalOutputPath,
            'duration' => $videoDuration,
            'size_mb' => round($videoSize / 1024 / 1024, 2),
        ]);

        return "{$outputDirectory}/final_video.mp4";
    }

    /**
     * Get video duration using ffprobe
     *
     * @param string $videoPath
     * @return float Duration in seconds
     */
    private function getVideoDuration(string $videoPath): float
    {
        try {
            $command = sprintf(
                '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "%s"',
                $this->ffprobePath,
                $videoPath
            );

            $output = shell_exec($command);

            if ($output && is_numeric(trim($output))) {
                return (float) trim($output);
            }

            return 0.0;

        } catch (Exception $e) {
            Log::warning('Failed to get video duration', [
                'error' => $e->getMessage(),
            ]);

            return 0.0;
        }
    }

    /**
     * Add intro and outro to video
     *
     * @param string $videoPath
     * @param string $introPath
     * @param string $outroPath
     * @param string $outputPath
     * @return string Path to final video
     * @throws Exception
     */
    public function addIntroOutro(
        string $videoPath,
        ?string $introPath = null,
        ?string $outroPath = null,
        string $outputPath
    ): string {
        // Implementation for adding intro/outro cards if needed
        // For now, return original video path
        return $videoPath;
    }

    /**
     * Generate thumbnail from video
     *
     * @param string $videoPath
     * @param string $outputPath
     * @return string Path to thumbnail
     * @throws Exception
     */
    public function generateThumbnail(string $videoPath, string $outputPath): string
    {
        $thumbnailPath = storage_path("app/{$outputPath}");

        $command = sprintf(
            '%s -i "%s" -ss 00:00:01 -vframes 1 -vf scale=1280:720 "%s" 2>&1',
            $this->ffmpegPath,
            storage_path("app/{$videoPath}"),
            $thumbnailPath
        );

        $output = shell_exec($command);

        if (!file_exists($thumbnailPath)) {
            throw new Exception('Failed to generate thumbnail: ' . $output);
        }

        return $outputPath;
    }

    /**
     * Clean up temporary files
     *
     * @param string $directory
     */
    public function cleanup(string $directory): void
    {
        try {
            // Remove segments directory
            $segmentsDir = storage_path("app/{$directory}/segments");
            if (file_exists($segmentsDir)) {
                $this->recursiveDelete($segmentsDir);
            }

            // Remove concat list file
            $concatListPath = storage_path("app/{$directory}/concat_list.txt");
            if (file_exists($concatListPath)) {
                unlink($concatListPath);
            }

            Log::info('Cleaned up temporary files', ['directory' => $directory]);

        } catch (Exception $e) {
            Log::warning('Failed to cleanup temporary files', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Recursively delete directory
     *
     * @param string $dir
     */
    private function recursiveDelete(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}

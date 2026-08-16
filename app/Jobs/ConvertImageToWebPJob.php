<?php

namespace App\Jobs;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ConvertImageToWebPJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 120;
    public $maxExceptions = 2;

    protected string $originalPath;
    protected string $folder;
    protected int $quality;
    protected ?string $disk;

    /**
     * Create a new job instance.
     *
     * @param string $originalPath Path to the original uploaded image on storage
     * @param string $folder Folder name (e.g., 'profile', 'banners')
     * @param int $quality WebP quality (0-100)
     * @param string|null $disk Storage disk name (null = default)
     */
    public function __construct(string $originalPath, string $folder, int $quality = 80, ?string $disk = null)
    {
        $this->originalPath = $originalPath;
        $this->folder = $folder;
        $this->quality = $quality;
        $this->disk = $disk ?? config('filesystems.default');
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $storage = Storage::disk($this->disk);

        // Check if original file exists
        if (!$storage->exists($this->originalPath)) {
            return;
        }

        $tempInput = sys_get_temp_dir() . '/' . uniqid('webp_input_', true);
        $tempOutput = sys_get_temp_dir() . '/' . uniqid('webp_output_', true) . '.webp';

        try {
            // Download original file to temp location first
            file_put_contents($tempInput, $storage->get($this->originalPath));

            if (!file_exists($tempInput)) {
                throw new \Exception('Failed to download original file from storage');
            }

            // Try Intervention Image first, fallback to ffmpeg if it fails
            $conversionMethod = 'unknown';

            try {
                // Try using Intervention Image (requires GD/Imagick with proper support)
                $manager = new ImageManager(new Driver());
                $image = $manager->read($tempInput);
                $encoded = $image->toWebp($this->quality);
                file_put_contents($tempOutput, $encoded->toString());
                $conversionMethod = 'intervention';

            } catch (\Throwable $interventionError) {
                // Intervention failed (Error or Exception) - try ffmpeg as fallback silently

                // Check if ffmpeg is available
                exec('which ffmpeg 2>&1', $ffmpegCheck, $ffmpegExists);

                if ($ffmpegExists !== 0) {
                    throw new \Exception('Neither Intervention Image nor ffmpeg is available for conversion');
                }

                // Use ffmpeg
                $cmd = sprintf(
                    'ffmpeg -y -i %s -c:v libwebp -lossless 0 -q:v %d -preset picture %s 2>&1',
                    escapeshellarg($tempInput),
                    $this->quality,
                    escapeshellarg($tempOutput)
                );

                exec($cmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    throw new \Exception('FFmpeg conversion failed: ' . implode("\n", $output));
                }

                $conversionMethod = 'ffmpeg';
            }

            if (!file_exists($tempOutput)) {
                throw new \Exception('WebP conversion failed - output file not created');
            }

            // Prepare for upload
            $pathInfo = pathinfo($this->originalPath);
            $webpFilename = $pathInfo['filename'] . '.webp';

            // Upload WebP version
            $uploadedFile = new UploadedFile(
                $tempOutput,
                $webpFilename,
                'image/webp',
                null,
                true
            );

            $webpPath = Common::upload($this->folder, $uploadedFile);

            if (!$webpPath) {
                throw new \Exception('Failed to upload WebP file to storage');
            }

            // Delete original file (already converted to WebP)
            $storage->delete($this->originalPath);

        } catch (\Exception $e) {
            throw $e;

        } finally {
            // Cleanup temp files
            @unlink($tempInput);
            @unlink($tempOutput);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Log the failure with details for debugging (most common: missing GD/Imagick, corrupt images, disk space)
        Log::error('ConvertImageToWebPJob failed permanently', [
            'path' => $this->originalPath,
            'folder' => $this->folder,
            'disk' => $this->disk,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile() . ':' . $exception->getLine(),
        ]);
        // Keep original file if conversion fails - better to have original than nothing
    }
}

<?php

namespace App\Tik\Services\Files;

use App\Helpers\Common;
use App\Jobs\ConvertImageToWebPJob;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageConverter
{
    /**
     * Convert an uploaded image to WebP and upload directly to GCS.
     *
     * @param  UploadedFile  $file  The uploaded file
     * @param  string  $folder  Folder to save file on it (e.g., "images")
     * @param  int  $qScale  Quality scale (0–100, default 80)
     * @param  bool  $async  Whether to convert asynchronously using queue (default true)
     * @return string|false Public URL or false on failure
     */
    public static function toWebpAndUpload(UploadedFile $file, string $folder, int $qScale = 80, bool $async = true): false|string
    {
        if (! $file->isValid()) {
            return false;
        }

        // If async mode: upload original first, then convert in background
        if ($async) {
            return self::uploadAndConvertAsync($file, $folder, $qScale);
        }

        // Sync mode: convert first, then upload (original behavior)
        return self::convertAndUploadSync($file, $folder, $qScale);
    }

    /**
     * Upload original image first, then convert to WebP in background queue.
     */
    private static function uploadAndConvertAsync(UploadedFile $file, string $folder, int $qScale): false|string
    {
        // Upload original file immediately
        $path = Common::upload($folder, $file);

        if (!$path) {
            return false;
        }

        // Dispatch job to convert in background
        ConvertImageToWebPJob::dispatch($path, $folder, $qScale)
            ->onQueue('default'); // Use default queue for image processing

        // Return path immediately (will be converted to WebP in background)
        return $path;
    }

    /**
     * Convert to WebP synchronously, then upload.
     * Tries Intervention Image first, falls back to ffmpeg if needed.
     */
    private static function convertAndUploadSync(UploadedFile $file, string $folder, int $qScale): false|string
    {
        $tempInput = $file->getPathname();
        $tempOutput = sys_get_temp_dir().'/'.uniqid('webp_', true).'.webp';
        $conversionMethod = 'unknown';

        try {
            // Try Intervention Image first
            try {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($tempInput);
                $encoded = $image->toWebp($qScale);
                file_put_contents($tempOutput, $encoded->toString());
                $conversionMethod = 'intervention';

            } catch (\Throwable $interventionError) {
                // Intervention failed (Error or Exception) - try ffmpeg as fallback silently

                // Check if ffmpeg is available
                exec('which ffmpeg 2>&1', $ffmpegCheck, $ffmpegExists);

                if ($ffmpegExists !== 0) {
                    \Log::error('WebP sync: Neither Intervention nor ffmpeg available');
                    return false;
                }

                // Use ffmpeg
                $cmd = sprintf(
                    'ffmpeg -y -i %s -c:v libwebp -lossless 0 -q:v %d -preset picture %s 2>&1',
                    escapeshellarg($tempInput),
                    $qScale,
                    escapeshellarg($tempOutput)
                );

                exec($cmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    return false;
                }

                $conversionMethod = 'ffmpeg';
            }

            if (!file_exists($tempOutput)) {
                return false;
            }

            // Prepare filename
            $pathInfo = pathinfo($file->getClientOriginalName());
            $webpFilename = $pathInfo['filename'] . '.webp';

            // Convert file path into UploadedFile
            $uploadedFile = new UploadedFile(
                $tempOutput,
                $webpFilename,
                'image/webp',
                null,
                true
            );

            $path = Common::upload($folder, $uploadedFile);

            // Delete temp file
            @unlink($tempOutput);

            // Return public URL
            return $path;

        } catch (\Exception $e) {
            @unlink($tempOutput);
            return false;
        }
    }
}

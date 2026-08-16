<?php

namespace App\Jobs;

use App\Helpers\Common;
use App\Models\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class OptimizeMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [2, 5, 10];

    /**
     * @param string $storagePath  Path on default storage (e.g. "profile/abc123.jpg")
     * @param string $folder       Target folder (e.g. "profile", "rooms")
     * @param string $context      Optimization context: profile|room|banner|gift|general
     * @param string|null $modelClass  Model class to update (e.g. App\Models\Profile)
     * @param int|null $modelId        Model ID to update
     * @param string|null $column      Column to update with new path
     */
    public function __construct(
        public string $storagePath,
        public string $folder = 'profile',
        public string $context = 'profile',
        public ?string $modelClass = null,
        public ?int $modelId = null,
        public ?string $column = null,
    ) {}

    public function handle(): void
    {
        $disk = Storage::disk(config('filesystems.default'));

        if (!$disk->exists($this->storagePath)) {
            Log::warning('File not found', ['path' => $this->storagePath]);
            return;
        }

        $ext = strtolower(pathinfo($this->storagePath, PATHINFO_EXTENSION));

        try {
            if ($ext === 'gif') {
                $this->processGif($disk);
            } else {
                $this->processImage($disk, $ext);
            }
        } catch (\Throwable $e) {
            Log::error('OptimizeMediaJob failed', [
                'path' => $this->storagePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * ─────────────────────────────────────────────
     *  🖼️ IMAGE: Resize + Compress + Convert WebP
     * ─────────────────────────────────────────────
     */
    private function processImage($disk, string $ext): void
    {
        $tempInput  = sys_get_temp_dir() . '/' . uniqid('opt_in_', true) . '.' . $ext;
        $tempOutput = sys_get_temp_dir() . '/' . uniqid('opt_out_', true) . '.webp';

        try {
            // Download from storage to temp
            file_put_contents($tempInput, $disk->get($this->storagePath));

            $config  = $this->getContextConfig();
            $maxW    = $config['max_width'];
            $quality = $config['quality'];

            // ── Try Intervention Image → FFmpeg → Skip ──
            $converted = false;

            // Method 1: Intervention Image
            try {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($tempInput);

                if ($image->width() > $maxW) {
                    $image->scaleDown(width: $maxW);
                }

                $encoded = $image->toWebp($quality);
                file_put_contents($tempOutput, $encoded->toString());
                $converted = true;
            } catch (\Throwable $e) {
                Log::info('Intervention Image not available, trying FFmpeg', ['error' => $e->getMessage()]);
            }

            // Method 2: FFmpeg fallback
            if (!$converted) {
                $ffmpeg = $this->findFfmpeg();
                if ($ffmpeg) {
                    $cmd = sprintf(
                        '%s -y -i %s -vf "scale=\'min(%d,iw)\':-1" -c:v libwebp -q:v %d -preset picture %s 2>&1',
                        escapeshellarg($ffmpeg),
                        escapeshellarg($tempInput),
                        $maxW,
                        $quality,
                        escapeshellarg($tempOutput)
                    );
                    exec($cmd, $output, $returnCode);
                    $converted = ($returnCode === 0 && file_exists($tempOutput));

                    if (!$converted) {
                        Log::warning('FFmpeg conversion failed', ['output' => implode("\n", $output)]);
                    }
                }
            }

            // If nothing worked, skip optimization — keep original file but still update model
            if (!$converted) {
                Log::warning('OptimizeMediaJob: No conversion tool available, keeping original', [
                    'path' => $this->storagePath,
                ]);
                // Still update model with original path
                $this->updateModel($this->storagePath);
                return;
            }

            // Save in SAME location, just change extension to .webp
            $pathInfo = pathinfo($this->storagePath);
            $newPath  = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

            $disk->put($newPath, file_get_contents($tempOutput));

            // Delete original if extension changed (e.g. .jpg → .webp)
            if ($newPath !== $this->storagePath) {
                $disk->delete($this->storagePath);
            }

            // ── Generate multiple versions (thumb, medium, large) ──
            $versions = $this->generateMultipleVersions($disk, $tempOutput, $pathInfo['filename'] . '.webp');

            // Update model with new path + version paths
            $this->updateModel($newPath, $versions);
        } finally {
            @unlink($tempInput);
            @unlink($tempOutput);
        }
    }

    /**
     * ─────────────────────────────────────────────
     *  🎬 GIF: Optimize + Convert to MP4
     * ─────────────────────────────────────────────
     */
    private function processGif($disk): void
    {
        $tempInput     = sys_get_temp_dir() . '/' . uniqid('gif_in_', true) . '.gif';
        $tempOptGif    = sys_get_temp_dir() . '/' . uniqid('gif_opt_', true) . '.gif';
        $tempMp4       = sys_get_temp_dir() . '/' . uniqid('gif_mp4_', true) . '.mp4';

        try {
            file_put_contents($tempInput, $disk->get($this->storagePath));

            $pathInfo = pathinfo($this->storagePath);
            $optimizedPath = null;

            $ffmpeg = $this->findFfmpeg();
            if (!$ffmpeg) {
                Log::warning('OptimizeMediaJob: FFmpeg not found, skipping GIF optimization');
                return;
            }

            // ── Step 1: Optimize GIF via FFmpeg ──
            //    Resize to 480px, reduce to 15fps, limit to 128 colors
            $gifCmd = sprintf(
                '%s -y -i %s -vf "fps=15,scale=480:-1:flags=lanczos,split[s0][s1];[s0]palettegen=max_colors=128[p];[s1][p]paletteuse=dither=bayer" %s 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($tempInput),
                escapeshellarg($tempOptGif)
            );
            exec($gifCmd, $gifOutput, $gifReturn);

            if ($gifReturn === 0 && file_exists($tempOptGif)) {
                // Save optimized GIF in same location
                $disk->put($this->storagePath, file_get_contents($tempOptGif));
                $optimizedPath = $this->storagePath;
            }

            // ── Step 2: Convert GIF → MP4 (much smaller, saved alongside) ──
            $mp4Cmd = sprintf(
                '%s -y -i %s -vf "fps=15,scale=480:-1:flags=lanczos" -c:v libx264 -pix_fmt yuv420p -movflags +faststart -preset fast -crf 23 %s 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($tempInput),
                escapeshellarg($tempMp4)
            );
            exec($mp4Cmd, $mp4Output, $mp4Return);

            if ($mp4Return === 0 && file_exists($tempMp4)) {
                // Save MP4 version alongside the GIF (same name, .mp4 extension)
                $mp4Path = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.mp4';
                $disk->put($mp4Path, file_get_contents($tempMp4));
            }

            // Update model with optimized GIF path (keeps same path)
            if ($optimizedPath) {
                $this->updateModel($optimizedPath);
            }
        } finally {
            @unlink($tempInput);
            @unlink($tempOptGif);
            @unlink($tempMp4);
        }
    }

    /**
     * ─────────────────────────────────────────────
     *  🔲 Generate Multiple Versions (thumb, medium, large)
     * ─────────────────────────────────────────────
     *  Storage structure:
     *    profile/abc123.webp                       ← Original optimized
     *    profile/versions/abc123_thumb.webp        ← 150px
     *    profile/versions/abc123_medium.webp       ← 512px
     *    profile/versions/abc123_large.webp        ← 1024px
     */
    private function generateMultipleVersions($disk, string $sourcePath, string $fileName): array
    {
        $sizes = [
            'thumb'  => 150,
            'medium' => 512,
            'large'  => 1024,
        ];

        $paths = [];
        foreach ($sizes as $name => $width) {
            $path = $this->generateVersion($disk, $sourcePath, $fileName, $name, $width);
            if ($path) {
                $paths[$name] = $path;
            }
        }

        return $paths;
    }

    /**
     * Generate a single version at a specific width
     */
    private function generateVersion($disk, string $sourcePath, string $fileName, string $versionName, int $width): ?string
    {
        $tempFile = sys_get_temp_dir() . '/' . uniqid("ver_{$versionName}_", true) . '.webp';

        try {
            $generated = false;

            // Method 1: Intervention Image
            try {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($sourcePath);
                $image->scaleDown(width: $width);

                $encoded = $image->toWebp(70);
                file_put_contents($tempFile, $encoded->toString());
                $generated = true;
            } catch (\Throwable $e) {
                // Intervention not available
            }

            // Method 2: FFmpeg fallback
            if (!$generated) {
                $ffmpeg = $this->findFfmpeg();
                if ($ffmpeg) {
                    $cmd = sprintf(
                        '%s -y -i %s -vf "scale=\'min(%d,iw)\':-1" -c:v libwebp -q:v 70 -preset picture %s 2>&1',
                        escapeshellarg($ffmpeg),
                        escapeshellarg($sourcePath),
                        $width,
                        escapeshellarg($tempFile)
                    );
                    exec($cmd, $output, $returnCode);
                    $generated = ($returnCode === 0 && file_exists($tempFile));
                }
            }

            if (!$generated) {
                return null;
            }

            // Build version path: folder/versions/filename_thumb.webp
            $nameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
            $versionPath = $this->folder . '/versions/' . $nameWithoutExt . '_' . $versionName . '.webp';

            $disk->put($versionPath, file_get_contents($tempFile));

            return $versionPath;
        } catch (\Throwable $e) {
            Log::warning("Version generation failed: {$versionName}", ['error' => $e->getMessage()]);
            return null;
        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Find FFmpeg binary path
     */
    private function findFfmpeg(): ?string
    {
        // Check laravel-ffmpeg config first
        $configPath = config('laravel-ffmpeg.ffmpeg.binaries');
        if ($configPath && file_exists($configPath)) {
            return $configPath;
        }

        // Check common paths
        $paths = PHP_OS_FAMILY === 'Windows'
            ? ['ffmpeg', 'C:\\ffmpeg\\bin\\ffmpeg.exe']
            : ['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', 'ffmpeg'];

        foreach ($paths as $path) {
            exec(escapeshellarg($path) . ' -version 2>&1', $out, $code);
            if ($code === 0) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Get optimization config based on context
     */
    private function getContextConfig(): array
    {
        $defaults = [
            'profile' => ['max_width' => 512,  'quality' => 75],
            'room'    => ['max_width' => 1024, 'quality' => 75],
            'banner'  => ['max_width' => 1920, 'quality' => 80],
            'gift'    => ['max_width' => 512,  'quality' => 80],
            'general' => ['max_width' => 1024, 'quality' => 75],
        ];

        return $defaults[$this->context] ?? $defaults['general'];
    }

    /**
     * Update the model column with the new optimized path
     */
    // private function updateModel(string $newPath): void
    // {
    //     if ($this->modelClass && $this->modelId && $this->column) {
    //         $model = $this->modelClass::find($this->modelId);
    //         if ($model) {
    //             $model->{$this->column} = $newPath;
    //             $model->save();
    //         }
    //     }
    // }

    private function updateModel(string $newPath, array $versions = []): void
    {
        if ($this->modelClass && $this->modelId && $this->column) {
            $model = $this->modelClass::find($this->modelId);
            if ($model) {
                // Original path
                $model->{$this->column} = $newPath;

                // Save version paths to dedicated columns:
                // avatar_thumb, avatar_medium, avatar_large
                foreach ($versions as $size => $path) {
                    $colName = 'avatar_' . $size; // avatar_thumb, avatar_medium, avatar_large
                    if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $colName)) {
                        $model->{$colName} = $path;
                    }
                }

                $model->save();

                Log::info('OptimizeMediaJob: Model updated', [
                    'model'    => $this->modelClass,
                    'id'       => $this->modelId,
                    'column'   => $this->column,
                    'newPath'  => $newPath,
                    'versions' => $versions,
                ]);
            }
        }
    }

    /**
     * Handle job failure — keep original file
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('OptimizeMediaJob permanently failed', [
            'path'  => $this->storagePath,
            'error' => $exception->getMessage(),
        ]);
    }
}

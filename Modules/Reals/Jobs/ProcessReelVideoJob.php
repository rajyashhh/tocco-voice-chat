<?php

namespace Modules\Reals\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Reals\Entities\Real;

/**
 * Reel post-upload pipeline. Every published reel goes through this job before
 * it can appear in any feed (feeds only serve status=ready):
 *
 *   source (whatever the client uploaded — HEVC, moov-at-end, any resolution)
 *     -> H.264/AAC mp4, <=720p, +faststart  -> reels/{id}_xxx.mp4  (url)
 *     -> jpg frame                          -> [test-]frames/{id}.jpg (thumbnail)
 *     -> 2s animated gif preview            -> sub-video/xxx.gif (sub_video, optional)
 *     -> duration (ffprobe)
 *
 * The source is downloaded through the authenticated Storage disk, never over a
 * public URL, so non-ASCII object names can never break ffmpeg/ffprobe again.
 */
class ProcessReelVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [15, 60];
    public int $timeout = 240;

    public function __construct(public int $realId)
    {
    }

    public function handle(): void
    {
        $real = Real::query()->find($this->realId);
        if (!$real) {
            return;
        }

        $source = $real->source_url ?: $real->url;
        $disk   = Storage::disk('gcs');

        if (!$disk->exists($source)) {
            // Retryable: with pre-signed uploads the client may publish a beat
            // before the PUT is fully visible. failed() marks it after tries run out.
            throw new \RuntimeException("source object missing: {$source}");
        }

        $tmpIn    = sys_get_temp_dir() . '/' . uniqid('reel_in_', true) . '.mp4';
        $tmpOut   = sys_get_temp_dir() . '/' . uniqid('reel_out_', true) . '.mp4';
        $tmpThumb = sys_get_temp_dir() . '/' . uniqid('reel_thumb_', true) . '.jpg';
        $tmpGif   = sys_get_temp_dir() . '/' . uniqid('reel_gif_', true) . '.gif';

        try {
            $stream = $disk->readStream($source);
            if ($stream === null) {
                throw new \RuntimeException("cannot read source stream: {$source}");
            }
            $local = fopen($tmpIn, 'wb');
            stream_copy_to_stream($stream, $local);
            fclose($local);
            fclose($stream);

            // ── Transcode: H.264/AAC, cap smaller dimension at 720, faststart ──
            $cmd = sprintf(
                'ffmpeg -y -i %s -map 0:v:0 -map 0:a:0? ' .
                '-c:v libx264 -profile:v high -level 4.0 -pix_fmt yuv420p ' .
                "-vf \"scale='min(720,iw)':-2\" -crf 26 -maxrate 2500k -bufsize 5000k -preset veryfast " .
                '-c:a aac -b:a 128k -movflags +faststart %s 2>&1',
                escapeshellarg($tmpIn),
                escapeshellarg($tmpOut)
            );
            exec($cmd, $out, $code);
            if ($code !== 0 || !is_file($tmpOut) || filesize($tmpOut) === 0) {
                throw new \RuntimeException('ffmpeg transcode failed: ' . mb_substr(implode("\n", $out), -400));
            }

            // ── Duration from the transcoded file ──
            exec(sprintf(
                'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
                escapeshellarg($tmpOut)
            ), $durOut, $durCode);
            $duration = ($durCode === 0 && isset($durOut[0]) && is_numeric(trim($durOut[0])))
                ? round((float) trim($durOut[0]), 2)
                : null;

            // ── Thumbnail (mandatory): frame at 1s, fallback to first frame ──
            exec(sprintf(
                'ffmpeg -y -ss 1 -i %s -frames:v 1 -q:v 3 %s 2>&1',
                escapeshellarg($tmpOut),
                escapeshellarg($tmpThumb)
            ), $thumbOut, $thumbCode);
            if ($thumbCode !== 0 || !is_file($tmpThumb) || filesize($tmpThumb) === 0) {
                exec(sprintf(
                    'ffmpeg -y -i %s -frames:v 1 -q:v 3 %s 2>&1',
                    escapeshellarg($tmpOut),
                    escapeshellarg($tmpThumb)
                ), $thumbOut2, $thumbCode);
            }
            if ($thumbCode !== 0 || !is_file($tmpThumb) || filesize($tmpThumb) === 0) {
                throw new \RuntimeException('thumbnail extraction failed');
            }

            // ── Animated gif preview (optional, matches legacy sub_video) ──
            exec(sprintf(
                'ffmpeg -y -ss 0 -t 2 -i %s -vf "fps=15,scale=162:-2:flags=lanczos,split[s0][s1];[s0]palettegen=max_colors=128[p];[s1][p]paletteuse" %s 2>&1',
                escapeshellarg($tmpOut),
                escapeshellarg($tmpGif)
            ), $gifOut, $gifCode);
            $gifOk = ($gifCode === 0 && is_file($tmpGif) && filesize($tmpGif) > 0);

            // ── Upload artifacts with explicit contentType + long cache ──
            // Content-addressed names (unique per encode) -> immutable cache is safe.
            $videoPath = 'reels/' . $real->id . '_' . uniqid() . '.mp4';
            $thumbPath = (config('app.env') != 'production' ? '' : 'test-') . 'frames/' . $real->id . '.jpg';
            $gifPath   = $gifOk ? ('sub-video/' . uniqid() . '.gif') : null;

            // Upload through the 'gcs'-named disk (driver is gcs OR s3 depending
            // on MEDIA_STORAGE_DRIVER) so artifacts land in the same store the
            // feed reads from, regardless of provider. Two option shapes are
            // passed side by side: the nested `metadata` array (contentType +
            // cacheControl) is what the spatie/GCS Flysystem adapter reads, while
            // the top-level ContentType/CacheControl are the S3 adapter's write
            // options — each adapter ignores the keys it does not recognise.
            $disk = Storage::disk('gcs');
            $disk->put($videoPath, fopen($tmpOut, 'rb'), [
                'visibility'   => 'public',
                'metadata'     => ['contentType' => 'video/mp4', 'cacheControl' => 'public, max-age=31536000'],
                'ContentType'  => 'video/mp4',
                'CacheControl' => 'public, max-age=31536000',
            ]);
            $disk->put($thumbPath, fopen($tmpThumb, 'rb'), [
                'visibility'   => 'public',
                'metadata'     => ['contentType' => 'image/jpeg', 'cacheControl' => 'public, max-age=3600'],
                'ContentType'  => 'image/jpeg',
                'CacheControl' => 'public, max-age=3600',
            ]);
            if ($gifPath) {
                $disk->put($gifPath, fopen($tmpGif, 'rb'), [
                    'visibility'   => 'public',
                    'metadata'     => ['contentType' => 'image/gif', 'cacheControl' => 'public, max-age=31536000'],
                    'ContentType'  => 'image/gif',
                    'CacheControl' => 'public, max-age=31536000',
                ]);
            }

            $real->forceFill([
                'url'         => $videoPath,
                'thumbnail'   => $thumbPath,
                'sub_video'   => $gifPath,
                'duration'    => $duration,
                'status'      => Real::STATUS_READY,
                'fail_reason' => null,
            ])->save();

            Log::info('Reel processed', ['reel_id' => $real->id, 'video' => $videoPath, 'duration' => $duration]);
        } finally {
            @unlink($tmpIn);
            @unlink($tmpOut);
            @unlink($tmpThumb);
            @unlink($tmpGif);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $real = Real::query()->find($this->realId);
        if ($real) {
            $this->markFailed($real, $exception->getMessage());
        }
    }

    private function markFailed(Real $real, string $reason): void
    {
        $real->forceFill([
            'status'      => Real::STATUS_FAILED,
            'fail_reason' => mb_substr($reason, 0, 500),
        ])->save();

        Log::error('Reel processing failed', ['reel_id' => $real->id, 'reason' => $reason]);
    }
}
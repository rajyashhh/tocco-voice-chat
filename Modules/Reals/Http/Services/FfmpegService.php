<?php

namespace Modules\Reals\Http\Services;

use FFMpeg\FFProbe;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;


class FfmpegService
{

    public function extract($videoPath, $id)
    {
        $imagePath = (config('app.env') != 'production' ? '' : 'test-') . "frames/" . $id . '.jpg';
        FFMpeg::openUrl($videoPath)
            ->getFrameFromSeconds(1)
            ->export()
            ->toDisk('gcs')
            ->save($imagePath);
    }

    public function extractByDuration($videoPath, $id): void
    {
        $imagePath = (config('app.env') != 'production' ? '' : 'test-') . "frames/" . $id . '.jpg';
        $media    = FFMpeg::openUrl($videoPath);
        $duration = $media->getDurationInSeconds();
        $timestamp = max(0, (int) floor($duration / 2));

        $media->getFrameFromSeconds($timestamp)
            ->export()
            ->toDisk('gcs')
            ->save($imagePath);
    }

    public function extractByFrame($videoPath, $id): void
    {
        $tempDir = storage_path('app/temp_frames');

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $imagePath = storage_path("app/temp_frames/{$id}.jpg"); // Adjust path as needed
        $ffprobe = FFProbe::create();

        // Get actual or estimated frame count
        $videoStream = $ffprobe->streams($videoPath)->videos()->first();
        $frameCount = $videoStream->get('nb_frames');

        if (!$frameCount || !is_numeric($frameCount)) {
            // Estimate frame count
            $duration = $ffprobe->format($videoPath)->get('duration');
            $frameRate = $videoStream->get('avg_frame_rate'); // e.g. "25/1"
            [$num, $den] = explode('/', $frameRate);
            $fps = $den != 0 ? $num / $den : 0;
            $frameCount = (int) floor($duration * $fps);
        }

        // Calculate target frame index (30% of total)
        $targetFrame = (int) floor($frameCount * 0.3);

        // Run FFmpeg command to extract specific frame by index
        $ffmpegCommand = sprintf(
            'ffmpeg -i "%s" -vf "select=eq(n\,%d)" -vframes 1 -q:v 2 "%s"',
            $videoPath,
            $targetFrame,
            $imagePath
        );

        shell_exec($ffmpegCommand);

        $prefix = config('app.env') !== 'production' ? '' : 'test-';
        // Upload image to GCS
        \Storage::disk('gcs')->put("{$prefix}frames/{$id}.jpg", file_get_contents($imagePath));
        unlink($imagePath); // optional: clean up
    }


}

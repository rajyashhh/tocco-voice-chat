<?php
namespace Modules\Chat\Traits;
use Illuminate\Support\Facades\Storage;
use FFMpeg\Format\Video\X264;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
trait FfmpegTrait {
    function extract_frame($videoPath , $thumbnailPath ) {
        FFMpeg::fromDisk(config('filesystems.default'))
        ->open($videoPath)
        ->getFrameFromSeconds(1)
        ->export()
        ->toDisk(config('filesystems.default'))
        ->save($thumbnailPath);
    }

}


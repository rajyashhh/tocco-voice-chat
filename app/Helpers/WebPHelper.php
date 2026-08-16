<?php
namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use App\Tik\Services\Files\ImageConverter;

class WebPHelper
{

    public static function imageQuality(string $type): int
    {
        return match ($type) {
            'profile_image' => 80,
            'profile_cover' => 80,
            'room_cover'    => 80,
            'banner'        => 80,
            'splash'        => 100,
            default         => 80,
        };
    }


    public static function uploadWebp(
        UploadedFile $file,
        string $folder,
        string $type = 'profile_image',
        bool $async = true
    ): false|string {

        if (! $file->isValid()) {
            return false;
        }

        $quality = self::imageQuality($type);

        return ImageConverter::toWebpAndUpload(
            $file,
            $folder,
            $quality,
            $async
        );
    }
}

<?php

namespace App\Admin\Services;

use App\Helpers\Common;
use App\Helpers\LogHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Reals\Http\Services\FfmpegService;

class FileService
{
    /**
     * @return void
     *
     * @throws ValidationException
     */
    public static function getExtension(UploadedFile $img2, mixed $wareId, bool $getFromService = false): ?string
    {

        if ($wareId == null) $wareId = \Str::random(10);
        $urlVideo = upload($img2);

        $allowedExtensions = ['svga', 'mp4', 'alpha', 'vap', 'png'];
        $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg', 'heic', 'heif'];

        $ext = mb_strtolower($img2->getClientOriginalExtension());
        $originalExt = mb_strtolower($img2->getClientOriginalExtension());

        if ($ext === 'zz' && $originalExt === 'svga') {
            $ext = 'svga';
        }

        // Default to original extension if allowed
        if (in_array($originalExt, $allowedImageTypes)) {
            $ext = 'png'; // Convert gif/webp to png

        }

        if ($ext === 'mp4' && $getFromService) {

            $videoPath = getDriverUrl().'/'.$urlVideo;

            (new FfmpegService())->extractByFrame($videoPath, $wareId);

            $imagePath = (config('app.env') !== 'production' ? '' : 'test-').'frames/'.$wareId.'.jpg';

            // White-label: media-analyze endpoint is resolved per deploy (DB
            // setting -> config/env). When it is unset, skip the analyze step
            // entirely and keep the locally-derived extension — a clone that has
            // no media service configured never calls a foreign URL.
            $analyzeUrl = Common::whiteLabel('utd_media_analyze_url', 'services.utd_media.analyze_url');

            if ($analyzeUrl !== '') {
                $response = Http::attach(
                    'image',
                    Storage::disk('gcs')->get($imagePath),
                    $wareId.'.jpg'
                )->post($analyzeUrl);

                $responseData = $response->json();

                if ($response->successful() && isset($responseData['data']['video_type'])) {
                    $ext = mb_strtolower(explode('-', $responseData['data']['video_type'])[0]);
                }
            }

        }
        if (! in_array($ext, $allowedExtensions)) {
            throw ValidationException::withMessages([
                'img2' => ['Invalid file type. Allowed extensions are: '.implode(', ', $allowedExtensions)],
            ]);
        }

        deleteFile($urlVideo);
        return $ext;
    }
}

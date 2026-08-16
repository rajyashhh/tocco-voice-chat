<?php

namespace Modules\Reals\Http\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;


class InterventionImage
{

    // public function combineImages(array $paths)
    // {
    //     $manager = new ImageManager(new Driver());

    //     $images = [];

    //     foreach ($paths as $path) {
    //         $img = $this->readImage($path);
    //         if ($img) {
    //             $images[] = $img->resize(300, 300);
    //         }
    //     }

    //     if (empty($images)) {
    //         return null;
    //     }

    //     $count   = count($images);
    //     $columns = ceil(sqrt($count));
    //     $rows    = ceil($count / $columns);

    //     $canvas = $manager->create($columns * 300, $rows * 300);

    //     foreach ($images as $i => $img) {
    //         $canvas->place(
    //             $img,
    //             'top-left',
    //             ($i % $columns) * 300,
    //             floor($i / $columns) * 300
    //         );
    //     }

    //     // Generate random filename
    //     $fileName = 'merged_' . Str::random(16) . '.png';

    //     // Get image content in memory
    //     $imageContent = (string) $canvas->toPng();


    //     // Upload to GCS (or other disk)
    //     Storage::disk('gcs')->put('merged/' . $fileName, $imageContent, 'public');


    //     return 'merged/' . $fileName;
    // }

    public function combineImages(array $paths, $maxTileSize = 100)
    {
        $manager = new ImageManager(new Driver());
        $images = [];

        // 1️⃣ Read and resize images
        foreach ($paths as $path) {

            $img = $this->readImage($path);
            if (!$img) {
                // Log missing file
             //   Log::warning("Image not found: $path");
                continue; // skip
            }
            if ($img) {
                // Resize to maxTileSize keeping aspect ratio
                $img->resize($maxTileSize, $maxTileSize, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $images[] = $img;
            }
        }

        if (empty($images)) {
            return null;
        }

        $count = count($images);
        $columns = ceil(sqrt($count));
        $rows = ceil($count / $columns);

        // 2️⃣ Calculate canvas size based on resized images
        $tileWidth = max(array_map(fn($img) => $img->width(), $images));
        $tileHeight = max(array_map(fn($img) => $img->height(), $images));

        $canvasWidth = $columns * $tileWidth;
        $canvasHeight = $rows * $tileHeight;

        $canvas = $manager->create($canvasWidth, $canvasHeight);


        // 3️⃣ Place images
        foreach ($images as $i => $img) {
            $x = ($i % $columns) * $tileWidth;
            $y = floor($i / $columns) * $tileHeight;
            $canvas->place($img, 'top-left', $x, $y);
        }

        // 4️⃣ Generate filename and save
        $fileName = 'merged_' . Str::random(16) . '.jpg';
        $imageContent = (string) $canvas->encode(new JpegEncoder(quality: 70));
        // smaller file

        Storage::disk('gcs')->put('merged/' . $fileName, $imageContent, ['visibility' => 'public']);

        return 'merged/' . $fileName;
    }






    public function readImage(string $pathOrUrl)
    {
        $manager = new ImageManager(new Driver());

        // 1️⃣ Local file
        if (file_exists($pathOrUrl)) {
            return $manager->read($pathOrUrl);
        }

        // 2️⃣ asset() URL
        if (str_starts_with($pathOrUrl, asset(''))) {
            $local = public_path(parse_url($pathOrUrl, PHP_URL_PATH));
            return file_exists($local) ? $manager->read($local) : null;
        }

        // 3️⃣ Google Cloud Storage URL
        if (str_contains($pathOrUrl, 'storage.googleapis.com')) {

            // Extract object path
            $objectPath = ltrim(parse_url($pathOrUrl, PHP_URL_PATH), '/');
            $objectPath = preg_replace('#^[^/]+/#', '', $objectPath);

            if (!Storage::disk('gcs')->exists($objectPath)) {
                return null;
            }

            // Read binary directly from GCS
            $binary = Storage::disk('gcs')->get($objectPath);

            return $manager->read($binary);
        }

        // 3b) S3-backed media (MEDIA_STORAGE_DRIVER=s3). The GCS branch above is
        // keyed on the googleapis.com host, so it never matches an S3/CDN URL.
        // Derive the object key by stripping the disk's configured public URL
        // prefix (works for virtual-hosted, path-style, and custom-CDN AWS_URL —
        // unlike the GCS first-segment strip), then read through the same
        // 'gcs'-named disk (driver=s3 here).
        if ((string) config('filesystems.disks.gcs.driver', 'gcs') === 's3') {
            $base = rtrim((string) config('filesystems.disks.gcs.url', ''), '/');

            if ($base !== '' && str_starts_with($pathOrUrl, $base)) {
                $objectPath = ltrim(substr($pathOrUrl, strlen($base)), '/');
            } else {
                $objectPath = ltrim((string) parse_url($pathOrUrl, PHP_URL_PATH), '/');
            }

            if ($objectPath === '' || !Storage::disk('gcs')->exists($objectPath)) {
                return null;
            }

            return $manager->read(Storage::disk('gcs')->get($objectPath));
        }

        // // 4️⃣ Any remote URL (CDN, S3, etc.)
        // if (filter_var($pathOrUrl, FILTER_VALIDATE_URL)) {

        //     $response = Http::timeout(10)->get($pathOrUrl);
        //     if (!$response->successful()) {
        //         return null;
        //     }

        //     return $manager->read($response->body());
        // }

        return null;
    }
}

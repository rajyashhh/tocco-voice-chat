<?php

namespace App\Admin\Fields;

use Encore\Admin\Form\Field\Image as BaseImage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Image extends BaseImage
{
    public function getStoreName($file)
    {
        if ($file instanceof UploadedFile) {
            return now()->timestamp . rand(100, 999) . '.' . $file->guessExtension();
        }

        return $file;
    }
}

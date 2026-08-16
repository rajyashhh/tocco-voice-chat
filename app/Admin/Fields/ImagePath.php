<?php

namespace App\Admin\Fields;

use App\Tik\Services\Files\ImageConverter;


class ImagePath extends \Encore\Admin\Form\Field\Image
{

    private int $resolution = 80;
    public function prepare($image)
    {
        if ($this->picker) {
            return parent::prepare($image);
        }

        if (request()->has(static::FILE_DELETE_FLAG)) {
            return $this->destroy();
        }

        $this->name = $this->getStoreName($image);


        $this->callInterventionMethods($image->getRealPath());

        $path = ImageConverter::toWebpAndUpload($image, 'banners', $this->resolution, async: false);
        if (!$path) {
            $path = $this->uploadAndDeleteOriginal($image);
        }

        $this->uploadAndDeleteOriginalThumbnail($image);

        return $path;
    }

    public function setResolution($resolution)
    {
        $this->resolution = $resolution;
        return $this;
    }

}

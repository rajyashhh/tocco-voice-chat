<?php

namespace App\Admin\Extensions\Form\Fields;

use Encore\Admin\Form\Field\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Helpers\WebPHelper;

class WebPImage extends File
{
    protected $view = 'admin.form.webp-image'; 
    protected $typeName = 'webpImage';
    protected $imageType = 'profile_image'; 

  
    public function imageType(string $type)
    {
        $this->imageType = $type;
        return $this;
    }

    public function prepare($file)
    {
        if ($file instanceof UploadedFile) {

            if ($this->model()->{$this->column}) {
                Storage::delete($this->model()->{$this->column});
            }

            $path = WebPHelper::uploadWebp($file, $this->getFolder(), $this->imageType, async: false);

            return $path ?: $file;
        }

        return $file;
    }

 
    protected function getFolder(): string
    {
        return $this->options['dir'] ?? 'images';
    }
}

<?php
namespace App\Admin\Extensions\Form\Field;

use Encore\Admin\Form\Field\File;

class CustomFile extends File
{
    protected $view = 'admin::form.file';
    protected function preview()
    {
        if (!$this->value) return '';

        $url = $this->objectUrl($this->value);
        $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $uniqueId = 'file_' . uniqid();

        if (in_array($ext, ['png','jpg','jpeg','gif','webp','svg'])) {
            return "<img src='{$url}' class='file-preview-image img-responsive' style='max-height:150px'>";
        }

        return handleShowImageWithTypes($uniqueId, $url, 100, 100, 10);
    }
}

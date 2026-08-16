<?php

namespace App\Tik\Repositories;

use App\Models\Image;

class ImageRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new Image());
    }

    public function getImage()
    {
        return $this->model->query()->where('type', 0)->where('status', 1)->select('id', 'name', 'url')->get();
    }
}
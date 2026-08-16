<?php

namespace App\Tik\Repositories;

use App\Models\Background;



class BackgroundRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new Background());
    }

    public function index()
    {
        return $this->model->where(['enable' => 1])->selectRaw('id,img')->orderBy('use_count', 'desc')->get();
    }
}

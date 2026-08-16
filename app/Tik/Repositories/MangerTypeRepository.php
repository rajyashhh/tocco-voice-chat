<?php

namespace App\Tik\Repositories;

use App\Models\MangerType;

class MangerTypeRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new MangerType());
    }

    public function all()
    {
        return $this->model->get();
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }
}
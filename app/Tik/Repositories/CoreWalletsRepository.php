<?php

namespace App\Tik\Repositories;

use App\Models\CoreWallets;

class CoreWalletsRepository extends AbstractRepository
{
    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new CoreWallets());
    }

    public function all($id, $perPage, $page)
    {
        return $this->model->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate($perPage, ['*'], 'page', $page);
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }
}

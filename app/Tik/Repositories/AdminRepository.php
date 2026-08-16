<?php

namespace App\Tik\Repositories;

use App\Models\Admin;


class AdminRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new Admin());
    }

    public function findById($userId)
    {
        return $this->model->find($userId);
    }

    public function allAgencyAdmins($id, $perPage, $page)
    {
        return $this->model->whereHas('roles', function ($query) {
            $query->whereRoleId(13);
        })->withCount('agencies')->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate($perPage, ['*'], 'page', $page);
    }
}

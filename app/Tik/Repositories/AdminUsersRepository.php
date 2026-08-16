<?php

namespace App\Tik\Repositories;

use App\Models\AdminUser;


class AdminUsersRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new AdminUser());
    }

    public function all($id, $perPage, $page)
    {
        return $this->model->whereHas('roles', fn($q) => $q->where('slug', 'like', '%_genc%_anager%'))
            ->where('app_id', '!=', 0)->with(['user' => fn($q) => $q->withCount('agencies')])->with(['managerAgencies' => fn($q) => $q->withSum('agencySalaries as total_salaries', 'sallary')])->when(isset($id), function ($query) use ($id) {
                $query->where('id', $id);
            })->paginate($perPage, ['*'], 'page', $page);
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function report($id, $perPage, $page)
    {
        return $this->model->where('app_id','!=',0)->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate($perPage, ['*'], 'page', $page);
    }
}

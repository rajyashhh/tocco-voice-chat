<?php

namespace App\Tik\Repositories;


use App\Models\UserTarget;
use Illuminate\Database\Eloquent\Collection;


class UserTargetRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new UserTarget());
    }

    public function all($perPage, $Page)
    {
        return $this->model->with('user', 'agency')->paginate($perPage, ['*'], 'page', $Page);
    }
}

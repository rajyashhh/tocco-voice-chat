<?php

namespace Modules\UsersWallet\Repositories\Eloquent;


use App\Models\Exchange;
use App\Tik\Repositories\AbstractRepository;

class ExchangeRepository extends AbstractRepository
{

    
    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new Exchange());
    }

    public function getByType($type)
    {
        return $this->model->query()->where('type', $type)->orderBy('diamonds')->get();
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function all($id, $perPage, $page)
    {
        return $this->model->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate($perPage, ['*'], 'page', $page);
    }
}

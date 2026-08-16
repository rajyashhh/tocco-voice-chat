<?php

namespace App\Tik\Repositories;

use App\Models\RequestBackgroundImage;
use Carbon\Carbon;

class RequestBackgroundImageRepository extends AbstractRepository
{
    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new RequestBackgroundImage());
    }

    // public function create($data)
    // {
    //     return $this->model->create([
    //         'owner_room_id' => $data['owner_room_id'],
    //         'img' => $data['img'],
    //         'status' => $data['status'],
    //         'price' => $data['price'],
    //     ]);
    // }

    public function findByUserId($userId)
    {
        return $this->model->query()->where('owner_room_id', $userId)->where('expair', '>=', now()->timestamp)->whereIn('status', [1, 3])->select('id', 'img','expair')->get();
    }

    public function all($id, $perPage, $page)
    {
        return $this->model->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate($perPage, ['*'], 'page', $page);
    }

    public function store($data){
        $this->model->create($data);
    }
}

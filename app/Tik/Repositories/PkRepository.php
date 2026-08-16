<?php

namespace App\Tik\Repositories;

use App\Models\Pk;

class PkRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new Pk());
    }


    // public function create($data): mixed
    // {
    //     return $this->model->create([
    //         'room_id'  => $data['room_id'],
    //         'status'   => $data['status'],
    //         'mics'     => $data['mics'],
    //         'start_at' => $data['start_at'],
    //         'end_at'   => $data['end_at'],
    //     ]);
    // }

    public function getPk($roomId)
    {
        return $this->model->query()->where('room_id', $roomId)->where('status', 1)->first();
    }

    public function findById($id)
    {
        return $this->model->query()->where('id', $id)->where('status', 1)->orderByDesc('id')->first();
    }

    public function roomPks($userId, $perPage, $page)
    {
        return $this->model->whereHas('room', function ($q) use ($userId) {
            $q->where('uid', $userId);
        })->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);
    }
}

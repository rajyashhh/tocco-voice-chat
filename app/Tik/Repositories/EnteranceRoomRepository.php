<?php

namespace App\Tik\Repositories;

use Illuminate\Database\Eloquent\Model;

class EnteranceRoomRepository
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function addVisitor(int $roomId, int $userId)
    {
        $this->model->where(['user_id' => $userId])->delete();
        $this->model->create(['user_id' => $userId, 'room_id' => $roomId]);
    }

    public function removeVisitor(int $roomId, int $userId)
    {
        $this->model->where(['user_id' => $userId, 'room_id' => $roomId])->delete();
    }

    public function countVisitors(int $roomId)
    {
        return $this->model->where('room_id', $roomId)->count();
    }

    
}
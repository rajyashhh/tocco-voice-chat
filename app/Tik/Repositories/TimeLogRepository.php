<?php

namespace App\Tik\Repositories;

use App\Models\TimeLog;


class TimeLogRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new TimeLog());
    }

    public function getByOwnerAndUserId($ownerRoomId, $userId)
    {
        return $this->model->where(['uid' => $ownerRoomId, 'user_id' => $userId])->orderByRaw('id desc')->limit(1)->first();
    }

    public function deleteAth($ownerRoomId, $userId = 0)
    {
        return $this->model->where(array('uid' => $ownerRoomId, 'user_id' => $userId))->delete();
    }
}

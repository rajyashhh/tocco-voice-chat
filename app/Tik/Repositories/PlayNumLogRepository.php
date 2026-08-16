<?php

namespace App\Tik\Repositories;

use App\Models\PlayNumLog;


class PlayNumLogRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new PlayNumLog());
    }

    public function getPlayNumValue($ownerRoomId,$userId)
    {
        return $this->model->where(['uid' => $ownerRoomId, 'user_id' => $userId])->value('price');
    }

}
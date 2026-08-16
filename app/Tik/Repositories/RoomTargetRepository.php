<?php

namespace App\Tik\Repositories;

use App\Models\RoomTarget;


class RoomTargetRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new RoomTarget());
    }

    public function getByCoins($coins)
    {
        return $this->model->where("coins", "<=", $coins)->orderBy('coins', 'desc')->first();
    }
}

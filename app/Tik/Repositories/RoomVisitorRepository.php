<?php

namespace App\Tik\Repositories;

use App\Models\RoomVisitor;

class RoomVisitorRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new RoomVisitor());
    }

    public function getByUser($id)
    {
        return $this->model->where('user_id',$id)->with('room')->get();
    }
}

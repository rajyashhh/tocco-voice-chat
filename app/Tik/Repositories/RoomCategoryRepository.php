<?php

namespace App\Tik\Repositories;

use App\Models\RoomCategory;

class RoomCategoryRepository extends AbstractRepository
{

  
    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new RoomCategory());
    }

    public function getRoomCategory()
    {
        return $this->model->query()->whereDoesntHave('parent')->select('id', 'name', 'img')->get();
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function getTypeRoomCategory()
    {
        return $this->model->query()->whereHas('parent')->select('id', 'name', 'img', 'name_en')->get();
    }
}

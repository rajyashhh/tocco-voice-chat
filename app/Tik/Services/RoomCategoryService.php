<?php

namespace App\Tik\Services;

use App\Tik\Repositories\RoomCategoryRepository;


class RoomCategoryService
{
    public function __construct(
        private readonly RoomCategoryRepository $roomCategoryRepository,
    ) {
    }

    public function index()
    {
        return $this->roomCategoryRepository->getRoomCategory();
    }

    public function findById($id)
    {
        return $this->roomCategoryRepository->findById($id);
    }

    public function getByType()
    {
        return $this->roomCategoryRepository->getTypeRoomCategory();
    }
}

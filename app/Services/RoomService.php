<?php

namespace App\Services;

use Exception;
use App\Repositories\Room\RoomRepo;
use App\Repositories\Room\RoomRepository;

class RoomService
{
    protected $roomRepo;
    protected $roomRepository;

    public function __construct(
        RoomRepo $roomRepo,
        RoomRepository $roomRepository,
    ) {
        $this->roomRepo = $roomRepo;
        $this->roomRepository = $roomRepository;
    }

    public function getAllRooms($request)
    {
        return $this->roomRepo->all($request);
    }

    public function roomDetails($userId)
    {
        $room = $this->roomRepository->FindByUserId($userId);
        if (!$room) throw new Exception('This user don\'t have room');
        return $room;
    }
}

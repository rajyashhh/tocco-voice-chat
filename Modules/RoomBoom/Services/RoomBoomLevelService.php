<?php

namespace Modules\RoomBoom\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\RoomBoom\Entities\BoomPercentage;
use Modules\RoomBoom\Entities\RoomBoomLevel;
use Modules\RoomBoom\Repositories\RoomBoomLevelRepository;

class RoomBoomLevelService
{
    public function __construct(private readonly RoomBoomLevelRepository $roomBoomLevelRepository) {}

    public function index($id): Collection|array
    {
        return $this->roomBoomLevelRepository->getLatestWithRewards($id);
    }

    public function getVideos(): \Illuminate\Support\Collection
    {
        return $this->roomBoomLevelRepository->getVideos();
    }

    public function boomPercentage()
    {
        $roomBoomLevel = RoomBoomLevel::orderBy('level')->get();
        $boomPercentage = BoomPercentage::orderBy('percentage')->get();
        return [$roomBoomLevel, $boomPercentage];
    }
}

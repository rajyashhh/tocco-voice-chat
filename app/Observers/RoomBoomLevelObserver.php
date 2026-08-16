<?php

namespace App\Observers;

use Modules\RoomBoom\Entities\RoomBoomLevel;

class RoomBoomLevelObserver
{
    public function created(RoomBoomLevel $roomBoomLevel): void
    {
        if ($roomBoomLevel->video) {
            settings()->set('room_boom_video_update_at', time());
        }
    }

    public function updated(RoomBoomLevel $roomBoomLevel): void
    {
        $oldVideo = $roomBoomLevel->getOriginal('video');

        if ($roomBoomLevel->video != $oldVideo) {
            settings()->set('room_boom_video_update_at', time());
        }
    }

    public function deleted(RoomBoomLevel $roomBoomLevel): void
    {
        if ($roomBoomLevel->video) {
            settings()->set('room_boom_video_update_at', time());
        }
    }
}

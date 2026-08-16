<?php

namespace Modules\Events\Tests;

use App\Models\Background;
use App\Models\Room;
use Tests\TestCase;

class RoomTest extends TestCase
{

    public function testGetFinalRoomImageAttribute()
    {

        $room = Room::first();

        $model = Background::first();
        echo 'this is background ' . $model->img . PHP_EOL;
        $room->room_background = null;
        $room->save();

        echo($room->final_room_image);

    }
}

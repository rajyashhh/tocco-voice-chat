<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomGame;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomMaxAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $rooms = Room::get();
        foreach ($rooms as $room) {
            $room->max_admin = 0;
            $room->save();
        }
    }
}

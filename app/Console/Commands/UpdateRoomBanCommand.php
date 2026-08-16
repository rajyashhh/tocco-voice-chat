<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Room;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateRoomBanCommand extends Command
{

    protected $signature = 'update-room-ban';

    protected $description = 'update room ban';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $rooms = Room::where('room_status', 2)->get();

        if ($rooms) {

            foreach ($rooms as $room) {
                $ban = $room->bans()
                    ->whereRaw("created_at + INTERVAL duration HOUR > ?", [now()])
                    ->first();
                if (!$ban) {
                    $room->room_status = 1;
                    $room->save();
                }
            }
        }
    }
}

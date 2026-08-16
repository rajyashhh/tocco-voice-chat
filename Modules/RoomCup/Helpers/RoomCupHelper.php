<?php

namespace Modules\RoomCup\Helpers;

use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\RoomBoom\Entities\TotalRoomGift;
use App\Models\EnteredRoom;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class RoomCupHelper
{
    public static function updateRoomVisitors(int $roomId): void
    {
        $timezone = Common::timeZone();
        $today    = Carbon::now($timezone)->startOfDay()->setTimezone('UTC');
        $tomorrow = Carbon::now($timezone)->endOfDay()->setTimezone('UTC');
//        $today    = Carbon::now($timezone)->startOfDay();
//        $tomorrow = (clone $today)->endOfDay();

        $uniqueVisitors = EnteredRoom::query()
            ->where('rid', $roomId)
            ->whereBetween('entered_at', [$today, $tomorrow])
            ->distinct('uid')
            ->count('uid');

        $gift = TotalRoomGift::where('room_id', $roomId)
            ->whereBetween('created_at', [$today, $tomorrow])
            ->lockForUpdate()
            ->first();

        if ($gift) {
            $gift->update(['number_of_visitors' => $uniqueVisitors]);
        } else {
            TotalRoomGift::create([
                'room_id'            => $roomId,
                'current_total'      => 0,
                'number_of_visitors' => $uniqueVisitors,
            ]);
        }
    }


    public static function updateRoomCupWallet(float|int $diffCoins): void
    {
        $sql = '
            UPDATE core_wallets
            SET coins = coins + :coins
            WHERE name = "room_cup_target"
        ';

        \DB::update($sql, [
            'coins' => $diffCoins,
        ]);
    }


}

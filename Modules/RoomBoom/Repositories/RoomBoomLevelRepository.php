<?php

namespace Modules\RoomBoom\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\RoomBoom\Entities\RoomBoomLevel;

class RoomBoomLevelRepository
{
    public function getLatestWithRewards(int $roomId): Collection|array
    {
        $tz = getTimezone();
        $today = Carbon::today($tz);

        return RoomBoomLevel::with([
            'roomBoomRewards' => function ($query) {
            $query->orderBy('priority');
        },
            'roomBooms' => function ($query) use ($roomId, $today) {
                $query->whereHas('totalRoomGift', function ($q) use ($roomId) {
                    $q->where('room_id', $roomId);
                })
                ->whereDate('started_at', $today);
        },
        ])->orderBy('level')->get();
    }

    public function getVideos(): \Illuminate\Support\Collection
    {
        // Cache for 5 minutes - data is static and returned same for all users
        return \Illuminate\Support\Facades\Cache::remember('boom_levels:videos', 300, function () {
            // Removed 'roomBooms' from eager loading - it was loading thousands of historical records!
            // Only load roomBoomRewards which are actually needed for the response
            return RoomBoomLevel::select(['id', 'level', 'video', 'image_type'])
                ->with(['roomBoomRewards' => function ($query) {
                    // Fixed: Use correct column names (target_type, target) not (type, value)
                    $query->select(['id', 'room_boom_level_id', 'priority', 'target_type', 'target', 'quantity', 'expire_days'])
                        ->orderBy('priority');
                }])
                ->orderBy('level')
                ->get();
        });
    }
}

<?php

namespace Modules\RoomBoom\Transformers;

use App\Http\Resources\Api\V1\TopUsersRankResource;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\RoomBoom\Entities\RoomBoomTopContributor;

class RoomBoomResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'total_gifts_value' => $this->total_gifts_value,
            'level' => $this->roomBoomLevel ? $this->roomBoomLevel->level : null,
            'top_contributors' => $this->ended_at ? TopUsersRankResource::collection($this->getTopContributors()) : [],
//            'top_contributors' => $this->ended_at ? TopUsersRankResource::collection($this->getTopContributors()) : [],
        ];
    }

    protected function getTopContributors(): Collection|array
    {
        return RoomBoomTopContributor::query()
            ->select(
                'user_id',
                DB::raw('SUM(price) as total_gift'),
                DB::raw('MIN(created_at) as first_contribution')
            )
            ->where('room_boom_level_id', $this->room_boom_level_id)
            ->where('total_room_gift_id', $this->total_room_gift_id)
            ->whereDate('created_at', '>=', Carbon::today())
            ->groupBy('user_id')
            ->orderByDesc('total_gift')
            ->orderBy('first_contribution')
            ->limit(10)
            ->get();
    }

//    protected function getTopContributors()
//    {
//        $topContributors = GiftLog::select('sender_id',
//            DB::raw('SUM(giftPrice) as total_gift'),
//            DB::raw('MIN(created_at) as first_contribution')
//        )
//            ->where('room_id', $this->totalRoomGift->room_id)
//            ->where('room_boom_level', $this->roomBoomLevel->level)
//            ->where('start_boom_ranking', 1)
//            ->where('created_at', '>=', Carbon::today())
//            ->groupBy('sender_id')
//            ->orderByDesc('total_gift')
//            ->orderBy('first_contribution', 'asc')
//            ->limit(3)
//            ->get();
//
//
//        if ($topContributors->isEmpty()) {
//            return collect([$this->getEmptyUser()]);
////            $lastGift = GiftLog::find($this->final_gift_id);
////            if ($lastGift) {
////                $user = User::with('profile')->find($lastGift->sender_id);
////                if ($user) {
////                    $user->total_gift = GiftLog::where('room_boom_uuid', $lastGift->room_boom_uuid)
////                        ->where('sender_id', $lastGift->sender_id)
////                        ->sum('giftPrice');
////
////                    return collect([$user]);
////                }
////            }
//        }
//
//        return $topContributors->map(function($contributor) {
//            $user = User::with('profile')->find($contributor->sender_id);
//            if ($user) {
//                $user->total_gift = $contributor->total_gift;
//                return $user;
//            }
//            return null;
//        })->filter();
//    }

    protected function getEmptyUser(): object
    {
        return (object) [
            'id' => 0,
            'name' => '',
            'uuid' => '',
            'profile' => (object) ['avatar' => ''],
            'total_gift' => 0,
        ];
    }
}

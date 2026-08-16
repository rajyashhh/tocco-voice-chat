<?php

namespace Modules\RoomBoom\Jobs;

use App\Events\RoomBoomRewardsEvent;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Models\GiftLog;
use App\Models\Room;
use App\Models\User;
use App\Models\UserGift;
use App\Models\Ware;
use Carbon\Carbon;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\RoomBoom\Entities\RoomBoom;
use Modules\RoomBoom\Entities\RoomBoomReward;
use Modules\RoomBoom\Transformers\RoomBoomRewardResource;

class OldRoomBoomRewardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $boomId;

    public function __construct($boomId)
    {
        $this->boomId = $boomId;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $boom = RoomBoom::with(['roomBoomLevel', 'totalRoomGift'])->find($this->boomId);
        if (!$boom || !$boom->roomBoomLevel || !$boom->totalRoomGift) return;

        $level = $boom->roomBoomLevel;
        $roomId = $boom->totalRoomGift->room_id;
        if (!$roomId) return;

        $rewards = RoomBoomReward::where('room_boom_level_id', $level->id)->orderBy('priority')->get();

        $rewardItems = [];
        foreach ($rewards as $reward) {
            for ($i = 0; $i < $reward->quantity; $i++) {
                $rewardCopy = clone $reward;
                $rewardCopy->quantity = 1;
                $rewardItems[] = $rewardCopy;
            }
        }

        $topContributorIds = GiftLog::
        select('sender_id',
            DB::raw('SUM(giftPrice) as total_gift'),
            DB::raw('MIN(created_at) as first_contribution')
        )
            ->where('room_id', $roomId)
            ->where('room_boom_level', $level->level)
            ->where('start_boom_ranking', 1)
            ->where('created_at', '>=', Carbon::today())
            ->groupBy('sender_id')
            ->orderByDesc('total_gift')
            ->orderBy('first_contribution', 'asc')
            ->limit(3)
            ->pluck('sender_id')
            ->toArray();

        $assignments = [];
        $assignedUserIds = [];
        $winnerData = [];

        foreach ($topContributorIds as $i => $userId) {
            if (!isset($rewardItems[$i])) break;
            $reward = $rewardItems[$i];             //first user will take first reward ordered by priority and quantity
            $this->distributeBoomRewards($userId, $reward);

            $assignedUserIds[] = $userId;
            $assignments[] = $reward;

            $winnerData[] = [
                'user_id' => $userId,
                'image'   => (new RoomBoomRewardResource((object)$reward))->getImageUrl(),
                'image_type' => (new RoomBoomRewardResource((object)$reward))->getGiftImageType(),
            ];
        }

//        $lastTriggerSenderId = GiftLog::where('room_id', $roomId)
//            ->where('room_boom_level', $level->level)
//            ->where('start_boom_ranking', 1)
//            ->orderByDesc('created_at')
//            ->value('sender_id');

        $lastTriggerSenderId = GiftLog::where('id', $boom->final_gift_id)->value('sender_id');

        if ($lastTriggerSenderId && !in_array($lastTriggerSenderId, $topContributorIds)) {
            if ($rewards->isNotEmpty()){
                $randomReward = $rewards->random();
                $this->distributeBoomRewards($lastTriggerSenderId, $randomReward);

                $assignedUserIds[] = $lastTriggerSenderId;
                $assignments[] = $randomReward;

                $winnerData[] = [
                    'user_id' => $lastTriggerSenderId,
                    'image'   => (new RoomBoomRewardResource((object)$randomReward))->getImageUrl(),
                    'image_type' => (new RoomBoomRewardResource((object)$randomReward))->getGiftImageType(),
                ];
            }
        }

        $numAssigned = count($assignments);
        $remainingRewards = array_slice($rewardItems, $numAssigned);    //remaining rewards by order

        $room = Room::where('id', $roomId)->first();

        $allVisitorIds = $room->roomVisitors()->pluck('user_id')->toArray();
        $unrewardedVisitorIds = array_diff($allVisitorIds, $assignedUserIds);
        shuffle($unrewardedVisitorIds);

        foreach ($unrewardedVisitorIds as $visitorId){
            if (!isset($remainingRewards[$i])) break;
            $reward = $remainingRewards[$i];
            $this->distributeBoomRewards($visitorId, $reward);

            $winnerData[] = [
                'user_id' => $visitorId,
                'image' => (new RoomBoomRewardResource((object)$reward))->getImageUrl(),
                'image_type' => (new RoomBoomRewardResource((object)$reward))->getGiftImageType(),
            ];
        }

        foreach ($winnerData as $winner) {
            $data = [
                "message" => "roomBoomEnded",
                'roomBoomLevel' => $level->level,
                'duration' => 10,
                'winner' => $winner
            ];

            event(new RoomBoomRewardsEvent($data));
        }
    }

    /**
     * @throws \Exception
     */
    public function distributeBoomRewards($userId, $reward): void
    {
        info($userId);
        $user = User::find($userId);
        if ($user){
            $expire = $reward['expire_days'];
            if ($reward['target_type'] == 'ware') {
                $ware = Ware::find($reward->target);
                UserCommon::addEvintsWareToUser($user, $ware, $expire,null,'room-boom');
            }
            if ($reward['target_type'] == 'achieve') {
                $target = $reward->target;
                $dateTimestamp = $expire ? Carbon::parse($expire)->format('Y-m-d H:i:s') : null;
                $title = __('Achievement Reward');
                $body = __('You have received a new achievement.');
                UserAchievementLevel::create([
                    'user_id' => $userId,
                    'custom_image' => $target,
                    'end_at' => $dateTimestamp,
                ]);
                Common::sendOfficialMessage($user->id, $title, $body);
                // $token = DB::table('users')->where('id', $user->id)->value('notification_id');
                // if ($token) {
                //     Common::send_firebase_notification([$token], $title, $body);
                // }
            }

            if ($reward['target_type'] == 'gift') {
                $target = $reward['target'];
                $title = __('Gift Reward');
                $body = __('You have received a new gift.');

                $data = [
                    'gift_id' => $target,
                    'user_id' => $userId,
                    'quantity' => $reward['quantity'],
                ];
                if ($expire){
                    $data['expire'] = $expire;
                }
                UserGift::create($data);
                Common::sendOfficialMessage($user->id, $title, $body);
                // $token = DB::table('users')->where('id', $user->id)->value('notification_id');
                // if ($token) {
                //     Common::send_firebase_notification([$token], $title, $body);
                // }
            }
        }
    }
}

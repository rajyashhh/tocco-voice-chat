<?php

namespace Modules\RoomBoom\Jobs;

use App\Enums\UserCoinLogType;
use App\Events\RoomBoomRewardsEvent;
use App\Helpers\Common;
use App\Jobs\SendRoomDataJob;
use App\Helpers\UserCoinLogHelper;
use App\Helpers\UserCommon;
use App\Models\Gift;
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
use Illuminate\Support\Collection;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\RoomBoom\Entities\RoomBoom;
use Modules\RoomBoom\Entities\RoomBoomGift;
use Modules\RoomBoom\Entities\RoomBoomLevel;
use Modules\RoomBoom\Entities\RoomBoomReward;
use Modules\RoomBoom\Entities\RoomBoomTopContributor;
use Modules\RoomBoom\Transformers\RoomBoomRewardResource;

class NewRoomBoomRewardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $boomId;
    public $userId;
    protected Collection $users;
    protected array $giftInsertData = [];
    protected array $achievementInsertData = [];
    protected array $assignedUserIds = [];
    protected array $assignments = [];
    protected array $winnerData = [];
    protected array $achievementNotifications = [];
    protected array $giftNotifications = [];
    protected array $wareNotifications = [];
    protected array $coinNotifications = [];

    public function __construct($boomId, $userId)
    {
        $this->boomId = $boomId;
        $this->userId = $userId;
    }

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function handle()
    {
        info('in room boom reward job');

        $boom = RoomBoom::with(['roomBoomLevel', 'totalRoomGift'])->find($this->boomId);
        $level = $boom->roomBoomLevel;
        $roomId = $boom->totalRoomGift->room_id;
        if (!$boom || !$boom->roomBoomLevel || !$boom->totalRoomGift || !$roomId) return;

        $rewards = RoomBoomReward::where('room_boom_level_id', $level->id)->orderBy('priority')->get();

        $rewardItems = [];

        $this->getRewardItems($rewards, $rewardItems);

        $topContributors = $this->getTopContributorIds($boom->total_room_gift_id, $level->level);

        $topContributorIds = $topContributors->pluck('user_id')->toArray();

        $lastTriggerSenderId = $this->userId;

        $room = Room::select('id')->with('roomVisitors:id,user_id,room_id')->find($roomId);

        if ($room) {
            $allUserIds = array_merge($topContributorIds, [$lastTriggerSenderId], $room->roomVisitors->pluck('user_id')->toArray());
        }

        $this->users = User::whereIn('id', $allUserIds)->select(['id', 'notification_id'])->get()->keyBy('id');

        $this->distributeTopContributors($topContributorIds, $rewardItems);

        $this->distributeLastTriggerSender($lastTriggerSenderId, $topContributorIds, $rewards, $rewardItems);

        $this->distributeVisitorRewards($rewardItems, $room);

        $this->sendEvent($level->level, $roomId);

        $this->bulkInsertGiftsAchievements();

        $this->storeTopContributors($topContributors, $boom);

        $this->storeWinners($boom);

        $this->dispatchPendingNotifications();
    }

    /**
     * @throws \Exception
     */
    public function distributeTopContributors($topContributorIds, &$rewardItems): void
    {
        foreach ($topContributorIds as $i => $userId) {
            $reward = $this->getNextAvailableReward($rewardItems);
            if (!$reward) break;

            $this->distributeBoomRewards($userId, $reward);
            $this->assignWinnerData($userId, $reward);
        }
    }

    /**
     * @throws \Exception
     */
    public function distributeLastTriggerSender($lastTriggerSenderId, $topContributorIds, $rewards, &$rewardItems): void
    {
        if ($lastTriggerSenderId && !in_array($lastTriggerSenderId, $topContributorIds)) {
            if ($rewards->isNotEmpty()){

                if (empty($topContributorIds)) {
                    $chosenReward = $this->getNextAvailableReward($rewardItems);
                } else {
                    $randomReward = $rewards->random();
                    $chosenReward = [
                        'id'          => $randomReward->id,
                        'target_type' => $randomReward->target_type,
                        'target'      => $randomReward->target,
                        'expire_days' => $randomReward->expire_days,
                        'priority'    => $randomReward->priority,
                        'quantity'    => 1,
                    ];
                }

                $this->distributeBoomRewards($lastTriggerSenderId, $chosenReward);
                $this->assignWinnerData($lastTriggerSenderId, $chosenReward);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function distributeVisitorRewards(&$rewardItems, $room): void
    {
        $visitorIds = $room->roomVisitors()
            ->whereNotIn('user_id', $this->assignedUserIds)
            ->inRandomOrder()
            ->pluck('user_id')
            ->toArray();

        foreach ($visitorIds as $i => $visitorId){
            $reward = $this->getNextAvailableReward($rewardItems);
            if (!$reward) break;

            $this->distributeBoomRewards($visitorId, $reward);

            $this->assignWinnerData($visitorId, $reward);
        }
    }

    /**
     * @throws \Exception
     */
    public function distributeBoomRewards($userId, $reward): void
    {
        info($userId);
        $user = $this->users[$userId] ?? null;

        if ($user){
            $token = $user->notification_id;
            $expire = $reward['expire_days'];
            if ($reward['target_type'] == 'ware') {
                $this->wareNotifications[$reward['target']]['user_ids'][] = $userId;
                $this->wareNotifications[$reward['target']]['expire_days'] = $expire;

                if ($token) {
                    $this->wareNotifications[$reward['target']]['tokens'][] = $token;
                }
            }

            if ($reward['target_type'] == 'achieve') {
                $this->achievementRewards($reward['target'], $expire, $userId, $token);
            }

            if ($reward['target_type'] == 'gift') {
                $this->giftRewards($reward, $userId, $expire, $token);
            }


            if ($reward['target_type'] == 'coin') {
                $this->coinRewards($reward['target'], $userId, $token);
            }
        }
    }

    public function achievementRewards($rewardTarget, $expire, $userId, $token): void
    {
        $dateTimestamp = $expire ? Carbon::parse($expire)->format('Y-m-d H:i:s') : null;

        $this->achievementInsertData[] = [
            'user_id' => $userId,
           // 'custom_image' => $rewardTarget,
            'custom_achievement_id' => $rewardTarget,
            'end_at' => $dateTimestamp,
            'created_at' => now(),
            'updated_at' => now()
        ];

        $this->achievementNotifications['user_ids'][] = $userId;
        if ($token) {
            $this->achievementNotifications['tokens'][] = $token;
        }
    }

    public function giftRewards($reward, $userId, $expire, $token): void
    {
        $giftData = [
            'gift_id' => $reward['target'],
            'user_id' => $userId,
            'quantity' => 1,
            'expire' => $expire ?? 0,
            'created_at' => now(),
            'updated_at' => now()
        ];

        $this->giftInsertData[] = $giftData;

        $this->giftNotifications[$reward['target']]['user_ids'][] = $userId;
        if ($token) {
            $this->giftNotifications[$reward['target']]['tokens'][] = $token;
        }
    }

    public function coinRewards($amount, $userId, $token = null): void
    {
        $user = User::findOrFail($userId);

        $amountBefore = $user->di ?? 0;
    
        UserCoinLogHelper::logByType(
            $user->id,
            $amount, 
            $amountBefore,
            UserCoinLogType::ROOM_BOOM,
            '' 
        );
    
        $user->increment('di', (int)$amount);


        $this->coinNotifications['users'][$userId] = ($this->coinNotifications['users'][$userId] ?? 0) + (int)$amount;
        if ($token) {
            $this->coinNotifications['tokens'][] = $token;
        }
    }

    public function getRewardItems($rewards, &$rewardItems): void
    {
        foreach ($rewards as $reward) {
            $rewardItems[] = [
                'id'          => $reward->id,
                'target_type' => $reward->target_type,
                'target'      => $reward->target,
                'expire_days' => $reward->expire_days,
                'priority'    => $reward->priority,
                'quantity'    => $reward->quantity,
            ];
        }
    }

    protected function getNextAvailableReward(&$rewardItems)
    {
        foreach ($rewardItems as &$reward) {
            if ($reward['quantity'] > 0) {
                $reward['quantity']--;
                return $reward;
            }
        }
        return null;
    }

    public function getTopContributorIds($totalRoomGiftId, $levelColumn)
    {
        return RoomBoomGift::query()
            ->select('user_id',
                DB::raw('SUM(price) as total_gift'),
                DB::raw('MIN(created_at) as first_contribution')
            )
            ->where('total_room_gift_id', $totalRoomGiftId)
            ->where('room_boom_level', $levelColumn)
            ->where('start_boom_ranking', 1)
            ->where('created_at', '>=', Carbon::today())
            ->groupBy('user_id')
            ->orderByDesc('total_gift')
            ->orderBy('first_contribution', 'asc')
            ->get();
    }

    public function assignWinnerData($userId, $reward): void
    {
        $this->assignedUserIds[] = $userId;
        $this->assignments[] = $reward;

        $this->winnerData[] = [
            'user_id' => $userId,
            'image'   => (new RoomBoomRewardResource((object)$reward))->getImageUrl(),
            'image_type' => (new RoomBoomRewardResource((object)$reward))->getGiftImageType(),
        ];
    }

    public function sendEvent($levelColumn, $roomID): void
    {
        $data = [
            "message" => "roomBoomEnded",
            'roomBoomLevel' => $levelColumn,
            'duration' => 10,
            'winners' => $this->winnerData
        ];

        event(new RoomBoomRewardsEvent($data, $roomID));

        $inRoom = json_encode(['messageContent' => $data]);
        dispatchJobToQueue(new SendRoomDataJob((int) $roomID, (int) $this->userId, [$inRoom]), 'heavyProcessing');
    }

    public function bulkInsertGiftsAchievements(): void
    {
        if (!empty($this->giftInsertData)) {
            UserGift::insert($this->giftInsertData);
        }
        if (!empty($this->achievementInsertData)) {
            UserAchievementLevel::insert($this->achievementInsertData);
        }
    }

    /**
     * @throws \Throwable
     */
    protected function dispatchPendingNotifications(): void
    {
        $this->dispatchWareNotification();
        $this->dispatchAchievementNotification();
        $this->dispatchGiftNotification();
        $this->dispatchCoinNotification();

    }

    /**
     * @throws \Throwable
     */
    public function dispatchWareNotification(): void
    {
        $wareTitle = __('congratulations');
        $wareBody = __('You have received a gift: :ware');

        $wareIds = array_keys($this->wareNotifications);
        $wares   = Ware::whereIn('id', $wareIds)->get()->keyBy('id');

        foreach ($this->wareNotifications as $wareId => $notification) {
            $ware     = $wares[$wareId] ?? null;
            $wareName = $ware->name ?? __('a special ware');
            $body     = str_replace(':ware', $wareName, $wareBody);
            $expire = $notification['expire_days'] ?? null;

            foreach ($notification['user_ids'] as $userId) {
                $user = $this->users[$userId] ?? null;
                if ($user) {
                    UserCommon::assignRoomBoomWare($user, $ware, $expire);
                }
            }

            if (!empty($notification['user_ids'])) {
                Common::sendOfficialMessage($notification['user_ids'], $wareTitle, $body);
            }

            if (!empty($notification['tokens'])) {
                Common::send_firebase_notification($notification['tokens'], $wareTitle, $body);
            }
        }
    }


    public function dispatchAchievementNotification(): void
    {
        if (!empty($this->achievementNotifications)) {
            $achievementTitle = __('Achievement Reward');
            $achievementBody = __('You have received a new achievement.');

            // ✅ تم إصلاح: استخدام $this->achievementNotifications بدلاً من $notification
            if (!empty($this->achievementNotifications['user_ids'])) {
                Common::sendOfficialMessage($this->achievementNotifications['user_ids'], $achievementTitle, $achievementBody);
            }

            // ✅ تم إصلاح: استخدام $this->achievementNotifications بدلاً من $notification
            if (!empty($this->achievementNotifications['tokens'])) {
                Common::send_firebase_notification($this->achievementNotifications['tokens'], $achievementTitle, $achievementBody);
            }
        }
    }

    public function dispatchGiftNotification(): void
    {
        $giftTitle = __('Gift Reward');
        $giftBody = __('You have received the gift: :giftName');
        $giftIds = array_keys($this->giftNotifications);
        $gifts = Gift::whereIn('id', $giftIds)->get()->keyBy('id');

        foreach ($this->giftNotifications as $giftId => $notification) {
            $giftName = $gifts[$giftId]->name ?? __('a special gift');

            $body = str_replace(':giftName', $giftName, $giftBody);

            if (!empty($notification['user_ids'])) {
                Common::sendOfficialMessage($notification['user_ids'], $giftTitle, $body);
            }

            if (!empty($notification['tokens'])) {
                Common::send_firebase_notification($notification['tokens'], $giftTitle, $body);
            }
        }
    }

    public function dispatchCoinNotification(): void
    {
        if (!empty($this->coinNotifications)) {
            $coinTitle = __('Coin Reward');
            $coinBody  = __('You have received :coin coin.');

            foreach ($this->coinNotifications['users'] as $userId => $coins) {
                $body = str_replace(':coin', $coins, $coinBody);

                if ($userId) {
                    Common::sendOfficialMessage($userId, $coinTitle, $body);
                }
            }

            if (!empty($this->coinNotifications['tokens'])) {
                Common::send_firebase_notification($this->coinNotifications['tokens'], $coinTitle, $body);
            }
        }
    }

    protected function storeTopContributors($topContributors, $boom): void
    {
        $level = $boom->roomBoomLevel;
        $totalRoomGiftId = $boom->total_room_gift_id;

        $topContributorData = array_map(function ($topContributor) use ($level, $totalRoomGiftId){
            return [
                'room_boom_level_id' => $level->id,
                'total_room_gift_id' => $totalRoomGiftId,
                'user_id' => $topContributor['user_id'],
                'price' => $topContributor['total_gift'],
                'created_at' => now(),
                'updated_at' => now()
            ];
        }, $topContributors->toArray());

        DB::table('room_boom_top_contributors')->insert($topContributorData);
    }

    protected function storeWinners($boom): void
    {
        $winnersData = array_map(function ($reward, $userId) use ($boom) {
            return [
                'room_boom_reward_id' => $reward['id'],
                'room_boom_id'        => $boom->id,
                'user_id'             => $userId,
                'created_at'          => now(),
                'updated_at'          => now(),
            ];
        }, $this->assignments, $this->assignedUserIds);

        DB::table('room_boom_winners')->insert($winnersData);
    }

}

//        $lastTriggerSenderId = GiftLog::where('room_id', $roomId)
//            ->where('room_boom_level', $level->level)
//            ->where('start_boom_ranking', 1)
//            ->orderByDesc('created_at')
//            ->value('sender_id');

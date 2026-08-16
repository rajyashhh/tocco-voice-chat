<?php

namespace Modules\HostLevel\Http\Services;

use Carbon\Carbon;
use App\Models\Ware;
use App\Helpers\Common;
use App\Models\GiftLog;
use App\Helpers\UserCommon;
use App\Enums\UserCoinLogType;
use Modules\Vip\Entities\OVip;
use App\Helpers\UserCoinLogHelper;
use Modules\Events\Entities\GeneralRole;
use Modules\HostLevel\Entities\HostLevel;
use Modules\HostLevel\Entities\HostLevelWinner;
use Modules\Achievement\Entities\UserAchievementLevel;

class HostLevelService
{


    public function hostLevelIndex()
    {
        return HostLevel::with('rewards')->orderBy('level', 'asc')->get();
    }

    public function roles()
    {
        return GeneralRole::where('type', 'host_level')->first();
    }

    public function userInfoLevel($user)
    {
        $eventType = $this->getEventType();
//        $lastPick = $user->lastHostLevelWinnerByEvent($eventType)->first();
//        if ($lastPick && $lastPick->hostLevel) {
//            $nextLevel = HostLevel::where('level', '>', $lastPick->hostLevel->level)
//                ->orderBy('level', 'asc')
//                ->first();
//        } else {
//            $nextLevel = HostLevel::orderBy('level', 'asc')->first();
//        }
//        $lastPickLevel = $user->lastHostLevelWinnerByEvent($eventType)->first();
//        $courant = $lastPickLevel?->hostLevel?->level;

        $diamonds = $this->computeDiamonds($user->id) ?? 0;
        $current = HostLevel::where('diamonds', '<=', $diamonds)->orderByDesc('diamonds')->first();
        $nextLevel = HostLevel::where('diamonds', '>', $diamonds)->orderBy('diamonds')->first();

//        $level = HostLevel::where('diamonds', '<=', $diamonds)->orderByDesc('level')->value('level');
        $lastLevelEvent = HostLevel::orderByDesc('level')->first();
        if ($lastLevelEvent && $diamonds > ($lastLevelEvent->diamonds ?? 0)) {
            $nextLevel = $lastLevelEvent;
        }

        return [$diamonds, $nextLevel->level ?? 0, $current->level ?? 0, $current?->level ?? 0, $eventType];
    }


    public function pickHostLevel($user, $HistLevelId)
    {

        $userId = $user->id;

        $eventType = $this->getEventType();

        $checkPick = HostLevelWinner::where('user_id', $userId)->where('host_level_id', $HistLevelId)->filterByEventType($eventType)->first();
        if ($checkPick) throw new \Exception(__('you have already picked this host level before'));
        $hostLevel = $this->hostLevel($HistLevelId);
        if (!$hostLevel) throw new \Exception(__('host level not found'));
        $diamonds = $this->computeDiamonds($userId);
        if (!$diamonds || ($diamonds < $hostLevel->diamonds_required)) {
            throw new \Exception(__('you do not meet the diamond requirement to pick this host level'));
        }

        HostLevelWinner::create(
            [
                'user_id' => $userId,
                'host_level_id' => $HistLevelId,
            ]
        );
        if (!$hostLevel->rewards)  return true;


        $this->assignReward($user, $hostLevel->rewards);

        return true;
    }


    public function hostLevel($id)
    {
        return HostLevel::with('rewards')->find($id);
    }

    public function assignReward($user, $rewards)
    {
        $notifications = [];

        foreach ($rewards as $reward) {
            if ($reward->type == "coins") {

                $amountBefore = $user->di;
                UserCoinLogHelper::logByType(
                    $user->id,
                    $reward->target,
                    $amountBefore,
                    UserCoinLogType::HOST_LEVEL,
                );

                $user->di += $reward->target;
                $user->save();

                $notifications[] = [
                    'title' => __('Coin Reward'),
                    'body'  => str_replace(':coin', $reward->target, __('You have received :coin coin.')),
                ];
            } elseif ($reward->type == "vip") {
                $vip = OVip::query()->find($reward->target);
                UserCommon::addVipToUser($user, $vip, $reward->expire, null, 'host-level');

                $notifications[] = [
                    'title' => __('congratulations'),
                    'body'  => __('vip_message', ['vip_name' => $vip->name]),
                ];
            } elseif ($reward->type == "ware") {
                $ware = Ware::query()->find($reward->target);
                if ($ware) {
                    UserCommon::addEvintsWareToUser($user, $ware, $reward->expire, null, 'host-level');

                    $wareName = $ware->name ?? __('a special ware');
                    $notifications[] = [
                        'title' => __('congratulations'),
                        'body'  => str_replace(':ware', $wareName, __('You have received a gift: :ware')),
                    ];
                }
            } elseif ($reward->type == "achievement") {

           //     $dateTimestamp = Carbon::parse($reward->expire)->format("Y-m-d H:i:s");

            $dateTimestamp = now()->addDays((int)$reward->expire)->format("Y-m-d H:i:s");

                $attributes = [
                    'user_id'       => $user->id,
                    'custom_achievement_id' => $reward->target,
                    'receive_type' => 'host-level',
                    'end_at' => $dateTimestamp,
                ];
                UserAchievementLevel::create($attributes);

                $notifications[] = [
                    'title' => __('Achievement Reward'),
                    'body'  => __('You have received a new achievement.'),
                ];
            } elseif ($reward->type == 'badge') {
                Common::userBadge($user->id, $reward->target, $reward->expire, 'host-level');

                $notifications[] = [
                    'title' => __('congratulations'),
                    'body'  => __('badge_gift_message'),
                ];
            }
        }

        $this->sendBatchNotifications($user, $notifications);
    }

    private function sendBatchNotifications($user, array $notifications): void
    {
        foreach ($notifications as $notification) {
            Common::sendOfficialMessage($user->id, $notification['title'], $notification['body']);
            Common::send_firebase_notification($user->notification_id, $notification['title'], $notification['body']);
        }
    }


    private function getEventType(): string
    {
        return Common::getSettingValue('host_level_type') ?? 'daily';
    }

    private function computeDiamonds($userId)
    {
        $eventType = $this->getEventType();

        return  GiftLog::where('receiver_id', $userId)
            ->filterByEventType($eventType)
            ->selectRaw('receiver_id, SUM(giftPrice) AS total_diamond')->groupBy("receiver_id")
            ->value('total_diamond');
    }
}

<?php

namespace App\Helpers;

use App\Enums\UserCoinLogType;
use App\Models\User;
use App\Models\Ware;
use Carbon\Carbon;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Vip\Entities\OVip;

class UserRewardsChargeKing
{
    public static function getUserById($id)
    {
        return User::find($id);
    }

    public static function assignCoins($amount, User $user): void
    {
        $amount = (int) $amount;
        if ($amount <= 0) {
            return;
        }

        UserCoinLogHelper::logByType(
            $user->id,
            $amount,
            $user->di,
            UserCoinLogType::CHARGE_EVENT,
        );

        $user->increment('di', $amount);
    }

    public static function assignVip($vipId, $expire, User $user): void
    {
        $vip = OVip::find($vipId);
        if (!$vip) {
            return;
        }
        UserCommon::addVipToUser($user, $vip, $expire, null, 'charge-king');
    }

    public static function assignWare($wareId, $expire, User $user): void
    {
        $ware = Ware::find($wareId);
        if (!$ware) {
            return;
        }
        UserCommon::addEvintsWareToUser($user, $ware, $expire, null, 'charge-king');
    }

    public static function assignBadge($badgeId, $expire, User $user): void
    {
        Common::userBadge($user->id, $badgeId, $expire, 'charge-king');
    }

    public static function assignAchievement($achievementId, $expire, User $user): void
    {
        UserAchievementLevel::create([
            'user_id' => $user->id,
            'custom_achievement_id' => $achievementId,
            'end_at' => Carbon::parse($expire)->format('Y-m-d H:i:s'),
            'receive_type' => 'charge-king',
        ]);
    }
}
<?php

namespace App\Helpers;

use Modules\Vip\Entities\OVip;
use Carbon\Carbon;
use Modules\Achievement\Entities\UserAchievementLevel;

class CoinsTarget
{
    public static function assignCoinsUser($amount, $user){
        $user->increment('di', $amount);
    }
    public static function assignVipUser($vipId, $expire, $userOne)
    {
        $vip = OVip::find($vipId);
        UserCommon::addVipToUser($userOne, $vip, $expire,null,'coins-target');
    }

    public static function assignWareUser($ware, $reward, $user ){
        UserCommon::addWareToUser($user, $ware, $reward->expire , null ,'coin-target');
    }

    public static function assignAchievementUser($itemId, $expire, $userOne)
    {
        $dateTimestamp = Carbon::parse($expire)->format('Y-m-d H:i:s');

        $attributes = [
            'custom_image' => $itemId,
            'end_at'       => $dateTimestamp,
        ];
        UserAchievementLevel::create(array_merge($attributes, ['user_id' => $userOne->id]));
    }
}

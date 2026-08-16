<?php

namespace Modules\Events\Services;

use App\Models\Pack;
use Modules\Vip\Entities\UserVip;
use App\Models\Ware;
use Carbon\Carbon;

class LoseWinnerRewards
{

    public function removePacksVip( $reward,UserVip $vip, $user,$expire)
    {
        $wares = Ware::query ()->where ('get_type',1)->where ('level',$vip->level)->get ();
        $targetIds = $wares->pluck('id');

        $createdAt = Carbon::parse($reward->created_at);
        $afterExpire = $createdAt->copy()->addDays($expire ?? 0)->timestamp;
        $expire = intval(($afterExpire - now()->timestamp) );
        if ($expire < 0) return;

        Pack::whereIn('target_id', $targetIds)->where('get_type', 1)->where('user_id', $user->id)->decrement('expire', $expire);

        Pack::query ()->where ('user_id',$user->id)
            ->whereIn('target_id', $targetIds)
            ->where('get_type', 1)
            ->where ('expire','<',now ()->timestamp)
            ->delete ();
    }
}

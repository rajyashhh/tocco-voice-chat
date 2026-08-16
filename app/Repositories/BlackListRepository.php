<?php

namespace App\Repositories;

use App\Helpers\Common;
use App\Models\BlackList;
use App\Models\User;
use Modules\Vip\Entities\Vip;
use Illuminate\Database\Eloquent\Model;

class BlackListRepository
{
    public function getUserBlackList($userId)
    {
        $black_list = Common::getUserBlackList ($userId);
        return User::query ()->whereIn ('id',$black_list)->get ();
    }

    public function removeUserFromBlackList($userId, $fromUserId)
    {
        return BlackList::query()
            ->where('user_id', $userId)
            ->where('from_uid', $fromUserId)
            ->delete();
    }

    public function addUserToBlackList($userId, $fromUserId)
    {
        return BlackList::create([
            'user_id' => $userId,
            'from_uid' => $fromUserId
        ]);
    }

    public function exists($authId, $userId) : bool
    {
        $cacheKey = "blacklist_check_{$authId}_{$userId}";

        return \Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            fn() => BlackList::where(fn($q) => $q->where('user_id', $authId)->where('from_uid', $userId))
                ->orWhere(fn($q) => $q->where('user_id', $userId)->where('from_uid', $authId))
                ->exists()
        );
    }
}

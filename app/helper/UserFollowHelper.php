<?php

namespace App\helper;

use App\Models\User;

class UserFollowHelper
{
    /**
     *
     * @param User $user
     * @return void
     */
    public static function updateCounts(User $user): void
    {
        $user->number_of_fans = $user->followers()->count();

        $user->number_of_followings = $user->followeds()->count();

        $user->number_of_friends = $user->friends()->count();


        $user->save();
    }
}

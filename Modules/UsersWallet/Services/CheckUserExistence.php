<?php

namespace Modules\UsersWallet\Services;

use App\Models\User;
use Exception;

class CheckUserExistence
{
    /**
     * @throws Exception
     */
    public static function userExists(User $userModel,int $userId)
    {
        $user = $userModel::whereId($userId)->first();

        if (! $user)throw new Exception(__('this user not found'));

        return $user;
    }
}

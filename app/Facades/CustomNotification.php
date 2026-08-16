<?php

namespace App\Facades;

use App\Models\User;
use Illuminate\Support\Facades\Facade;

/**
 * @method static senderLevel(int $userId)
 * @method static receiverLevel(int $userId)
 * @method static target(int $userId)
 * @method static momentComment($moment, $user)
 * @method static familyLevelUpgrade(int $id)
 * @method static officialMsg(\App\Models\OfficialMessageAdmin $officialMessageAdmin,$usersId)
 * @method static family(\App\Models\Family $family, mixed $user)
 * @method static banUser(User $user, $duration)
 * @method static removeBanUser(User $user)
 * @method static roomcupReward(User $user, float $amount, string $rewardType = 'owner')
 */
class CustomNotification extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'CustomNotification';
    }


}

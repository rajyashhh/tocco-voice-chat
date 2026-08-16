<?php

namespace App\Helpers;

use App\Models\User;

class UserLevelHelper
{
    public static function getSenderImage(?User $user): string
    {
        if (!$user) return '';
        return $user->senderLevel?->img ?? '';
    }

    public static function getReceiverImage(?User $user): string
    {
        if (!$user) return '';
        return $user->receiverLevel?->img ?? '';
    }
}

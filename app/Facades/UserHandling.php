<?php

namespace App\Facades;

use App\Models\User;
use Illuminate\Support\Facades\Facade;

/**
 * @method static kickUserFromAgency(User $model, $isApp = 0)
 * @method static checkIfUserOwnerOfAgency(User $model)
 * @method static checkIfUserOwnerOfFamily(int $userId)
 * @method static hasReasonOfBan($uuid, \Illuminate\Http\Request $request)
 * @method static haveBan(string $uuid, \Illuminate\Http\Request $request)
 * @method static getLevel(int $int, $total_received_diamonds)
 */
class UserHandling extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'UserHandling';
    }


}

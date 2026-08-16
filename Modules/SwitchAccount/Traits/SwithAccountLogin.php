<?php

namespace Modules\SwitchAccount\Traits;

use Illuminate\Support\Facades\Auth;

trait SwithAccountLogin
{

    public function checkIsSameAccount(int $userId, array $fields)
    {
        if (array_key_exists('is_multi', $fields) && $fields['is_multi']){
            $mainUser = Auth::guard('sanctum')->user();
            if ($userId == $mainUser->id){
                return true;
            }
        }
        return false;
    }

    public function getSameAccountMessage()
    {
        return "Not Allow to add same account";
    }
}

<?php

namespace Modules\SwitchAccount\Http\Services;


use App\models\User;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Modules\SwitchAccount\Entities\UserDevicesHistory;


class SwitchAccountServices
{
    public function saveDeviceUser(int $userId,  $deviceToken)
    {
        if ($deviceToken != null) {
            $userDevice = UserDevicesHistory::where('user_id', $userId)->where('device_token', $deviceToken)->exists();
            if (!$userDevice) {
                UserDevicesHistory::create(
                    [
                        'user_id' =>  $userId,
                        'device_token' => $deviceToken
                    ],
                );
            }
        }
    }

    public function getUsersAccountsByDevice(int $userId)
    {
        $user = UserDevicesHistory::where('user_id', $userId)->first();
        if (!$user) return Common::apiResponse(0, 'user not found', 404);
        $deviceToken = $user->device_token;
        $users = UserDevicesHistory::where('device_token', $deviceToken)->with('user')->get();

        return $users;
    }


    public function countUsersAccountsByDevice(int $userId)
    {
        $user = UserDevicesHistory::where('user_id', $userId)->first();
        if (!$user) return Common::apiResponse(0, 'user not found', 404);
        $deviceToken = $user->device_token;

        return UserDevicesHistory::where('device_token', $deviceToken)->count();
    }
}

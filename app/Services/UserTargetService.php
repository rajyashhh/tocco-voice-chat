<?php

namespace App\Services;

use App\Models\User;
use App\Helpers\Common;
use App\Models\Setting;
use App\Models\UserTarget;
use Modules\Public\Http\Services\UserCounterServices;

class UserTargetService
{
    //UserTargetAchieveJob
    public function sendNotification(UserTarget $userTarget)
    {
        $appNameEn = Setting::where('key', 'app_title_en')->value('value') ?? 'Default';

        $appNameAr = Setting::where('key', 'app_title_ar')->value('value') ?? 'Default';
        $user         = $userTarget->user;
        $targetSalary = $userTarget->target_usd;
        $lang = $user->lan ?? 'en';
        $body = __('api.achieve_target', ['salary' => $targetSalary], $lang);
        
        $notificationIds[] = $user->notification_id;
        Common::sendOfficialMessage($user->id, $body, $user->name, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
        $title = ($user->lan == 'ar') ?  $appNameAr : $appNameEn;
        $data["target_user"] = $userTarget->id;
        Common::send_firebase_notification($notificationIds, $title, $body, data: $data, messageType: 'achieve-target');
    }
}

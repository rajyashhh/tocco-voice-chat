<?php

namespace Modules\SalaryTransaction\Helpers;

use Modules\Vip\Entities\Vip;
use App\Models\Gift;
use App\Models\User;
use App\Models\Ware;
use App\Models\Agency;
use App\Models\Family;
use App\Helpers\Common;
use App\Models\Setting;
use App\Models\OfficialMessage;
use Modules\Reals\Entities\Real;
use Illuminate\Support\Facades\DB;
use Modules\Moment\Entities\Moment;
use App\Models\OfficialMessageAdmin;

class TransactionCustomNotification
{

    public static function sendRequest(int $userId)
    {

        $appNameEn = Setting::where('key', 'app_title_en')->value('value') ?? 'Default';
        $appNameAr = Setting::where('key', 'app_title_ar')->value('value') ?? 'Default';
        $user = User::withoutAppends()->where('id', $userId)->first();
        if (!$user) {
            return 0;
        }
        $tokens_notfacion = $user->notification_id;
        $lang = $user->lan ?? 'en';
        $body = __('salaryTransaction::api_responses.request_added', ['user' => $user->name], $lang);
        
        $title = __('salaryTransaction::api_responses.request_title',[],$lang);
        $titleAppName = ($user->lan == 'ar') ?  $appNameAr : $appNameEn;
        Common::send_firebase_notification($tokens_notfacion, $titleAppName, $body);
        Common::sendOfficialMessage($user->id, title: $body, content: $title, titleAr: $body);
    }

    public static function action_request(int $userId, $answer, $amount = 0, $userHId = null)
    {
        $user = User::withoutAppends()->where('id', $userId)->first();
        if (!$user) {
            return 0;
        }
        $tokens_notfacion = $user->notification_id;
        if ($answer == 3) {
            $body_ar =  __('salaryTransaction::api_responses.tranfer_action', ['usd' => $amount], 'ar');
            $body_en =  __('salaryTransaction::api_responses.tranfer_action', ['usd' => $amount], 'en');
        } elseif ($answer == 4) {
            $user = User::find($userHId);
            $body_ar =  __('salaryTransaction::api_responses.complete_action', ['user' => $user->name, 'usd' => $amount], 'ar');
            $body_en =  __('salaryTransaction::api_responses.complete_action', ['user' => $user->name_en, 'usd' => $amount], 'en');
        } elseif ($answer == 5) {
            $body_ar =  __('salaryTransaction::api_responses.host_rejected', ['user' => $user->name, 'count' => $amount], 'ar');
            $body_en =  __('salaryTransaction::api_responses.host_rejected', ['user' => $user->name_en, 'count' => $amount], 'en');
        } elseif ($answer == 6) {
            $body_ar =  __('salaryTransaction::api_responses.accept_request', ['user' => $user->name, 'count' => $amount], 'ar');
            $body_en =  __('salaryTransaction::api_responses.accept_request', ['user' => $user->name_en, 'count' => $amount], 'en');
        } elseif ($answer == 7) {
            $body_ar =  __('salaryTransaction::api_responses.refused_request', ['user' => $user->name, 'count' => $amount], 'ar');
            $body_en =  __('salaryTransaction::api_responses.refused_request', ['user' => $user->name_en, 'count' => $amount], 'en');
        } else {
            $body_ar =  $answer == 0 ? __('salaryTransaction::api_responses.accept_request', ['coins' => $amount], 'ar') : __('salaryTransaction::api_responses.refused_request', [], 'ar');
            $body_en =  $answer == 0 ? __('salaryTransaction::api_responses.accept_request', ['coins' => $amount], 'en') : __('salaryTransaction::api_responses.refused_request', [], 'en');
        }

        $firebaseBody = ($user->lan === 'ar') ? $body_ar : $body_en;
        $title = __('salaryTransaction::api_responses.action_title');
        $titleAppName = ($user->lan == 'ar') ?  config('app.name_ar') : config('app.name_en');
        Common::send_firebase_notification($tokens_notfacion,  $titleAppName, $firebaseBody);
        Common::sendOfficialMessage($user->id, title: $body_en, content: $title, titleAr: $body_ar);
    }
}

<?php

namespace App\Admin\Actions;

use Exception;
use App\Models\Ban;
use App\Models\Room;
use App\Models\User;
use App\Models\Admin;
use App\Models\Agency;
use App\Models\Charge;
use Encore\Admin\Form;
use App\Helpers\Common;
use App\Models\BanType;
use App\Models\CoinLog;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Encore\Admin\Actions\Action;
use Encore\Admin\Auth\Permission;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Encore\Admin\Facades\Admin as AuthAdmin;

class BanUser extends Action
{
    public $name;

    protected $selector = '.ban_user_action';
    public $permission_name = 'bans';

    public function handle(Request $request)
    {
      
        $countryID = Common::filterCountryIds();


        if (!AuthAdmin::user()->can('*')) {
            Permission::check('create-' . $this->permission_name);
        }
        $user = User::query()
            ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->searchByUuid($request->uuid)->first();
        if (!$user) return $this->response()->error('user not found')->refresh();
        $userUuid  = $user->original_uuid;
        $userId  = $user->id;
        $now = now();
        $messages = [];
        $newBan = false;

        if (!$user) {
            return $this->response()->error(__('user not found'))->refresh();
        }
        $room = Room::query()->where('uid',  $user->now_room_uid)->first();
        // $ban = Ban::query()->where('uid', $userUuid)->where('ty')->first();
        if (in_array('ip', $request->type)) {

            $haveBan = Ban::query()->where('uid', $userUuid)->where('user_id', $userId)->where('type', 'ip')->whereRaw("created_at + INTERVAL duration HOUR > '$now'")
                ->exists();
            if ($haveBan) {
                $messages[] = __('already have ip ban');
            } else {
                $newBan = true;
                $ips = $user->ips;
                foreach ($ips as $ip) {
                    // if(!$ban && $ip != $ban->ip){
                    Ban::query()->create(
                        [
                            'uid' => $userUuid,
                            'user_id' => $userId,
                            'duration' => $request->duration,
                            'ip' => $ip->ip,
                            'type' => 'ip',
                            'user_type' => 0,
                            'staff_id' => Auth::id(),
                            'description_ar' => $request->description_ar,
                            'description_en' => $request->description_en ?? $request->description_ar,
                            'img' => ($request->file('img') ? ($request->file('img') ? Common::upload('bans', $request->file('img')) : "") : "")
                        ]
                    );
                    // }
                }
            }
        }
        if (in_array('device', $request->type)) {
            $haveBan = Ban::query()->where('uid', $userUuid)->where('user_id', $userId)->where('type', 'device')->whereRaw("created_at + INTERVAL duration HOUR > '$now'")
                ->exists();
            if ($haveBan) {
                $messages[] = __('already have device ban');
            } else {
                $newBan = true;

                Ban::query()->create(
                    [
                        'uid' => $userUuid,
                        'user_id' => $userId,
                        'duration' => $request->duration,
                        'device_number' => $user->device_token,
                        'type' => 'device',
                        'user_type' => 0,
                        'staff_id' => Auth::id(),
                        'description_ar' => $request->description_ar,
                        'description_en' => $request->description_en ?? $request->description_ar,
                        'img' => ($request->file('img') ? Common::upload('bans', $request->file('img')) : "")
                    ]
                );
            }
        }
        if (in_array('normal', $request->type)) {
            $haveBan = Ban::query()->where('uid', $userUuid)->where('user_id', $userId)->where('type', 'normal')->whereRaw("created_at + INTERVAL duration HOUR > '$now'")
                ->exists();
            if ($haveBan) {
                $messages[] = __('already have normal ban');
            } else {
                $newBan = true;

                Ban::query()->create(
                    [
                        'uid' => $userUuid,
                        'user_id' => $userId,
                        'duration' => $request->duration,
                        'type' => 'normal',
                        'user_type' => 0,
                        'staff_id' => Auth::id(),
                        'description_ar' => $request->description_ar,
                        'description_en' => $request->description_en ?: $request->description_ar,
                        'img' => ($request->file('img') ? Common::upload('bans', $request->file('img')) : ""),
                    ]
                );
            }
        }

        if ($request->ban_type_id) {

            $haveBan = Ban::query()->where('uid', $userUuid)->where('user_id', $userId)->where('ban_type_id', $request->ban_type_id)->whereRaw("created_at + INTERVAL duration HOUR > '$now'")->exists();
             if ($haveBan)  $messages[] = __('already have ban');
            $ban = Ban::query()->create(
                [
                    'uid' => $userUuid,
                    'user_id' => $userId,
                    'duration' => $request->duration,
                    'type' => 'action',
                    'user_type' => 0,
                    'staff_id' => Auth::id(),
                    'description_ar' => $request->description_ar,
                    'description_en' => $request->description_en ?: $request->description_ar,
                    'img' => ($request->file('img') ? Common::upload('bans', $request->file('img')) : ""),
                    'ban_type_id' => $request->ban_type_id,
                ]
            );

            $route = $ban->banType?->route;
                           

            if ($route == 'rooms/enter_room' && $user->nowRoom) {
                $ms = [
                    'messageContent' => [
                        "message" => "unableToEnterRoom",
                        'user_id' => $user->id,
                        'reason_ar' => $request->description_ar ?? '',
                        'reason_en' => $request->description_en ?? '',
                        'duration' => $request->duration ?? 0,
                    ]
                ];
                $json = json_encode($ms);
               $response =  Common::sendToStream('SendCustomCommand', @$user->nowRoom->id, $user->nowRoom->uid, $json);
            } else if ($route == 'rooms/up_microphone' && $user->nowRoom) {
                $ms = [
                    'messageContent' => [
                        "message" => "unableToUPMicrophone",
                        'user_id' => $user->id,
                        'reason_ar' => $request->description_ar ?? '',
                        'reason_en' => $request->description_en ?? '',
                        'duration' => $request->duration ?? 0,
                    ]
                ];
                $json = json_encode($ms);
                Common::sendToStream('SendCustomCommand', @$user->nowRoom->id, $user->nowRoom->uid, $json);
            } else if ($route == 'rooms/up-microphone' && $user->nowRoom) {
                $ms = [
                    'messageContent' => [
                        "message" => "unableToUPMicrophone",
                        'user_id' => $user->id,
                        'reason_ar' => $request->description_ar ?? '',
                        'reason_en' => $request->description_en ?? '',
                        'duration' => $request->duration ?? 0,
                    ]
                ];
                $json = json_encode($ms);
                Common::sendToStream('SendCustomCommand', @$user->nowRoom->id, $user->nowRoom->uid, $json);
            }
        }
        if ($room && $newBan) {
            $d = [
                "messageContent" => [
                    "message" => "banDevice",
                    "userId" => $user->id,
                    'reason_ar' => $request->description_ar ?? '',
                    'reason_en' => $request->description_en ?? '',
                    'duration' => $request->duration ?? 0,
                ]
            ];
            $json = json_encode($d);
           $response= Common::sendToStream('SendCustomCommand', $room->id, $user->id, $json);
        }
        if ($newBan) {
            CustomNotification::banUser($user, $request->duration);
        }
        // Drop the cached ban snapshot so the new ban is enforced immediately.
        \App\Services\BanGuard::invalidate($userUuid);
        if (count($messages) > 0) {
            return $this->response()->error(implode("<br>", $messages))->refresh();
        }
        return $this->response()->success('success')->refresh();
    }

    public function form()
    {
        $this->text('uuid', __('uuid'))->rules('required');
        $this->integer('duration', __('duration(hours)'))->rules('required|max:6');
        $this->text('description_ar', __('Enter the reason for the ban(arabic)'))
            ->rules(['required', 'string', 'max:255']);

        $this->text('description_en', __('Enter the reason for the ban(english)'))
            ->rules(['nullable', 'string', 'max:255']);

        $this->image('img', __('img'))
            ->rules(['nullable', 'image', 'mimes:jpeg,png,jpg',])
            ->setValidationMessages('img', [
                'image' => __('admin.custom_img_image'),
                'mimes' => __('admin.custom_img_mimes'),
            ]);

        $this->checkbox('type', __('type'))->options([
            'normal' => __('normal'),
           // 'ip' => __('ip'),
            'device' => __('device'),
        ]);
        $this->select('ban_type_id', __('ban_type'))->options(function ($value) {
            $ops2 = [];
            foreach (BanType::get() as $ban) {
                $ops2[$ban->id] = $ban->name_en . '_' . $ban->name_ar;
            }
            return $ops2;
        });
    }

    public function html()
    {
        $banText = __('create bans'); // Laravel translation
        return <<<HTML
    <a href="javascript:void(0);" class="ban_user_action btn btn-sm  text-white"
       style="background-color: var(--primary-color); border-color: var(--secondary-color); color: var(--text-secondary-color);">
        {$banText}
    </a>
    HTML;
    }
}

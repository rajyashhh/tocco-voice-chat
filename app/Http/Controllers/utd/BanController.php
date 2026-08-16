<?php

namespace App\Http\Controllers\utd;

use App\Facades\CustomNotification;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\BanResource;
use App\Models\Ban;
use App\Models\BanType;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BanController extends Controller
{
    public function index()
    {
        // Get the current time
        $now = now();

        $search= request('search');
        $uid = request('uid');
        // Fetch the bans based on the grid logic
        $perPage = request('per_page') ?? 10;

        $bans = Ban::whereHas('user')
        ->when($search, function($q)use($search){
            $q->where('search', $search);
        })
        ->when($uid, function($q)use($uid){
            $q->where('uid', $uid);
        })
        ->whereHas('user')
        ->whereRaw("DATE_ADD(created_at, INTERVAL duration HOUR) > '$now'")
        ->select('uid', 'duration', 'type', 'device_number', 'staff_id', 'description_ar', 'img', DB::raw('(SELECT created_at FROM bans AS b WHERE b.uid = bans.uid AND b.type = bans.type ORDER BY b.id DESC LIMIT 1) AS created_at'), 'ban_type_id')
        ->groupBy(['uid', 'type', 'duration', 'device_number', 'staff_id', 'description_ar', 'img', 'ban_type_id'])
        ->orderByDesc(DB::raw('MAX(created_at)'))
        ->paginate($perPage);
        // Return the data as a JSON response
        return Common::apiResponse(true, 'Success', BanResource::collection($bans));
    }

    public function banTypes(){
        $result = BanType::all();

        return Common::apiResponse(true, 'Success', $result);
    }
    public function removeBan(Request $request)
    {
        $user = User::query ()->where ('uuid',$request->uid)->first();
        if (!$user){
            return Common::apiResponse(false,__('user not found'));
        }
        Ban::query ()->where('uid',$request->uid)->delete();
        CustomNotification::removeBanUser($user);
        return Common::apiResponse(true,'Success');
    }
    public function delete(Request $request){

        $user = User::query ()->where ('uuid',$request->uid)->first();
        if (!$user){
            return Common::apiResponse(false, __('user not found'));
        }
        Ban::query ()->where('uid',$request->uid)->where('type',$request->type)->where('ban_type_id',$request->ban_type_id)->delete();
        CustomNotification::removeBanUser($user);
        return Common::apiResponse(true, 'Success');
    }


    public function banUser(Request $request)
    {
        $user = User::query()->searchByUuid($request->uuid)->first();
        $userUuid  = $user->original_uuid;
        $now = now();
        $messages = [];
        $newBan = false;

        $types = explode(',', $request->type);

        if (!$user) {
            return Common::apiResponse('false',__('user not found'));
        }
        $room = Room::query()->where('uid',  $user->now_room_uid)->first();
        // $ban = Ban::query()->where('uid', $userUuid)->where('ty')->first();
        if (in_array('ip', $types)) {

            $haveBan = Ban::query()->where('uid', $userUuid)->where('type', 'ip')->whereRaw("created_at + INTERVAL duration HOUR > '$now'")
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
                            'duration' => $request->duration,
                            'ip' => $ip->ip,
                            'type' => 'ip',
                            'user_type' => 0,
                            'staff_id' => $request->user_id,
                            'description_ar' => $request->description_ar,
                            'description_en' => $request->description_en ?? $request->description_ar,
                            'img' => ($request->file('img') ? ($request->file('img') ? Common::upload('bans', $request->file('img')) : "") : "")
                        ]
                    );
                    // }
                }
            }
        }
        if (in_array('device', $types)) {
            $haveBan = Ban::query()->where('uid', $userUuid)->where('type', 'device')->whereRaw("created_at + INTERVAL duration HOUR > '$now'")
                ->exists();
            if ($haveBan) {
                $messages[] = __('already have device ban');
            } else {
                $newBan = true;

                Ban::query()->create(
                    [
                        'uid' => $userUuid,
                        'duration' => $request->duration,
                        'device_number' => $user->device_token,
                        'type' => 'device',
                        'user_type' => 0,
                        'staff_id' => $request->user_id,
                        'description_ar' => $request->description_ar,
                        'description_en' => $request->description_en ?? $request->description_ar,
                        'img' => ($request->file('img') ? Common::upload('bans', $request->file('img')) : "")
                    ]
                );
            }
        }
        if (in_array('normal', $types)) {
            $haveBan = Ban::query()->where('uid', $userUuid)->where('type', 'normal')->whereRaw("created_at + INTERVAL duration HOUR > '$now'")
                ->exists();
            if ($haveBan) {
                $messages[] = __('already have normal ban');
            } else {
                $newBan = true;

                Ban::query()->create(
                    [
                        'uid' => $userUuid,
                        'duration' => $request->duration,
                        'type' => 'normal',
                        'user_type' => 0,
                        'staff_id' => $request->user_id,
                        'description_ar' => $request->description_ar,
                        'description_en' => $request->description_en ?: $request->description_ar,
                        'img' => ($request->file('img') ? Common::upload('bans', $request->file('img')) : ""),
                    ]
                );
            }
        }

        if ($request->ban_type_id) {
            $haveBan = Ban::query()->where('uid', $userUuid)->where('ban_type_id', $request->ban_type_id)->whereRaw("created_at + INTERVAL duration HOUR > '$now'")->exists();
            if ($haveBan)  $messages[] = __('already have ban');
            $ban = Ban::query()->create(
                [
                    'uid' => $userUuid,
                    'duration' => $request->duration,
                    'type' => 'action',
                    'user_type' => 0,
                    'staff_id' => $request->user_id,
                    'description_ar' => $request->description_ar,
                    'description_en' => $request->description_en ?: $request->description_ar,
                    'img' => ($request->file('img') ? Common::upload('bans', $request->file('img')) : ""),
                    'ban_type_id' => $request->ban_type_id,
                ]
            );

            $route = $ban->banType?->route;

            if ($route == 'rooms/enter_room' && $user->room) {
                // dd( $user->room());
                $ms = [
                    'messageContent' => [
                        "message" => "unableToEnterRoom",
                        'user_id' => $user->id
                    ]
                ];
                $json = json_encode($ms);
                Common::sendToStream('SendCustomCommand', @$user->room->id, $user->room->uid, $json);
            } else if ($route == 'rooms/up_microphone' && $user->room) {
                $ms = [
                    'messageContent' => [
                        "message" => "unableToUPMicrophone",
                        'user_id' => $user->id
                    ]
                ];
                $json = json_encode($ms);
                Common::sendToStream('SendCustomCommand', @$user->room->id, $user->room->uid, $json);
            }else if ($route == 'rooms/up-microphone' && $user->room) {
                $ms = [
                    'messageContent' => [
                        "message" => "unableToUPMicrophone",
                        'user_id' => $user->id
                    ]
                ];
                $json = json_encode($ms);
                Common::sendToStream('SendCustomCommand', @$user->room->id, $user->room->uid, $json);
            }
        }



        if ($room && $newBan) {
            $d = [
                "messageContent" => [
                    "message" => "banDevice",
                    "userId" => $user->id
                ]
            ];
            $json = json_encode($d);

            Common::sendToStream('SendCustomCommand', $room->id, $user->id, $json);
        }
        if ($newBan) {
            CustomNotification::banUser($user, $request->duration);
        }

        if (count($messages) > 0) {
            return Common::apiResponse(false, implode("<br>", $messages));
        }
        return Common::apiResponse(true, 'Success');
    }

}

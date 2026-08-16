<?php

namespace Modules\LuckyBox\Services;

use App\Enums\UserCoinLogType;
use Carbon\Carbon;
use App\Models\User;
use App\Helpers\Common;
use App\Models\CoreWallet;
use App\Events\SuperLuckyBox;
use App\Facades\RedisService;
use App\Helpers\UserCoinLogHelper;
use App\Jobs\SuperLuckyBoxJob;
use App\Jobs\NormalLuckyBoxJob;
use App\Jobs\SendRoomDataJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\LuckyBox\Entities\BoxUse;
use App\Http\Resources\Api\V1\BoxUseResource;


class BoxService
{
    public function __construct() {}

    /**
     * @throws \Throwable
     */
    public function sendBox($request, $user, $box, $room, $label)
    {
        $boxCoin = $box->type == 0 ?  $box->coins : $this->calculationSendBox($box);

        DB::beginTransaction();
        if ($box->type == 0) {
            $boxU = $this->sendNormalBox($box, $request, $boxCoin, $label, $room, $user->id);
        } else {
            $boxU = $this->sendSuperBox($box, $request,  $boxCoin, $label, $room, $user);
        }

        $amountBefore = $user->di;
        UserCoinLogHelper::logByType(
            $user->id ,
            -abs($box->coins),
            $amountBefore,
            UserCoinLogType::LUCK_BOX,
        );
        $user->decrement('di', $box->coins);
        try {
            DB::commit();
            $c = BoxUse::query()->where('room_id', $room->id)->where('not_used_num', '>', 0)->count();
            $rem_time = Carbon::createFromTimestamp($boxU->start_at)->diffInSeconds(
                Carbon::createFromTimestamp($boxU->end_at)
            );
            $type = $box->type == 1 ? 'super' : 'normal';
            $coins = $box->coins;
            $m = [
                "messageContent" => [
                    "message" => "showluckybox",
                    "ownerBoxId" => $user->id,
                    "ownerBoxName" => $user->name,
                    "boxCoins" => $coins,
                    "boxId" => $boxU->id,
                    "boxType" => $type,
                    "numOfBoxes" => (int)$c,
                    "ownerBoxImage" => $user->avatar,
                    "ownerBoxUId"  => $user->uuid,
                    "end_time" => Carbon::createFromTimestamp($boxU->end_at)->toDateTimeString(),
                    //'usersNum' => $request->users_num ?: $box->users,
                    //'rem_time' => $rem_time,
                    //'is_closed' => $box->is_closed,
                ]
            ];
            $json = json_encode($m);

            Common::sendToStream('SendCustomCommand', $room->id, $user->id, $json);



            return Common::apiResponse(1, '', new BoxUseResource($boxU), 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception;
            return Common::apiResponse(0, 'fail', null, 400);
        }
    }

    public function sendNormalBox($box, $request, $boxCoin, $label, $room, $userId)
    {
        $normalDuration = Common::getConf('normal_box_duration') ?? 1;
        $box_use_data = [
            'box_id' => $box->id,
            'user_id' => $userId,
            'coins' => $box->coins,
            'start_at' => now()->timestamp,
            'end_at' => now()->addHours($normalDuration)->timestamp,
            'room_uid' => $room->uid,
            'room_id' => $room->id,
            'users_num' =>  $request->users_num,
            'used_num' => 0,
            'used_coins' => 0,
            'not_used_num' =>  $request->users_num,
            'unused_coins' => $boxCoin,
            'type' => $box->type,
            'label' => $label,
            'image' => $box->image,
            'is_closed' => false,
        ];

        $boxUser = BoxUse::query()->create(
            $box_use_data
        );
        $key  = 'BoxUse_' . $boxUser->id;
        RedisService::updateUnSerialize($key, $box_use_data);
        dispatch(new NormalLuckyBoxJob())->delay(now()->addHours($normalDuration))->onQueue('test-super-lucky-box');
        return $boxUser;
    }

    public function sendSuperBox($box, $request,  $boxCoin, $label, $room, $user)
    {
        $box_use_data = [
            'box_id' => $box->id,
            'user_id' => $user->id,
            'coins' => $box->coins,
            'start_at' => now()->timestamp,
            'end_at' => now()->addMinutes($box->duration)->timestamp,
            'room_uid' => $room->uid,
            'room_id' => $room->id,
            'users_num' =>  $box->users,
            'used_num' => 0,
            'used_coins' => 0,
            'not_used_num' =>  $box->users,
            'unused_coins' => $boxCoin,
            'type' => $box->type,
            'label' => $label,
            'image' => $box->image,
            'is_closed' => false,
        ];

        //        dispatch(new SuperLuckyBoxJob())->delay(now()->seconds(30))->onQueue('');
        $boxUser = BoxUse::query()->create(
            $box_use_data
        );
        dispatch(new SuperLuckyBoxJob($boxUser->id))->delay(now()->addMinutes($box->duration))->onQueue('test-super-lucky-box');

        $key  = 'BoxUse_' . $boxUser->id;
        RedisService::updateUnSerialize($key, $box_use_data);
        if (!$user instanceof User) return;
        $d2 = [
            // "messageContent" => [
            //     "message" => "bannerSuperBox",
            'coins' => $request->coins ?: $box->coins,
            "boxUId" => $boxUser->id,
            "end_time" => Carbon::createFromTimestamp($boxUser->end_at)->toDateTimeString(),
            "room" => [
                "id" => $room->id,
                "uuid" => $room->owner->uuid,
                "room_name" => $room->room_name ?? '',
                "room_session" => $room->session,
                "room_owner_id" => $room->uid,
                "is_password" => $room->room_pass ? true : false,
                "room_cover" => $room->room_cover ?? '',
                "room_background" => $room->final_room_image ?? '',
                "room_mode" => $room->mode,
                "room_type" => $room->type,
            ],
            "sender" => [
                "id" => $user->id,
                "name" => @$user->name ?? '',
                "s_image" => @$user->profile->avatar ?? '',
                "s_name" => @$user->name,
                "s_sender_level" => $user->total_sender_level,
                "s_receiver_level" => $user->total_received_level,
            ],

            "ownerBoxAL"  => $user->UserVip?->level ?? 0,
            // ]
        ];
        try {
            event(new SuperLuckyBox($d2));
        } catch (\Exception $e) {
        }
        $inRoom = ['messageContent' => array_merge(['message' => 'bannerSuperBox'], $d2)];
        dispatchJobToQueue(new SendRoomDataJob((int) $room->id, (int) $user->id, [json_encode($inRoom)]), 'heavyProcessing');
        return $boxUser;
    }

    public function calculationSendBox($box)
    {
        $app_percentage = Common::getConfig('app_wallet_lucky_box') ?? 20;

        $walletCoins = ($box->coins * $app_percentage) / 100;
        $boxCoin = $box->coins - $walletCoins;

        $walletApp = CoreWallet::where('name', 'lucky_box')->first();
        $newWalletCoins = $walletApp->coins + $walletCoins;
        $walletApp->update([
            'coins' => $newWalletCoins,
        ]);
        return $boxCoin;
    }
}

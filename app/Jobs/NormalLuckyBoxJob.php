<?php

namespace App\Jobs;

use App\Enums\UserCoinLogType;
use App\Helpers\UserCoinLogHelper;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\User;

use App\Helpers\Common;
use App\Models\RoomVisitor;
use Illuminate\Bus\Queueable;
use Modules\LuckyBox\Entities\BoxUse;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Modules\LuckyBox\Entities\UserBoxGift;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Facades\CustomNotification;

class NormalLuckyBoxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $timezone = Common::timeZone();
        $timestamp = Carbon::now($timezone)->timestamp;

        $userBoxes = BoxUse::where('end_at', '<', $timestamp)->where('type', 0)->where('is_closed', false)->get();
        if ($userBoxes->isEmpty()) return;

         foreach ($userBoxes as $userBox) {
             $user = User::withoutAppends()->select('id', 'di')->find($userBox->user_id);
             if (!$user) continue;
             $amountBefore = $user->di;
             UserCoinLogHelper::logByType(
                 $user->id ,
                 $userBox->unused_coins,
                 $amountBefore,
                 UserCoinLogType::LUCK_BOX,
             );
             $user->increment('di', $userBox->unused_coins);
             $userBox->is_closed = true;
             $userBox->save();

             $room = Room::withoutAppends()->where('id', $userBox->room_id)->select('id')->first();
             if (!$room) continue;
             $c = BoxUse::query()->where('room_id', $userBox->room_id)->where('not_used_num', '>', 0)->count();
             $owner = User::withoutAppends()->select('id', 'name')->find($userBox->user_id);
             if (!$owner) continue; // ✅ إضافة null check للـ owner
             $userWinner = UserBoxGift::where('box_uses_id', $userBox->id)->pluck('user_id')->toArray();
             $usersRoomVisit = RoomVisitor::where('room_id', $room->id)->whereNotIn('user_id', $userWinner)->pluck('user_id')->toArray();

             $winnerBox = UserBoxGift::where('box_uses_id', $userBox->id)->exists();
             if (!$winnerBox) {
                 CustomNotification::closedLuckyBosWithReturnCoins($user, $userBox->unused_coins, @$userBox?->image, 0);
             } else {
                 CustomNotification::closeLuckyBox($user, @$userBox?->image, 0);
             }
              foreach ($usersRoomVisit as $userRoomVisit) {

                  $m = [
                      "messageContent" => [
                          "message" => "hideluckybox",
                          "ownerBoxId" => $owner->id, // ✅ آمن الآن - تم التحقق من null
                          "ownerBoxName" => $owner->name, // ✅ آمن الآن - تم التحقق من null
                          "boxCoins" => $userBox->coins,
                          "boxId" => $userBox->id,
                          "boxType" => $userBox->type == 1 ? 'super' : 'normal',
                          "numOfBoxes" => $c
                      ]
                  ];
                  $json = json_encode($m);
                  Common::sendToStream('SendCustomCommand', $room->id, $userRoomVisit, $json); // ✅ آمن الآن - تم التحقق من null
              }
          }
    }
}

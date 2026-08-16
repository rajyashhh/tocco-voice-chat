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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Facades\CustomNotification;

class NormalBoxRtmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $boxUseId;

    public function __construct($boxUseId)
    {
        $this->boxUseId = $boxUseId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $userBox = BoxUse::with(['userBoxGifts', 'user', 'box'])->find($this->boxUseId);
        $user = $userBox->user;
        $user->increment('di', $userBox->unused_coins);
        $userBox->is_closed = true;
        $userBox->save();
        $room = Room::withoutAppends()->where('id', $userBox->room_id)->select('id')->first();
        $c = BoxUse::query()->where('room_uid', $userBox->room_uid)->where('not_used_num', '>', 0)->count();
        $userWinner = $userBox->userBoxGifts()->pluck('user_id')->toArray();
        $usersRoomVisit = RoomVisitor::where('room_id', $room->id)->whereNotIn('user_id', $userWinner)->pluck('user_id')->toArray();
        CustomNotification::closeLuckyBox($user, $userBox?->image, 0);

        foreach ($usersRoomVisit as $userRoomVisit) {

            $m = [
                "messageContent" => [
                    "message" => "hideluckybox",
                    "ownerBoxId" => @$user->id,
                    "ownerBoxName" => @$user->name,
                    "boxCoins" => $userBox->coins,
                    "boxId" => $userBox->id,
                    "boxType" => $userBox->type == 1 ? 'super' : 'normal',
                    "numOfBoxes" => $c
                ]
            ];
            $json = json_encode($m);
            Common::sendToStream('SendCustomCommand', @$room->id, @$userRoomVisit->user_id, $json);
        }
    }
}

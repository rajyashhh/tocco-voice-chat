<?php

namespace App\Jobs;

use App\Enums\UserCoinLogType;
use App\Facades\CustomNotification;
use App\Helpers\Common;
use App\Helpers\LogHelper;
use App\Helpers\UserCoinLogHelper;
use App\Models\RoomVisitor;
use App\Models\User;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Promise\Utils;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\LuckyBox\Entities\BoxUse;
use Modules\LuckyBox\Entities\PickBoxList;
use Modules\LuckyBox\Entities\UserBoxGift;
use Modules\LuckyBox\Services\LuckyBoxServices;

class SuperLuckyBoxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?BoxUse $boxUse = null;

    public function __construct(?int $boxUseId = null)
    {

        $this->boxUse = BoxUse::with(['user', 'roomV2.owner'])
            ->findOrFail($boxUseId);

//        LogHelper::info('this is box ', $this->boxUse);
    }

    public function handle(): void
    {
        $box = $this->boxUse;
        if (! $box) {
            return;
        }

        $pickerIds = PickBoxList::where('box_user_id', $box->id)->pluck('user_id')->toArray();

        $users = User::whereIn('id', $pickerIds)->inRandomOrder()->take(100)->get();
        $giftService = new LuckyBoxServices();

        DB::transaction(function () use ($box, $users, $giftService) {
            foreach ($users as $user) {
                if ($box->users_num <= $box->used_num) {
                    break;
                }

                // Skip if not in room
                if (! RoomVisitor::where('user_id', $user->id)->exists()) {
                    continue;
                }

                // Skip if already gifted
                if (UserBoxGift::where('user_id', $user->id)->where('box_uses_id', $box->id)->exists()) {
                    continue;
                }

                $final = $box->not_used_num === 1;
                $box->not_used_num = max(0, $box->not_used_num - 1);
                $coins = $giftService->getCoins($final ? 1 : 0, $box->unused_coins, $box->not_used_num);

                UserBoxGift::create([
                    'box_uses_id' => $box->id,
                    'user_id' => $user->id,
                    'coins' => $coins,
                    'room_uid' => $box->room_uid,
                    'room_id' => $box->room_id,
                    'type' => $box->type,
                    'box_uses_owner_id' => $box->user_id,
                    'image' => $box->image,
                    'label' => $box->label,
                ]);

                $box->used_num += 1;
                $box->used_coins += $coins;
                $box->unused_coins = max(0, $box->unused_coins - $coins);

                $amountBefore = $user->di;
                UserCoinLogHelper::logByType(
                    userId: $user->id,
                    amount: $coins,
                    amountBefore: $amountBefore,
                    type: UserCoinLogType::LUCK_BOX,
                    itemNameOverride: null,
                    helperAmount: 0,
                    fromDate: now(),
                    toDate: now(),
                );

                // Safely update user's DI
                User::where('id', $user->id)->lockForUpdate()->increment('di', $coins);

                if ($coins > 0) {
                    CustomNotification::luckyBox($user, $coins, $box->image);
                }
            }

            $amountBefore = $box->user?->di;
            UserCoinLogHelper::logByType(
                 $box?->user?->id,
                 $box->unused_coins,
                 $amountBefore,
                UserCoinLogType::LUCK_BOX,
                 null,
                 0,
                 now(),
                now(),
            );
            // Refund remaining coins to box owner
            User::where('id', $box->user_id)->lockForUpdate()->increment('di', $box->unused_coins);

            $box->is_closed = true;
            $box->save();
        });

        $winners = UserBoxGift::where('box_uses_id', $box->id)->where('coins', '>', 0)
            ->select('user_id', 'coins')->get()->toArray();

        $remainingBoxCount = BoxUse::where('room_uid', $box->room_uid)->where('not_used_num', '>', 0)->count();

        $roomId = $box->roomV2?->id ?? 0;


        $js[] = $this->hideLuckyBoxForAllUsers( $box->user, $box, $remainingBoxCount);
        if ($winners) {
            $js[] = $this->sendWinnerMap($box, $winners);
        }

        $promises = Common::sendToStream3('SendCustomCommand', $roomId, $box->user?->id, $js);

        if (empty($winners)) {
            CustomNotification::closedLuckyBosWithReturnCoins($box->user, $box->unused_coins, $box->image, 1);
        } else {
            CustomNotification::closeLuckyBox($box->user, $box->image, 1);
        }

        try {
            Utils::unwrap($promises);
        } catch (BadResponseException $e) {
        }


    }

    public function sendWinnerMap(BoxUse $box, array $winners): string|bool
    {
        if (! $box || ! $box->user) {
            return false;
        }

        $payload = [
            'messageContent' => [
                'message' => 'winnerLuckyBox',
                'boxUId' => $box->id,
                'ownerId' => $box->user->id,
                'ownerName' => $box->user->name ?? '',
                'ownerImage' => $box->user->profile->avatar ?? '',
                'ownerUuId' => $box->user->uuid,
                'winners' => $winners,
            ],
        ];
     
        return json_encode($payload);

    }

    public function hideLuckyBoxForAllUsers(User $owner, BoxUse $box, int $remaining): string|bool
    {
        $payload = [
            'messageContent' => [
                'message' => 'hideluckybox',
                'ownerBoxId' => $owner->id,
                'ownerBoxName' => $owner->name,
                'boxCoins' => $box->coins,
                'boxId' => $box->id,
                'boxType' => $box->type === 1 ? 'super' : 'normal',
                'numOfBoxes' => $remaining,
            ],
        ];
     
      
     
        return json_encode($payload);

    }
}

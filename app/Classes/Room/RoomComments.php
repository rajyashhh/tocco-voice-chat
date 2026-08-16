<?php

namespace App\Classes\Room;

use App\Enums\UserCoinLogType;
use App\Exceptions\NotInfCoins;
use App\Helpers\Common;
use App\Helpers\UserCoinLogHelper;
use App\Jobs\AllOpeningRoomsZegoRequest;
use App\Jobs\SendRoomDataJob;
use App\Models\Room;
use App\Models\User;
use App\Repositories\Room\RoomRepoInterface;
use Illuminate\Validation\ValidationException;

class RoomComments
{

    private $roomRepo;

    public function __construct(RoomRepoInterface $roomRepo)
    {
        $this->roomRepo = $roomRepo;
    }

    /**
     *
     * @throws NotInfCoins
     */
    public function sendComments(User $user, array $data)
    {
        // configuration to number of coins required
        $commentCoinsValue = Common::getConf('special_bar_coin') ?? 50;

        if ($this->checkUserCommentCoins($user->di, $commentCoinsValue)) {
            $room = Room::query()->find($data['room_id']);
            $ms = [
                'messageContent' => [
                    'msg' => 'yellowBanner',
                    'event' => 'room.comment.event',
                    'uId' => $user->id,
                    'umsg' => $data['message'],
                    'oid' => @$room->uid,
                    'ps' => @$room->room_pass != null || @$room->room_pass != '', // password_status
                    'room' => [
                        'id' => @$room->id ?? 0,
                        'name' => @$room->room_name ?? '',
                        'cover' => @$room->room_cover ?? '',
                        'background' => @$room->final_room_image ?? '',
                        'mode' => @$room->mode ?? 0,
                        'stream_type' => @$room->is_live ?? false,
                        // Was: $room->gifts->sum(...) which lazy-loads the owner's
                        // ENTIRE gift_log history as Eloquent models and sums in PHP
                        // on EVERY comment (tail-latency source on this hot path).
                        // Same value via a single SQL SUM served by the covering
                        // index gift_logs(roomowner_id, created_at, giftPrice).
                        'gift_price' => (float) \App\Models\GiftLog::where('roomowner_id', $room->uid)->sum('giftPrice'),
                        'owner' => [
                            'id' => @$room->owner->id ?? 0,
                            'uuid' => @$room->owner->uuid ?? 0,
                        ],
                        'room_type' => $room->type
                    ],
                ]
            ];
            $json = json_encode($ms);

//            Common::sendToStream('SendCustomCommand', $room->id, $user->id, $json);
            // AllOpeningRoomsZegoRequest::dispatch($json, $user->id, $data['room_id'])
            // ->onQueue('zegoRequests');
            dispatchJobToQueue(new AllOpeningRoomsZegoRequest($json, $user->id, $data['room_id']), 'heavyProcessing');

            $inRoom = $ms;
            $inRoom['messageContent']['message'] = 'yellowBanner';
            dispatchJobToQueue(new SendRoomDataJob((int) $data['room_id'], (int) $user->id, [json_encode($inRoom)]), 'heavyProcessing');

            //send comment in queue
            //            dispatch(new SendCustomToZend($user->id, $data['room_id'], $data['message'], $rooms))->onQueue('sendComment');
            // minus coins for comments

            $user = $this->minusUserCoins($user, $commentCoinsValue);

            $user->save();
        } else {
            throw new NotInfCoins('Not inf coins');
        }
    }

    private function checkUserCommentCoins(int $totalCoins, int $coinsForComment): bool
    {
        return $totalCoins >= $coinsForComment;
    }

    private function minusUserCoins(User $user, int $numOfCoins)
    {
        $amountBefore =  $user->di;
        $logAmount = -abs($numOfCoins);
        UserCoinLogHelper::logByType(
            $user->id,
            $logAmount,
            $amountBefore,
            UserCoinLogType::ROOM_COMMENT,
        );
        $user->di -= $numOfCoins;
        return $user;
    }
}

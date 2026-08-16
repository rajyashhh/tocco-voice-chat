<?php

namespace App\Traits\Gifts;

use App\Helpers\Common;
use Illuminate\Support\Facades\Log;

trait WinLuckyGift
{
    public function sendToStreamLuckyGift($streamData)
    {
        $d     = [
            "messageContent" => [
                "msg"     => "SHBL",
                'event' => 'win.lucky.gift.event',
                'uid' => $streamData['user_id'],
                'uImg' => $streamData['user_image'],
                'gImg' => $streamData['gift_image'],
                'ownerId' => $streamData['owner_id'],
                'uName' => $streamData['user_name'],
                'per'   => $streamData['cache_value'],
                'isPass' => $streamData['is_room_pass'],
                'gNum' => $streamData['percentage']/*$streamData['gift_price']*/,

                'gift_price'   => @$streamData['gift_price'],
                'room_id'   => @$streamData['room_id'],
                'room_name'   => @$streamData['room_name'],
                'room_cover'   =>  @$streamData['room_cover'],
                'room_background'   => @$streamData['room_background'],
                'room_mode'   =>  @$streamData['room_mode'],
                'room_uuid'   =>  @$streamData['room_uuid'],
                'room_owner_id'   =>  @$streamData['room_owner_id'],
                'is_password'   =>   @$streamData['is_password'],
                'room_type'   =>   @$streamData['room_type'],
            ]
        ];
        $json  = json_encode($d);
        // Global (outside-room) banner -> BannerEvent -> Centrifugo banner:lucky_gift.
        // Fired DIRECTLY (BannerEvent is ShouldBroadcastNow, so the Centrifugo
        // publish runs synchronously) — the SAME reliability class as the gift
        // banner (GiftLogService fires GiftBannerEvent inline). The previous
        // AllOpeningRoomsZegoRequest->onQueue('default') hop had no reliable
        // consumer and silently swallowed every win banner.
        event(new \App\Events\BannerEvent(json_decode($json, true)));

        // In-room banner for everyone in the room, via the SAME live UTD-Stream
        // shim the win animation already uses (Common::sendToStream ->
        // PushStreamDataJob on default). Identical SendCustomCommand envelope.
        $inRoom = $d;
        $inRoom['messageContent']['message'] = 'win.lucky.gift.event';
        Common::sendToStream('SendCustomCommand', (int) $streamData['room_id'], (int) $streamData['user_id'], json_encode($inRoom));
    }

    public function sendToStreamLuckyGiftV2($streamData)
    {
        $d     = [
            "messageContent" => [
                "msg"     => "SHBL",
                'event' => 'win.lucky.gift.event',
                'uid' => $streamData['user_id'],
                'uImg' => $streamData['user_image'],
                'gImg' => $streamData['gift_image'],
                'ownerId' => $streamData['owner_id'],
                'uName' => $streamData['user_name'],
                'per'   => $streamData['cache_value'],
                'isPass' => $streamData['is_room_pass'],
                'gNum' => $streamData['percentage']/*$streamData['gift_price']*/,

                'gift_price'   => @$streamData['gift_price'],
                'room_id'   => @$streamData['room_id'],
                'room_name'   => @$streamData['room_name'],
                'room_cover'   =>  @$streamData['room_cover'],
                'room_background'   => @$streamData['room_background'],
                'room_mode'   =>  @$streamData['room_mode'],
                'room_uuid'   =>  @$streamData['room_uuid'],
                'room_owner_id'   =>  @$streamData['room_owner_id'],
                'is_password'   =>   @$streamData['is_password'],
                'room_type'   =>   @$streamData['room_type'],
                // Unique per-win id so the client keys each banner distinctly
                // and never merges two separate wins (inside + outside).
                'win_id'   =>   @$streamData['win_id'],
            ]
        ];
        $json  = json_encode($d);
        // Banner/sound trigger = COIN win value ≥ admin threshold (lucky_gift_coins),
        // NOT the multiplier band. The win value in coins is `cache_value`
        // (= ceil(iterationWin) = ceil(multiplier × giftPrice × number)), the exact
        // amount credited to the sender. The admin panel threshold is the single
        // source of truth for what is "big enough" to broadcast to all rooms.
        $winCoins  = (int) ($streamData['cache_value'] ?? 0);
        $threshold = (int) (\App\Helpers\Common::getSettingsValue('lucky_gift_coins') ?: 2000);
        if ($winCoins >= $threshold) {
            // Global (outside-room) banner -> BannerEvent -> Centrifugo
            // banner:lucky_gift. Fired DIRECTLY (BannerEvent is ShouldBroadcastNow,
            // publish runs synchronously) — the SAME reliability class as the gift
            // banner, which fires its event inline and is confirmed working outside
            // the room. The previous AllOpeningRoomsZegoRequest->onQueue('default')
            // hop had no reliable consumer and silently swallowed every win banner.
            // The originating room is covered by the in-room banner below.
            event(new \App\Events\BannerEvent(json_decode($json, true)));

            // In-room banner for everyone in the room, via the SAME live
            // UTD-Stream shim the win animation uses (Common::sendToStream ->
            // PushStreamDataJob on default). Identical SendCustomCommand envelope.
            $inRoom = $d;
            $inRoom['messageContent']['message'] = 'win.lucky.gift.event';
            Common::sendToStream('SendCustomCommand', (int) $streamData['room_id'], (int) $streamData['user_id'], json_encode($inRoom));
        }

    }
}

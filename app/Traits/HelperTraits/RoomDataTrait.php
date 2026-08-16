<?php

namespace App\Traits\HelperTraits;

use App\Helpers\Common;
use App\Jobs\PushStreamDataJob;
use Illuminate\Support\Facades\Log;

/**
 * In-room realtime data channel.
 *
 * This replaces the legacy third-party REST data channel. The public method
 * surface (sendToStream, sendToStream_2/_3/_4) is kept byte-for-byte so the
 * ~40 existing call sites across rooms/gifts/mics/boxes/bans keep working, but
 * every call is now routed to UTD-Stream (the only RTC provider) via
 * UtdStreamTrait::streamSendData. No third-party app id, server secret or
 * signature is involved anymore.
 *
 * The UTD-Stream room is addressed by its room name. The legacy callers pass the
 * numeric room id (or, in a few ban/partner cases, a room uid); the UTD-Stream
 * node resolves both forms (room_name / numid), so the identifier is forwarded
 * as-is. The Action + MessageContent envelope is preserved inside the data frame
 * so the client contract is unchanged.
 *
 * Failure policy: an RTC publish must never break — nor slow — the HTTP request
 * that triggered it (front-proxy 504 protection). Every method swallows its own
 * errors and returns a null-safe value, exactly like the old provider path did, and
 * the HTTP hop itself runs on a queue worker (PushStreamDataJob): a degraded
 * engine held Octane workers for the full send-data timeout per frame before
 * (139 cURL timeouts/24h, 2026-06-12).
 */
trait RoomDataTrait
{
    public static function getSignatureNonce()
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Forward an in-room data frame to UTD-Stream. Single seam used by every
     * public sendToStream* shim below. The publish is queued (fire-and-forget):
     * no shim caller reads the engine response, so nothing may hold the
     * request worker on a slow engine.
     */
    private static function pushRoomData($Action, $RoomId, array $frame, $destinationIdentities = null)
    {
        $roomName = (string) ($RoomId ?? request()->room_id);

        if ($roomName === '') {
            return null;
        }

        // Backend->room frames are sent in the {Action, MessageContent, FromUserId}
        // envelope. The APP's room_message_processor unwraps this envelope
        // (reads result['MessageContent'], json-decodes it, applies
        // receiver_charisma_totals on the server `showGifts` copy, then dispatches
        // banners/etc). The kit's own seat_controller ignores it (reads lowercase
        // `messageContent`), which is correct — only the app consumes server frames.
        // Do NOT unwrap here: stripping the envelope routes the server `showGifts`
        // copy down the client (animation) path, dropping charisma + double-animating.
        $payload = json_encode([
            'Action' => $Action,
            'MessageContent' => $frame['MessageContent'] ?? null,
        ] + $frame);

        try {
            dispatch(new PushStreamDataJob($roomName, $payload, $destinationIdentities));
        } catch (\Throwable $e) {
            Log::error('RoomDataTrait::pushRoomData exception', [
                'action' => $Action,
                'roomId' => $RoomId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public static function sendToStream($Action, $RoomId, $FromUserId, $MessageContent, $IsTest = 'false')
    {
        return self::pushRoomData($Action, $RoomId, [
            'FromUserId' => $FromUserId,
            'MessageContent' => $MessageContent,
        ]);
    }

    public static function sendToStream_2($Action, $RoomId, $UserId, $UserName, $MessageContent, $IsTest = 'false')
    {
        return self::pushRoomData($Action, $RoomId, [
            'UserId' => $UserId,
            'UserName' => $UserName,
            'MessageContent' => $MessageContent,
        ]);
    }

    public static function sendToStream_3($Action, $RoomId, $UserId, $IsTest = 'false')
    {
        return self::pushRoomData($Action, $RoomId, [
            'UserId' => $UserId,
        ], $UserId);
    }

    public static function sendToStream_4($Action, $RoomId, $fromUserId, $toUserId, $MessageContent, $IsTest = 'false')
    {
        return self::pushRoomData($Action, $RoomId, [
            'FromUserId' => $fromUserId,
            'MessageContent' => $MessageContent,
        ], $toUserId);
    }

    /**
     * Legacy helper used by Modules\Chat\Http\Controllers\ChatRoomController.
     * The legacy user-list REST call no longer exists; UTD-Stream exposes participants
     * via its rooms API, so return that shape (null-safe).
     */
    public static function get_users_list()
    {
        try {
            return Common::listRooms();
        } catch (\Throwable $e) {
            Log::error('RoomDataTrait::get_users_list exception', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\RoomCharismaStore;
use Illuminate\Http\Request;

class CharismaResetController extends Controller
{
    /**
     * Explicit owner/admin charisma reset: clears the server-authoritative
     * per-room store (RoomCharismaStore) and broadcasts a zeroed showGifts frame
     * so every client renders 0 for all current holders. This is a separate
     * lifecycle action from the 24h increment TTL and from mic-down/logout
     * handling, which stay untouched.
     */
    public function reset(Request $request)
    {
        if (!$request->owner_id) {
            return Common::apiResponse(false, __('api_responses.missing_owner_id'), null, 422);
        }

        $room = Room::query()
            ->where('uid', $request->owner_id)
            ->select('id', 'uid', 'room_admin')
            ->first();

        if (!$room) {
            return Common::apiResponse(false, __('api_responses.room_not_found'), null, 404);
        }

        $userId = $request->user()->id;
        $isOwner = $userId == $room->uid;
        $admins = explode(',', (string) $room->room_admin);
        $isAdmin = in_array($userId, $admins);

        if (!$isOwner && !$isAdmin) {
            return Common::apiResponse(false, __('you don not have permission'), null, 403);
        }

        $ids = RoomCharismaStore::userIds((int) $room->id);
        RoomCharismaStore::reset((int) $room->id);

        if (!empty($ids)) {
            $zeroTotals = array_fill_keys(array_map('strval', $ids), 0);
            Common::sendToStream('SendCustomCommand', $room->id, 0, json_encode([
                'messageContent' => [
                    'message' => 'showGifts',
                    'receiver_charisma_totals' => $zeroTotals,
                ],
            ], JSON_UNESCAPED_UNICODE));
        }

        return Common::apiResponse(true, __('success process'), null, 200);
    }
}

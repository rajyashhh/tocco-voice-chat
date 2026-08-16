<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\ActionAbuseGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * Live tap-hearts (التكبيس) aggregation.
 *
 * Clients render taps locally and fan them out peer-to-peer over the UTD
 * Stream lossy data channel; every ~2s each tapper POSTs its batched count
 * here. This endpoint is the AUTHORITY:
 *  - per-user credit is capped at 5 taps/sec via ActionAbuseGuard (warnings →
 *    1-min → 10-min blocks for auto-clickers),
 *  - the room total lives in Redis (atomic INCRBY — survives any load),
 *  - at most ONE official-total broadcast per second per room is pushed via
 *    UTD Stream, so fan-out cost is constant no matter how many tappers.
 * The per-broadcast total is persisted by the end-live path for the future
 * points system.
 */
class LiveTapsController extends Controller
{
    private const TOTAL_TTL = 6 * 3600; // safety TTL; cleared at end-live

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_id' => 'required|integer',
            'count' => 'required|integer|min:1|max:1000',
        ]);

        $user = $request->user();
        $room = Room::query()->find($data['room_id']);
        if (!$room || !$room->is_live) {
            return Common::apiResponse(false, 'Live not found', null, 404);
        }

        $verdict = ActionAbuseGuard::register(
            (int) $user->id,
            'live_taps',
            (int) $data['count'],
            5,
        );

        $total = null;
        if ($verdict['credited'] > 0) {
            $key = self::totalKey($room->id);
            $total = (int) Redis::incrby($key, $verdict['credited']);
            Redis::expire($key, self::TOTAL_TTL);
            $this->broadcastTotalThrottled($room, $total);
        }

        return Common::apiResponse(true, '', [
            'total' => $total ?? (int) Redis::get(self::totalKey($room->id)),
            'credited' => $verdict['credited'],
            'warning' => $verdict['warning'],
            'blocked_for' => $verdict['blocked_for'],
        ]);
    }

    public static function totalKey(int|string $roomId): string
    {
        return "live:taps:{$roomId}";
    }

    /**
     * Push the official total to the room via UTD Stream, at most once per
     * second per room (Redis NX gate). Triggered by traffic, so idle rooms
     * cost nothing and busy rooms still fan out exactly one message/second.
     */
    private function broadcastTotalThrottled(Room $room, int $total): void
    {
        if (!Redis::set("live:taps:bc:{$room->id}", 1, 'EX', 1, 'NX')) {
            return;
        }

        try {
            Common::sendToStream('SendCustomCommand', $room->id, 0, json_encode([
                'messageContent' => [
                    'message' => 'live_taps_total',
                    'total' => $total,
                ],
            ]));
        } catch (\Throwable $e) {
            // Best-effort: the next batch re-broadcasts.
        }
    }
}

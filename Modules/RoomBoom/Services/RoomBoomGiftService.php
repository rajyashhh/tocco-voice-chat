<?php

namespace Modules\RoomBoom\Services;

use App\Helpers\Common;
use App\Models\GiftLog;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\RoomBoom\Entities\RoomBoom;
use Modules\RoomBoom\Entities\RoomBoomLevel;
use Modules\RoomBoom\Entities\TotalRoomGift;
use Modules\RoomBoom\Jobs\EndBoomPusherJob;
use Modules\RoomBoom\Jobs\RoomBoomRewardJob;

class RoomBoomGiftService
{
    /**
     * @throws \Throwable
     */
    public function sendGift($room, $totalPrice, $roomBoomUuid): void
    {
        DB::transaction(function () use ($room, $totalPrice, $roomBoomUuid) {
            $roomId = $room->id;
            $roomUid = $room->uid;
            $tz = getTimezone();
            $todayStart = Carbon::now($tz)->startOfDay()->copy()->setTimezone('UTC');

            $totalRoomGift = $this->getOrCreateTotalRoomGift($roomId, $todayStart, $totalPrice);

            if ($totalRoomGift) {
                $currentTotal = $totalRoomGift->current_total;
                $newTotal = $currentTotal + $totalPrice;
            } else {
                $newTotal = $currentTotal = $totalPrice;
            }

            $currentLevel = RoomBoomLevel::where('min_target', '<=', $newTotal)
                ->where('target', '>=', $newTotal)
                ->orderBy('level')
                ->first();

            $levelsToActivate = RoomBoomLevel::where('min_target', '<=', $newTotal)
                ->where('target', '<=', $newTotal)
                ->orderBy('level', 'asc')
                ->get();

            foreach ($levelsToActivate as $level){
                $existingNotActiveBoom = RoomBoom::where('room_boom_level_id', $level->id)
                    ->where('total_room_gift_id', $totalRoomGift->id)
                    ->first();

                if (!$existingNotActiveBoom) {
                    $giftLogId = GiftLog::where('room_boom_uuid', $roomBoomUuid)->orderByDesc('id')->latest()->value('id');
                    try {
                        RoomBoom::create([
                            'total_room_gift_id' => $totalRoomGift->id,
                            'room_boom_level_id' => $level->id,
                            'started_at' => Carbon::now(),
                            'total_gifts_value' => $newTotal,
                            'trigger_gift_id' => $giftLogId
                        ]);
                    } catch (QueryException $e) {
                        if ($e->errorInfo[1] == 1452) {
                            logger()->warning('RoomBoom FK error: room_boom_level_id not found', ['level_id' => $level->id]);
                            continue;
                        }
                        if ($e->errorInfo[1] != 1062) {
                            throw $e;
                        }
                    }
                }
            }

            if ($currentLevel) {
                $existingBoom = RoomBoom::where('room_boom_level_id', $currentLevel->id)
                    ->where('total_room_gift_id', $totalRoomGift->id)
                    ->lockForUpdate()
                    ->first();

                if (!$existingBoom) {
                    $giftLogId = GiftLog::where('room_boom_uuid', $roomBoomUuid)->orderByDesc('id')->value('id');

                    try {
                        $existingBoom = RoomBoom::create([
                            'total_room_gift_id' => $totalRoomGift->id,
                            'room_boom_level_id' => $currentLevel->id,
                            'started_at' => Carbon::now(),
                            'total_gifts_value' => $newTotal,
                            'trigger_gift_id' => $giftLogId
                        ]);
                    } catch (QueryException $e) {
                        if ($e->errorInfo[1] == 1452) {
                            logger()->warning('RoomBoom FK error: room_boom_level_id not found', ['level_id' => $currentLevel->id]);
                            return;
                        }
                        if ($e->errorInfo[1] == 1062) {
                            $existingBoom = RoomBoom::where('total_room_gift_id', $totalRoomGift->id)
                                ->where('room_boom_level_id', $currentLevel->id)
                                ->first();
                        } else {
                            throw $e;
                        }
                    }

                    $d = [
                        "messageContent" => [
                            "message" => "roomBoomStarted",
                            'roomBoomLevel' => $currentLevel->id,
                        ]
                    ];
                    $json = json_encode($d);

                    info('next level stream');
                    Common::sendToStream('SendCustomCommand', $roomId, $roomUid, $json);
                }

                if ($newTotal >= $currentLevel->min_target) {
                    $startBoomRanking = 1;
                } else {
                    $startBoomRanking = 0;
                }

                GiftLog::where('room_boom_uuid', $roomBoomUuid)->update([
                    'room_boom_level' => $currentLevel->level,
                    'start_boom_ranking' => $startBoomRanking
                ]);
                $existingBoom->total_gifts_value = $newTotal;
                $existingBoom->save();
            } else {
                $nextLevel = RoomBoomLevel::where('min_target', '>', $newTotal)
                    ->orderBy('min_target', 'asc')
                    ->first();

                if ($nextLevel) {
                    GiftLog::where('room_boom_uuid', $roomBoomUuid)->update([
                        'room_boom_level' => $nextLevel->level,
                        'start_boom_ranking' => 0
                    ]);
                }
            }

            $totalRoomGift->current_total = $newTotal;

            $this->checkAndEndBoom($totalRoomGift, $roomBoomUuid, $newTotal, $room);

            $totalRoomGift->save();
        });
    }

    private function getTimezone(): string
    {
        $tz = request()->header('tz', Common::timeZone());
        return in_array($tz, timezone_identifiers_list()) ? $tz : 'UTC';
    }

    private function getOrCreateTotalRoomGift($roomId, $todayStart, $totalPrice){
        $totalRoomGift = TotalRoomGift::where('room_id', $roomId)
            ->where('created_at', '>=', $todayStart)
            ->lockForUpdate()
            ->first();

        if (!$totalRoomGift) {
            $totalRoomGift = TotalRoomGift::create([
                'room_id' => $roomId,
                'current_total' => 0,
            ]);
        }

        return $totalRoomGift;
    }

    private function checkAndEndBoom($totalRoomGift, $roomBoomUuid, $newTotal, $room): void
    {
        $openBooms = RoomBoom::where('total_room_gift_id', $totalRoomGift->id)
            ->whereNull('ended_at')
            ->latest()
            ->get();

        foreach ($openBooms as $openBoom){
            $boomLevel = RoomBoomLevel::find($openBoom->room_boom_level_id);
            $giftLog = GiftLog::where('room_boom_uuid', $roomBoomUuid)->orderByDesc('id')->first(['id', 'sender_id']);

            if ($boomLevel && $newTotal >= $boomLevel->target) {
                $openBoom->ended_at = Carbon::now();
                $openBoom->total_gifts_value = $newTotal;
                $openBoom->final_gift_id = $giftLog->id;
                $openBoom->save();

                dispatch(new RoomBoomRewardJob($openBoom->id))->delay(now()->addSeconds(30));
                dispatch(new EndBoomPusherJob($boomLevel, $newTotal, auth()->id(), $room));

                $this->mayStartNextBoom($totalRoomGift, $giftLog, $newTotal, $room, $boomLevel);
            }
        }
    }

    private function mayStartNextBoom($totalRoomGift, $giftLog, $newTotal, $room, $boomLevel): void
    {
        if ($newTotal == $boomLevel->target) {
            $nextLevel = RoomBoomLevel::where('min_target', $newTotal)->first();

            if ($nextLevel) {
                try {
                    RoomBoom::firstOrCreate([
                        'total_room_gift_id' => $totalRoomGift->id,
                        'room_boom_level_id' => $nextLevel->id,
                    ], [
                        'started_at'        => now(),
                        'total_gifts_value' => $newTotal,
                        'trigger_gift_id'   => $giftLog->id
                    ]);
                } catch (QueryException $e) {
                    if ($e->errorInfo[1] == 1452) {
                        logger()->warning('RoomBoom FK error: room_boom_level_id not found', ['level_id' => $nextLevel->id]);
                        return;
                    }
                    throw $e;
                }

                $d = [
                    "messageContent" => [
                        "message" => "roomBoomStarted",
                        "roomBoomLevel" => $nextLevel->id,
                    ]
                ];

                Common::sendToStream('SendCustomCommand', $room->id, $room->uid, json_encode($d));
            }
        }
    }

}

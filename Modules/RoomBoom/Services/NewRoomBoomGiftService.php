<?php

namespace Modules\RoomBoom\Services;

use App\Helpers\Common;
use App\Http\Services\RoomService;
use App\Models\GiftLog;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\RoomBoom\Entities\RoomBoom;
use Modules\RoomBoom\Entities\RoomBoomGift;
use Modules\RoomBoom\Entities\RoomBoomLevel;
use Modules\RoomBoom\Entities\TotalRoomGift;
use Modules\RoomBoom\Jobs\EndBoomPusherJob;
use Modules\RoomBoom\Jobs\NewRoomBoomRewardJob;
use Modules\RoomBoom\Jobs\RoomBoomRewardJob;

class NewRoomBoomGiftService
{
    /**
     * @throws \Throwable
     */
    public function sendGift($room, $totalPrice, $userId): void
    {
        DB::transaction(function () use ($room, $totalPrice, $userId) {
            $roomId = $room->id;
            $roomUid = $room->uid;
            $tz = getTimezone();
            $todayStart = Carbon::now($tz)->startOfDay()->copy()->setTimezone('UTC');

            $totalRoomGift = (new RoomService())->getOrCreateTotalRoomGift($room->id, $todayStart);

            if ($totalRoomGift) {
                $currentTotal = $totalRoomGift->current_total;
                $newTotal = $currentTotal + $totalPrice;
            } else {
                $newTotal = $currentTotal = $totalPrice;
            }

            $this->oldLevels($totalPrice, $totalRoomGift->id, $userId, $currentTotal);

            $this->activateLevels($totalRoomGift, $newTotal);

            $this->handleCurrentLevel($totalRoomGift, $newTotal, $roomId, $roomUid);

            $totalRoomGift->current_total = $newTotal;

            $this->checkAndEndBoom($totalRoomGift, $userId, $newTotal, $room);

            $totalRoomGift->save();
        });
    }

    private function checkAndEndBoom($totalRoomGift, $userId, $newTotal, $room): void
    {
        $openBooms = RoomBoom::where('total_room_gift_id', $totalRoomGift->id)
            ->whereNull('ended_at')
            ->latest()
            ->get();

        foreach ($openBooms as $openBoom) {
            $boomLevel = RoomBoomLevel::find($openBoom->room_boom_level_id);

            if ($boomLevel && $newTotal >= $boomLevel->target) {
                $openBoom->ended_at = Carbon::now();
                $openBoom->total_gifts_value = $newTotal;
                $openBoom->save();

                dispatch(new NewRoomBoomRewardJob($openBoom->id, $userId))->delay(now()->addSeconds(30));
                dispatch(new EndBoomPusherJob($boomLevel, $newTotal, auth()->id(), $room));

                $this->mayStartNextBoom($totalRoomGift, $newTotal, $room, $boomLevel, $userId);
            }
        }
    }

    private function mayStartNextBoom($totalRoomGift, $newTotal, $room, $boomLevel, $userId): void
    {
        if ($newTotal == $boomLevel->target) {
            $nextLevel = RoomBoomLevel::where('min_target', $newTotal)->first();

            if ($nextLevel) {
                try {
                    RoomBoom::firstOrCreate([
                        'total_room_gift_id' => $totalRoomGift->id,
                        'room_boom_level_id' => $nextLevel->id,
                    ], [
                        'started_at' => now(),
                        'total_gifts_value' => $newTotal,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1452) {
                        logger()->warning('RoomBoom FK error: room_boom_level_id not found', ['level_id' => $nextLevel->id]);
                        return;
                    }
                    throw $e;
                }

                \Illuminate\Support\Facades\DB::afterCommit(function () use ($room, $nextLevel) {
                    $d = [
                        "messageContent" => [
                            "message" => "roomBoomStarted",
                            "roomBoomLevel" => $nextLevel->id,
                        ]
                    ];
                    Common::sendToStream('SendCustomCommand', $room->id, $room->uid, json_encode($d));
                });
            }
        }
    }

    public function oldLevels($totalPrice, $totalRoomGiftId, $userId, &$currentTotal): void
    {
        $remaining = $totalPrice;
        $levels = \Illuminate\Support\Facades\Cache::remember('room_boom_levels', 3600, function () {
            return RoomBoomLevel::orderBy('level', 'asc')->get();
        });

        $giftsToInsert = [];
        $now = Carbon::now();

        foreach ($levels as $level) {
            if ($remaining <= 0)
                break;

            if ($currentTotal >= $level->target) {
                continue;
            }

            $neededForLevel = $level->target - $currentTotal;
            $levelAmount = min($remaining, $neededForLevel);
            $neededToMin = max(0, $level->min_target - $currentTotal);

            if ($currentTotal < $level->min_target) {
                if ($levelAmount >= $neededToMin && $neededToMin > 0) {
                    $giftsToInsert[] = [
                        'total_room_gift_id' => $totalRoomGiftId,
                        'user_id' => $userId,
                        'price' => $neededToMin,
                        'room_boom_level' => $level->level,
                        'start_boom_ranking' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $remainingPart = $levelAmount - $neededToMin;
                    if ($remainingPart > 0) {
                        $giftsToInsert[] = [
                            'total_room_gift_id' => $totalRoomGiftId,
                            'user_id' => $userId,
                            'price' => $remainingPart,
                            'room_boom_level' => $level->level,
                            'start_boom_ranking' => 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                } else {
                    $giftsToInsert[] = [
                        'total_room_gift_id' => $totalRoomGiftId,
                        'user_id' => $userId,
                        'price' => $levelAmount,
                        'room_boom_level' => $level->level,
                        'start_boom_ranking' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            } else {
                $giftsToInsert[] = [
                    'total_room_gift_id' => $totalRoomGiftId,
                    'user_id' => $userId,
                    'price' => $levelAmount,
                    'room_boom_level' => $level->level,
                    'start_boom_ranking' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $currentTotal += $levelAmount;
            $remaining -= $levelAmount;
        }

        if (!empty($giftsToInsert)) {
            RoomBoomGift::insert($giftsToInsert);
        }
    }

    private function activateLevels($totalRoomGift, $newTotal): void
    {
        $levelsToActivate = \Illuminate\Support\Facades\Cache::remember('room_boom_levels', 3600, function () {
            return RoomBoomLevel::orderBy('level', 'asc')->get();
        })->where('min_target', '<=', $newTotal)->where('target', '>=', $newTotal);

        if ($levelsToActivate->isEmpty())
            return;

        $existingLevelIds = RoomBoom::where('total_room_gift_id', $totalRoomGift->id)
            ->whereIn('room_boom_level_id', $levelsToActivate->pluck('id'))
            ->pluck('room_boom_level_id')
            ->toArray();

        $boomsToInsert = [];
        $now = Carbon::now();

        foreach ($levelsToActivate as $level) {
            if (!in_array($level->id, $existingLevelIds)) {
                $boomsToInsert[] = [
                    'total_room_gift_id' => $totalRoomGift->id,
                    'room_boom_level_id' => $level->id,
                    'started_at' => $now,
                    'total_gifts_value' => $newTotal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($boomsToInsert)) {
            try {
                RoomBoom::insert($boomsToInsert);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->errorInfo[1] == 1452) {
                    logger()->warning('RoomBoom FK error: room_boom_level_id not found', ['ids' => array_column($boomsToInsert, 'room_boom_level_id')]);
                    return;
                }
                throw $e;
            }
        }
    }

    private function handleCurrentLevel($totalRoomGift, $newTotal, $roomId, $roomUid): void
    {
        $currentLevel = \Illuminate\Support\Facades\Cache::remember('room_boom_levels', 3600, function () {
            return RoomBoomLevel::orderBy('level', 'asc')->get();
        })->where('min_target', '<=', $newTotal)->where('target', '>=', $newTotal)->first();

        if (!$currentLevel)
            return;

        $existingBoom = RoomBoom::where('room_boom_level_id', $currentLevel->id)
            ->where('total_room_gift_id', $totalRoomGift->id)
            ->first(); // Avoided lockForUpdate to prevent deadlocks on high concurrency

        if (!$existingBoom) {
            $this->safeCreateBoom($totalRoomGift->id, $currentLevel->id, $newTotal);

            // Defer the stream HTTP request to run after the DB transaction ends
            DB::afterCommit(function () use ($roomId, $roomUid, $currentLevel) {
                $d = [
                    "messageContent" => [
                        "message" => "roomBoomStarted",
                        'roomBoomLevel' => $currentLevel->id,
                    ]
                ];
                Common::sendToStream('SendCustomCommand', $roomId, $roomUid, json_encode($d));
            });
        } elseif ($existingBoom->total_gifts_value != $newTotal) {
            $existingBoom->total_gifts_value = $newTotal;
            $existingBoom->save();
        }
    }

    private function safeCreateBoom($totalRoomGiftId, $levelId, $newTotal)
    {
        try {
            return RoomBoom::firstOrCreate(
                [
                    'total_room_gift_id' => $totalRoomGiftId,
                    'room_boom_level_id' => $levelId,
                ],
                [
                    'started_at' => Carbon::now(),
                    'total_gifts_value' => $newTotal,
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1452) {
                logger()->warning('RoomBoom FK error: room_boom_level_id not found', ['level_id' => $levelId]);
                return null;
            }
            throw $e;
        }
    }

}

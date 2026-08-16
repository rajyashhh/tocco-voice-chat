<?php

namespace Modules\RoomBoom\Services;

use App\Helpers\Common;
use Cache;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Modules\RoomBoom\Entities\RoomBoom;
use Modules\RoomBoom\Entities\RoomBoomLevel;
use Modules\RoomBoom\Entities\TotalRoomGift;
use Modules\RoomBoom\Jobs\EndBoomPusherJob;
use Modules\RoomBoom\Jobs\RoomBoomRewardJob;
use Throwable;

class BoomGiftService
{
    public function getAllLevels(): Collection
    {
        return Cache::rememberForever('room_boo_levels', fn () => RoomBoomLevel::orderBy('target')->all());
    }

    /**
     * @return int $level
     *
     * @throws Throwable
     */
    public function sendGift(int $roomId, int $roomUid, $totalPrice, $roomBoomUuid): ?int
    {
        $tz = getTimezone();
        $todayStart = Carbon::now($tz)->startOfDay();

        $roomDScore = $this->incrementTodayRoomGift($roomId, $todayStart, $totalPrice);

        $currentLevel = $this->getCurrentLevel($roomDScore);

        if (! $currentLevel) {

            return null;
        }

        //        if ()

        $currentId = $currentLevel->id;
        $existingBoom = RoomBoom::where('room_boom_level_id', $currentId)
            ->whereDate('created_at', Carbon::now($tz)->toDate())
            ->where('total_room_gift_id', $roomId)
            ->first();

        if ($existingBoom) {
            return $currentLevel->id;
        }

        $achievedLevelsIds = RoomBoom::query()->where('total_room_gift_id', $roomId)
            ->whereDate('created_at', Carbon::now($tz)->toDate())
            ->pluck('room_boom_level_id')->toArray();

        $prevIds = [];
        if ($currentLevel->min_target < $roomDScore) {
            $prevIds = $this->getPreviousLevelIds($currentId);

            $idsDiff = array_diff($prevIds, $achievedLevelsIds);

            $this->insertNewRoomLevel($currentId, $roomId, $roomDScore, idsDiff: $idsDiff);

            $d = [
                'messageContent' => [
                    'message' => 'roomBoomStarted',
                    'roomBoomLevel' => $currentId,
                ],
            ];
            $json = json_encode($d);
            Common::sendToStream('SendCustomCommand', $roomId, $roomUid, $json);
        }

        if (count($prevIds) !== 0 || ($currentLevel->target === $roomDScore)) {
            $targetLevel = ($currentLevel->target === $roomDScore) ? $currentId : $prevIds[count($prevIds) - 1];
            $openBoom = RoomBoom::query()->where('total_room_gift_id', $roomId)
                ->whereDate('created_at', Carbon::now($tz)->toDate())
                ->where('room_boom_level_id', $targetLevel)
                ->first();

            if (! $openBoom->ended_at) {
                dispatch(new RoomBoomRewardJob($openBoom->id))->delay(now()->addSeconds(30));
                dispatch(new EndBoomPusherJob($currentLevel, $roomDScore, auth()->id(), $roomId));
                // Close previous
                RoomBoom::query()->where('total_room_gift_id', $roomId)
                    ->whereDate('created_at', Carbon::now($tz)->toDate())
                    ->whereNull('ended_at')
                    ->when($targetLevel !== $currentId, fn ($q) => $q->where('room_boom_level_id', '!=', $currentId))
                    ->update(['ended_at' => now()]);
            }
        }

        return $currentId;

    }

    public function getPreviousLevelIds(int $id): array
    {
        $levels = $this->getAllLevels();

        $index = $levels->search(fn ($level) => $level->id === $id);

        if ($index === false) {
            return []; // if not found, return empty
        }

        return $levels->take($index)->pluck('id')->toArray();
    }

    public function getCurrentLevel(mixed $newTotal): ?RoomBoomLevel
    {
        return $this->getAllLevels()
            ->filter(fn ($level) => $level->target > $newTotal)
            ->sortByDesc('target')
            ->first();
    }

    public function getNextLevel(mixed $newTotal): ?RoomBoomLevel
    {
        return $this->getAllLevels()
            ->filter(fn ($level) => $level->target > $newTotal)
            ->sortBy('target')
            ->first();
    }

    public function getFirsLevel(): ?RoomBoomLevel
    {
        return RoomBoomLevel::orderBy('min_target')->first();
    }

    /**
     * @param  RoomBoomLevel  $currentLevel
     * @param  array  $achievedLevelsIds
     */
    public function insertNewRoomLevel(int $currentId, int $roomId, mixed $roomDScore, array $idsDiff = []): void
    {

        foreach ($idsDiff as $id) {
            $attributes[] = [
                'total_room_gift_id' => $roomId,
                'room_boom_level_id' => $id,
                'started_at' => Carbon::now(),
                'total_gifts_value' => $roomDScore,
                'trigger_gift_id' => 0,
            ];
        }
        $attributes[] = [
            'total_room_gift_id' => $roomId,
            'room_boom_level_id' => $currentId,
            'started_at' => Carbon::now(),
            'total_gifts_value' => $roomDScore,
            'trigger_gift_id' => 0,
        ];

        try {
            RoomBoom::upsert(
                $attributes,
                ['total_room_gift_id', 'room_boom_level_id'],
                ['started_at', 'ended_at', 'total_gifts_value', 'trigger_gift_id']
            );
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1452) {
                logger()->warning('RoomBoom FK error: room_boom_level_id not found', ['ids' => array_column($attributes, 'room_boom_level_id')]);
                return;
            }
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    private function incrementTodayRoomGift($roomId, $todayStart, $totalPrice)
    {
        return DB::transaction(function () use ($roomId, $todayStart, $totalPrice) {
            $totalRoomGift = TotalRoomGift::where('room_id', $roomId)
                ->where('created_at', '>=', $todayStart)
                ->lockForUpdate()
                ->first();

            if (! $totalRoomGift) {
                $totalRoomGift = TotalRoomGift::create([
                    'room_id' => $roomId,
                    'current_total' => $totalPrice,
                ]);
            } else {
                $totalRoomGift->increment('current_total', $totalPrice);
                $totalRoomGift->refresh(); 
            }

            return $totalRoomGift->current_total;
        });
    }
}

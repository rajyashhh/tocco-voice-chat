<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Server-authoritative per-room charisma store.
 *
 * One Redis hash per room (`room:{roomId}:charisma`), field = receiver id,
 * value = cumulative charisma stored in HUNDREDTHS of a coin (× 100, exact
 * integer) so sub-coin shares — e.g. a lucky receiver cut of 0.50 — accumulate
 * losslessly via HINCRBY without float drift. Readers floor to whole coins for
 * display (matching the legacy client display floor).
 *
 * The backend is the SOLE source of truth: every credited path (normal +
 * lucky) HINCRBYs here, the in-room gift frame and enter-room payload ship the
 * resulting TOTAL (not the increment), and the client only renders. Reset on a
 * charisma toggle DELs the hash. Lives on the durable `fairluck` AOF Redis the
 * engine already uses, so a restart never loses live room badges.
 */
class RoomCharismaStore
{
    /** Hash kept warm while a room is active; refreshed on every credit. */
    private const TTL_SECONDS = 86400; // 24h safety net (reset is the real lifecycle)

    private static function redis()
    {
        return Redis::connection('fairluck');
    }

    private static function key(int $roomId): string
    {
        return "room:{$roomId}:charisma";
    }

    /**
     * Credit each receiver by their received value (in whole coins). Stored in
     * hundredths so a decimal increment (lucky receiver cut) accumulates exactly.
     *
     * @param array<int|string, int|float> $byReceiver receiverId => coins credited
     * @return array<int, int> receiverId => NEW floored coin total (post-credit)
     */
    public static function increment(int $roomId, array $byReceiver): array
    {
        $clean = [];
        foreach ($byReceiver as $rid => $coins) {
            $rid = (int) $rid;
            $hund = (int) round(((float) $coins) * 100);
            if ($rid > 0 && $hund > 0) {
                $clean[$rid] = $hund;
            }
        }
        if (empty($clean)) {
            return [];
        }

        $key = self::key($roomId);
        try {
            $redis = self::redis();
            $newValues = $redis->pipeline(function ($pipe) use ($key, $clean) {
                foreach ($clean as $rid => $hund) {
                    $pipe->hincrby($key, (string) $rid, $hund);
                }
                $pipe->expire($key, self::TTL_SECONDS);
            });

            // pipeline returns results in issue order: one per HINCRBY, then EXPIRE.
            $totals = [];
            $i = 0;
            foreach ($clean as $rid => $hund) {
                $hundTotal = (int) ($newValues[$i] ?? 0);
                $totals[$rid] = intdiv($hundTotal, 100);
                $i++;
            }
            return $totals;
        } catch (\Throwable $e) {
            Log::warning('RoomCharismaStore increment failed', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Floored whole-coin charisma totals for the given user ids in a room.
     *
     * @param int[] $userIds
     * @return array<int, int> userId => floored coin total (only credited users)
     */
    public static function totals(int $roomId, array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (empty($userIds)) {
            return [];
        }
        try {
            $raw = self::redis()->hmget(self::key($roomId), array_map('strval', $userIds));
        } catch (\Throwable $e) {
            Log::warning('RoomCharismaStore totals failed', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        $out = [];
        foreach ($userIds as $i => $uid) {
            $hund = $raw[$i] ?? null;
            if ($hund !== null && $hund !== false) {
                $out[$uid] = intdiv((int) $hund, 100);
            }
        }
        return $out;
    }

    /** Whole-coin charisma total for a single user (0 if none). */
    public static function total(int $roomId, int $userId): int
    {
        return self::totals($roomId, [$userId])[$userId] ?? 0;
    }

    /**
     * Current charisma holder ids in a room (the hash field ids). Used by the
     * explicit owner/admin reset to know which receivers to zero on the clients.
     *
     * @return int[] receiver ids that currently hold charisma
     */
    public static function userIds(int $roomId): array
    {
        try {
            $fields = self::redis()->hkeys(self::key($roomId));
        } catch (\Throwable $e) {
            Log::warning('RoomCharismaStore userIds failed', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
        return array_values(array_filter(array_map('intval', $fields ?? [])));
    }

    /** Clear the whole room's charisma (charisma toggled on/off). */
    public static function reset(int $roomId): void
    {
        try {
            self::redis()->del(self::key($roomId));
        } catch (\Throwable $e) {
            Log::warning('RoomCharismaStore reset failed', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

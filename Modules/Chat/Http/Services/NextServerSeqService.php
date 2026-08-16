<?php

namespace Modules\Chat\Http\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Atomic, gap-free per-room sequence generator for chat messages.
 *
 * Each message carries a strictly increasing `server_seq` scoped to its room.
 * This value drives Centrifugo recovery/offset, client-side ordering and the
 * O(1) unread count (chat_rooms.last_seq - last_read_seq).
 *
 * Two strategies behind config('chat.seq.driver'):
 *  - "db"    : atomic UPDATE then read, inside one short transaction. NEVER uses
 *              SELECT ... FOR UPDATE (caused 504s under load in the FairLuck
 *              vault-lock incident). chat_rooms.last_seq is the source of truth.
 *  - "redis" : per-room INCR/INCRBY (cheaper under bursts), with the result
 *              mirrored into chat_rooms.last_seq so the DB stays canonical for
 *              backfill, recovery and any later strategy switch.
 *
 * All allocations are usable from bulk inserts via reserve(): it hands out a
 * contiguous block of seq values in a single atomic step.
 */
class NextServerSeqService
{
    /**
     * Allocate the next sequence value for a room.
     */
    public function next(int $roomId): int
    {
        $range = $this->reserve($roomId, 1);

        return $range['from'];
    }

    /**
     * Atomically reserve a contiguous block of `$count` sequence values.
     *
     * Returns ['from' => int, 'to' => int] (inclusive). For a bulk insert,
     * assign $from, $from+1, ... $to in row order. Returns the same shape with
     * from > to semantics avoided: $count is clamped to a minimum of 1.
     */
    public function reserve(int $roomId, int $count): array
    {
        $count = max(1, $count);

        return $this->driver() === 'redis'
            ? $this->reserveViaRedis($roomId, $count)
            : $this->reserveViaDatabase($roomId, $count);
    }

    /**
     * Atomic DB allocation: increment then read inside one transaction.
     * No row lock held across a read — the UPDATE itself is the atomic step.
     */
    private function reserveViaDatabase(int $roomId, int $count): array
    {
        return DB::transaction(function () use ($roomId, $count) {
            DB::update(
                'UPDATE chat_rooms SET last_seq = last_seq + ?, updated_at = updated_at WHERE id = ?',
                [$count, $roomId]
            );

            $to = (int) DB::table('chat_rooms')->where('id', $roomId)->value('last_seq');

            return ['from' => $to - $count + 1, 'to' => $to];
        });
    }

    /**
     * Redis INCRBY allocation. The returned ceiling is mirrored into
     * chat_rooms.last_seq so the DB remains the canonical counter.
     */
    private function reserveViaRedis(int $roomId, int $count): array
    {
        $to = (int) Redis::connection($this->redisConnection())
            ->incrby($this->redisKey($roomId), $count);

        // Keep the DB counter monotonic with Redis without ever moving it
        // backwards (guards against a stale/just-warmed Redis key).
        DB::update(
            'UPDATE chat_rooms SET last_seq = GREATEST(last_seq, ?), updated_at = updated_at WHERE id = ?',
            [$to, $roomId]
        );

        return ['from' => $to - $count + 1, 'to' => $to];
    }

    private function driver(): string
    {
        return config('chat.seq.driver', 'db');
    }

    private function redisConnection(): string
    {
        return config('chat.seq.redis_connection', 'default');
    }

    /**
     * Application-level key. The Redis facade prepends the global
     * options.prefix ([REMOVED]_database_) automatically.
     */
    private function redisKey(int $roomId): string
    {
        return config('chat.seq.redis_key_prefix', 'chat:room:') . $roomId . ':seq';
    }
}

<?php

namespace App\Repositories;

use App\Models\RoomVisitor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoomVisitorRepository
{
    /**
     * Add a visitor to a room atomically.
     *
     * Uses insertOrIgnore against the UNIQUE(room_id, user_id) constraint
     * (uq_room_visitors_room_user) instead of firstOrCreate inside a
     * transaction. This is a single atomic INSERT keyed on one deterministic
     * index — no SELECT-then-INSERT, no gap/insert-intention locks, and no
     * wrapping transaction — which eliminates the InnoDB deadlock cycle on
     * concurrent join for the same room.
     *
     * @param int $roomId
     * @param int $userId
     * @return bool
     */
    public function addVisitor(int $roomId, int $userId): bool
    {
        $now = now();

        // insertOrIgnore is a single atomic INSERT, but under very high concurrent
        // join/leave churn on the same hot room two index inserts can still form an
        // insert-intention lock cycle (InnoDB 1213). A bounded retry here makes the
        // operation resilient for EVERY caller, not only the ones wrapped upstream.
        $this->withDeadlockRetry(function () use ($roomId, $userId, $now) {
            DB::table('room_visitors')->insertOrIgnore([
                'room_id' => $roomId,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        return true;
    }

    /**
     * Remove a visitor from a room.
     *
     * The authoritative visitor list is the room_visitors table; the legacy
     * rooms.room_visitor column is no longer maintained on join (see addVisitor),
     * so we no longer write it here. Removing the rooms-row write also removes the
     * exclusive lock on the hot rooms row that was causing DELETE deadlocks under
     * concurrent join/leave on popular rooms.
     *
     * @param int $roomId
     * @param int $userId
     * @return bool
     */
    public function removeVisitor(int $roomId, int $userId): bool
    {
        $this->withDeadlockRetry(function () use ($roomId, $userId) {
            RoomVisitor::where('room_id', $roomId)
                ->where('user_id', $userId)
                ->delete();
        });

        return true;
    }

    /**
     * Run a single-statement visitor write, retrying on InnoDB deadlock /
     * lock-wait (1213/1205) with exponential backoff (100/200/400ms). Keeps the
     * join/leave path resilient under hot-room contention regardless of caller.
     */
    private function withDeadlockRetry(callable $operation, int $maxRetries = 3)
    {
        $attempt = 0;
        while (true) {
            try {
                return $operation();
            } catch (QueryException $e) {
                $errno = $e->errorInfo[1] ?? null;
                $isLockError = $errno === 1213 || $errno === 1205
                    || str_contains($e->getMessage(), 'Deadlock')
                    || str_contains($e->getMessage(), 'Lock wait timeout');

                if (! $isLockError || ++$attempt >= $maxRetries) {
                    throw $e;
                }

                usleep(100000 * (2 ** ($attempt - 1))); // 100ms, 200ms, 400ms
            }
        }
    }

    /**
     * Check if a user is a visitor in a room
     *
     * @param int $roomId
     * @param int $userId
     * @return bool
     */
    public function isVisitor(int $roomId, int $userId): bool
    {
        return RoomVisitor::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get all visitor IDs for a room
     *
     * @param int $roomId
     * @return Collection
     */
    public function getVisitorIds(int $roomId): Collection
    {
        return RoomVisitor::where('room_id', $roomId)
            ->pluck('user_id');
    }

    /**
     * Get visitor count for a room
     *
     * @param int $roomId
     * @return int
     */
    public function getVisitorCount(int $roomId): int
    {
        return RoomVisitor::where('room_id', $roomId)->count();
    }

    /**
     * Get visitors with user details
     *
     * @param int $roomId
     * @return Collection
     */
    public function getVisitorsWithDetails(int $roomId): Collection
    {
        return RoomVisitor::where('room_id', $roomId)
            ->with('user:id,name,avatar')
            ->get();
    }

    /**
     * Clear all visitors from a room (for room reset)
     *
     * @param int $roomId
     * @return bool
     */
    public function clearAllVisitors(int $roomId): bool
    {
        RoomVisitor::where('room_id', $roomId)->delete();

        return true;
    }

    /**
     * Sync visitors based on event (login/logout)
     * This is the main method used by webhooks
     *
     * @param int $roomId
     * @param int $userId
     * @param string $event 'room_login' or 'room_logout'
     * @return bool
     */
    public function syncVisitorByEvent(int $roomId, int $userId, string $event): bool
    {
        if ($event === 'room_login') {
            return $this->addVisitor($roomId, $userId);
        } elseif ($event === 'room_logout') {
            return $this->removeVisitor($roomId, $userId);
        }

        return false;
    }

    /**
     * Get recently joined visitors (last N minutes)
     *
     * @param int $roomId
     * @param int $minutes
     * @return Collection
     */
    public function getRecentVisitors(int $roomId, int $minutes = 5): Collection
    {
        return RoomVisitor::where('room_id', $roomId)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->with('user:id,name,avatar')
            ->get();
    }
}

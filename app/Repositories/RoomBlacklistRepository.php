<?php

namespace App\Repositories;

use App\Models\Room;
use App\Models\RoomBlacklist;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoomBlacklistRepository
{
    /**
     * Check if a user is currently blacklisted in a room
     *
     * @param int $roomId
     * @param int $userId
     * @return bool
     */
    public function isBlacklisted(int $roomId, int $userId): bool
    {
        return RoomBlacklist::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->valid()
            ->exists();
    }

    /**
     * Add a ban with dual-write to both new table and legacy column
     *
     * @param int $roomId
     * @param int $userId
     * @param int|null $bannedBy
     * @param int|null $durationSeconds
     * @param string|null $reason
     * @return bool
     */
    public function addBan(
        int $roomId,
        int $userId,
        ?int $bannedBy = null,
        ?int $durationSeconds = null,
        ?string $reason = null
    ): bool {
        return DB::transaction(function () use ($roomId, $userId, $bannedBy, $durationSeconds, $reason) {
            $bannedAt = now();
            $expiresAt = $durationSeconds ? $bannedAt->copy()->addSeconds($durationSeconds) : null;

            // First, deactivate any existing active bans for this user in this room
            RoomBlacklist::where('room_id', $roomId)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create new ban entry in new table
            RoomBlacklist::create([
                'room_id' => $roomId,
                'user_id' => $userId,
                'banned_by' => $bannedBy,
                'banned_at' => $bannedAt,
                'duration_seconds' => $durationSeconds,
                'expires_at' => $expiresAt,
                'is_active' => true,
                'reason' => $reason
            ]);

            // Dual-write: update legacy column
            $room = Room::find($roomId);
            if ($room) {
                $blacklist = array_filter(explode(',', $room->room_black ?? ''));

                // Remove any existing entries for this user
                $blacklist = array_filter($blacklist, function($entry) use ($userId) {
                    $parts = explode('#', $entry);
                    return $parts[0] != $userId;
                });

                // Add new entry in legacy format: user_id#timestamp#duration
                $entry = $userId . '#' . $bannedAt->timestamp;
                if ($durationSeconds) {
                    $entry .= '#' . $durationSeconds;
                }
                $blacklist[] = $entry;

                $room->room_black = implode(',', array_values($blacklist));
                $room->save();
            }

            return true;
        });
    }

    /**
     * Remove a ban (mark as inactive) with dual-write
     *
     * @param int $roomId
     * @param int $userId
     * @return bool
     */
    public function removeBan(int $roomId, int $userId): bool
    {
        return DB::transaction(function () use ($roomId, $userId) {
            // Mark as inactive in new table
            RoomBlacklist::where('room_id', $roomId)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Dual-write: remove from legacy column
            $room = Room::find($roomId);
            if ($room) {
                $blacklist = array_filter(explode(',', $room->room_black ?? ''));
                $blacklist = array_filter($blacklist, function($entry) use ($userId) {
                    $parts = explode('#', $entry);
                    return $parts[0] != $userId;
                });

                $room->room_black = implode(',', array_values($blacklist));
                $room->save();
            }

            return true;
        });
    }

    /**
     * Get all currently valid bans for a room
     *
     * @param int $roomId
     * @return Collection
     */
    public function getActiveBans(int $roomId): Collection
    {
        return RoomBlacklist::where('room_id', $roomId)
            ->valid()
            ->with('user:id,name,avatar')
            ->get();
    }

    /**
     * Get blacklisted user IDs for a room
     *
     * @param int $roomId
     * @return Collection
     */
    public function getBlacklistedUserIds(int $roomId): Collection
    {
        return RoomBlacklist::where('room_id', $roomId)
            ->valid()
            ->pluck('user_id');
    }

    /**
     * Get ban details for a specific user in a room
     *
     * @param int $roomId
     * @param int $userId
     * @return RoomBlacklist|null
     */
    public function getBanDetails(int $roomId, int $userId): ?RoomBlacklist
    {
        return RoomBlacklist::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->valid()
            ->first();
    }

    /**
     * Mark expired bans as inactive
     * This should be called by a scheduled job
     *
     * @return int Number of bans marked as inactive
     */
    public function markExpiredBansAsInactive(): int
    {
        return RoomBlacklist::active()
            ->expired()
            ->update(['is_active' => false]);
    }

    /**
     * Clean up old expired bans (older than X days)
     *
     * @param int $daysOld
     * @return int Number of records deleted
     */
    public function cleanupOldExpiredBans(int $daysOld = 30): int
    {
        return RoomBlacklist::where('is_active', false)
            ->where('expires_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Get time remaining for a user's ban (in seconds)
     *
     * @param int $roomId
     * @param int $userId
     * @return int|null Null if no ban, 0 if expired, seconds if active
     */
    public function getTimeRemaining(int $roomId, int $userId): ?int
    {
        $ban = $this->getBanDetails($roomId, $userId);

        if (!$ban) {
            return null;
        }

        return $ban->getTimeRemaining();
    }

    /**
     * Check if a ban is permanent
     *
     * @param int $roomId
     * @param int $userId
     * @return bool
     */
    public function isPermanentBan(int $roomId, int $userId): bool
    {
        $ban = RoomBlacklist::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->valid()
            ->first();

        return $ban && $ban->expires_at === null;
    }
}

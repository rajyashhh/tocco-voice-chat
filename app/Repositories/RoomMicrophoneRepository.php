<?php

namespace App\Repositories;

use App\Models\Room;
use App\Models\RoomMicrophone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoomMicrophoneRepository
{
    /**
     * Assign a user to a microphone position with dual-write
     *
     * @param int $roomId
     * @param int $position
     * @param int|null $userId
     * @param string $status
     * @return bool
     */
    public function assignMicrophone(int $roomId, int $position, ?int $userId = null, string $status = '0'): bool
    {
        return DB::transaction(function () use ($roomId, $position, $userId, $status) {
            // Write to new table
            RoomMicrophone::updateOrCreate([
                'room_id' => $roomId,
                'position' => $position
            ], [
                'user_id' => $userId,
                'status' => $status,
                'updated_at' => now()
            ]);

            // Dual-write: update legacy column
            $this->updateLegacyColumn($roomId);

            return true;
        });
    }

    /**
     * Remove user from microphone position
     *
     * @param int $roomId
     * @param int $position
     * @return bool
     */
    public function clearMicrophone(int $roomId, int $position): bool
    {
        return $this->assignMicrophone($roomId, $position, null, '0');
    }

    /**
     * Lock/unlock a microphone position
     *
     * @param int $roomId
     * @param int $position
     * @param bool $lock true to lock (-1), false to unlock (0)
     * @return bool
     */
    public function lockMicrophone(int $roomId, int $position, bool $lock = true): bool
    {
        $status = $lock ? '-1' : '0';
        return $this->assignMicrophone($roomId, $position, null, $status);
    }

    /**
     * Mute/unmute a microphone position
     *
     * @param int $roomId
     * @param int $position
     * @param bool $mute
     * @return bool
     */
    public function muteMicrophone(int $roomId, int $position, bool $mute = true): bool
    {
        $mic = RoomMicrophone::where('room_id', $roomId)
            ->where('position', $position)
            ->first();

        if (!$mic) {
            return false;
        }

        $status = $mute ? '-2' : '0';
        return $this->assignMicrophone($roomId, $position, $mic->user_id, $status);
    }

    /**
     * Get all microphones for a room
     *
     * @param int $roomId
     * @return Collection
     */
    public function getMicrophones(int $roomId): Collection
    {
        return RoomMicrophone::where('room_id', $roomId)
            ->orderBy('position')
            ->get();
    }

    /**
     * Get microphone at specific position
     *
     * @param int $roomId
     * @param int $position
     * @return RoomMicrophone|null
     */
    public function getMicrophoneAtPosition(int $roomId, int $position): ?RoomMicrophone
    {
        return RoomMicrophone::where('room_id', $roomId)
            ->where('position', $position)
            ->first();
    }

    /**
     * Check if position is available
     *
     * @param int $roomId
     * @param int $position
     * @return bool
     */
    public function isPositionAvailable(int $roomId, int $position): bool
    {
        $mic = $this->getMicrophoneAtPosition($roomId, $position);

        if (!$mic) {
            return true; // No record = available
        }

        // Available if status is 0 and no user assigned
        return $mic->status === '0' && $mic->user_id === null;
    }

    /**
     * Check if position is locked
     *
     * @param int $roomId
     * @param int $position
     * @return bool
     */
    public function isPositionLocked(int $roomId, int $position): bool
    {
        $mic = $this->getMicrophoneAtPosition($roomId, $position);
        return $mic && $mic->status === '-1';
    }

    /**
     * Get user's current microphone position
     *
     * @param int $roomId
     * @param int $userId
     * @return int|null Position number or null
     */
    public function getUserPosition(int $roomId, int $userId): ?int
    {
        $mic = RoomMicrophone::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->first();

        return $mic ? $mic->position : null;
    }

    /**
     * Get occupied microphones with user details
     *
     * @param int $roomId
     * @return Collection
     */
    public function getOccupiedMicrophones(int $roomId): Collection
    {
        return RoomMicrophone::where('room_id', $roomId)
            ->whereNotNull('user_id')
            ->with('user:id,name,avatar')
            ->orderBy('position')
            ->get();
    }

    /**
     * Initialize microphones for a room (create all positions)
     *
     * @param int $roomId
     * @param int $totalPositions Default 10
     * @return bool
     */
    public function initializeMicrophones(int $roomId, int $totalPositions = 10): bool
    {
        return DB::transaction(function () use ($roomId, $totalPositions) {
            for ($position = 0; $position < $totalPositions; $position++) {
                RoomMicrophone::firstOrCreate([
                    'room_id' => $roomId,
                    'position' => $position
                ], [
                    'user_id' => null,
                    'status' => '0'
                ]);
            }

            // Update legacy column
            $this->updateLegacyColumn($roomId);

            return true;
        });
    }

    /**
     * Update legacy microphone column based on new table data
     * This maintains backward compatibility
     *
     * @param int $roomId
     * @return void
     */
    private function updateLegacyColumn(int $roomId): void
    {
        $room = Room::find($roomId);
        if (!$room) {
            return;
        }

        $microphones = RoomMicrophone::where('room_id', $roomId)
            ->orderBy('position')
            ->get();

        // Build legacy format: user_id#status or just status
        $legacyArray = [];
        $maxPosition = 9; // Default 10 positions (0-9)

        for ($i = 0; $i <= $maxPosition; $i++) {
            $mic = $microphones->firstWhere('position', $i);

            if ($mic && $mic->user_id) {
                // Format: user_id#status
                $legacyArray[$i] = $mic->user_id . '#' . $mic->status;
            } elseif ($mic && $mic->status !== '0') {
                // Special status (locked, muted, etc.)
                $legacyArray[$i] = $mic->status;
            } else {
                // Empty position
                $legacyArray[$i] = '0';
            }
        }

        $room->microphone = implode(',', $legacyArray);
        $room->save();
    }

    /**
     * Parse legacy microphone column and sync to new table
     * This is used during migration or when syncing from legacy data
     *
     * @param int $roomId
     * @param string $legacyMicrophone
     * @return bool
     */
    public function syncFromLegacyColumn(int $roomId, string $legacyMicrophone): bool
    {
        $positions = explode(',', $legacyMicrophone);

        return DB::transaction(function () use ($roomId, $positions) {
            foreach ($positions as $position => $value) {
                $value = trim($value);

                if (empty($value)) {
                    continue;
                }

                $userId = null;
                $status = '0';

                if (strpos($value, '#') !== false) {
                    // Format: user_id#status
                    [$userId, $status] = explode('#', $value);
                    $userId = (int) $userId;
                } else {
                    // Just status (0, -1, -2, etc.)
                    $status = $value;
                }

                RoomMicrophone::updateOrCreate([
                    'room_id' => $roomId,
                    'position' => $position
                ], [
                    'user_id' => $userId > 0 ? $userId : null,
                    'status' => $status
                ]);
            }

            return true;
        });
    }
}

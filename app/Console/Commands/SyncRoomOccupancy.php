<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Tik\Services\RoomOccupancyReconciler;
use App\Traits\HelperTraits\UtdStreamTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconcile local room occupancy against the authoritative UTD Stream / LiveKit
 * live participant list.
 *
 * Pulls every active room from the ListRooms server API in a single call,
 * maps each LiveKit room_name to a local rooms row, reconciles room_visitors so
 * the local rows match the live participant identities, sets is_live=1 for
 * active (non-audio) rooms, and sets is_live=0 / clears visitors for rooms that
 * are no longer in the active set (this also fixes the stale is_live rows left
 * behind when an owner disconnected without a clean room_logout).
 *
 * Idempotent and safe to run every ~45s.
 */
class SyncRoomOccupancy extends Command
{
    use UtdStreamTrait;

    protected $signature = 'rooms:sync-occupancy';

    protected $description = 'Reconcile room_visitors and is_live against the live UTD Stream / LiveKit participant list';

    public function handle(RoomOccupancyReconciler $reconciler): int
    {
        $rooms = $this->normalizeRooms(self::listRooms());

        if ($rooms === null) {
            // listRooms() already logged the transport error. Do NOT deactivate
            // anything on a failed/empty fetch — that would wipe live rooms.
            $this->warn('ListRooms returned no usable data; skipping this cycle.');
            return self::SUCCESS;
        }

        $activeRoomIds = [];
        $reconciled = 0;

        foreach ($rooms as $liveRoom) {
            // ListRooms returns the room name under `room_name` (the webhook
            // payload uses `name`); accept either.
            $roomName = $liveRoom['room_name'] ?? $liveRoom['name'] ?? null;
            $room = $reconciler->resolveRoom($roomName);

            if (! $room) {
                // LiveKit room with no matching internal room (e.g. a call room).
                continue;
            }

            $participants = $this->resolveParticipants($liveRoom, $roomName);
            $identities = $this->pluckIdentities($participants);

            $reconciler->reconcileRoomVisitors($room, $identities);

            // A non-audio (live) room with ZERO participants is OVER, not
            // active: LiveKit keeps empty rooms alive for its emptyTimeout
            // window, which kept a closed broadcast listed (is_live=1) for
            // minutes after the host left. Let deactivateStaleRooms flip it.
            if (empty($identities) && $room->type !== 'audio') {
                continue;
            }

            // Self-healing backstop for is_broadcasting (the lives-list gate):
            // the webhooks are the real-time source of truth, but a dropped
            // track_published/track_unpublished would otherwise leave the flag
            // wrong until the room ends. Re-derive it here from the authoritative
            // participant list — a live room is "broadcasting" iff its owner is
            // present AND publishing a video track.
            if ($room->type !== 'audio') {
                $reconciler->setBroadcasting(
                    $room,
                    $this->ownerPublishingVideo($participants, (int) $room->uid)
                );
            }

            $activeRoomIds[$room->id] = true;
            $reconciled++;
        }

        $deactivated = $this->deactivateStaleRooms($reconciler, array_keys($activeRoomIds));

        $cleared = $this->clearStaleNowRoomMarkers();

        $this->info("Occupancy sync: {$reconciled} active rooms reconciled, {$deactivated} stale rooms deactivated, {$cleared} stale now_room markers cleared.");

        return self::SUCCESS;
    }

    /**
     * users.now_room_uid is written on room enter and survives app kills, so
     * it accumulates stale "in a room" markers (37K measured 2026-06-11) that
     * fed a lying تتبع badge. room_visitors is the reconciled truth this
     * command just refreshed — clear the marker for everyone without a
     * presence row. Bounded single UPDATE; runs after reconciliation so a
     * genuinely-present user is never cleared.
     */
    private function clearStaleNowRoomMarkers(): int
    {
        try {
            return DB::table('users')
                ->whereNotNull('now_room_uid')
                ->where('now_room_uid', '!=', 0)
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('room_visitors')
                        ->whereColumn('room_visitors.user_id', 'users.id');
                })
                ->update(['now_room_uid' => null]);
        } catch (\Throwable $e) {
            Log::warning('Failed clearing stale now_room_uid markers', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * The ListRooms endpoint returns a JSON array of rooms, but the HTTP layer
     * may wrap it ({ data: [...] } / { rooms: [...] }). Normalise to a plain
     * list, or null when the payload is unusable (transport failure / empty).
     *
     * @return array<int, array>|null
     */
    private function normalizeRooms($response): ?array
    {
        if (! is_array($response)) {
            return null;
        }

        if (isset($response['data']) && is_array($response['data'])) {
            $response = $response['data'];
        } elseif (isset($response['rooms']) && is_array($response['rooms'])) {
            $response = $response['rooms'];
        }

        // A list of room objects is a 0-indexed array of arrays.
        $rooms = array_values(array_filter($response, 'is_array'));

        return $rooms;
    }

    /**
     * Resolve the full live participant list for one LiveKit room (each element
     * carries identity + published tracks / published_video). Prefer the array
     * inlined in the ListRooms payload; fall back to a per-room GetRoomInfo call
     * only when ListRooms reported occupants but did not inline the list.
     *
     * @return array<int, array>
     */
    private function resolveParticipants(array $liveRoom, ?string $roomName): array
    {
        $participants = $liveRoom['participants'] ?? null;

        if (is_array($participants)) {
            return array_values(array_filter($participants, 'is_array'));
        }

        // ListRooms reports occupancy under `participant_count` (webhook uses
        // `num_participants`); accept either. Participants are not inlined by
        // ListRooms, so fetch them per occupied room via GetRoomInfo.
        $numParticipants = (int) ($liveRoom['participant_count'] ?? $liveRoom['num_participants'] ?? 0);
        if ($numParticipants <= 0 || ! $roomName) {
            return [];
        }

        $info = self::getRoomInfo($roomName);
        if (! is_array($info)) {
            return [];
        }

        $info = $info['data'] ?? $info;
        $participants = $info['participants'] ?? [];

        return is_array($participants) ? array_values(array_filter($participants, 'is_array')) : [];
    }

    /**
     * True iff the room owner is present in the live participant list AND is
     * publishing a video track. GetRoomInfo exposes this two ways depending on
     * whether it hit LiveKit (tracks[] with type 'VIDEO') or its degraded DB
     * fallback (published_video boolean) — accept both.
     *
     * @param array<int, array> $participants
     */
    private function ownerPublishingVideo(array $participants, int $ownerId): bool
    {
        foreach ($participants as $participant) {
            if ((int) ($participant['identity'] ?? 0) !== $ownerId) {
                continue;
            }

            if (! empty($participant['published_video'])) {
                return true;
            }

            $tracks = $participant['tracks'] ?? [];
            if (is_array($tracks)) {
                foreach ($tracks as $track) {
                    if (is_array($track) && strtoupper((string) ($track['type'] ?? '')) === 'VIDEO') {
                        return true;
                    }
                }
            }

            return false;
        }

        return false;
    }

    /**
     * @param array<int, array> $participants
     * @return array<int, int|string>
     */
    private function pluckIdentities(array $participants): array
    {
        $identities = [];
        foreach ($participants as $participant) {
            $identity = is_array($participant) ? ($participant['identity'] ?? null) : null;
            if ($identity !== null && $identity !== '') {
                $identities[] = $identity;
            }
        }

        return $identities;
    }

    /**
     * Deactivate every room that currently holds occupancy state locally but is
     * NOT in the active LiveKit set. Scoped to rooms that have visitors or are
     * flagged live, so the scan stays bounded; writes are chunked.
     *
     * @param array<int> $activeRoomIds
     */
    private function deactivateStaleRooms(RoomOccupancyReconciler $reconciler, array $activeRoomIds): int
    {
        $deactivated = 0;

        Room::query()
            ->select(['id', 'type', 'is_live'])
            ->where(function ($q) {
                $q->where('is_live', true)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('room_visitors')
                            ->whereColumn('room_visitors.room_id', 'rooms.id');
                    });
            })
            ->when(! empty($activeRoomIds), fn ($q) => $q->whereNotIn('id', $activeRoomIds))
            ->orderBy('id')
            ->chunkById(500, function ($staleRooms) use ($reconciler, &$deactivated) {
                foreach ($staleRooms as $room) {
                    try {
                        $reconciler->deactivateRoom($room);
                        $deactivated++;
                    } catch (\Throwable $e) {
                        Log::warning('Failed to deactivate stale room during occupancy sync', [
                            'room_id' => $room->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $deactivated;
    }
}

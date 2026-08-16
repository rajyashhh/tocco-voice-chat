<?php

namespace App\Tik\Services;

use App\Models\Room;
use App\Repositories\RoomVisitorRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the backend the source of truth for room occupancy.
 *
 * The authoritative live participant list lives in UTD Stream / LiveKit. This
 * service reconciles the local room_visitors table (and rooms.is_live) against
 * that authoritative list, either in bulk from the ListRooms server API (see
 * App\Console\Commands\SyncRoomOccupancy) or per-event from the UTD Stream
 * participant webhooks (see App\Http\Controllers\Api\V1\UtdStreamWebhookController).
 *
 * Single source of truth = room_visitors with UNIQUE(room_id, user_id). All
 * writes go through RoomVisitorRepository (atomic insertOrIgnore / delete, each
 * wrapped in withDeadlockRetry) — mirroring App\Tik\Services\EnteranceRoomServices.
 */
class RoomOccupancyReconciler
{
    protected RoomVisitorRepository $visitorRepo;

    public function __construct(RoomVisitorRepository $visitorRepo)
    {
        $this->visitorRepo = $visitorRepo;
    }

    /**
     * Resolve a LiveKit room_name to a local rooms row.
     *
     * The client uses the local rooms.id as the UTD Stream room name, so the
     * LiveKit room_name equals rooms.id. Verified live against the UTD Stream
     * ListRooms API (room_name "9656" -> rooms.id 9656; that room's
     * owner_identity matched rooms.uid).
     */
    public function resolveRoom(?string $roomName): ?Room
    {
        $id = (int) $roomName;
        if ($id <= 0) {
            return null;
        }

        return Room::find($id);
    }

    /**
     * Reconcile a single room's visitor rows against the authoritative set of
     * live participant identities (each identity is a users.id — see
     * UtdStreamController::token where identity = (string) $user->id).
     *
     * Adds rows for participants present in LiveKit but missing locally, and
     * removes rows for visitors no longer present in LiveKit. Idempotent.
     *
     * @param array<int|string> $participantIdentities
     */
    public function reconcileRoomVisitors(Room $room, array $participantIdentities): void
    {
        // Normalise to a unique set of positive integer user ids.
        $liveIds = [];
        foreach ($participantIdentities as $identity) {
            $id = (int) $identity;
            if ($id > 0) {
                $liveIds[$id] = true;
            }
        }
        $liveIds = array_keys($liveIds);

        $currentIds = $this->visitorRepo->getVisitorIds($room->id)->all();
        $currentIds = array_map('intval', $currentIds);

        $toAdd = array_diff($liveIds, $currentIds);
        $toRemove = array_diff($currentIds, $liveIds);

        foreach ($toAdd as $userId) {
            $this->visitorRepo->addVisitor($room->id, $userId);
        }

        foreach ($toRemove as $userId) {
            $this->visitorRepo->removeVisitor($room->id, $userId);
        }

        $this->syncIsLive($room, count($liveIds) > 0);
        // Old/new bridge: the room lists (popular/trend/quick-switch) still
        // filter on rooms.count_room_socket, which nothing wrote after the move
        // to the UTD Stream webhooks - keep it equal to the live participant set.
        $this->syncSocketCount($room, count($liveIds));
    }

    /**
     * Add a single participant (room_login equivalent). Used by the
     * participant_joined webhook for real-time reconciliation.
     */
    public function addParticipant(Room $room, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $this->visitorRepo->addVisitor($room->id, $userId);
        $this->syncIsLive($room, true);
        // Old/new bridge: keep the legacy list-filter column in step (see syncSocketCount).
        $this->syncSocketCount($room, $this->visitorRepo->getVisitorCount($room->id));
    }

    /**
     * Remove a single participant (room_logout equivalent). Used by the
     * participant_left webhook for real-time reconciliation.
     */
    public function removeParticipant(Room $room, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $this->visitorRepo->removeVisitor($room->id, $userId);

        $count = $this->visitorRepo->getVisitorCount($room->id);
        $this->syncIsLive($room, $count > 0);
        // Old/new bridge: keep the legacy list-filter column in step (see syncSocketCount).
        $this->syncSocketCount($room, $count);
    }

    /**
     * Mark a room as no longer live and drop all its visitor rows.
     *
     * Used when a room is no longer in the active LiveKit set (full sync) or on
     * the room_finished webhook. Fixes stale is_live rows whose owner left
     * without a clean logout.
     */
    public function deactivateRoom(Room $room): void
    {
        $this->persistTapTotal($room);
        $this->resetLiveViewers($room);
        $this->visitorRepo->clearAllVisitors($room->id);
        $this->setIsLive($room, false);
        // The broadcast is over: the room must leave the lives/trending list.
        $this->setBroadcasting($room, false);
        // Old/new bridge: room gone from the engine -> hide it from the legacy lists.
        $this->syncSocketCount($room, 0);
    }

    /**
     * Flip rooms.is_broadcasting — the authoritative "the host is pushing media"
     * flag the lives/trending list filters on. Set true only when the room owner
     * publishes a VIDEO track on the engine (track_published webhook); cleared on
     * stop-broadcast / owner-leave / room-finished / end-live. No-op for audio
     * rooms (they never use is_broadcasting).
     */
    public function setBroadcasting(Room $room, bool $value): void
    {
        if (! Schema::hasColumn('rooms', 'is_broadcasting')) {
            return;
        }

        if ($room->type === 'audio') {
            return;
        }

        if ((bool) $room->is_broadcasting === $value) {
            return;
        }

        try {
            DB::table('rooms')->where('id', $room->id)->update(['is_broadcasting' => $value]);
            $room->is_broadcasting = $value;
        } catch (\Throwable $e) {
            Log::warning('Failed to update room is_broadcasting during occupancy reconcile', [
                'room_id' => $room->id,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Old/new bridge: rooms.count_room_socket is the column every legacy room
     * list (popular / trend / quick-switch) filters on (!= 0), but nothing
     * updates it since presence moved to the UTD Stream webhooks + sync. Keep
     * it mirroring the live participant count so those lists keep working.
     */
    private function syncSocketCount(Room $room, int $count): void
    {
        // NOTE: read the RAW column — Room has a legacy getCountRoomSocketAttribute()
        // accessor that recomputes from the room_visitor CSV and would shadow the
        // real DB value here (explode(',', null) === [''] -> a phantom count of 1).
        if ((int) $room->getRawOriginal('count_room_socket') === $count) {
            return;
        }

        try {
            DB::table('rooms')->where('id', $room->id)->update(['count_room_socket' => $count]);
            $room->count_room_socket = $count;
        } catch (\Throwable $e) {
            Log::warning('Failed to update room count_room_socket during occupancy reconcile', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * End-of-broadcast reset for the cumulative unique-viewers stat: drop the
     * session's dedupe rows and zero rooms.live_viewers_total so the next
     * broadcast starts from zero. Mirrors persistTapTotal's lifecycle. No-op
     * for audio rooms.
     */
    private function resetLiveViewers(Room $room): void
    {
        if ($room->type === 'audio') {
            return;
        }

        try {
            DB::table('live_session_viewers')->where('room_id', $room->id)->delete();
            DB::table('rooms')->where('id', $room->id)->update(['live_viewers_total' => 0]);
            $room->live_viewers_total = 0;
        } catch (\Throwable $e) {
            Log::warning('resetLiveViewers failed', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Persist the broadcast's tap-hearts total (التكبيس) when a live ends —
     * one row per broadcast, the durable input for the future points system.
     * The Redis counter is deleted afterwards so the next broadcast starts
     * from zero. No-op for audio rooms and zero totals.
     */
    private function persistTapTotal(Room $room): void
    {
        if ($room->type === 'audio') {
            return;
        }

        try {
            $key = \App\Http\Controllers\Api\V1\LiveTapsController::totalKey($room->id);
            $total = (int) \Illuminate\Support\Facades\Redis::get($key);
            if ($total <= 0) {
                return;
            }
            \Illuminate\Support\Facades\Redis::del($key);
            \Illuminate\Support\Facades\DB::table('live_tap_totals')->insert([
                'room_id' => $room->id,
                'owner_id' => (int) $room->uid,
                'total' => $total,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('persistTapTotal failed', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Keep rooms.is_live in step with whether the room currently has anyone in
     * it, but only for non-audio (live) rooms — audio rooms do not use is_live
     * for the live-rooms list (see EnteranceRoomServices::updateRoomCountFromAgora).
     */
    private function syncIsLive(Room $room, bool $alive): void
    {
        if (! Schema::hasColumn('rooms', 'is_live')) {
            return;
        }

        if ($room->type === 'audio') {
            return;
        }

        $this->setIsLive($room, $alive);
    }

    private function setIsLive(Room $room, bool $value): void
    {
        if (! Schema::hasColumn('rooms', 'is_live')) {
            return;
        }

        if ((bool) $room->is_live === $value) {
            return;
        }

        try {
            DB::table('rooms')->where('id', $room->id)->update(['is_live' => $value]);
            $room->is_live = $value;
        } catch (\Throwable $e) {
            Log::warning('Failed to update room is_live during occupancy reconcile', [
                'room_id' => $room->id,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

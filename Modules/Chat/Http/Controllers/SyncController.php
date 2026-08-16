<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\ChatRoomMember;
use Modules\Chat\Http\Resources\SyncMessageResource;
use Modules\Chat\Http\Resources\SyncRoomResource;

/**
 * REST sync surface (plan §6.5) — the recovery/gap-fill fallback for the
 * offline-first client when Centrifugo history recovery returns recovered:false
 * (or before the realtime layer is even connected on cold start).
 *
 * Read-only and app-owned-state oriented: every response is keyed by the
 * authoritative per-room server_seq so the drift layer can upsert deterministically.
 *
 * Membership is the access boundary: a user may only read a room they are an
 * ACTIVE member of. Authority is chat_room_members (the unified model, plan §10);
 * for rooms not yet backfilled we fall back to the legacy user_id/user_id2
 * participant columns so the endpoint is correct on live production data during
 * the transition.
 */
class SyncController extends Controller
{
    /**
     * Hard ceiling on page size for message endpoints, regardless of the
     * client-supplied limit, to bound per-request work on the production DB.
     */
    private const MAX_MESSAGE_LIMIT = 100;

    private const DEFAULT_MESSAGE_LIMIT = 30;

    /**
     * GET /api/v1/sync/rooms
     *
     * Keyset-paginated list of the caller's rooms (newest activity first) with,
     * per room: last_seq, my_last_read_seq (from chat_room_members) and the last
     * message. Cursor = ?before_room_id=N (return rooms with id < N), avoiding
     * OFFSET on a hot table.
     */
    public function rooms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit'          => ['sometimes', 'integer', 'min:1', 'max:100'],
            'before_room_id' => ['sometimes', 'integer', 'min:1'],
        ]);

        $user  = $request->user();
        $limit = (int) ($validated['limit'] ?? 30);

        // Membership rows for the caller across every room they can still read
        // (active or muted — muting restricts posting, not reading). This is the
        // canonical participation source and also carries the per-room read
        // cursor we need for unread math. Banned/left rooms are excluded.
        $memberships = ChatRoomMember::query()
            ->where('user_id', $user->id)
            ->whereIn('status', self::READ_ALLOWED_STATUSES)
            ->get()
            ->keyBy('chat_room_id');

        // Rooms the caller belongs to: union of chat_room_members and the legacy
        // 1:1 participant columns (rooms not yet backfilled). Both feed a single
        // id-keyset query so paging stays consistent.
        $memberRoomIds = $memberships->keys()->all();

        // Rooms where the caller has an authoritative non-readable membership row
        // (banned/left). These must be excluded even if the legacy participant
        // columns still name them — the membership row wins.
        $excludedRoomIds = ChatRoomMember::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', self::READ_ALLOWED_STATUSES)
            ->pluck('chat_room_id')
            ->all();

        $query = ChatRoom::query()
            ->where(function ($q) use ($user, $memberRoomIds) {
                $q->where('user_id', $user->id)
                    ->orWhere('user_id2', $user->id);
                if (!empty($memberRoomIds)) {
                    $q->orWhereIn('id', $memberRoomIds);
                }
            })
            ->when(!empty($excludedRoomIds), fn ($q) => $q->whereNotIn('id', $excludedRoomIds))
            // Exclude group rooms whose chat_groups row is soft-deleted. Such a
            // room keeps type='group' but its `group` relation resolves to null
            // (SoftDeletes hides the trashed row), so SyncRoomResource would emit
            // it as a broken type='dm' header the client re-upserts into drift on
            // every sync — re-materializing a deleted group in the chats list. A
            // group room is only valid while a LIVE chat_groups row exists.
            ->where(function ($q) {
                $q->where('type', '!=', 'group')
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('chat_groups')
                            ->whereColumn('chat_groups.chat_room_id', 'chat_rooms.id')
                            ->whereNull('chat_groups.deleted_at');
                    });
            })
            // Eager-load the 1:1 participants (+ their avatar) so the resource can
            // emit the peer's name/avatar for dm rooms without an N+1 per row.
            ->with(['lastMessage', 'group', 'userOne.profile', 'userTwo.profile'])
            ->orderByDesc('id');

        if (!empty($validated['before_room_id'])) {
            $query->where('id', '<', (int) $validated['before_room_id']);
        }

        // Fetch one extra row to compute has_more without a COUNT.
        $rooms = $query->limit($limit + 1)->get();

        $hasMore = $rooms->count() > $limit;
        $rooms   = $rooms->take($limit);

        // Inject the caller's membership (real or legacy fallback) onto each room
        // so the resource never issues a per-row query.
        $rooms->each(function (ChatRoom $room) use ($memberships, $user) {
            $room->setAttribute(
                'my_membership',
                $memberships->get($room->id) ?? $this->legacyMembershipFallback($room, $user->id)
            );
        });

        $nextCursor = $hasMore ? (int) $rooms->last()->id : null;

        return response()->json([
            'success' => true,
            'data'    => SyncRoomResource::collection($rooms),
            'paginates' => [
                'has_more'         => $hasMore,
                'next_before_room_id' => $nextCursor,
                'per_page'         => $limit,
            ],
        ]);
    }

    /**
     * GET /api/v1/rooms/{id}/messages
     *
     * Two modes, mutually exclusive:
     *  - ?since_seq=N            -> messages with server_seq > N, ASC by server_seq
     *                              (gap-fill / recovery fallback; forward catch-up).
     *  - ?before_seq=N&limit=30  -> messages with server_seq < N, DESC by server_seq
     *                              (keyset history paging for older messages).
     *
     * With neither, returns the newest page (DESC). All modes are bounded by
     * limit and exclude history below the caller's cleared_seq.
     */
    public function messages(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'since_seq'  => ['sometimes', 'integer', 'min:0'],
            'before_seq' => ['sometimes', 'integer', 'min:1'],
            'limit'      => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $room = ChatRoom::find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found',
            ], 404);
        }

        $membership = $this->resolveActiveMembership($room, $user->id);

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $limit = min(
            (int) $request->integer('limit', self::DEFAULT_MESSAGE_LIMIT),
            self::MAX_MESSAGE_LIMIT
        );

        $base = ChatMessage::query()
            ->where('chat_room_id', $room->id)
            ->whereNotNull('server_seq')
            // Respect the caller's per-member clear point (plan §5.3): a user that
            // cleared history up to cleared_seq must not see those messages again.
            ->when($membership->cleared_seq > 0, function ($q) use ($membership) {
                $q->where('server_seq', '>', (int) $membership->cleared_seq);
            })
            ->with(['reacts', 'albums']);

        if ($request->filled('since_seq')) {
            // Forward gap-fill: ascending so the client appends in order.
            $messages = $base
                ->where('server_seq', '>', (int) $request->integer('since_seq'))
                ->orderBy('server_seq', 'asc')
                ->limit($limit)
                ->get();

            $mode = 'since';
        } else {
            // Backward history paging (keyset, no OFFSET). Newest-first; when
            // before_seq is absent this returns the most recent page.
            $query = $base->orderBy('server_seq', 'desc')->limit($limit);

            if ($request->filled('before_seq')) {
                $query->where('server_seq', '<', (int) $request->integer('before_seq'));
            }

            $messages = $query->get();
            $mode = 'before';
        }

        $hasMore = $messages->count() === $limit;

        return response()->json([
            'success' => true,
            'data'    => SyncMessageResource::collection($messages),
            'paginates' => [
                'mode'     => $mode,
                'limit'    => $limit,
                'has_more' => $hasMore,
                'room_last_seq' => (int) $room->last_seq,
                'my_last_read_seq' => (int) $membership->last_read_seq,
            ],
        ]);
    }

    /**
     * Statuses that may still READ a room's history. Muting (plan §5.2) only
     * restricts posting, not reading; banned/left lose access entirely.
     *
     * @var array<int, string>
     */
    private const READ_ALLOWED_STATUSES = ['active', 'muted'];

    /**
     * Resolve the caller's readable membership for a room.
     *
     * Authority is chat_room_members. A membership row — of ANY status — is
     * authoritative: if it exists but the status cannot read (banned/left),
     * access is denied and we do NOT fall back to the legacy columns (that would
     * let a banned participant of an old 1:1 room slip back in). Only when no row
     * exists at all (room not yet backfilled) do we synthesize a transient
     * membership from the legacy 1:1 participant columns so reads stay correct
     * during the unification rollout. Returns null when the user is neither a
     * readable member nor a legacy participant of an un-backfilled room.
     */
    private function resolveActiveMembership(ChatRoom $room, int $userId): ?ChatRoomMember
    {
        $membership = ChatRoomMember::query()
            ->where('chat_room_id', $room->id)
            ->where('user_id', $userId)
            ->first();

        if ($membership) {
            return in_array($membership->status, self::READ_ALLOWED_STATUSES, true)
                ? $membership
                : null;
        }

        return $this->legacyMembershipFallback($room, $userId);
    }

    /**
     * Build a non-persisted ChatRoomMember from the legacy participant columns
     * for a room that predates the chat_room_members backfill. Read cursors are
     * unknown in the legacy model, so they default to 0 (client treats the room
     * as fully unread until the first mark_read writes a real row).
     */
    private function legacyMembershipFallback(ChatRoom $room, int $userId): ?ChatRoomMember
    {
        if ((int) $room->user_id !== $userId && (int) $room->user_id2 !== $userId) {
            return null;
        }

        $member = new ChatRoomMember([
            'chat_room_id'  => $room->id,
            'user_id'       => $userId,
            'role'          => 'member',
            'status'        => 'active',
            'last_read_seq' => 0,
            'cleared_seq'   => 0,
        ]);
        $member->exists = false;

        return $member;
    }
}

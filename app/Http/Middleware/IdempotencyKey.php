<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Http\Resources\ChatRoomResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotent message creation guard for the offline-first chat path.
 *
 * The client sends each message with a stable `Idempotency-Key` header equal to
 * its locally generated `client_uuid`. When the outbox retries a send (lost
 * connection, app restart) the same key arrives again. The DB-level unique index
 * `uq_msg_room_client (chat_room_id, client_uuid)` is the hard guarantee against
 * duplicates; this middleware is the fast path that, on a repeat key, returns the
 * already-persisted message instead of running the full create flow a second time.
 *
 * It is intentionally conservative: when it cannot positively identify a prior
 * message (no header, room not resolvable, or first-ever send) it simply lets the
 * request continue and the unique index still protects the write.
 */
class IdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientUuid = trim((string) $request->header('Idempotency-Key', ''));

        // No key supplied (legacy client) — nothing to dedupe, fall through.
        if ($clientUuid === '') {
            return $next($request);
        }

        $user = $request->user();
        $recipientId = $request->input('user_id');

        // Without an authenticated sender and a recipient we cannot scope the
        // lookup to a room; defer entirely to the unique index on insert.
        if (!$user || !$recipientId) {
            return $next($request);
        }

        $room = ChatRoom::betweenUsers($user->id, $recipientId)->first();

        if (!$room) {
            return $next($request);
        }

        $existing = ChatMessage::where('chat_room_id', $room->id)
            ->where('client_uuid', $clientUuid)
            ->first();

        // First time we have seen this key in this room — let the create flow run.
        if (!$existing) {
            return $next($request);
        }

        // Repeat delivery: return the stored message in the exact shape store()
        // produces so the client treats the retry as a successful send.
        return response()->json([
            'message' => new ChatMessageResource($existing),
            'card' => new ChatRoomResource($room),
        ]);
    }
}

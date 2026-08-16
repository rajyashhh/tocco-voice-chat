<?php

namespace Modules\Chat\Http\Repositories;

use App\Models\User;
use Illuminate\Database\QueryException;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;

class ChatRepository
{
    public function findChatRoomBetweenUsers($userId, $userId2)
    {
        $chatRoom = ChatRoom::BetweenUsers($userId, $userId2)->first();

        if ($chatRoom) {
            if ($chatRoom->user_1_deleted) {
                $chatRoom->update(['user_1_deleted' => null]);
            }

            if ($chatRoom->user_2_deleted) {
                $chatRoom->update(['user_2_deleted' => null]);
            }
        }

        return $chatRoom;
    }

    public function getUserChatRooms(int $userId): array
    {
        return ChatRoom::where('user_id', $userId)
            ->orWhere('user_id2', $userId)
            ->pluck('id')
            ->toArray();
    }

    public function findChatRoomForUser(int $userId, string $chatRoomId): ?ChatRoom
    {
        return ChatRoom::where(function ($query) use ($userId, $chatRoomId) {
                $query->where('user_id', $userId)->orWhere('user_id2', $userId);
            })
            ->where('id', $chatRoomId)
            ->first();
    }

    public function countMessagesByUserInRoom($chatRoomId, $userId)
    {
        return ChatMessage::ByUserInRoom($chatRoomId, $userId)->count();
    }

    public function countDistinctUsersInRoom($chatRoomId)
    {
        return ChatMessage::distinctUserInRoom($chatRoomId)->count();
    }

    /**
     * Insert a chat message, letting the unique index uq_msg_room_client
     * (chat_room_id, client_uuid) be the single source of truth for idempotency.
     *
     * Under a concurrent retry of the same offline message (same Idempotency-Key),
     * two requests may both pass the optional middleware fast-path read and reach
     * this INSERT. Exactly one wins; the loser's INSERT fails with a duplicate-key
     * QueryException (SQLSTATE 23000 / driver errno 1062) on that index. Instead of
     * surfacing a 500, we catch *only* that specific collision, re-read the row the
     * winner persisted, and return it.
     *
     * The returned model carries Eloquent's natural wasRecentlyCreated flag:
     *  - true  when this call performed the INSERT (happy path),
     *  - false when we recovered the row after a duplicate-key race.
     * store() branches on that flag so a retry never double-broadcasts or
     * double-notifies. No extra lock or transaction is added — it stays one INSERT,
     * plus a single point lookup on the unique index only in the rare retry case.
     */
    public function createChatMessage($data)
    {
        try {
            return ChatMessage::create($data);
        } catch (QueryException $e) {
            if (! $this->isClientUuidDuplicate($e, $data)) {
                throw $e;
            }

            $existing = ChatMessage::where('chat_room_id', $data['chat_room_id'])
                ->where('client_uuid', $data['client_uuid'])
                ->first();

            // If the winning row is somehow gone (e.g. deleted between collision and
            // re-read), we have no row to return idempotently — rethrow so the
            // failure is visible and classified by the Handler rather than masked.
            if (! $existing) {
                throw $e;
            }

            return $existing;
        }
    }

    /**
     * True only when the QueryException is a duplicate-key violation on the
     * uq_msg_room_client unique index for a request that actually carried a
     * client_uuid. We assert on SQLSTATE 23000 (integrity constraint) plus the
     * index name so an unrelated unique collision (e.g. uq_msg_room_seq) is never
     * swallowed as an idempotent retry.
     */
    private function isClientUuidDuplicate(QueryException $e, array $data): bool
    {
        if (empty($data['client_uuid'])) {
            return false;
        }

        $errno = $e->errorInfo[1] ?? null;
        $sqlState = $e->errorInfo[0] ?? $e->getCode();

        $isIntegrityViolation = $errno === 1062 || (string) $sqlState === '23000';

        return $isIntegrityViolation
            && str_contains($e->getMessage(), 'uq_msg_room_client');
    }

    public function findChatMessageById($id)
    {
        return ChatMessage::find($id);
    }

    public function updateMessage(ChatMessage $message, $newContent)
    {
        $message->message = $newContent;
        $message->save();

        return $message;
    }

    public function deleteMessages(array $ids)
    {
        return ChatMessage::whereIn('id', $ids)->delete();
    }

    public function updateDeletedTimestamp(ChatMessage $message, $userId)
    {
        if ($message->user_id == $userId) {
            $message->user_1_deleted = now();
        } else {
            $message->user_2_deleted = now();
        }
        return $message->save();
    }

}

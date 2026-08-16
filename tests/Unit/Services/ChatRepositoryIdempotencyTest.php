<?php

namespace Tests\Unit\Services;

use Illuminate\Database\QueryException;
use Modules\Chat\Http\Repositories\ChatRepository;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Proves the idempotency discriminator in ChatRepository::createChatMessage:
 * the unique index uq_msg_room_client(chat_room_id, client_uuid) is the single
 * source of truth. Only a duplicate-key violation (SQLSTATE 23000 / errno 1062)
 * ON THAT index, for a request that actually carried a client_uuid, is treated
 * as an idempotent retry to recover from. Every other QueryException — a
 * collision on a different unique index (uq_msg_room_seq), a non-integrity
 * failure, or a request with no client_uuid — must rethrow so it surfaces as a
 * classified error instead of being silently swallowed.
 *
 * These cases are pure logic (no DB), so they run deterministically anywhere.
 */
class ChatRepositoryIdempotencyTest extends TestCase
{
    private function discriminator(): ReflectionMethod
    {
        $method = new ReflectionMethod(ChatRepository::class, 'isClientUuidDuplicate');
        $method->setAccessible(true);

        return $method;
    }

    private function makeQueryException(string $sqlState, ?int $driverCode, string $message): QueryException
    {
        $previous = new \PDOException($message, 0);
        $previous->errorInfo = [$sqlState, $driverCode, $message];

        // Laravel 9+ QueryException signature: (connectionName, sql, bindings, previous)
        return new QueryException(
            'mysql',
            'insert into `chat_messages` ...',
            [],
            $previous
        );
    }

    public function test_duplicate_on_uq_msg_room_client_is_recovered(): void
    {
        $repo = new ChatRepository();
        $e = $this->makeQueryException(
            '23000',
            1062,
            "Integrity constraint violation: 1062 Duplicate entry '5-abc' for key 'uq_msg_room_client'"
        );

        $result = $this->discriminator()->invoke($repo, $e, [
            'chat_room_id' => 5,
            'client_uuid' => 'abc',
        ]);

        $this->assertTrue($result, 'A 1062/23000 collision on uq_msg_room_client must be treated as an idempotent retry.');
    }

    public function test_collision_on_a_different_unique_index_is_rethrown(): void
    {
        $repo = new ChatRepository();
        $e = $this->makeQueryException(
            '23000',
            1062,
            "Integrity constraint violation: 1062 Duplicate entry '5-42' for key 'uq_msg_room_seq'"
        );

        $result = $this->discriminator()->invoke($repo, $e, [
            'chat_room_id' => 5,
            'client_uuid' => 'abc',
        ]);

        $this->assertFalse($result, 'A collision on uq_msg_room_seq must NOT be swallowed as an idempotent retry.');
    }

    public function test_non_integrity_query_error_is_rethrown(): void
    {
        $repo = new ChatRepository();
        $e = $this->makeQueryException(
            'HY000',
            2006,
            'MySQL server has gone away'
        );

        $result = $this->discriminator()->invoke($repo, $e, [
            'chat_room_id' => 5,
            'client_uuid' => 'abc',
        ]);

        $this->assertFalse($result, 'A non-integrity (connection) error must never be treated as a duplicate.');
    }

    public function test_request_without_client_uuid_is_never_treated_as_duplicate(): void
    {
        $repo = new ChatRepository();
        $e = $this->makeQueryException(
            '23000',
            1062,
            "Integrity constraint violation: 1062 Duplicate entry for key 'uq_msg_room_client'"
        );

        $resultNull = $this->discriminator()->invoke($repo, $e, [
            'chat_room_id' => 5,
            'client_uuid' => null,
        ]);
        $this->assertFalse($resultNull, 'A null client_uuid (legacy client) has no idempotency key to recover by.');

        $resultMissing = $this->discriminator()->invoke($repo, $e, [
            'chat_room_id' => 5,
        ]);
        $this->assertFalse($resultMissing, 'A missing client_uuid key must not be treated as a duplicate.');
    }
}

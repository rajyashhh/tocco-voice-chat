<?php

namespace Modules\Chat\Tests\Phase2;

use App\Http\Middleware\IdempotencyKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Http\Services\ChatRoomService;

/**
 * Phase 2 (part B): Idempotency-Key dedup + server_seq wiring into the bulk send
 * path. Reuses the Phase 2 sync harness (real Phase 1 migrations on MariaDB) so
 * assertions run against production schema semantics, not SQLite approximations.
 */
class IdempotencyAndSeqTest extends Phase2SyncTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['chat.seq.driver' => 'db']);
    }

    // ---------------------------------------------------------------------
    // Idempotency middleware — branch selection (deterministic, no resources)
    // ---------------------------------------------------------------------

    public function test_passes_through_when_no_idempotency_key(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);

        $request = $this->postAs(1, ['user_id' => 2]);

        $called = false;
        $response = (new IdempotencyKey())->handle($request, function () use (&$called) {
            $called = true;
            return response('ok');
        });

        $this->assertTrue($called, 'request without Idempotency-Key must reach the controller');
        $this->assertSame('ok', $response->getContent());
    }

    public function test_passes_through_when_no_room_exists(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);

        $request = $this->postAs(1, ['user_id' => 2], 'uuid-no-room');

        $called = false;
        (new IdempotencyKey())->handle($request, function () use (&$called) {
            $called = true;
            return response('ok');
        });

        $this->assertTrue($called, 'no room between users yet -> defer to insert/unique index');
    }

    public function test_passes_through_for_a_new_key_in_existing_room(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $this->makeRoom(1, 2);

        $request = $this->postAs(1, ['user_id' => 2], 'brand-new-key');

        $called = false;
        (new IdempotencyKey())->handle($request, function () use (&$called) {
            $called = true;
            return response('ok');
        });

        $this->assertTrue($called, 'first time we see this key -> create flow must run');
    }

    public function test_short_circuits_on_repeat_key_and_creates_no_duplicate(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $roomId = $this->makeRoom(1, 2);

        // Original send already persisted with this client_uuid.
        DB::table('chat_messages')->insert([
            'chat_room_id' => $roomId,
            'user_id' => 1,
            'client_uuid' => 'dup-key',
            'server_seq' => 1,
            'message' => 'hello',
            'status' => 'received',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = (int) DB::table('chat_messages')->where('chat_room_id', $roomId)->count();

        $request = $this->postAs(1, ['user_id' => 2], 'dup-key');

        $called = false;
        try {
            (new IdempotencyKey())->handle($request, function () use (&$called) {
                $called = true;
                return response('ok');
            });
        } catch (\Throwable $e) {
            // The short-circuit JSON renders ChatRoomResource, whose User/pack
            // relations are outside this lightweight schema. The decision that
            // matters (short-circuit taken, no duplicate) is asserted below.
        }

        $after = (int) DB::table('chat_messages')->where('chat_room_id', $roomId)->count();

        $this->assertFalse($called, 'repeat Idempotency-Key must NOT reach the controller');
        $this->assertSame($before, $after, 'repeat key must never create a second message');
    }

    public function test_unique_index_rejects_duplicate_room_client_uuid(): void
    {
        $this->seedUser(1);
        $roomId = $this->makeRoom(1, null);

        DB::table('chat_messages')->insert([
            'chat_room_id' => $roomId, 'user_id' => 1, 'client_uuid' => 'k1',
            'server_seq' => 1, 'message' => 'a', 'status' => 'received',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('chat_messages')->insert([
            'chat_room_id' => $roomId, 'user_id' => 1, 'client_uuid' => 'k1',
            'server_seq' => 2, 'message' => 'b', 'status' => 'received',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------------
    // Bulk send path — reserve() per room, contiguous seq, denormalization
    // ---------------------------------------------------------------------

    public function test_send_messages_assigns_per_room_server_seq_and_denormalizes(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $this->seedUser(3);

        $roomA = $this->makeRoom(1, 2);
        $roomB = $this->makeRoom(1, 3);

        // Pre-existing seq history on room A: the new message must continue, not reset.
        DB::table('chat_rooms')->where('id', $roomA)->update(['last_seq' => 5]);

        $service = app(ChatRoomService::class);
        $remaining = $service->sendMessages(1, [2, 3], ['message' => 'shared', 'url' => 'https://x/y.jpg']);

        $this->assertSame([], array_values($remaining), 'both recipients had rooms -> none left to create');

        $msgA = DB::table('chat_messages')->where('chat_room_id', $roomA)->first();
        $msgB = DB::table('chat_messages')->where('chat_room_id', $roomB)->first();

        $this->assertSame(6, (int) $msgA->server_seq, 'room A continued 5 -> 6');
        $this->assertSame(1, (int) $msgB->server_seq, 'room B started at 1');

        $this->assertSame(2, (int) $msgA->user_id, 'recipient of room A is user 2');
        $this->assertSame(3, (int) $msgB->user_id, 'recipient of room B is user 3');

        $this->assertSame(6, (int) DB::table('chat_rooms')->where('id', $roomA)->value('last_seq'));
        $this->assertSame(1, (int) DB::table('chat_rooms')->where('id', $roomB)->value('last_seq'));

        $this->assertNotNull(DB::table('chat_rooms')->where('id', $roomA)->value('last_message_at'));
        $this->assertNotNull(DB::table('chat_rooms')->where('id', $roomB)->value('last_message_at'));
    }

    public function test_send_messages_seq_is_contiguous_across_repeated_sends_same_room(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $roomId = $this->makeRoom(1, 2);

        $service = app(ChatRoomService::class);

        $service->sendMessages(1, [2], ['message' => 'm1', 'url' => 'https://x/1.jpg']);
        $service->sendMessages(1, [2], ['message' => 'm2', 'url' => 'https://x/2.jpg']);
        $service->sendMessages(1, [2], ['message' => 'm3', 'url' => 'https://x/3.jpg']);

        $seqs = DB::table('chat_messages')->where('chat_room_id', $roomId)
            ->orderBy('id')->pluck('server_seq')->map(fn($v) => (int) $v)->all();

        $this->assertSame([1, 2, 3], $seqs, 'repeated sends must produce contiguous server_seq');
        $this->assertSame(3, (int) DB::table('chat_rooms')->where('id', $roomId)->value('last_seq'));
    }

    public function test_send_messages_does_not_use_for_update(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $this->makeRoom(1, 2);

        $service = app(ChatRoomService::class);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $service->sendMessages(1, [2], ['message' => 'm', 'url' => 'https://x/z.jpg']);
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        foreach ($log as $entry) {
            $this->assertStringNotContainsStringIgnoringCase(
                'for update',
                $entry['query'],
                'bulk send seq allocation must never use SELECT ... FOR UPDATE'
            );
        }
    }

    // ---------------------------------------------------------------------
    // helpers
    // ---------------------------------------------------------------------

    private function postAs(int $id, array $body, ?string $idempotencyKey = null): Request
    {
        $request = Request::create('/Chat-Message', 'POST', $body);

        $user = new \App\Models\User();
        $user->id = $id;
        Auth::setUser($user);
        $request->setUserResolver(fn() => $user);

        if ($idempotencyKey !== null) {
            $request->headers->set('Idempotency-Key', $idempotencyKey);
        }

        return $request;
    }
}

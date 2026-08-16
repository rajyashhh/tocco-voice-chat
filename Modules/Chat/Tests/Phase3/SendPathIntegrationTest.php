<?php

namespace Modules\Chat\Tests\Phase3;

use App\Http\Middleware\IdempotencyKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Chat\Events\Chat;
use Modules\Chat\Events\Conversation;
use Modules\Chat\Events\OpenChat;

/**
 * SEND PATH integration — the regression gate that the isolated component tests
 * missed. These run THROUGH the real controller action + real service graph +
 * the real (inline, sync) BroadcastChatMessage job, so a re-introduction of the
 * old inline event() calls in store(), or a second broadcast anywhere on the
 * default Pusher path, fails here.
 *
 * 🔴 Top invariant (per the rebuild plan §6.4 and the byte-for-byte rule): a 1:1
 * message on the default transport must broadcast EACH of Conversation / Chat /
 * OpenChat exactly ONCE.
 */
class SendPathIntegrationTest extends SendPathIntegrationTestCase
{
    // ---------------------------------------------------------------------
    // 🔴 1. No double broadcast through the real send path
    // ---------------------------------------------------------------------

    public function test_one_to_one_send_broadcasts_each_event_exactly_once(): void
    {
        $sender = $this->seedUser(1);
        $this->seedUser(2);
        $roomId = $this->makeRoom(1, 2);

        Event::fake([Conversation::class, Chat::class, OpenChat::class]);

        $request = $this->storeRequest($sender, ['user_id' => 2, 'message' => 'hello once']);

        $this->controller()->store($request);

        // The job runs inline (sync) and is the SINGLE broadcaster. If the inline
        // event() calls ever come back to store(), these counts become 2.
        Event::assertDispatchedTimes(Conversation::class, 1);
        Event::assertDispatchedTimes(Chat::class, 1);
        Event::assertDispatchedTimes(OpenChat::class, 1);
    }

    public function test_send_persists_exactly_one_message_row(): void
    {
        $sender = $this->seedUser(1);
        $this->seedUser(2);
        $roomId = $this->makeRoom(1, 2);

        $request = $this->storeRequest($sender, ['user_id' => 2, 'message' => 'single row']);

        $this->controller()->store($request);

        $this->assertSame(
            1,
            (int) DB::table('chat_messages')->where('chat_room_id', $roomId)->count(),
            'a single 1:1 send must persist exactly one message'
        );
    }

    /**
     * Source-level guard: the inline event() trio must not exist in store()
     * anymore. Belt-and-braces with the runtime count above so a future edit that
     * re-adds inline broadcasting is caught even if the job were ever disabled.
     */
    public function test_store_has_no_residual_inline_event_calls(): void
    {
        $source = file_get_contents(
            module_path('Chat', 'Http/Controllers/ChatMessagesController.php')
        );

        // Isolate the store() method body.
        $start = strpos($source, 'public function store(');
        $this->assertNotFalse($start, 'store() must exist');
        $next = strpos($source, 'private function isValidFileExtension(', $start);
        $storeBody = substr($source, $start, $next - $start);

        foreach (['new Conversation(', 'new Chat(', 'new OpenChat('] as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $storeBody,
                "store() must not broadcast {$needle} inline — the job is the single broadcaster"
            );
        }
    }

    // ---------------------------------------------------------------------
    // 🟠 2. Idempotency through the real route pipeline (middleware -> action)
    // ---------------------------------------------------------------------

    /**
     * Reproduces the route binding: POST /Chat-Message runs IdempotencyKey
     * middleware in front of store(). Two sends with the same Idempotency-Key must
     * persist exactly ONE row; the second short-circuits in the middleware and
     * returns the already-stored message — zero 500s.
     */
    public function test_repeat_idempotency_key_creates_one_row_and_returns_existing(): void
    {
        $sender = $this->seedUser(1);
        $this->seedUser(2);
        $roomId = $this->makeRoom(1, 2);

        $key = 'outbox-uuid-abc';

        // First send: middleware passes through (key unseen) -> action persists.
        $first = $this->runPipeline($sender, ['user_id' => 2, 'message' => 'idem first'], $key);
        $firstReached = $first['reached'];

        $afterFirst = (int) DB::table('chat_messages')->where('chat_room_id', $roomId)->count();

        // Second send: SAME key -> middleware short-circuits, action NOT reached.
        $second = $this->runPipeline($sender, ['user_id' => 2, 'message' => 'idem retry'], $key);

        $afterSecond = (int) DB::table('chat_messages')->where('chat_room_id', $roomId)->count();

        $this->assertTrue($firstReached, 'first unique key must reach store()');
        $this->assertSame(1, $afterFirst, 'first send persists exactly one row');
        $this->assertFalse($second['reached'], 'repeat key must short-circuit in middleware, not reach store()');
        $this->assertSame(1, $afterSecond, 'repeat key must NOT create a second row');

        // Second response renders the EXISTING message (same id), not a 500.
        $this->assertSame(200, $second['status'], 'idempotent replay must not 500');
        $stored = DB::table('chat_messages')->where('chat_room_id', $roomId)->first();
        $this->assertSame(
            (int) $stored->id,
            (int) ($second['body']['message']['id'] ?? -1),
            'replay must return the stored message id'
        );
        // The stored message keeps the FIRST body; the retry body is discarded.
        $this->assertSame('idem first', $stored->message);
    }

    /**
     * client_uuid must be written AT INSERT (controller line ~104/113), so the
     * unique index uq_msg_room_client gatekeeps a concurrent duplicate insert
     * before any later UPDATE could patch it. We assert: (a) the column is set on
     * the inserted row, and (b) a second raw INSERT with the same
     * (chat_room_id, client_uuid) is rejected by the DB — proving the index is the
     * hard guarantee even if two requests race past the middleware simultaneously.
     */
    public function test_client_uuid_is_written_at_insert_and_unique_index_blocks_duplicate(): void
    {
        $sender = $this->seedUser(1);
        $this->seedUser(2);
        $roomId = $this->makeRoom(1, 2);

        $key = 'race-uuid-xyz';

        Event::fake([Conversation::class, Chat::class, OpenChat::class]);
        $request = $this->storeRequest($sender, ['user_id' => 2, 'message' => 'race'], $key);
        $this->controller()->store($request);

        $row = DB::table('chat_messages')->where('chat_room_id', $roomId)->first();
        $this->assertSame($key, $row->client_uuid, 'client_uuid must be persisted at INSERT time');
        $this->assertNotNull($row->server_seq, 'server_seq must be assigned');

        // A concurrent retry that slipped past the fast-path middleware would hit
        // the same INSERT — the unique index must reject it.
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('chat_messages')->insert([
            'chat_room_id' => $roomId,
            'user_id'      => 1,
            'client_uuid'  => $key,
            'server_seq'   => 999,
            'message'      => 'concurrent dup',
            'status'       => 'sended',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    // ---------------------------------------------------------------------
    // 🟡 3. Broadcast payload carries the offline-sync keys
    // ---------------------------------------------------------------------

    public function test_broadcast_payload_contains_client_uuid_and_server_seq(): void
    {
        $sender = $this->seedUser(1);
        $this->seedUser(2);
        $this->makeRoom(1, 2);

        $key = 'payload-uuid-1';

        $captured = null;
        Event::fake([Conversation::class, Chat::class, OpenChat::class]);

        $request = $this->storeRequest($sender, ['user_id' => 2, 'message' => 'with keys'], $key);
        $this->controller()->store($request);

        // The Conversation event carries the message payload (the dual-transport
        // dedup/ordering keys live on the message, not the room).
        Event::assertDispatched(Conversation::class, function (Conversation $event) use ($key) {
            $payload = (array) $event->message;

            return ($payload['client_uuid'] ?? null) === $key
                && array_key_exists('server_seq', $payload)
                && $payload['server_seq'] !== null
                && (int) $payload['server_seq'] >= 1;
        });
    }

    public function test_message_resource_payload_exposes_sync_keys(): void
    {
        // Direct resource check: the payload feeding the job is built from
        // ChatMessageResource, which must surface client_uuid + server_seq.
        $sender = $this->seedUser(1);
        $this->seedUser(2);
        $roomId = $this->makeRoom(1, 2);

        $key = 'resource-uuid-1';
        Event::fake([Conversation::class, Chat::class, OpenChat::class]);
        $request = $this->storeRequest($sender, ['user_id' => 2, 'message' => 'res'], $key);
        $this->controller()->store($request);

        $stored = \Modules\Chat\Entities\ChatMessage::where('chat_room_id', $roomId)->first();
        $resource = (new \Modules\Chat\Http\Resources\ChatMessageResource($stored))
            ->toResponse(request())->getData()->data;
        $payload = (array) $resource;

        $this->assertSame($key, $payload['client_uuid']);
        $this->assertArrayHasKey('server_seq', $payload);
        $this->assertSame((int) $stored->server_seq, (int) $payload['server_seq']);
    }

    // ---------------------------------------------------------------------
    // helpers
    // ---------------------------------------------------------------------

    /**
     * Run the REAL per-request pipeline for POST /Chat-Message: the
     * IdempotencyKey middleware in front of the controller's store() action — the
     * exact composition the route declares.
     *
     * @return array{reached: bool, status: int, body: array}
     */
    private function runPipeline(\App\Models\User $sender, array $body, ?string $key): array
    {
        // Build a base Request the middleware reads (user + Idempotency-Key + user_id).
        $request = $this->storeRequest($sender, $body, $key);

        $reached = false;
        $response = (new IdempotencyKey())->handle($request, function () use (&$reached, $sender, $body, $key) {
            $reached = true;

            // Past the middleware -> resolve a fresh ChatStoreRequest for the
            // action (FormRequest validation context), same user + headers.
            $storeRequest = $this->storeRequest($sender, $body, $key);
            $result = $this->controller()->store($storeRequest);

            return $this->toResponse($result);
        });

        return [
            'reached' => $reached,
            'status'  => $response->getStatusCode(),
            'body'    => json_decode($response->getContent(), true) ?? [],
        ];
    }

    /**
     * Normalize whatever store() returns (array of resources, or a JsonResponse)
     * into a Response with a JSON body for assertions.
     */
    private function toResponse($result): \Symfony\Component\HttpFoundation\Response
    {
        if ($result instanceof \Symfony\Component\HttpFoundation\Response) {
            return $result;
        }

        return response()->json($result);
    }
}

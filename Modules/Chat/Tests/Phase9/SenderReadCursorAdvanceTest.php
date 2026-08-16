<?php

namespace Modules\Chat\Tests\Phase9;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\ChatRoomMember;
use Modules\Chat\Http\Controllers\SyncController;
use Modules\Chat\Http\Resources\SyncRoomResource;
use Modules\Chat\Http\Services\MessageService;
use Modules\Chat\Http\Services\NextServerSeqService;
use Modules\Chat\Tests\Phase2\Phase2SyncTestCase;
use ReflectionMethod;

/**
 * Sender read-cursor advance on send (REALTIME_CHAT_REBUILD_PLAN §unread).
 *
 * The unread badge everywhere (SyncRoomResource / GroupResource) is derived
 * O(1) as `max(0, last_seq - my_last_read_seq)`. NextServerSeqService bumps
 * chat_rooms.last_seq on EVERY send, so without also advancing the SENDER's own
 * chat_room_members.last_read_seq the sender's own just-sent message would be
 * counted as unread to themselves — the room would show a stuck "1" the instant
 * they sent. MessageService::advanceSenderReadCursor (run inside the same hot
 * send transaction) is the fix; this suite is its regression guard.
 *
 * The tests drive the REAL production code, not a re-implementation:
 *   - real seq allocation via NextServerSeqService::next()
 *   - the real cursor SQL via MessageService::advanceSenderReadCursor()
 *   - the real unread formula via SyncRoomResource AND the live
 *     SyncController::rooms() endpoint
 *
 * Reuses the Phase 2 MariaDB harness (real Phase 1 migrations) so server_seq /
 * last_seq / chat_room_members semantics match production, not a SQLite shim.
 */
class SenderReadCursorAdvanceTest extends Phase2SyncTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Force the DB seq driver so allocation matches the live send path and the
        // test never depends on a running Redis.
        config(['chat.seq.driver' => 'db']);

        // The harness builds `users` by hand (Phase2SyncTestCase) without the
        // SoftDeletes `deleted_at` column that the real User model declares, so the
        // SyncController::rooms() eager-load of userOne/userTwo would 1054 on
        // `users.deleted_at`. Add it here, scoped to this class's run, so the live
        // endpoint assertion exercises the true rooms() path. Idempotent.
        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function ($table) {
                $table->softDeletes();
            });
        }

        // SyncRoomResource renders the 1:1 peer header via $peer->profile->avatar,
        // so the real resource/endpoint path lazy-loads `profiles`. The harness does
        // not build it; create a minimal one so the production resource renders
        // end-to-end instead of being short-circuited. Non-protected -> dropped on
        // teardown. Idempotent.
        if (! Schema::hasTable('profiles')) {
            Schema::create('profiles', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('avatar')->nullable();
                $table->timestamps();
            });
        }
    }

    private function messageService(): MessageService
    {
        return app(MessageService::class);
    }

    private function controller(): SyncController
    {
        return new SyncController();
    }

    /**
     * Persist a message for $userId in $roomId exactly as the send path does:
     * allocate the room's next server_seq (real NextServerSeqService, which bumps
     * chat_rooms.last_seq), insert the row at that seq, then advance the SENDER's
     * read cursor via the production MessageService::advanceSenderReadCursor.
     *
     * Returns the allocated server_seq.
     */
    private function sendAs(int $roomId, int $userId, string $body): int
    {
        $seq = app(NextServerSeqService::class)->next($roomId);

        $messageId = DB::table('chat_messages')->insertGetId([
            'chat_room_id' => $roomId,
            'user_id'      => $userId,
            'message'      => $body,
            'server_seq'   => $seq,
            'kind'         => 'user',
            'type'         => 'message',
            'status'       => 'sended',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('chat_rooms')->where('id', $roomId)->update([
            'last_message_id' => $messageId,
            'last_message_at' => now(),
        ]);

        // The exact production method that runs inside the send transaction.
        $advance = new ReflectionMethod(MessageService::class, 'advanceSenderReadCursor');
        $advance->setAccessible(true);
        $advance->invoke($this->messageService(), $roomId, $userId, $seq);

        return $seq;
    }

    /**
     * Resolve the caller's membership row exactly as SyncController::rooms does
     * (active/muted only) and inject it so SyncRoomResource computes unread.
     */
    private function unreadCountFor(int $roomId, int $callerId): int
    {
        $room = ChatRoom::query()
            ->with(['lastMessage', 'group', 'userOne', 'userTwo'])
            ->findOrFail($roomId);

        $membership = ChatRoomMember::query()
            ->where('chat_room_id', $roomId)
            ->where('user_id', $callerId)
            ->first();

        $room->setAttribute('my_membership', $membership);

        $data = (new SyncRoomResource($room))->toArray($this->requestAs($callerId));

        return $data['unread_count'];
    }

    /** Fetch the caller's row for a room from the live SyncController::rooms(). */
    private function roomRowFromEndpoint(int $roomId, int $callerId): ?array
    {
        $request = $this->requestAs($callerId);

        // The controller returns SyncRoomResource::collection() inside a
        // JsonResponse; Laravel serializes that against the GLOBAL request()
        // (which the HTTP kernel binds in production), and SyncRoomResource reads
        // $request->user() to resolve the 1:1 peer. Set the SAME user resolver on
        // the global request so the resource sees the authenticated caller, exactly
        // as the kernel would, without rebinding the controller's request object.
        $resolver = $request->getUserResolver();
        app('request')->setUserResolver($resolver);

        $body = $this->decode($this->controller()->rooms($request));

        foreach ($body['data'] as $row) {
            if ($row['room_id'] === $roomId) {
                return $row;
            }
        }

        return null;
    }

    // ---------------------------------------------------------------------
    // Core: a room whose last message is MINE shows 0 unread (to me).
    // ---------------------------------------------------------------------

    public function test_sending_advances_my_read_cursor_to_my_message_seq(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $room = $this->makeRoom(1, 2);
        $this->addMember($room, 1);
        $this->addMember($room, 2);

        $seq = $this->sendAs($room, 1, 'mine');

        $this->assertSame(1, $seq, 'first message in the room is server_seq 1');
        $this->assertSame(
            1,
            (int) DB::table('chat_room_members')
                ->where('chat_room_id', $room)->where('user_id', 1)
                ->value('last_read_seq'),
            'the sender read cursor must advance to the seq they just sent'
        );
        $this->assertSame(
            1,
            (int) DB::table('chat_rooms')->where('id', $room)->value('last_seq'),
            'last_seq is bumped by the seq allocator on send'
        );
    }

    public function test_room_whose_last_message_is_mine_shows_zero_unread(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $room = $this->makeRoom(1, 2);
        $this->addMember($room, 1);
        $this->addMember($room, 2);

        // Peer sends two, then I send the last one.
        $this->sendAs($room, 2, 'p1'); // seq 1
        $this->sendAs($room, 2, 'p2'); // seq 2
        // I had NOT read the peer's two -> 2 unread before I reply.
        $this->assertSame(2, $this->unreadCountFor($room, 1));

        $this->sendAs($room, 1, 'reply'); // seq 3, mine

        // last message is mine -> my cursor jumped past everything -> 0 unread.
        $this->assertSame(0, $this->unreadCountFor($room, 1));
        $this->assertSame(
            3,
            (int) DB::table('chat_room_members')
                ->where('chat_room_id', $room)->where('user_id', 1)
                ->value('last_read_seq')
        );
    }

    public function test_zero_unread_is_visible_through_the_live_rooms_endpoint(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $room = $this->makeRoom(1, 2);
        $this->addMember($room, 1);
        $this->addMember($room, 2);

        $this->sendAs($room, 2, 'hi');   // seq 1, peer
        $this->sendAs($room, 1, 'mine'); // seq 2, mine

        $row = $this->roomRowFromEndpoint($room, 1);

        $this->assertNotNull($row, 'the room must appear in the sender rooms list');
        $this->assertSame(2, $row['last_seq']);
        $this->assertSame(2, $row['my_last_read_seq']);
        $this->assertSame(0, $row['unread_count'], 'a room whose last message is mine shows 0 unread on the endpoint');
    }

    // ---------------------------------------------------------------------
    // The peer is NOT over-suppressed: they still see my message as unread.
    // ---------------------------------------------------------------------

    public function test_peer_still_sees_my_message_as_unread(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $room = $this->makeRoom(1, 2);
        $this->addMember($room, 1);
        $this->addMember($room, 2);

        $this->sendAs($room, 1, 'mine'); // seq 1

        // Advancing MY cursor must not touch the peer's cursor.
        $this->assertSame(0, $this->unreadCountFor($room, 1), 'sender sees 0');
        $this->assertSame(1, $this->unreadCountFor($room, 2), 'peer sees 1 unread');
        $this->assertSame(
            0,
            (int) DB::table('chat_room_members')
                ->where('chat_room_id', $room)->where('user_id', 2)
                ->value('last_read_seq'),
            'peer cursor untouched by the sender advance'
        );
    }

    // ---------------------------------------------------------------------
    // Monotonic: a stale/late advance never rewinds the cursor.
    // ---------------------------------------------------------------------

    public function test_cursor_is_monotonic_and_never_rewinds(): void
    {
        $this->seedUser(1);
        $room = $this->makeRoom(1, null);
        // Already-read further than an old in-flight send.
        $this->addMember($room, 1, ['last_read_seq' => 5]);

        $advance = new ReflectionMethod(MessageService::class, 'advanceSenderReadCursor');
        $advance->setAccessible(true);
        // A late/out-of-order advance for an older seq must NOT move it back.
        $advance->invoke($this->messageService(), $room, 1, 3);

        $this->assertSame(
            5,
            (int) DB::table('chat_room_members')
                ->where('chat_room_id', $room)->where('user_id', 1)
                ->value('last_read_seq'),
            'GREATEST guard keeps the cursor monotonic'
        );
    }

    // ---------------------------------------------------------------------
    // Legacy 1:1 room with no member row yet: the advance creates one.
    // ---------------------------------------------------------------------

    public function test_advance_creates_member_row_for_legacy_room(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        // Legacy room: participants on the columns, but NO chat_room_members row
        // for the sender (predates the unified membership model).
        $room = $this->makeRoom(1, 2);

        $this->assertSame(
            0,
            DB::table('chat_room_members')->where('chat_room_id', $room)->where('user_id', 1)->count()
        );

        $seq = $this->sendAs($room, 1, 'first'); // seq 1

        $member = DB::table('chat_room_members')
            ->where('chat_room_id', $room)->where('user_id', 1)->first();

        $this->assertNotNull($member, 'the advance must firstOrCreate the missing member row');
        $this->assertSame($seq, (int) $member->last_read_seq);
        $this->assertSame('active', $member->status);
    }

    public function test_multiple_sends_keep_cursor_at_my_latest(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $room = $this->makeRoom(1, 2);
        $this->addMember($room, 1);
        $this->addMember($room, 2);

        $this->sendAs($room, 1, 'a'); // 1
        $this->sendAs($room, 2, 'b'); // 2 (peer)
        $last = $this->sendAs($room, 1, 'c'); // 3 (mine, last)

        $this->assertSame(3, $last);
        $this->assertSame(0, $this->unreadCountFor($room, 1));
        $this->assertSame(
            3,
            (int) DB::table('chat_room_members')
                ->where('chat_room_id', $room)->where('user_id', 1)
                ->value('last_read_seq')
        );
    }
}

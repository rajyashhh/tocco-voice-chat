<?php

namespace Modules\Chat\Tests\Phase6;

use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Chat\Entities\ChatGroup;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Exceptions\GroupException;
use Modules\Chat\Http\Services\GroupService;
use Modules\Chat\Http\Services\GroupSystemEventService;
use Modules\Chat\Http\Services\MessageService;
use Tests\TestCase;

/**
 * Phase 6 gate items 3 + 4 — the GROUP MESSAGE SEND PATH end to end through the
 * REAL MessageService, plus the now-wired system-event broadcast.
 *
 * MessageService::handleMessage() is the documented send-path fork:
 *   - a group room (chat_rooms.type='group') runs GroupService::assertCanPost
 *     (the §5.2 posting gate) BEFORE the message earns its server_seq, so a
 *     non-member / muted / only_admins_post sender is rejected before persistence
 *     or any fan-out;
 *   - an authorized group send assigns server_seq, then dispatchBroadcast enqueues
 *     BroadcastGroupMessage (NOT BroadcastChatMessage);
 *   - a 1:1 room keeps the exact BroadcastChatMessage path (no regression).
 *
 * With QUEUE_CONNECTION=sync the dispatched job runs INLINE, and with
 * realtime_transport=centrifugo the real CentrifugoBroadcaster turns the fan-out
 * into a single HTTP /broadcast to the active members' user:#{id} channels — which
 * we assert with Http::fake. This proves the dispatch is actually wired (item 4 for
 * system events) and that a group message reaches every active member via one
 * broadcast (item 3).
 *
 * Schema is rich enough for the real ChatMessageResource (the payload builder) to
 * render: reacts / message_albums / message_replays / profiles in addition to the
 * Phase 1 group DDL.
 */
class GroupSendPathIntegrationTest extends TestCase
{
    protected string $migrationPath;

    protected array $phase1Files = [
        '2026_06_01_100001_add_offline_sync_to_chat_messages_table.php',
        '2026_06_01_100002_add_sequence_to_chat_rooms_table.php',
        '2026_06_01_100003_create_chat_room_members_table.php',
        '2026_06_01_100004_create_chat_groups_table.php',
        '2026_06_01_100005_create_chat_group_audit_logs_table.php',
    ];

    protected array $protectedTables = [
        'settings', 'languages', 'configs', 'app_features',
        'web_settings', 'user_settings', 'chat_settings',
    ];

    private const API_URL = 'http://centrifugo.test:8000/api';

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'sync']);
        config(['chat.seq.driver' => 'db']);

        // Drive the REAL Centrifugo broadcaster as the active connection so the
        // group fan-out becomes an observable HTTP /broadcast call.
        config(['broadcasting.realtime_transport' => 'centrifugo']);
        config(['broadcasting.default' => 'centrifugo']);
        config(['broadcasting.connections.centrifugo' => [
            'driver'  => 'centrifugo',
            'api_url' => self::API_URL,
            'api_key' => 'test-key',
            'timeout' => 3,
            'verify'  => false,
        ]]);

        // Fake Centrifugo from the start so fixture-time fan-outs (createGroup /
        // addMembers system events) never hit the network. Tests that need a clean
        // recorded set re-arm Http::fake() right before the observed action.
        $this->fakeCentrifugo();

        $this->migrationPath = module_path('Chat', 'Database/Migrations') . DIRECTORY_SEPARATOR;
        $this->buildSchema();
        $this->applyPhase1Migrations();
    }

    /**
     * Apply the Phase 1 migrations idempotently: the create-style migrations
     * (chat_room_members / chat_groups / chat_group_audit_logs) call
     * Schema::create() unconditionally, so drop each owned table engine-direct
     * immediately before its up() to stay 1050-safe even if a trace survived
     * (gate item 2). Column/index migrations are already internally guarded.
     */
    private function applyPhase1Migrations(): void
    {
        $createdTables = [
            '2026_06_01_100003_create_chat_room_members_table.php'      => 'chat_room_members',
            '2026_06_01_100004_create_chat_groups_table.php'           => 'chat_groups',
            '2026_06_01_100005_create_chat_group_audit_logs_table.php' => 'chat_group_audit_logs',
        ];

        foreach ($this->phase1Files as $file) {
            if (isset($createdTables[$file])) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::statement("DROP TABLE IF EXISTS `{$createdTables[$file]}`");
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
            (require $this->migrationPath . $file)->up();
        }
    }

    protected function tearDown(): void
    {
        $this->dropAllTables();
        parent::tearDown();
    }

    // --- schema ----------------------------------------------------------

    private function buildSchema(): void
    {
        $this->dropAllTables();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('uuid')->nullable();
            $t->tinyInteger('online')->default(0);
            $t->string('current_room_chat')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('profiles', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('avatar')->nullable();
            $t->integer('gender')->nullable();
            $t->timestamps();
        });
        Schema::create('chat_rooms', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('user_id2')->nullable();
            $t->string('type')->default('friends');
            $t->dateTime('user_1_deleted')->nullable();
            $t->dateTime('user_2_deleted')->nullable();
            $t->timestamps();
        });
        Schema::create('chat_messages', function ($t) {
            $t->id();
            $t->unsignedBigInteger('chat_room_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('message')->nullable();
            $t->string('type')->default('message');
            $t->string('status')->default('sended');
            $t->dateTime('user_1_deleted')->nullable();
            $t->dateTime('user_2_deleted')->nullable();
            $t->integer('duration')->nullable();
            $t->timestamps();
        });
        Schema::create('reacts', function ($t) {
            $t->id();
            $t->unsignedBigInteger('chat_room_id')->nullable();
            $t->unsignedBigInteger('chat_message_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('react')->nullable();
            $t->timestamps();
        });
        Schema::create('message_albums', function ($t) {
            $t->id();
            $t->unsignedBigInteger('chat_message_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('file')->nullable();
            $t->string('frame')->nullable();
            $t->string('type')->nullable();
            $t->timestamps();
        });
        Schema::create('message_replays', function ($t) {
            $t->id();
            $t->unsignedBigInteger('message_id')->nullable();
            $t->unsignedBigInteger('from_message_id')->nullable();
            $t->timestamps();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Deterministically drop every non-protected base table via SHOW TABLES.
     *
     * information_schema.tables lags the data-dictionary cache on MariaDB 10.4
     * immediately after DDL, leaving a partial schema that breaks the next
     * setUp() (1050/1146/errno 150). SHOW FULL TABLES reads the storage engine
     * directly and is consistent post-DDL.
     */
    private function dropAllTables(): void
    {
        $rows = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');

        $tables = [];
        foreach ($rows as $row) {
            $vars = array_values((array) $row);
            $name = $vars[0] ?? null;
            if ($name !== null && !in_array($name, $this->protectedTables, true)) {
                $tables[] = $name;
            }
        }

        if (empty($tables)) {
            return;
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    // --- fixtures --------------------------------------------------------

    private function seedUser(int $id, ?string $name = null): User
    {
        DB::table('users')->insert(['id' => $id, 'name' => $name ?? "user{$id}", 'uuid' => "uuid-{$id}"]);

        return User::withoutAppends()->find($id);
    }

    private function groupService(): GroupService
    {
        return $this->app->make(GroupService::class);
    }

    private function messageService(): MessageService
    {
        return $this->app->make(MessageService::class);
    }

    /** Create a group with the owner + the given extra active members. */
    private function makeGroupWithMembers(User $owner, array $memberIds, array $meta = []): ChatGroup
    {
        $group = $this->groupService()->createGroup($owner, array_merge(['name' => 'Send Group'], $meta));
        foreach ($memberIds as $id) {
            $this->seedUser($id);
        }
        if (!empty($memberIds)) {
            $this->groupService()->addMembers($owner, $group, $memberIds);
        }

        return $group;
    }

    /** Bind a real request as the active request the resources resolve. */
    private function bindRequest(User $sender): Request
    {
        $request = Request::create('/Chat-Message', 'POST', []);
        $request->setUserResolver(fn () => $sender);
        \Illuminate\Support\Facades\Auth::setUser($sender);
        $this->app->instance('request', $request);

        return $request;
    }

    /** Persist a plain user message row in a room (the row handleMessage operates on). */
    private function insertMessageRow(int $roomId, int $userId, string $body): ChatMessage
    {
        return ChatMessage::create([
            'chat_room_id' => $roomId,
            'user_id'      => $userId,
            'message'      => $body,
            'type'         => 'text',
            'status'       => 'sended',
        ]);
    }

    private function fakeCentrifugo(): void
    {
        Http::fake([self::API_URL . '/*' => Http::response(['result' => (object) []], 200)]);
    }

    // --- gate item 3a: assertCanPost blocks unauthorized in the SEND path ----

    public function test_send_path_blocks_a_non_member_before_persist_or_broadcast(): void
    {
        $owner   = $this->seedUser(1);
        $group   = $this->makeGroupWithMembers($owner, [2]);
        $stranger = $this->seedUser(99);
        $room    = ChatRoom::find($group->chat_room_id);

        $this->fakeCentrifugo();
        $request = $this->bindRequest($stranger);

        // The stranger never gets a server_seq or a broadcast: assertCanPost fires
        // first. We pass a row keyed to the room but the gate rejects on membership.
        $msg = $this->insertMessageRow($room->id, 99, 'sneaky');

        try {
            $this->messageService()->handleMessage($request, $msg, $stranger, null, $room);
            $this->fail('a non-member must be blocked by the posting gate');
        } catch (GroupException $e) {
            $this->assertSame(403, $e->getStatus());
        }

        // No server_seq assigned, no fan-out attempted.
        $this->assertNull($msg->fresh()->server_seq, 'blocked send must not allocate a server_seq');
        Http::assertNothingSent();
    }

    public function test_send_path_blocks_a_muted_member(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroupWithMembers($owner, [2]);
        // mute user 2 via the real service.
        $this->groupService()->muteMember($owner, $group, 2);
        $room = ChatRoom::find($group->chat_room_id);

        $this->fakeCentrifugo();
        $muted = User::find(2);
        $request = $this->bindRequest($muted);
        $msg = $this->insertMessageRow($room->id, 2, 'muted speak');

        $this->expectException(GroupException::class);
        try {
            $this->messageService()->handleMessage($request, $msg, $muted, null, $room);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_send_path_blocks_member_when_only_admins_post(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroupWithMembers($owner, [2], ['only_admins_post' => true]);
        $room  = ChatRoom::find($group->chat_room_id);

        $this->fakeCentrifugo();
        $member = User::find(2);
        $request = $this->bindRequest($member);
        $msg = $this->insertMessageRow($room->id, 2, 'members hush');

        try {
            $this->messageService()->handleMessage($request, $msg, $member, null, $room);
            $this->fail('only_admins_post must block a plain member in the send path');
        } catch (GroupException $e) {
            $this->assertSame(403, $e->getStatus());
        }
        Http::assertNothingSent();
    }

    // --- gate item 3b: authorized group send -> ONE /broadcast to user:#{id} --

    public function test_authorized_group_send_broadcasts_once_to_active_members_user_channels(): void
    {
        $owner = $this->seedUser(1);
        // owner(1) + members 2,3 active; 4 muted-out via 'left' to prove exclusion.
        $group = $this->makeGroupWithMembers($owner, [2, 3]);
        $this->seedUser(4);
        $this->groupService()->addMembers($owner, $group, [4]);
        $this->groupService()->removeMember($owner, $group, 4); // user 4 now 'left'
        $room = ChatRoom::find($group->chat_room_id);

        $this->fakeCentrifugo();
        $request = $this->bindRequest($owner);
        $msg = $this->insertMessageRow($room->id, 1, 'hello group');

        $this->messageService()->handleMessage($request, $msg, $owner, null, $room);

        // server_seq assigned (the send path ran fully).
        $this->assertNotNull($msg->fresh()->server_seq);

        // Exactly ONE Centrifugo HTTP call (one chunk) — the group fan-out is a
        // single broadcast, not one call per member.
        Http::assertSentCount(1);
        Http::assertSent(function (HttpRequest $r) {
            if ($r->url() !== self::API_URL . '/broadcast') {
                return false;
            }
            $channels = $r->data()['channels'] ?? [];
            sort($channels);

            // sender (owner 1) excluded; active members 2 + 3 mapped to user:#{id};
            // the 'left' user 4 excluded.
            return $r->method() === 'POST'
                && $r->hasHeader('X-API-Key', 'test-key')
                && $channels === ['user:#2', 'user:#3'];
        });
    }

    public function test_authorized_group_send_payload_carries_server_seq_and_event(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroupWithMembers($owner, [2]);
        $room  = ChatRoom::find($group->chat_room_id);

        $this->fakeCentrifugo();
        $request = $this->bindRequest($owner);
        $msg = $this->insertMessageRow($room->id, 1, 'payload check');

        $this->messageService()->handleMessage($request, $msg, $owner, null, $room);

        $expectedSeq = (int) $msg->fresh()->server_seq;
        Http::assertSent(function (HttpRequest $r) use ($expectedSeq) {
            $data = $r->data()['data'] ?? [];

            return ($data['event'] ?? null) === 'getGroupMessageBloc'
                && (int) ($data['payload']['server_seq'] ?? 0) === $expectedSeq
                && (int) ($data['payload']['user_id'] ?? 0) === 1;
        });
    }

    // --- gate item 3c: 1:1 send still uses BroadcastChatMessage (no regression) --

    public function test_one_to_one_send_does_not_use_group_event(): void
    {
        $a = $this->seedUser(1);
        $b = $this->seedUser(2);
        $roomId = DB::table('chat_rooms')->insertGetId([
            'user_id' => 1, 'user_id2' => 2, 'type' => 'friends', 'last_seq' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $room = ChatRoom::find($roomId);

        $this->fakeCentrifugo();
        $request = $this->bindRequest($a);
        $msg = $this->insertMessageRow($roomId, 1, 'one to one');

        $this->messageService()->handleMessage($request, $msg, $a, $b, $room);

        $this->assertNotNull($msg->fresh()->server_seq);

        // The 1:1 path is BroadcastChatMessage (Conversation/Chat/OpenChat events),
        // never the group event. No request may carry getGroupMessageBloc.
        Http::assertSent(fn (HttpRequest $r) => true); // at least one broadcast happened
        Http::assertNotSent(function (HttpRequest $r) {
            $data = $r->data()['data'] ?? [];

            return ($data['event'] ?? null) === 'getGroupMessageBloc';
        });
    }

    // --- gate item 4: system-event broadcast is actually wired (dispatch sent) --

    public function test_system_event_is_broadcast_to_active_members(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroupWithMembers($owner, [2, 3]);
        $room  = ChatRoom::find($group->chat_room_id);

        // From here on, only the NEXT system event's broadcast is observed.
        $this->fakeCentrifugo();
        $this->bindRequest($owner);

        // Promote user 2 -> a member_promoted system event is recorded AND its
        // BroadcastGroupMessage is dispatched (and runs inline under sync).
        $this->groupService()->promote($owner, $group, 2);

        // The system event was persisted (kind=system) ...
        $ev = ChatMessage::where('chat_room_id', $room->id)
            ->where('kind', 'system')
            ->where('system_event', GroupSystemEventService::MEMBER_PROMOTED)
            ->first();
        $this->assertNotNull($ev, 'promote must persist a member_promoted system event');

        // ... and a broadcast carrying it actually went out to the active members'
        // user:#{id} channels (no sender exclusion for system events -> owner too).
        Http::assertSent(function (HttpRequest $r) {
            if ($r->url() !== self::API_URL . '/broadcast') {
                return false;
            }
            $channels = $r->data()['channels'] ?? [];
            sort($channels);
            $data = $r->data()['data'] ?? [];

            return ($data['payload']['kind'] ?? null) === 'system'
                && ($data['payload']['system_event'] ?? null) === GroupSystemEventService::MEMBER_PROMOTED
                && $channels === ['user:#1', 'user:#2', 'user:#3'];
        });
    }
}

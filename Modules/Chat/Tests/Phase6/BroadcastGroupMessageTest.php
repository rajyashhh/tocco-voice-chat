<?php

namespace Modules\Chat\Tests\Phase6;

use App\Broadcasting\Centrifugo\ChannelMapper;
use App\Broadcasting\CentrifugoBroadcaster;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Chat\Jobs\BroadcastGroupMessage;
use Tests\TestCase;

/**
 * Phase 6 §5.4 — group message fan-out job (BroadcastGroupMessage).
 *
 * The job is the recipient-gathering + chunked single-broadcast seam: it reads the
 * active chat_room_members for the group's unified room, excludes the sender, maps
 * each to the legacy `user-{id}` channel and hands the whole chunk to
 * Broadcast::connection()->broadcast() in ONE call (not one call per member — the
 * AllOpeningRoomsZegoRequest anti-pattern is exactly what this avoids).
 *
 * We assert the recipient set and the single-call shape by binding a recording
 * stand-in as the active broadcast connection, so the test is independent of which
 * transport (pusher/centrifugo) the realtime_transport flag selects. The last test
 * additionally proves that with the flag at its default ('pusher') the job never
 * touches the Centrifugo HTTP API.
 *
 * A small real schema (users + chat_rooms + chat_room_members via the Phase 1
 * migration) backs the membership query — the job's recipient gathering is a real
 * DB read, mirroring the Phase 3 integration harness rather than mocking the model.
 */
class BroadcastGroupMessageTest extends TestCase
{
    protected string $migrationPath;

    /** Phase 1 migration that creates chat_room_members (the recipient source). */
    protected array $phase1Files = [
        '2026_06_01_100003_create_chat_room_members_table.php',
    ];

    protected array $protectedTables = [
        'settings', 'languages', 'configs', 'app_features',
        'web_settings', 'user_settings', 'chat_settings',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrationPath = module_path('Chat', 'Database/Migrations') . DIRECTORY_SEPARATOR;
        $this->buildSchema();

        // Idempotent apply: 100003 calls Schema::create('chat_room_members')
        // unconditionally, so drop any leftover engine-direct first (gate item 2).
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('DROP TABLE IF EXISTS `chat_room_members`');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        foreach ($this->phase1Files as $file) {
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

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('chat_rooms', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('user_id2')->nullable();
            $table->string('type')->default('friends');
            $table->unsignedBigInteger('last_seq')->default(0);
            $table->timestamps();
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

    private function makeGroupRoom(): int
    {
        return DB::table('chat_rooms')->insertGetId([
            'type'       => 'group',
            'last_seq'   => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addMember(int $roomId, int $userId, string $status = 'active', string $role = 'member'): void
    {
        DB::table('users')->insertOrIgnore(['id' => $userId, 'name' => "u{$userId}"]);
        DB::table('chat_room_members')->insert([
            'chat_room_id' => $roomId,
            'user_id'      => $userId,
            'role'         => $role,
            'status'       => $status,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    /**
     * Bind a recording stand-in as the active broadcast connection so the job's
     * Broadcast::connection()->broadcast(...) lands here regardless of transport.
     */
    private function recordBroadcaster(): RecordingGroupBroadcaster
    {
        $rec = new RecordingGroupBroadcaster();
        Broadcast::shouldReceive('connection')->andReturn($rec);

        return $rec;
    }

    // --- tests -----------------------------------------------------------

    public function test_fans_out_one_broadcast_to_all_active_members(): void
    {
        $room = $this->makeGroupRoom();
        $this->addMember($room, 1, 'active', 'owner'); // sender
        $this->addMember($room, 2, 'active');
        $this->addMember($room, 3, 'active');

        $rec = $this->recordBroadcaster();

        (new BroadcastGroupMessage($room, ['id' => 99, 'message' => 'hi'], senderId: 1))->handle();

        // Exactly one broadcast call (one chunk) for the whole group.
        $this->assertCount(1, $rec->calls);

        $channels = $rec->calls[0]['channels'];
        sort($channels);
        // sender (1) excluded; recipients mapped to legacy user-{id}.
        $this->assertSame(['user-2', 'user-3'], $channels);
        $this->assertSame('getGroupMessageBloc', $rec->calls[0]['event']);
        $this->assertSame(['id' => 99, 'message' => 'hi'], $rec->calls[0]['payload']);
    }

    public function test_excludes_non_active_members(): void
    {
        $room = $this->makeGroupRoom();
        $this->addMember($room, 1, 'active', 'owner'); // sender
        $this->addMember($room, 2, 'active');
        $this->addMember($room, 3, 'muted');
        $this->addMember($room, 4, 'banned');
        $this->addMember($room, 5, 'left');

        $rec = $this->recordBroadcaster();

        (new BroadcastGroupMessage($room, ['x' => 1], senderId: 1))->handle();

        $this->assertCount(1, $rec->calls);
        // Only the single ACTIVE non-sender member is targeted.
        $this->assertSame(['user-2'], $rec->calls[0]['channels']);
    }

    public function test_without_sender_targets_all_active_members(): void
    {
        $room = $this->makeGroupRoom();
        $this->addMember($room, 1, 'active', 'owner');
        $this->addMember($room, 2, 'active');

        $rec = $this->recordBroadcaster();

        // No sender id -> nobody excluded (e.g. system event fan-out).
        (new BroadcastGroupMessage($room, ['x' => 1]))->handle();

        $this->assertCount(1, $rec->calls);
        $channels = $rec->calls[0]['channels'];
        sort($channels);
        $this->assertSame(['user-1', 'user-2'], $channels);
    }

    public function test_no_broadcast_when_sender_is_only_active_member(): void
    {
        $room = $this->makeGroupRoom();
        $this->addMember($room, 1, 'active', 'owner'); // sender, alone

        $rec = $this->recordBroadcaster();

        (new BroadcastGroupMessage($room, ['x' => 1], senderId: 1))->handle();

        // Empty channel set -> the job must NOT call broadcast at all.
        $this->assertCount(0, $rec->calls);
    }

    public function test_centrifugo_path_produces_one_broadcast_call_targeting_user_channels(): void
    {
        // Drive the job through the REAL CentrifugoBroadcaster bound as the active
        // connection (what the realtime_transport flag selects under 'centrifugo'),
        // faking only the Centrifugo HTTP API. This proves the §5.4 contract end to
        // end: a single group message becomes exactly ONE /broadcast HTTP request
        // whose channels are the active members mapped to user:#{id}.
        $apiUrl = 'http://centrifugo.test:8000/api';
        $broadcaster = new CentrifugoBroadcaster(
            ['api_url' => $apiUrl, 'api_key' => 'k', 'timeout' => 3, 'verify' => false],
            new ChannelMapper()
        );
        Broadcast::shouldReceive('connection')->andReturn($broadcaster);

        Http::fake([$apiUrl . '/*' => Http::response(['result' => (object) []], 200)]);

        $room = $this->makeGroupRoom();
        $this->addMember($room, 1, 'active', 'owner'); // sender, excluded
        $this->addMember($room, 2, 'active');
        $this->addMember($room, 3, 'active');

        (new BroadcastGroupMessage($room, ['id' => 7, 'message' => 'hi'], senderId: 1))->handle();

        // Exactly one HTTP call to the Centrifugo /broadcast endpoint (one chunk).
        Http::assertSentCount(1);
        Http::assertSent(function (HttpRequest $request) use ($apiUrl) {
            $body = $request->data();
            $channels = $body['channels'] ?? [];
            sort($channels);

            return $request->url() === $apiUrl . '/broadcast'
                && $request->method() === 'POST'
                && $request->hasHeader('X-API-Key', 'k')
                // active non-sender members mapped user-{id} -> user:#{id}
                && $channels === ['user:#2', 'user:#3']
                && $body['data']['event'] === 'getGroupMessageBloc'
                && $body['data']['payload'] === ['id' => 7, 'message' => 'hi'];
        });
    }

    public function test_centrifugo_single_recipient_uses_publish_endpoint(): void
    {
        // One recipient -> CentrifugoBroadcaster collapses to a single /publish call
        // (still one HTTP request, no fan-out waste).
        $apiUrl = 'http://centrifugo.test:8000/api';
        $broadcaster = new CentrifugoBroadcaster(
            ['api_url' => $apiUrl, 'api_key' => 'k', 'timeout' => 3, 'verify' => false],
            new ChannelMapper()
        );
        Broadcast::shouldReceive('connection')->andReturn($broadcaster);

        Http::fake([$apiUrl . '/*' => Http::response(['result' => (object) []], 200)]);

        $room = $this->makeGroupRoom();
        $this->addMember($room, 1, 'active', 'owner'); // sender
        $this->addMember($room, 2, 'active');          // sole recipient

        (new BroadcastGroupMessage($room, ['x' => 1], senderId: 1))->handle();

        Http::assertSentCount(1);
        Http::assertSent(function (HttpRequest $request) use ($apiUrl) {
            return $request->url() === $apiUrl . '/publish'
                && $request->data()['channel'] === 'user:#2';
        });
    }

    public function test_does_not_hit_centrifugo_when_transport_is_pusher(): void
    {
        config(['broadcasting.realtime_transport' => 'pusher']);

        // Any Centrifugo HTTP call would be recorded here; with the default
        // transport the group fan-out must never reach the Centrifugo API.
        Http::fake();
        Log::spy();

        $room = $this->makeGroupRoom();
        $this->addMember($room, 1, 'active', 'owner');
        $this->addMember($room, 2, 'active');

        // Use the real broadcasting manager (NOT the recording stand-in) so the
        // active connection is whatever the flag selects. With 'pusher' the
        // Centrifugo HTTP seam must stay untouched.
        (new BroadcastGroupMessage($room, ['x' => 1], senderId: 1))->handle();

        Http::assertNothingSent();
    }
}

/**
 * Records broadcast() calls so the recipient set + single-call fan-out shape can
 * be asserted independently of the underlying transport.
 */
class RecordingGroupBroadcaster implements BroadcasterContract
{
    public array $calls = [];

    public function broadcast(array $channels, $event, array $payload = [])
    {
        $this->calls[] = compact('channels', 'event', 'payload');
    }

    public function auth($request) { return null; }
    public function validAuthenticationResponse($request, $result) { return $result; }
    public function channel($channel, $callback, $options = []) { return $this; }
}

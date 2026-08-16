<?php

namespace Modules\Chat\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Chat\Http\Services\NextServerSeqService;

class Phase1SchemaAndSeqTest extends Phase1MigrationTestCase
{
    // ---------------------------------------------------------------------
    // (a) Migrations apply (up) then revert (down) cleanly.
    // ---------------------------------------------------------------------

    public function test_all_phase1_migrations_run_up(): void
    {
        $this->migrateUpAll();

        // New chat_messages columns exist after up().
        foreach ([
            'client_uuid', 'server_seq', 'kind', 'system_event', 'system_meta',
            'reply_to_id', 'edited_at', 'delivered_at', 'read_at',
        ] as $col) {
            $this->assertTrue(
                Schema::hasColumn('chat_messages', $col),
                "chat_messages.$col should exist after up()"
            );
        }

        // New chat_rooms columns exist.
        foreach (['last_seq', 'last_message_id', 'last_message_at'] as $col) {
            $this->assertTrue(Schema::hasColumn('chat_rooms', $col), "chat_rooms.$col should exist after up()");
        }

        // New tables exist.
        $this->assertTrue(Schema::hasTable('chat_room_members'));
        $this->assertTrue(Schema::hasTable('chat_groups'));
        $this->assertTrue(Schema::hasTable('chat_group_audit_logs'));

        // Unique / index objects from the message migration are present.
        $this->assertTrue($this->indexExists('chat_messages', 'uq_msg_room_client'));
        $this->assertTrue($this->indexExists('chat_messages', 'uq_msg_room_seq'));
        $this->assertTrue($this->indexExists('chat_messages', 'idx_msg_room_id'));

        // Hygiene unique indexes are present.
        $this->assertTrue($this->indexExists('block_users', 'uq_block'));
        $this->assertTrue($this->indexExists('reacts', 'uq_react'));
        $this->assertTrue($this->indexExists('pin_to_tops', 'uq_pin'));

        // message widened to TEXT.
        $this->assertSame('text', $this->columnDataType('chat_messages', 'message'));
    }

    /**
     * Every Phase 1 migration must reverse cleanly. Run the full suite up(),
     * then down() in reverse, and assert the schema returns to its pre-migration
     * state.
     *
     * Historical note: 100001 and 100006 ADD composite unique indexes whose
     * leftmost column equals a pre-existing single-column FK (e.g.
     * uq_msg_room_client(chat_room_id, client_uuid)). Creating such an index
     * makes MariaDB silently drop the FK's auto-index, because the composite
     * now covers the FK. A naive down() that just drops the uniques then fails
     * with errno 1553 — the last covering index cannot be removed while the FK
     * exists. Both down() methods now restore an explicit single-column
     * FK-covering index BEFORE dropping the uniques, so the rollback is clean
     * and idempotent on MariaDB.
     */
    public function test_reversible_migrations_down_cleanly(): void
    {
        $this->migrateUpAll();

        $this->migrateDownAll();

        // New tables removed.
        $this->assertFalse(Schema::hasTable('chat_group_audit_logs'));
        $this->assertFalse(Schema::hasTable('chat_groups'));
        $this->assertFalse(Schema::hasTable('chat_room_members'));

        // chat_rooms sequence columns removed.
        foreach (['last_seq', 'last_message_id', 'last_message_at'] as $col) {
            $this->assertFalse(Schema::hasColumn('chat_rooms', $col));
        }

        // chat_messages offline-sync columns removed.
        foreach ([
            'client_uuid', 'server_seq', 'kind', 'system_event', 'system_meta',
            'reply_to_id', 'edited_at', 'delivered_at', 'read_at',
        ] as $col) {
            $this->assertFalse(Schema::hasColumn('chat_messages', $col), "chat_messages.$col should be gone after down()");
        }

        // Indexes added by the migrations are gone.
        $this->assertFalse($this->indexExists('chat_messages', 'uq_msg_room_client'));
        $this->assertFalse($this->indexExists('chat_messages', 'uq_msg_room_seq'));
        $this->assertFalse($this->indexExists('chat_messages', 'idx_msg_room_id'));
        $this->assertFalse($this->indexExists('block_users', 'uq_block'));
        $this->assertFalse($this->indexExists('reacts', 'uq_react'));
        $this->assertFalse($this->indexExists('pin_to_tops', 'uq_pin'));

        // The single-column FK-covering indexes that up() displaced are restored,
        // leaving each table at its exact pre-migration index state.
        $this->assertTrue($this->indexExists('chat_messages', 'chat_messages_chat_room_id_foreign'));
        $this->assertTrue($this->indexExists('block_users', 'block_users_blocker_id_foreign'));
        $this->assertTrue($this->indexExists('reacts', 'reacts_chat_message_id_foreign'));
        $this->assertTrue($this->indexExists('pin_to_tops', 'pin_to_tops_user_id_foreign'));

        // message reverted from TEXT back to its original VARCHAR.
        $this->assertSame('varchar', $this->columnDataType('chat_messages', 'message'));
    }

    public function test_down_of_unique_index_migrations_is_reversible_on_mariadb(): void
    {
        // Regression guard for the errno-1553 rollback defect: up() then down()
        // on the two FK-prefixed-unique migrations must round-trip without error
        // and restore the displaced FK-covering single-column indexes.
        $msg = $this->migration('2026_06_01_100001_add_offline_sync_to_chat_messages_table.php');
        $msg->up();
        $msg->down(); // must NOT throw errno 1553

        $this->assertFalse($this->indexExists('chat_messages', 'uq_msg_room_client'));
        $this->assertFalse($this->indexExists('chat_messages', 'uq_msg_room_seq'));
        $this->assertFalse($this->indexExists('chat_messages', 'idx_msg_room_id'));
        $this->assertFalse(Schema::hasColumn('chat_messages', 'server_seq'));
        $this->assertTrue(
            $this->indexExists('chat_messages', 'chat_messages_chat_room_id_foreign'),
            'FK-covering index on chat_room_id must be restored after down()'
        );

        // The hygiene migration depends on uq-target tables only; exercise it on
        // the freshly-restored schema to prove its down() is reversible too.
        $hygiene = $this->migration('2026_06_01_100006_add_hygiene_unique_constraints.php');
        $hygiene->up();
        $hygiene->down(); // must NOT throw errno 1553

        $this->assertFalse($this->indexExists('block_users', 'uq_block'));
        $this->assertFalse($this->indexExists('reacts', 'uq_react'));
        $this->assertFalse($this->indexExists('pin_to_tops', 'uq_pin'));
        $this->assertTrue($this->indexExists('block_users', 'block_users_blocker_id_foreign'));
        $this->assertTrue($this->indexExists('reacts', 'reacts_chat_message_id_foreign'));
        $this->assertTrue($this->indexExists('pin_to_tops', 'pin_to_tops_user_id_foreign'));
    }

    public function test_message_migration_up_is_idempotent_on_rerun(): void
    {
        // The guards (hasColumn / indexExists) must make a second up() a no-op
        // rather than erroring on duplicate columns/indexes.
        $msgMigration = $this->migration('2026_06_01_100001_add_offline_sync_to_chat_messages_table.php');
        $msgMigration->up();
        $msgMigration->up(); // must not throw

        $this->assertTrue(Schema::hasColumn('chat_messages', 'server_seq'));
        $this->assertTrue($this->indexExists('chat_messages', 'uq_msg_room_seq'));
    }

    // ---------------------------------------------------------------------
    // (b) server_seq is atomic, gap-free and contiguous; no FOR UPDATE.
    // ---------------------------------------------------------------------

    public function test_next_server_seq_is_sequential_and_gap_free(): void
    {
        $this->migration('2026_06_01_100002_add_sequence_to_chat_rooms_table.php')->up();

        $this->seedUser(1);
        $roomId = DB::table('chat_rooms')->insertGetId([
            'user_id' => 1, 'type' => 'friends', 'created_at' => now(), 'updated_at' => now(),
        ]);

        config(['chat.seq.driver' => 'db']);
        $service = new NextServerSeqService();

        $seqs = [];
        for ($i = 0; $i < 50; $i++) {
            $seqs[] = $service->next($roomId);
        }

        // Strictly increasing 1..50 with no gaps and no duplicates.
        $this->assertSame(range(1, 50), $seqs, 'server_seq must be contiguous 1..50');
        $this->assertSame(count($seqs), count(array_unique($seqs)), 'no duplicate seq values');

        // Room counter mirrors the last allocation.
        $this->assertSame(50, (int) DB::table('chat_rooms')->where('id', $roomId)->value('last_seq'));
    }

    public function test_reserve_allocates_contiguous_block(): void
    {
        $this->migration('2026_06_01_100002_add_sequence_to_chat_rooms_table.php')->up();

        $this->seedUser(1);
        $roomId = DB::table('chat_rooms')->insertGetId([
            'user_id' => 1, 'type' => 'friends', 'created_at' => now(), 'updated_at' => now(),
        ]);

        config(['chat.seq.driver' => 'db']);
        $service = new NextServerSeqService();

        $first = $service->next($roomId);          // 1
        $block = $service->reserve($roomId, 10);    // 2..11
        $after = $service->next($roomId);           // 12

        $this->assertSame(1, $first);
        $this->assertSame(['from' => 2, 'to' => 11], $block);
        $this->assertSame(12, $after);
        // The reserved block is exactly 10 values wide (2..11 inclusive).
        $this->assertSame(10, $block['to'] - $block['from'] + 1);
    }

    public function test_reserve_clamps_count_to_minimum_one(): void
    {
        $this->migration('2026_06_01_100002_add_sequence_to_chat_rooms_table.php')->up();

        $this->seedUser(1);
        $roomId = DB::table('chat_rooms')->insertGetId([
            'user_id' => 1, 'type' => 'friends', 'created_at' => now(), 'updated_at' => now(),
        ]);

        config(['chat.seq.driver' => 'db']);
        $service = new NextServerSeqService();

        $range = $service->reserve($roomId, 0);
        $this->assertSame(['from' => 1, 'to' => 1], $range);
    }

    public function test_seq_is_isolated_per_room(): void
    {
        $this->migration('2026_06_01_100002_add_sequence_to_chat_rooms_table.php')->up();

        $this->seedUser(1);
        $roomA = DB::table('chat_rooms')->insertGetId(['user_id' => 1, 'type' => 'friends', 'created_at' => now(), 'updated_at' => now()]);
        $roomB = DB::table('chat_rooms')->insertGetId(['user_id' => 1, 'type' => 'friends', 'created_at' => now(), 'updated_at' => now()]);

        config(['chat.seq.driver' => 'db']);
        $service = new NextServerSeqService();

        $this->assertSame(1, $service->next($roomA));
        $this->assertSame(2, $service->next($roomA));
        $this->assertSame(1, $service->next($roomB)); // room B counter independent
        $this->assertSame(3, $service->next($roomA));
        $this->assertSame(2, $service->next($roomB));
    }

    public function test_seq_db_strategy_does_not_use_for_update(): void
    {
        // Guard against re-introducing SELECT ... FOR UPDATE (the FairLuck 504
        // incident). Capture the query log while allocating and assert none of
        // the statements contain "for update".
        $this->migration('2026_06_01_100002_add_sequence_to_chat_rooms_table.php')->up();

        $this->seedUser(1);
        $roomId = DB::table('chat_rooms')->insertGetId([
            'user_id' => 1, 'type' => 'friends', 'created_at' => now(), 'updated_at' => now(),
        ]);

        config(['chat.seq.driver' => 'db']);
        $service = new NextServerSeqService();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $service->next($roomId);
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertNotEmpty($log, 'expected the seq allocation to emit SQL');
        foreach ($log as $entry) {
            $this->assertStringNotContainsStringIgnoringCase(
                'for update',
                $entry['query'],
                'seq allocation must never use SELECT ... FOR UPDATE'
            );
        }
    }

    // ---------------------------------------------------------------------
    // (c) chat_room_members unique(room,user) prevents duplicates.
    // ---------------------------------------------------------------------

    public function test_chat_room_members_unique_prevents_duplicate_membership(): void
    {
        $this->migration('2026_06_01_100002_add_sequence_to_chat_rooms_table.php')->up();
        $this->migration('2026_06_01_100003_create_chat_room_members_table.php')->up();

        $this->seedUser(1);
        $roomId = DB::table('chat_rooms')->insertGetId([
            'user_id' => 1, 'type' => 'friends', 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('chat_room_members')->insert([
            'chat_room_id' => $roomId, 'user_id' => 1, 'role' => 'member', 'status' => 'active',
            'last_read_seq' => 0, 'last_delivered_seq' => 0, 'cleared_seq' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('chat_room_members')->insert([
            'chat_room_id' => $roomId, 'user_id' => 1, 'role' => 'admin', 'status' => 'active',
            'last_read_seq' => 0, 'last_delivered_seq' => 0, 'cleared_seq' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_chat_room_members_allows_distinct_users_same_room(): void
    {
        $this->migration('2026_06_01_100002_add_sequence_to_chat_rooms_table.php')->up();
        $this->migration('2026_06_01_100003_create_chat_room_members_table.php')->up();

        $this->seedUser(1);
        $this->seedUser(2);
        $roomId = DB::table('chat_rooms')->insertGetId([
            'user_id' => 1, 'user_id2' => 2, 'type' => 'friends', 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('chat_room_members')->insert([
            ['chat_room_id' => $roomId, 'user_id' => 1, 'role' => 'member', 'status' => 'active', 'last_read_seq' => 0, 'last_delivered_seq' => 0, 'cleared_seq' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['chat_room_id' => $roomId, 'user_id' => 2, 'role' => 'member', 'status' => 'active', 'last_read_seq' => 0, 'last_delivered_seq' => 0, 'cleared_seq' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertSame(2, (int) DB::table('chat_room_members')->where('chat_room_id', $roomId)->count());
    }

    // ---------------------------------------------------------------------
    // (d) backfill produces correct membership + contiguous server_seq.
    // ---------------------------------------------------------------------

    public function test_backfill_produces_membership_and_sequential_seq(): void
    {
        $this->migrateUpAll();

        $this->seedUser(1);
        $this->seedUser(2);
        $this->seedUser(3);

        // Room 1: a normal 1:1 room with two participants and three messages.
        $room1 = DB::table('chat_rooms')->insertGetId([
            'user_id' => 1, 'user_id2' => 2, 'type' => 'friends',
            'created_at' => '2025-01-01 10:00:00', 'updated_at' => now(),
        ]);
        // Room 2: a one-sided room (user_id2 NULL) with one message.
        $room2 = DB::table('chat_rooms')->insertGetId([
            'user_id' => 3, 'user_id2' => null, 'type' => 'friends',
            'created_at' => '2025-01-02 10:00:00', 'updated_at' => now(),
        ]);

        // Insert legacy messages (server_seq NULL) out of strict order to verify
        // backfill orders by id ASC.
        $m1 = DB::table('chat_messages')->insertGetId(['chat_room_id' => $room1, 'user_id' => 1, 'message' => 'a', 'status' => 'sended', 'created_at' => now(), 'updated_at' => now()]);
        $m2 = DB::table('chat_messages')->insertGetId(['chat_room_id' => $room1, 'user_id' => 2, 'message' => 'b', 'status' => 'sended', 'created_at' => now(), 'updated_at' => now()]);
        $m3 = DB::table('chat_messages')->insertGetId(['chat_room_id' => $room1, 'user_id' => 1, 'message' => 'c', 'status' => 'sended', 'created_at' => now(), 'updated_at' => now()]);
        $m4 = DB::table('chat_messages')->insertGetId(['chat_room_id' => $room2, 'user_id' => 3, 'message' => 'd', 'status' => 'sended', 'created_at' => now(), 'updated_at' => now()]);

        $exit = Artisan::call('chat:backfill-sequence-members', ['--message-chunk' => 2]);
        $this->assertSame(0, $exit);

        // --- server_seq: contiguous per room, ordered by id ---
        $this->assertSame(1, (int) DB::table('chat_messages')->where('id', $m1)->value('server_seq'));
        $this->assertSame(2, (int) DB::table('chat_messages')->where('id', $m2)->value('server_seq'));
        $this->assertSame(3, (int) DB::table('chat_messages')->where('id', $m3)->value('server_seq'));
        $this->assertSame(1, (int) DB::table('chat_messages')->where('id', $m4)->value('server_seq'));

        // chat_rooms.last_seq synced to each room's max.
        $this->assertSame(3, (int) DB::table('chat_rooms')->where('id', $room1)->value('last_seq'));
        $this->assertSame(1, (int) DB::table('chat_rooms')->where('id', $room2)->value('last_seq'));

        // --- membership: one row per non-null participant ---
        $room1Members = DB::table('chat_room_members')->where('chat_room_id', $room1)->pluck('user_id')->sort()->values()->all();
        $this->assertSame([1, 2], $room1Members);

        $room2Members = DB::table('chat_room_members')->where('chat_room_id', $room2)->pluck('user_id')->all();
        $this->assertSame([3], $room2Members);

        // role/status defaults applied.
        $sample = DB::table('chat_room_members')->where('chat_room_id', $room1)->where('user_id', 1)->first();
        $this->assertSame('member', $sample->role);
        $this->assertSame('active', $sample->status);
        // joined_at inherits the room's created_at.
        $this->assertStringStartsWith('2025-01-01', (string) $sample->joined_at);
    }

    public function test_backfill_is_idempotent_on_rerun(): void
    {
        $this->migrateUpAll();

        $this->seedUser(1);
        $this->seedUser(2);
        $room = DB::table('chat_rooms')->insertGetId([
            'user_id' => 1, 'user_id2' => 2, 'type' => 'friends', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $a = DB::table('chat_messages')->insertGetId(['chat_room_id' => $room, 'user_id' => 1, 'message' => 'a', 'status' => 'sended', 'created_at' => now(), 'updated_at' => now()]);
        $b = DB::table('chat_messages')->insertGetId(['chat_room_id' => $room, 'user_id' => 2, 'message' => 'b', 'status' => 'sended', 'created_at' => now(), 'updated_at' => now()]);

        Artisan::call('chat:backfill-sequence-members');
        Artisan::call('chat:backfill-sequence-members'); // second run must not duplicate or renumber

        // Membership count unchanged (insertOrIgnore against uq_room_user).
        $this->assertSame(2, (int) DB::table('chat_room_members')->where('chat_room_id', $room)->count());

        // seq unchanged: already-sequenced rows are skipped (whereNull guard).
        $this->assertSame(1, (int) DB::table('chat_messages')->where('id', $a)->value('server_seq'));
        $this->assertSame(2, (int) DB::table('chat_messages')->where('id', $b)->value('server_seq'));
        $this->assertSame(2, (int) DB::table('chat_rooms')->where('id', $room)->value('last_seq'));
    }

    // ---------------------------------------------------------------------
    // helpers
    // ---------------------------------------------------------------------

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function columnDataType(string $table, string $column): string
    {
        $database = DB::getDatabaseName();

        return (string) DB::table('information_schema.columns')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('data_type');
    }
}

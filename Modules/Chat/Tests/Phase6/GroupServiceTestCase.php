<?php

namespace Modules\Chat\Tests\Phase6;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Chat\Entities\ChatGroup;
use Modules\Chat\Entities\ChatGroupAuditLog;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoomMember;
use Modules\Chat\Http\Services\GroupService;
use Tests\TestCase;

/**
 * Base harness for the Phase 6 GROUP DOMAIN integration tests (plan §5.2/§5.4/§5.5).
 *
 * Like the Phase 3 SendPathIntegrationTestCase, this drives the REAL group write
 * path against a real MariaDB schema rather than mocking the models:
 *
 *   GroupService (createGroup / addMembers / removeMember / leave /
 *                 transferOwnership / promote / demote / mute / updateGroupMeta /
 *                 deleteGroup / joinViaInvite / assertCanPost / ...)
 *     -> GroupPolicy            (the authoritative §5.2 permission matrix)
 *     -> GroupSystemEventService -> NextServerSeqService  (real INSERTs into
 *                                  chat_messages with kind='system' + server_seq,
 *                                  and chat_group_audit_logs rows)
 *
 * The service graph is resolved from the container exactly as production wires it,
 * so the policy gate, the atomic members_count maintenance, the system-event
 * timeline write and the audit-trail write are all exercised end to end.
 *
 * Schema is built with the SAME Phase 1 migration objects that ship the group
 * tables (chat_room_members / chat_groups / chat_group_audit_logs) plus the
 * offline-sync columns on chat_messages and the sequence columns on chat_rooms —
 * i.e. the production DDL, not a hand-rolled approximation. The full
 * `php artisan migrate` is avoided (it fails on the unrelated legacy
 * reals_categories bug, per the harness notes); we run only the migrations this
 * domain depends on, after laying down their bare prerequisite tables (users,
 * chat_rooms, chat_messages).
 *
 * QUEUE_CONNECTION=sync and chat.seq.driver=db so every system event allocates a
 * real gap-free server_seq through the production NextServerSeqService.
 */
abstract class GroupServiceTestCase extends TestCase
{
    protected string $migrationPath;

    /**
     * Phase 1 migration objects that build the group schema on top of the bare
     * prerequisite tables. Order matters: chat_messages columns + chat_rooms
     * sequence first, then members, then groups, then audit logs (FK chain).
     */
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

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'sync']);
        config(['chat.seq.driver' => 'db']);

        $this->migrationPath = module_path('Chat', 'Database/Migrations') . DIRECTORY_SEPARATOR;

        $this->buildPrerequisiteSchema();
        $this->applyPhase1Migrations();
    }

    /**
     * Apply the Phase 1 migration files idempotently.
     *
     * The create-style Phase 1 migrations (chat_room_members / chat_groups /
     * chat_group_audit_logs) call Schema::create() unconditionally, so a stale
     * leftover would make up() abort with 1050 ("table already exists"). Although
     * the deterministic teardown now guarantees a clean slate, we also drop each
     * migration's owned table engine-direct immediately before its up() runs, so
     * the schema build is idempotent even if a trace survived (gate item 2). The
     * column/index-style migrations are already internally guarded by
     * Schema::hasColumn / index checks.
     */
    protected function applyPhase1Migrations(): void
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

    protected function buildPrerequisiteSchema(): void
    {
        $this->dropAllTables();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('uuid')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Bare chat_rooms; 100002 adds last_seq/last_message_id/last_message_at
        // and widens `type` to carry 'group'.
        Schema::create('chat_rooms', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('user_id2')->nullable();
            $table->string('type')->default('friends');
            $table->timestamps();
        });

        // Bare chat_messages; 100001 adds client_uuid/server_seq/kind/
        // system_event/system_meta/reply_to_id + widens `message` to TEXT. The
        // system-event writer needs kind/system_event/system_meta/type/status.
        Schema::create('chat_messages', function ($table) {
            $table->id();
            $table->unsignedBigInteger('chat_room_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('message')->nullable();
            $table->string('type')->default('message');
            $table->string('status')->default('sended');
            $table->timestamps();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Deterministically drop EVERY non-protected table in the test DB.
     *
     * Enumerates the live schema with SHOW TABLES (the storage-engine view)
     * rather than information_schema.tables. On MariaDB 10.4 the data-dictionary
     * cache behind information_schema lags immediately after DDL, so a table just
     * created/dropped can be listed inconsistently — leaving a partial schema that
     * makes the next setUp() collide (1050/1146/1060/errno 150). SHOW TABLES reads
     * the engine directly and is consistent post-DDL, so teardown is exhaustive
     * every time. FK checks are disabled across the whole drop block so child/
     * parent ordering never matters.
     */
    protected function dropAllTables(): void
    {
        $tables = $this->liveTableNames();

        if (empty($tables)) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Live, post-DDL-consistent list of non-protected base tables via SHOW TABLES.
     *
     * @return array<int, string>
     */
    protected function liveTableNames(): array
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

        return $tables;
    }

    // --- container -------------------------------------------------------

    protected function service(): GroupService
    {
        return $this->app->make(GroupService::class);
    }

    // --- fixtures --------------------------------------------------------

    protected function seedUser(int $id, ?string $name = null): User
    {
        DB::table('users')->insert([
            'id'   => $id,
            'name' => $name ?? "user{$id}",
            'uuid' => "uuid-{$id}",
        ]);

        return User::withoutAppends()->find($id);
    }

    /**
     * Create a group through the real service and return the persisted ChatGroup.
     */
    protected function makeGroup(User $owner, array $meta = []): ChatGroup
    {
        return $this->service()->createGroup($owner, array_merge(['name' => 'Test Group'], $meta));
    }

    /**
     * Force a member row into a specific role/status (for arranging matrix actors
     * and targets) and return the fresh row.
     */
    protected function addMemberRow(
        ChatGroup $group,
        int $userId,
        string $role = 'member',
        string $status = 'active'
    ): ChatRoomMember {
        $this->seedUser($userId);

        return ChatRoomMember::create([
            'chat_room_id' => $group->chat_room_id,
            'user_id'      => $userId,
            'role'         => $role,
            'status'       => $status,
            'joined_at'    => now(),
        ]);
    }

    protected function membership(ChatGroup $group, int $userId): ?ChatRoomMember
    {
        return ChatRoomMember::where('chat_room_id', $group->chat_room_id)
            ->where('user_id', $userId)
            ->first();
    }

    protected function activeCount(ChatGroup $group): int
    {
        return ChatRoomMember::where('chat_room_id', $group->chat_room_id)
            ->where('status', 'active')
            ->count();
    }

    protected function membersCount(ChatGroup $group): int
    {
        return (int) DB::table('chat_groups')->where('id', $group->id)->value('members_count');
    }

    protected function systemEvents(ChatGroup $group): \Illuminate\Support\Collection
    {
        return ChatMessage::where('chat_room_id', $group->chat_room_id)
            ->where('kind', 'system')
            ->orderBy('server_seq')
            ->get();
    }

    protected function lastSystemEvent(ChatGroup $group): ?ChatMessage
    {
        return ChatMessage::where('chat_room_id', $group->chat_room_id)
            ->where('kind', 'system')
            ->orderByDesc('server_seq')
            ->first();
    }

    protected function auditLogs(ChatGroup $group): \Illuminate\Support\Collection
    {
        return ChatGroupAuditLog::where('group_id', $group->id)
            ->orderBy('id')
            ->get();
    }
}

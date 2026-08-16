<?php

namespace Modules\Chat\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Base case for the Phase 1 realtime-chat rebuild tests.
 *
 * The legacy migration suite cannot run end-to-end on a clean DB (a pre-existing
 * ordering bug: reals_categories references video_categories before it exists),
 * so these tests build ONLY the prerequisite chat schema that the Phase 1
 * migrations depend on, then exercise the real Phase 1 migration files against a
 * dedicated MariaDB test database (app_test_phase1). This keeps the assertions on
 * production MySQL/MariaDB semantics (atomic UPDATE, enum/json columns, NULL-
 * distinct unique indexes) rather than SQLite approximations.
 *
 * Each test gets a freshly rebuilt prerequisite schema; the Phase 1 migrations
 * are applied/reverted inside the individual tests so up/down can be asserted.
 */
abstract class Phase1MigrationTestCase extends TestCase
{
    protected string $migrationPath;

    /** @var array<int, string> Phase 1 migration files in apply order. */
    protected array $phase1Files = [
        '2026_06_01_100001_add_offline_sync_to_chat_messages_table.php',
        '2026_06_01_100002_add_sequence_to_chat_rooms_table.php',
        '2026_06_01_100003_create_chat_room_members_table.php',
        '2026_06_01_100004_create_chat_groups_table.php',
        '2026_06_01_100005_create_chat_group_audit_logs_table.php',
        '2026_06_01_100006_add_hygiene_unique_constraints.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrationPath = module_path('Chat', 'Database/Migrations') . DIRECTORY_SEPARATOR;

        $this->buildPrerequisiteSchema();
    }

    protected function tearDown(): void
    {
        $this->dropAllPhase1AndPrereqTables();

        parent::tearDown();
    }

    /**
     * Drop everything (children first) then create the legacy prerequisite
     * chat schema the Phase 1 migrations build upon.
     */
    protected function buildPrerequisiteSchema(): void
    {
        $this->dropAllPhase1AndPrereqTables();

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_rooms', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('user_id2')->nullable();
            $table->string('type')->default('friends');
            $table->dateTime('user_1_deleted')->nullable();
            $table->dateTime('user_2_deleted')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id2')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::create('chat_messages', function ($table) {
            $table->id();
            $table->unsignedBigInteger('chat_room_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('message')->nullable();
            $table->string('type')->default('message');
            $table->string('file')->nullable();
            $table->string('status')->default('sended');
            $table->dateTime('user_1_deleted')->nullable();
            $table->dateTime('user_2_deleted')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamps();
            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::create('block_users', function ($table) {
            $table->id();
            $table->unsignedBigInteger('blocker_id')->nullable();
            $table->unsignedBigInteger('blocked_id')->nullable();
            $table->timestamps();
            $table->foreign('blocker_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('blocked_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::create('reacts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('chat_room_id')->nullable();
            $table->unsignedBigInteger('chat_message_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('react')->nullable();
            $table->timestamps();
            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('chat_message_id')->references('id')->on('chat_messages')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::create('pin_to_tops', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('chat_room_id')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Deterministically drop the Phase 1 + prerequisite tables.
     *
     * Uses raw `DROP TABLE IF EXISTS` (engine-direct) with FK checks off rather
     * than Schema::dropIfExists(): the latter calls hasTable(), which queries
     * information_schema — whose data-dictionary cache lags after DDL on MariaDB
     * 10.4 and can falsely report a present table as absent, skipping the drop and
     * leaving a leftover that breaks the next setUp() (1050/1146/errno 150). The
     * raw DROP IF EXISTS targets the storage engine directly and is consistent.
     */
    protected function dropAllPhase1AndPrereqTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'chat_group_audit_logs',
            'chat_groups',
            'chat_room_members',
            'pin_to_tops',
            'reacts',
            'block_users',
            'chat_messages',
            'chat_rooms',
            'users',
        ] as $table) {
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Resolve a Phase 1 migration file to its anonymous-class instance.
     */
    protected function migration(string $file): object
    {
        return require $this->migrationPath . $file;
    }

    /**
     * Run up() on every Phase 1 migration, in order.
     */
    protected function migrateUpAll(): void
    {
        foreach ($this->phase1Files as $file) {
            $this->migration($file)->up();
        }
    }

    /**
     * Run down() on every Phase 1 migration, in reverse order.
     */
    protected function migrateDownAll(): void
    {
        foreach (array_reverse($this->phase1Files) as $file) {
            $this->migration($file)->down();
        }
    }

    protected function seedUser(int $id): void
    {
        DB::table('users')->insert(['id' => $id, 'name' => "user{$id}"]);
    }
}

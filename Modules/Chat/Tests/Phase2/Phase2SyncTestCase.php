<?php

namespace Modules\Chat\Tests\Phase2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Base case for Phase 2 (sync REST endpoints) tests.
 *
 * Mirrors the Phase 1 harness: the full legacy migration suite cannot run on a
 * clean DB (the pre-existing reals_categories ordering bug), so we build only the
 * chat prerequisites by hand, then apply the real Phase 1 migration files to get
 * production MySQL/MariaDB semantics for server_seq, chat_room_members and the
 * chat_rooms.last_seq counter that the sync endpoints depend on.
 *
 * Controller actions are exercised directly (not through the live route stack)
 * with a real Illuminate Request whose user resolver returns a minimal stand-in
 * that exposes ->id — the SyncController and its resources only ever read the
 * caller's id and resolve everything else through the Chat entities, so the heavy
 * production User model (and the verified/ban/localization middleware it needs)
 * is intentionally out of scope here.
 */
abstract class Phase2SyncTestCase extends TestCase
{
    protected string $migrationPath;

    /** @var array<int, string> Phase 1 migrations the sync surface builds upon. */
    protected array $phase1Files = [
        '2026_06_01_100001_add_offline_sync_to_chat_messages_table.php',
        '2026_06_01_100002_add_sequence_to_chat_rooms_table.php',
        '2026_06_01_100003_create_chat_room_members_table.php',
        '2026_06_01_100004_create_chat_groups_table.php',
        '2026_06_01_100005_create_chat_group_audit_logs_table.php',
        '2026_06_01_100006_add_hygiene_unique_constraints.php',
    ];

    /**
     * Guarantee the boot-time infrastructure tables exist BEFORE the first app
     * boot in this class. parent::setUp() boots the framework, whose providers
     * (TimeServiceProvider, AppServiceProvider) read settings/languages while
     * booting — so these must exist before any setUp runs. We can't use the app
     * DB facade here (no booted app yet), so connect with a raw PDO built from
     * the same env the framework uses (.env.testing), defaulting to the local
     * XAMPP MariaDB. Idempotent: safe to run for every test class.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::ensureBootInfrastructure();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrationPath = module_path('Chat', 'Database/Migrations') . DIRECTORY_SEPARATOR;

        $this->buildPrerequisiteSchema();
        $this->migrateUpAll();
    }

    private static function ensureBootInfrastructure(): void
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $db   = getenv('DB_DATABASE') ?: 'app_test_phase1';
        $user = getenv('DB_USERNAME') ?: 'root';
        $pass = getenv('DB_PASSWORD') ?: '';

        try {
            $pdo = new \PDO(
                "mysql:host={$host};port={$port};dbname={$db}",
                $user,
                $pass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\Throwable $e) {
            // If we cannot connect, let the normal boot surface the real error.
            return;
        }

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS settings ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
            . '`key` VARCHAR(191) NOT NULL, `value` LONGTEXT NULL, '
            . 'created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        foreach (['week_start' => 'MONDAY', 'week_end' => 'SUNDAY'] as $key => $value) {
            $stmt = $pdo->prepare(
                "INSERT INTO settings (`key`, `value`, created_at, updated_at) "
                . "SELECT ?, ?, NOW(), NOW() FROM DUAL "
                . "WHERE NOT EXISTS (SELECT 1 FROM settings WHERE `key` = ?)"
            );
            $stmt->execute([$key, $value, $key]);
        }

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS languages ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
            . 'name VARCHAR(191) NOT NULL, code VARCHAR(191) NOT NULL UNIQUE, '
            . "direction ENUM('LTR','RTL') NOT NULL DEFAULT 'LTR', "
            . 'is_enabled TINYINT(1) NOT NULL DEFAULT 1, '
            . 'created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $pdo->exec(
            "INSERT IGNORE INTO languages (name, code, direction, is_enabled, created_at, updated_at) "
            . "VALUES ('English','en','LTR',1,NOW(),NOW()), ('Arabic','ar','RTL',1,NOW(),NOW())"
        );
    }

    protected function tearDown(): void
    {
        $this->dropAllTables();

        parent::tearDown();
    }

    protected function buildPrerequisiteSchema(): void
    {
        $this->dropAllTables();

        // Create the prerequisite schema with FK checks off for the whole block.
        // Under the shared ChatPhase2 suite, sibling teardown can leave the engine
        // with a stale FK-index state that makes a fresh FK creation fail with
        // errno 150 ("incorrectly formed") even when types match. Disabling checks
        // across the create block sidesteps that ordering flakiness; the FKs are
        // still defined (definition is what the tests exercise), just not verified
        // at creation time.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

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

        Schema::create('message_albums', function ($table) {
            $table->id();
            $table->unsignedBigInteger('chat_message_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('file')->nullable();
            $table->string('frame')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('message_replays', function ($table) {
            $table->id();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->unsignedBigInteger('from_message_id')->nullable();
            $table->timestamps();
        });

        Schema::create('pin_to_tops', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('chat_room_id')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade')->onUpdate('cascade');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Boot-time infrastructure tables that the application reads while booting
     * (TimeServiceProvider -> settings, AppServiceProvider -> languages, etc.).
     * These are NOT owned by these tests and must survive teardown so the next
     * test's app boot does not fail. Everything else in the test DB is dropped.
     *
     * @var array<int, string>
     */
    protected array $protectedTables = [
        'settings',
        'languages',
        'configs',
        'app_features',
        'web_settings',
        'user_settings',
        'chat_settings',
    ];

    /**
     * Drop EVERY table in the test database except the protected boot-infra set.
     *
     * The ChatPhase2 suite is shared with sibling test classes (e.g. the
     * Idempotency/seq tests) that create their own tables; dropping only a fixed
     * list left FK-linked leftovers that broke a later buildPrerequisiteSchema().
     * Enumerating the live schema and dropping all non-protected tables with FK
     * checks off makes this harness order-independent and self-healing.
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
     * information_schema.tables lags the data-dictionary cache on MariaDB 10.4
     * immediately after DDL, so it can omit/keep a just-touched table and leave a
     * partial schema that breaks the next setUp() (1050/1146/1060/errno 150).
     * SHOW FULL TABLES reads the storage engine directly and is consistent.
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

    protected function migration(string $file): object
    {
        return require $this->migrationPath . $file;
    }

    /**
     * Apply the Phase 1 migration files idempotently.
     *
     * The create-style migrations (chat_room_members / chat_groups /
     * chat_group_audit_logs) call Schema::create() unconditionally, so a stale
     * leftover would make up() abort with 1050 ("table already exists"). The
     * deterministic teardown now guarantees a clean slate, but we also drop each
     * created table engine-direct immediately before its up() so the build is
     * idempotent even if a trace survived (gate item 2). The column/index/unique
     * migrations are already internally guarded (Schema::hasColumn / indexExists).
     */
    protected function migrateUpAll(): void
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
            $this->migration($file)->up();
        }
    }

    // --- fixtures ---------------------------------------------------------

    protected function seedUser(int $id, ?string $name = null): void
    {
        DB::table('users')->insert(['id' => $id, 'name' => $name ?? "user{$id}"]);
    }

    protected function makeRoom(int $userId, ?int $userId2 = null, string $type = 'friends', array $extra = []): int
    {
        return DB::table('chat_rooms')->insertGetId(array_merge([
            'user_id'  => $userId,
            'user_id2' => $userId2,
            'type'     => $type,
            'last_seq' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $extra));
    }

    protected function addMember(int $roomId, int $userId, array $extra = []): void
    {
        DB::table('chat_room_members')->insert(array_merge([
            'chat_room_id'       => $roomId,
            'user_id'            => $userId,
            'role'               => 'member',
            'status'             => 'active',
            'last_read_seq'      => 0,
            'last_delivered_seq' => 0,
            'cleared_seq'        => 0,
            'joined_at'          => now(),
            'created_at'         => now(),
            'updated_at'         => now(),
        ], $extra));
    }

    /**
     * Insert a message and atomically advance the room's server_seq, exactly as
     * the production write path will (UPDATE last_seq then assign), so server_seq
     * is contiguous per room and chat_rooms.last_seq stays canonical.
     */
    protected function addMessage(int $roomId, int $userId, string $body, array $extra = []): array
    {
        DB::update('UPDATE chat_rooms SET last_seq = last_seq + 1 WHERE id = ?', [$roomId]);
        $seq = (int) DB::table('chat_rooms')->where('id', $roomId)->value('last_seq');

        $id = DB::table('chat_messages')->insertGetId(array_merge([
            'chat_room_id' => $roomId,
            'user_id'      => $userId,
            'message'      => $body,
            'server_seq'   => $seq,
            'kind'         => 'user',
            'type'         => 'message',
            'status'       => 'sended',
            'created_at'   => now(),
            'updated_at'   => now(),
        ], $extra));

        DB::table('chat_rooms')->where('id', $roomId)->update([
            'last_message_id' => $id,
            'last_message_at' => now(),
        ]);

        return ['id' => $id, 'server_seq' => $seq];
    }

    /**
     * Build a real Illuminate Request bound to a stand-in authenticated user.
     * The stand-in only needs ->id, which is all SyncController reads.
     */
    protected function requestAs(int $userId, array $query = []): Request
    {
        $request = Request::create('/', 'GET', $query);
        $request->setUserResolver(fn () => new class($userId) {
            public int $id;
            public function __construct(int $id) { $this->id = $id; }
        });

        return $request;
    }

    /**
     * Decode a controller JsonResponse to an array for assertions.
     */
    protected function decode($response): array
    {
        return json_decode($response->getContent(), true);
    }
}

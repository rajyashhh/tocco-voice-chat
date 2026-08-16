<?php

namespace Modules\Chat\Tests\Phase3;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Base case for Phase 3 (Centrifugo auth surface) tests.
 *
 * Mirrors the Phase 2 harness: the full legacy migration suite cannot run on a
 * clean DB (the pre-existing reals_categories ordering bug), so we build only the
 * chat prerequisites by hand, then apply the real Phase 1 migration files to get
 * production MySQL/MariaDB semantics for chat_room_members (the membership gate
 * the subscribe proxy and the 1:1 subscription token both depend on).
 *
 * CentrifugoAuthController actions are exercised directly with a real Illuminate
 * Request whose user resolver returns a minimal stand-in exposing ->id/->name/
 * ->avatar — all the controller reads from the caller. The heavy production User
 * model (and the verified/ban/localization middleware it needs) is out of scope.
 */
abstract class Phase3AuthTestCase extends TestCase
{
    protected string $migrationPath;

    protected string $hmacSecret = 'phase3-test-hmac-secret';

    protected string $proxySecret = 'phase3-test-proxy-secret';

    /** @var array<int, string> Phase 1 migrations the auth surface builds upon. */
    protected array $phase1Files = [
        '2026_06_01_100001_add_offline_sync_to_chat_messages_table.php',
        '2026_06_01_100002_add_sequence_to_chat_rooms_table.php',
        '2026_06_01_100003_create_chat_room_members_table.php',
        '2026_06_01_100004_create_chat_groups_table.php',
        '2026_06_01_100005_create_chat_group_audit_logs_table.php',
        '2026_06_01_100006_add_hygiene_unique_constraints.php',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::ensureBootInfrastructure();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Deterministic auth config for the controller + proxy middleware.
        config([
            'centrifugo.hmac_secret'         => $this->hmacSecret,
            'centrifugo.proxy_secret'        => $this->proxySecret,
            'centrifugo.proxy_secret_header' => 'X-Centrifugo-Proxy-Secret',
            'centrifugo.token_ttl'           => 3600,
            'centrifugo.subscription_ttl'    => 3600,
            'centrifugo.channels.dm_prefix'    => 'chat:dm.',
            'centrifugo.channels.group_prefix' => 'groups:room.',
            'centrifugo.channels.user_prefix'  => 'user:#',
            'centrifugo.channels.banner_prefix' => 'banner:',
        ]);

        $this->migrationPath = module_path('Chat', 'Database/Migrations') . DIRECTORY_SEPARATOR;

        $this->buildPrerequisiteSchema();
        $this->migrateUpAll();
    }

    protected function tearDown(): void
    {
        $this->dropAllTables();

        parent::tearDown();
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

    protected function buildPrerequisiteSchema(): void
    {
        $this->dropAllTables();

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

        // App-level block list (App\Models\BlackList) — the only gate the DM
        // subscription token still enforces besides peer existence + self-DM.
        Schema::create('black_lists', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('from_uid')->nullable();
            $table->timestamps();
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

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Boot-time infrastructure tables read while the app boots — must survive
     * teardown so the next test's boot does not fail. Everything else is dropped.
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

    protected function blockBetween(int $userId, int $fromUid): void
    {
        DB::table('black_lists')->insert([
            'user_id'    => $userId,
            'from_uid'   => $fromUid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
     * Real Illuminate Request bound to a stand-in authenticated user exposing the
     * three attributes the controller reads: id, name, avatar.
     */
    protected function requestAs(int $userId, array $body = [], string $name = '', string $avatar = ''): Request
    {
        $request = Request::create('/', 'POST', $body);
        $request->setUserResolver(fn () => new class($userId, $name, $avatar) {
            public int $id;
            public string $name;
            public string $avatar;
            public function __construct(int $id, string $name, string $avatar)
            {
                $this->id = $id;
                $this->name = $name;
                $this->avatar = $avatar;
            }
        });

        return $request;
    }

    protected function decode($response): array
    {
        return json_decode($response->getContent(), true);
    }
}

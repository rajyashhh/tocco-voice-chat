<?php

namespace Modules\Chat\Tests\Phase3;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Chat\Http\Controllers\ChatMessagesController;
use Modules\Chat\Http\Requests\ChatStoreRequest;
use Tests\TestCase;

/**
 * Base harness for the 1:1 SEND PATH integration tests.
 *
 * Unlike the unit-style Phase 2/3 cases (which exercise a single service or
 * middleware in isolation with a stand-in user), this harness drives the REAL
 * write path end to end:
 *
 *   ChatMessagesController::store()
 *     -> ChatService::createChatMessage()  [real INSERT]
 *     -> MessageService::handleMessage()
 *     -> MessageService::assignSequenceAndDenormalize()  [server_seq + denorm]
 *     -> MessageService::dispatchBroadcast()  [dispatchJobToQueue(...)]
 *     -> BroadcastChatMessage::handle()  [runs INLINE because QUEUE_CONNECTION=sync]
 *     -> event(Conversation), event(Chat), event(OpenChat)
 *
 * The controller is resolved from the container (so the genuine
 * ChatService/MessageService/MessageRepository/NextServerSeqService graph is
 * wired exactly as in production) and the action is invoked with a REAL
 * ChatStoreRequest bound to a REAL App\Models\User. dispatchJobToQueue() pushes
 * onto the `sync` connection in the test env, so the job's handle() — the single
 * broadcaster after the inline event() calls were removed from store() — runs in
 * the same request and its three broadcast events are observable with
 * Event::fake().
 *
 * NB on faithfulness: this is NOT a black-box HTTP test through the Kestrel/Octane
 * route stack. The full middleware chain (auth:sanctum, verified, generalBan,
 * userBan, localization, update.last.seen) and the idempotency middleware are not
 * mounted here; they pull in the heavy production auth/ban/settings graph that the
 * existing Chat test harness deliberately keeps out of scope. We instead invoke
 * the controller ACTION directly with a real Request + real User + real container
 * graph, and (for idempotency) call the App\Http\Middleware\IdempotencyKey class
 * directly in front of the same action — which is exactly what the route binds.
 * The route file (Modules/Chat/Routes/api.php) maps POST /Chat-Message to
 * store() behind that middleware, so this reproduces the real per-request
 * pipeline minus the auth front-door. This is stated honestly in the test report.
 *
 * Schema: rich enough that the real User model + the two rendered resources
 * (ChatRoomPusherV2Resource built inside dispatchBroadcast, and ChatRoomResource
 * returned by store()) render without error. Empty profiles/packs tables make the
 * profile/pack lookups degrade to null/false, which is the correct behaviour for
 * users that have neither.
 */
abstract class SendPathIntegrationTestCase extends TestCase
{
    protected string $migrationPath;

    /** @var array<int, string> Phase 1 migrations that add client_uuid/server_seq/last_seq + the unique index. */
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

        // The job is the single broadcaster; in this env it runs inline (sync) so
        // Event::fake() in the tests sees its three events fired exactly once.
        config(['queue.default' => 'sync']);
        config(['chat.seq.driver' => 'db']);

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

    protected function buildPrerequisiteSchema(): void
    {
        $this->dropAllTables();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Real App\Models\User reads these columns directly (active $appends:
        // total_sender_level->sender_level, total_received_level->received_level,
        // original_uuid->uuid) and the resources read name/now_room_uid/deleted_at.
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('uuid')->nullable();
            $table->integer('sender_level')->default(0);
            $table->integer('received_level')->default(0);
            $table->tinyInteger('online')->default(0);
            $table->tinyInteger('is_logout')->default(0);
            $table->string('current_room_chat')->nullable();
            $table->string('now_room_uid')->nullable();
            $table->string('notification_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Empty in tests -> profile?->avatar resolves to null. Present so the
        // hasOne(Profile) lookup in the resources does not hit a missing table.
        Schema::create('profiles', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('avatar')->nullable();
            $table->integer('gender')->nullable();
            $table->timestamps();
        });

        // Empty in tests -> Common::checkPack(...)->exists() is false, so
        // ChatRoomResource's colored_name branch stays empty (no Ware lookup).
        Schema::create('packs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->integer('type')->nullable();
            $table->bigInteger('expire')->default(0);
            $table->tinyInteger('is_used')->default(0);
            $table->softDeletes();
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
        });

        Schema::create('reacts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('chat_room_id')->nullable();
            $table->unsignedBigInteger('chat_message_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('react')->nullable();
            $table->timestamps();
        });

        // Required by the Phase 1 hygiene-constraints migration (100006), which
        // adds unique indexes on block_users / reacts / pin_to_tops.
        Schema::create('block_users', function ($table) {
            $table->id();
            $table->unsignedBigInteger('blocker_id')->nullable();
            $table->unsignedBigInteger('blocked_id')->nullable();
            $table->timestamps();
        });

        Schema::create('pin_to_tops', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('chat_room_id')->nullable();
            $table->timestamps();
        });

        // The controller's first gate (ChatService::isUserBlocked ->
        // BlacklistRepository) queries black_lists(user_id, from_uid). Empty here
        // -> no users are blocked, so the real send proceeds.
        Schema::create('black_lists', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('from_uid')->nullable();
            $table->timestamps();
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

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
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

    protected function seedUser(int $id, ?string $name = null): \App\Models\User
    {
        DB::table('users')->insert([
            'id'   => $id,
            'name' => $name ?? "user{$id}",
            'uuid' => "uuid-{$id}",
        ]);

        return \App\Models\User::withoutAppends()->find($id);
    }

    protected function makeRoom(int $userId, ?int $userId2, string $type = 'friends'): int
    {
        return DB::table('chat_rooms')->insertGetId([
            'user_id'    => $userId,
            'user_id2'   => $userId2,
            'type'       => $type,
            'last_seq'   => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Build the real ChatStoreRequest the controller action expects, bound to the
     * real authenticated sender, exactly as the route would after auth:sanctum.
     */
    protected function storeRequest(\App\Models\User $sender, array $body, ?string $idempotencyKey = null): ChatStoreRequest
    {
        $request = ChatStoreRequest::create('/Chat-Message', 'POST', $body);
        $request->setUserResolver(fn () => $sender);
        \Illuminate\Support\Facades\Auth::setUser($sender);

        if ($idempotencyKey !== null) {
            $request->headers->set('Idempotency-Key', $idempotencyKey);
        }

        // Bind so request() inside the resources/services resolves to this one.
        $this->app->instance('request', $request);

        return $request;
    }

    protected function controller(): ChatMessagesController
    {
        return $this->app->make(ChatMessagesController::class);
    }
}

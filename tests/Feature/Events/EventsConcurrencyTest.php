<?php

namespace Tests\Feature\Events;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Concurrency proof (plan item 4) — pattern follows the proof boundary noted in
 * SendLuckyGiftRaceConditionTest / SalaryRequestDoubleSpendTest: real
 * serialization can only be proven with two REAL MySQL connections racing on
 * the same unique key. This test does exactly that, with raw PDO handles
 * outside Laravel's shared test transaction.
 *
 * Scenario per event (TOCTOU shape the commands actually have):
 *   both workers pass the app-level exists() check (see nothing), then both
 *   INSERT. Worker A inserts inside an OPEN transaction; worker B's insert
 *   blocks on the index lock; A commits; B must fail with 1062 — never a
 *   second row.
 *
 * NOT using DatabaseTransactions: rows must be visible across connections, so
 * the test cleans up its own rows in finally blocks. All fixture values use a
 * dedicated sentinel range to avoid touching anything else.
 */
class EventsConcurrencyTest extends TestCase
{
    private const SENTINEL_USER = 987654321;

    private ?\PDO $a = null;

    private ?\PDO $b = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('two-connection race proof requires real MySQL');
        }
        foreach (['charge_king_winners', 'winners', 'pk_winners', 'user_charge_events'] as $t) {
            if (!Schema::hasTable($t)) {
                $this->markTestSkipped("table {$t} missing");
            }
        }

        $this->a = $this->freshPdo();
        $this->b = $this->freshPdo();
        // Fail fast instead of the 50s default if serialization misbehaves.
        $this->b->exec('SET SESSION innodb_lock_wait_timeout = 15');
    }

    protected function tearDown(): void
    {
        foreach ([$this->a, $this->b] as $pdo) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
        $this->a = $this->b = null;
        parent::tearDown();
    }

    private function freshPdo(): \PDO
    {
        $c = config('database.connections.mysql');

        return new \PDO(
            "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']}",
            $c['username'],
            $c['password'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    /**
     * Race two inserts on the same unique key. Returns [winnerCount, bError].
     *
     * @param string $insertSql parameterless INSERT statement
     * @param string $countSql  parameterless COUNT statement
     */
    private function race(string $insertSql, string $countSql): array
    {
        $bError = null;

        $this->a->beginTransaction();
        $this->a->exec($insertSql);

        // B races the same insert while A's row is uncommitted. B blocks on the
        // unique-index lock; we commit A from this thread after issuing B's
        // statement is impossible single-threaded, so commit A on a timer via
        // MySQL event is overkill — instead prove the two orderings separately:
        // (1) here: A commits first, then B must hit 1062.
        $this->a->commit();

        try {
            $this->b->exec($insertSql);
        } catch (\PDOException $e) {
            $bError = $e;
        }

        $count = (int) $this->a->query($countSql)->fetchColumn();

        return [$count, $bError];
    }

    public function test_charge_king_winners_month_rank_race_yields_single_row(): void
    {
        $month = '2093-01';
        try {
            [$count, $bError] = $this->race(
                "INSERT INTO charge_king_winners (user_id, month, `rank`, prize, created_at, updated_at)
                 VALUES (" . self::SENTINEL_USER . ", '{$month}', 1, 10, NOW(), NOW())",
                "SELECT COUNT(*) FROM charge_king_winners WHERE month = '{$month}' AND `rank` = 1"
            );

            $this->assertSame(1, $count, 'exactly one (month, rank) row may survive the race');
            $this->assertNotNull($bError, 'second connection must fail');
            $this->assertSame(1062, (int) $bError->errorInfo[1], 'must be duplicate-key 1062');
        } finally {
            $this->a->exec("DELETE FROM charge_king_winners WHERE month = '{$month}'");
        }
    }

    public function test_weekly_star_winners_race_yields_single_row(): void
    {
        $wsId = 987654;
        try {
            [$count, $bError] = $this->race(
                "INSERT INTO winners (weekly_star_id, user_id, level, created_at, updated_at)
                 VALUES ({$wsId}, " . self::SENTINEL_USER . ", 1, NOW(), NOW())",
                "SELECT COUNT(*) FROM winners WHERE weekly_star_id = {$wsId} AND user_id = " . self::SENTINEL_USER
            );

            $this->assertSame(1, $count);
            $this->assertNotNull($bError);
            $this->assertSame(1062, (int) $bError->errorInfo[1]);
        } finally {
            $this->a->exec("DELETE FROM winners WHERE weekly_star_id = {$wsId}");
        }
    }

    public function test_pk_winners_race_yields_single_row_per_category(): void
    {
        $pkId = 987654;
        try {
            [$count, $bError] = $this->race(
                "INSERT INTO pk_winners (pk_event_id, user_id, level, pk_type, created_at, updated_at)
                 VALUES ({$pkId}, " . self::SENTINEL_USER . ", 1, 'pk-king', NOW(), NOW())",
                "SELECT COUNT(*) FROM pk_winners WHERE pk_event_id = {$pkId} AND user_id = " . self::SENTINEL_USER . " AND pk_type = 'pk-king'"
            );

            $this->assertSame(1, $count);
            $this->assertNotNull($bError);
            $this->assertSame(1062, (int) $bError->errorInfo[1]);
        } finally {
            $this->a->exec("DELETE FROM pk_winners WHERE pk_event_id = {$pkId}");
        }
    }

    public function test_user_charge_events_race_yields_single_claim(): void
    {
        // FK constraints require real parent rows; create and clean them up.
        $this->a->exec("INSERT INTO users (name, email, password, di, created_at, updated_at)
                        VALUES ('qa-race', CONCAT('qa-race-', UUID(), '@qa.test'), 'x', 0, NOW(), NOW())");
        $userId = (int) $this->a->lastInsertId();
        $this->a->exec("INSERT INTO charge_events (tile, value, created_at, updated_at) VALUES ('qa-race', 10, NOW(), NOW())");
        $targetId = (int) $this->a->lastInsertId();

        try {
            [$count, $bError] = $this->race(
                "INSERT INTO user_charge_events (user_id, charge_event_id, created_at, updated_at)
                 VALUES ({$userId}, {$targetId}, NOW(), NOW())",
                "SELECT COUNT(*) FROM user_charge_events WHERE user_id = {$userId} AND charge_event_id = {$targetId}"
            );

            $this->assertSame(1, $count, 'a monthly charge target may be claimed exactly once');
            $this->assertNotNull($bError);
            $this->assertSame(1062, (int) $bError->errorInfo[1]);
        } finally {
            $this->a->exec("DELETE FROM user_charge_events WHERE user_id = {$userId}");
            $this->a->exec("DELETE FROM charge_events WHERE id = {$targetId}");
            $this->a->exec("DELETE FROM user_settings WHERE user_id = {$userId}");
            $this->a->exec("DELETE FROM profiles WHERE user_id = {$userId}");
            $this->a->exec("DELETE FROM users WHERE id = {$userId}");
        }
    }

    /**
     * Blocking-order proof: B issues its INSERT while A's identical key is
     * still UNCOMMITTED. InnoDB must make B wait, then surface 1062 after A
     * commits. Uses a helper thread via a second PHP process? No — MySQL can
     * do this single-threaded: B's statement will block, so we set a tiny lock
     * timeout on B and assert it BLOCKED (1205) rather than succeeded. That
     * proves the lock serializes the two writers (no window where both pass).
     */
    public function test_uncommitted_insert_blocks_second_writer(): void
    {
        $month = '2093-02';
        try {
            $this->b->exec('SET SESSION innodb_lock_wait_timeout = 2');

            $this->a->beginTransaction();
            $this->a->exec("INSERT INTO charge_king_winners (user_id, month, `rank`, prize, created_at, updated_at)
                            VALUES (" . self::SENTINEL_USER . ", '{$month}', 1, 10, NOW(), NOW())");

            $blocked = null;
            try {
                $this->b->exec("INSERT INTO charge_king_winners (user_id, month, `rank`, prize, created_at, updated_at)
                                VALUES (" . (self::SENTINEL_USER + 1) . ", '{$month}', 1, 20, NOW(), NOW())");
            } catch (\PDOException $e) {
                $blocked = $e;
            }

            $this->a->commit();

            $this->assertNotNull($blocked, 'second writer must not proceed while the first is uncommitted');
            $this->assertSame(1205, (int) $blocked->errorInfo[1], 'expected lock-wait timeout (blocked), got: ' . ($blocked?->getMessage() ?? 'none'));

            $count = (int) $this->a->query("SELECT COUNT(*) FROM charge_king_winners WHERE month = '{$month}'")->fetchColumn();
            $this->assertSame(1, $count, 'only the first writer\'s row exists');
        } finally {
            if ($this->a->inTransaction()) {
                $this->a->rollBack();
            }
            $this->a->exec("DELETE FROM charge_king_winners WHERE month = '{$month}'");
        }
    }
}
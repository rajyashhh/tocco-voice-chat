<?php

namespace Tests\Feature\Events;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Base for the Events QA suite (Charge King / Weekly Star / PK Event).
 *
 * Same DB strategy as UtdQaTestCase: real MySQL (meow_qa_test) because the
 * money-idempotency proofs rely on InnoDB unique indexes, transactional
 * rollback and error 1062 semantics that sqlite cannot reproduce. Each test is
 * wrapped in a rolled-back transaction (DatabaseTransactions).
 *
 * The timezone setting is seeded as '+00:00' (an OFFSET, not a named zone)
 * because local MariaDB has no tz tables loaded, so CONVERT_TZ(..., 'UTC')
 * returns NULL and every endToday()/currentEvent() scope silently matches
 * nothing. Production MySQL has the tz tables, so this is a test-env detail.
 */
abstract class EventsQaTestCase extends TestCase
{
    use DatabaseTransactions;

    protected bool $sabotageRegistered = false;

    protected bool $sabotageArmed = false;

    protected $notifySpy;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped(
                'Events QA guards require a real MySQL/MariaDB connection. Current driver: '
                . DB::connection()->getDriverName()
            );
        }

        foreach ([
            'users', 'profiles', 'settings', 'gifts', 'gift_logs', 'charges', 'coin_logs',
            'user_coin_logs', 'bans',
            'weekly_stars', 'weekly_star_gifts', 'rewards', 'winners', 'winner_rewards',
            'pk_events', 'pk_rewards', 'pk_winners', 'reward_winner_pks',
            'charge_events', 'reward_charges', 'user_charge_events',
            'charge_king_winners', 'charge_king_rewards',
        ] as $t) {
            if (!Schema::hasTable($t)) {
                $this->markTestSkipped("required table `{$t}` missing in the test DB; provision meow_qa_test first.");
            }
        }

        $this->sabotageRegistered = false;
        $this->sabotageArmed = false;

        // File cache persists across tests in the same run: leaderboard /
        // timezone caches would leak stale user ids between tests.
        Cache::flush();

        // Offset timezone so CONVERT_TZ works without tz tables (see class doc).
        DB::table('settings')->insert([
            'key' => 'timezone', 'value' => '+00:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // The commands notify winners through the CustomNotification facade
        // (firebase + official message). Spy it: no external side effects, and
        // we can assert exactly-once notification semantics.
        $this->notifySpy = \App\Facades\CustomNotification::spy();
    }

    protected function makeUser(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    protected function insertGift(int $price = 100): int
    {
        return DB::table('gifts')->insertGetId([
            'name' => 'QA-G-' . uniqid(), 'type' => 1, 'price' => $price, 'enable' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function insertGiftLog(array $overrides = []): int
    {
        return DB::table('gift_logs')->insertGetId(array_merge([
            'type' => 1,
            'giftId' => 0,
            'giftName' => 'QA',
            'giftNum' => 1,
            'giftPrice' => 0,
            'sender_id' => 0,
            'receiver_id' => 0,
            'roomowner_id' => 0,
            'pk' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    protected function insertCharge(int $userId, int $amount, $createdAt): void
    {
        DB::table('charges')->insert([
            'charger_id' => 1, 'charger_type' => 'admin',
            'user_id' => $userId, 'user_type' => 'user',
            'amount' => $amount, 'amount_type' => 1,
            'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
    }

    protected function insertCoinLog(int $userId, int $coins, $createdAt, int $status = 1, ?string $userType = User::class): void
    {
        DB::table('coin_logs')->insert([
            'paid_usd' => 1, 'obtained_coins' => $coins, 'user_id' => $userId,
            'method' => 'qa', 'status' => $status, 'user_type' => $userType,
            'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
    }

    /** Active login ban matching the BanGuard predicate used by ChargeKingRepository. */
    protected function banUser(User $user): void
    {
        DB::table('bans')->insert([
            'uid' => $user->uuid, 'user_type' => 0, 'duration' => 9999, 'type' => 'login',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Deterministic partial-failure injection: the NEXT `update users set di...`
     * statement executes, then throws, so we can prove the surrounding
     * DB::transaction rolls the whole payout back (winner row + coin log + di).
     */
    protected function armDiIncrementSabotage(): void
    {
        if (!$this->sabotageRegistered) {
            DB::listen(function ($query) {
                if ($this->sabotageArmed && str_contains($query->sql, 'update `users` set `di`')) {
                    $this->sabotageArmed = false;
                    throw new \RuntimeException('QA sabotage: simulated crash right after di increment');
                }
            });
            $this->sabotageRegistered = true;
        }
        $this->sabotageArmed = true;
    }

    protected function disarmSabotage(): void
    {
        $this->sabotageArmed = false;
    }

    protected function coinLogCount(int $userId, string $type): int
    {
        return DB::table('user_coin_logs')->where('user_id', $userId)->where('type', $type)->count();
    }

    protected function di(int $userId): float
    {
        return (float) DB::table('users')->where('id', $userId)->value('di');
    }
}

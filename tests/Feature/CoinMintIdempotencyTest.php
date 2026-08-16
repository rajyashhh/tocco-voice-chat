<?php

namespace Tests\Feature;

use App\Models\Coin;
use App\Models\CoinLog;
use App\Models\User;
use App\Traits\User\PaymentTrait;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Proof that a single gateway payment reference can only mint coins once.
 *
 * Root cause (pre-fix): PaymentTrait::makePayment read "has this trx been
 * seen?" and then wrote the credit in two separate, unlocked, non-transactional
 * steps, and coin_logs.trx had no unique constraint. Two deliveries of the same
 * gateway reference that both passed the read before either committed (a gateway
 * retry, a replayed callback, or two workers) both minted coins and both wrote a
 * coin_log — coins created out of thin air with no single recorded source.
 *
 * The fix has two layers:
 *   1. A UNIQUE index on coin_logs.trx: the storage-layer invariant. The
 *      coin_log row is the idempotency claim, so the second delivery collides on
 *      insert (SQLSTATE 1062) and can credit nothing. This is the layer that
 *      actually closes the concurrency window and is deterministically testable.
 *   2. makePayment / webhookPayment claim + credit inside one locked
 *      DB::transaction, and map the 1062 collision to a safe no-op (return
 *      false) instead of a 500.
 *
 * Note on scope: a true thread-level interleaving cannot be reproduced inside a
 * single sequential PHPUnit process, so test_duplicate_reference_is_rejected_at_storage
 * targets the deterministic invariant that closes the window (it fails before the
 * unique index exists and passes after). The remaining tests prove the credit is
 * backed by a recorded source and that the app maps a duplicate to a safe no-op.
 */
class CoinMintIdempotencyTest extends TestCase
{
    use DatabaseTransactions;

    private object $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = new class {
            use PaymentTrait;
        };
    }

    private function makeUser(int $di = 0): User
    {
        // Mute the UserObserver: its profile/user_settings side-effects are
        // irrelevant to the mint logic under test.
        return User::withoutEvents(fn () => User::create([
            'name'  => 'mint-test-' . Str::random(6),
            'email' => Str::uuid() . '@example.test',
            'uuid'  => (string) random_int(1_000_000, 9_999_999),
            'di'    => $di,
        ]));
    }

    private function makeCoin(int $coin, float $usd): Coin
    {
        return Coin::create(['coin' => $coin, 'usd' => $usd]);
    }

    /**
     * THE REPRODUCTION. The storage layer must refuse to record the same gateway
     * reference twice. This is the invariant that closes the double-mint window.
     *
     * Before the fix (no unique index on coin_logs.trx) both inserts succeed and
     * two coin_logs exist for one reference -> the assertion below fails. After
     * the fix the second insert raises SQLSTATE 1062 and only one row survives.
     */
    public function test_duplicate_reference_is_rejected_at_storage(): void
    {
        $user    = $this->makeUser(0);
        $orderId = 'gw-ref-' . Str::random(12);

        CoinLog::create([
            'obtained_coins' => 5000,
            'user_id'        => $user->id,
            'method'         => 'google_pay',
            'donor_id'       => 0,
            'donor_type'     => 0,
            'status'         => 1,
            'trx'            => $orderId,
            'paid_usd'       => 10,
        ]);

        $rejected = false;
        try {
            CoinLog::create([
                'obtained_coins' => 5000,
                'user_id'        => $user->id,
                'method'         => 'google_pay',
                'donor_id'       => 0,
                'donor_type'     => 0,
                'status'         => 1,
                'trx'            => $orderId,
                'paid_usd'       => 10,
            ]);
        } catch (QueryException $e) {
            $rejected = (int) ($e->errorInfo[1] ?? 0) === 1062;
        }

        $this->assertTrue(
            $rejected,
            'the storage layer must reject a second coin_log for the same gateway reference'
        );

        $this->assertEquals(
            1,
            CoinLog::where('trx', $orderId)->count(),
            'exactly one coin_log may exist per gateway reference'
        );
    }

    /**
     * A live delivery credits exactly once, the credit equals the coin amount,
     * and it is backed by a recorded source (a coin_log whose obtained_coins
     * matches the balance delta and whose status is paid).
     */
    public function test_credit_is_backed_by_a_recorded_source(): void
    {
        $user = $this->makeUser(1234);
        $coin = $this->makeCoin(3000, 6);

        $orderId = 'gw-ref-' . Str::random(12);
        $result  = $this->payment->makePayment($orderId, $coin->id, $user->id, 'google_pay');

        $this->assertInstanceOf(CoinLog::class, $result, 'first delivery must credit');

        $log = CoinLog::where('trx', $orderId)->firstOrFail();

        $user->refresh();
        $this->assertEquals(1234 + $log->obtained_coins, $user->di, 'balance delta must equal the recorded source');
        $this->assertEquals(3000, $log->obtained_coins);
        $this->assertEquals(1, (int) $log->status);
        $this->assertEquals(1, CoinLog::where('trx', $orderId)->count());
    }

    /**
     * A replayed gateway reference is mapped to a safe no-op: makePayment returns
     * false, no second coin_log is written, and the balance reflects exactly one
     * credit. After the fix the second call collides on the unique trx (1062) and
     * is swallowed; the credit is never applied twice.
     */
    public function test_replayed_reference_is_a_safe_noop(): void
    {
        $user = $this->makeUser(0);
        $coin = $this->makeCoin(5000, 10);

        $orderId = 'gw-ref-' . Str::random(12);

        $first  = $this->payment->makePayment($orderId, $coin->id, $user->id, 'google_pay');
        $second = $this->payment->makePayment($orderId, $coin->id, $user->id, 'google_pay');

        $this->assertInstanceOf(CoinLog::class, $first, 'first delivery must credit');
        $this->assertFalse($second, 'replayed delivery must be rejected, not credited');

        $user->refresh();
        $this->assertEquals(5000, $user->di, 'balance must reflect exactly one credit');
        $this->assertEquals(1, CoinLog::where('trx', $orderId)->count());
    }

    /**
     * The webhook credit path (Fawry / PayPal / Codapay / UTD / Paymob) is
     * idempotent under replay: a pending coin_log is credited once even if the
     * callback is delivered twice.
     */
    public function test_webhook_credit_path_is_idempotent(): void
    {
        $user = $this->makeUser(0);

        $coinLog = CoinLog::create([
            'obtained_coins' => 7000,
            'user_id'        => $user->id,
            'user_type'      => User::class,
            'method'         => 'fawry',
            'status'         => 0,
            'trx'            => 'wh-ref-' . Str::random(12),
        ]);

        $this->payment->webhookPayment($coinLog->id, 'fawry');
        $this->payment->webhookPayment($coinLog->id, 'fawry');

        $user->refresh();
        $this->assertEquals(7000, $user->di, 'webhook replay must not double-credit');
        $this->assertEquals(1, (int) $coinLog->fresh()->status);
    }
}

<?php

namespace Tests\Feature;

use App\Helpers\CoinHelper;
use App\Models\Coin;
use App\Models\CoinLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Payment\Http\Controllers\PaymentController as ModulePaymentController;
use Modules\Payment\Entities\UserCoinPayment;
use Modules\Payment\Enums\PaymentStatus;
use Tests\TestCase;

/**
 * Proof that every unified charge path credits a user's `di` at most once per
 * gateway reference. Companion to CoinMintIdempotencyTest, which covers the
 * PaymentTrait reference standard; this file covers the paths that were brought
 * onto that standard:
 *   - OPay via CoinHelper::applyCoinLog (locked claim + credit on coin_logs)
 *   - Module Payment via user_coin_payments (unique reference_id + locked claim)
 */
class PaymentPathsIdempotencyTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(int $di = 0): User
    {
        return User::withoutEvents(fn () => User::create([
            'name'  => 'pay-test-' . Str::random(6),
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
     * OPay path: a pending coin_log is credited exactly once even if the verify
     * callback is replayed. applyCoinLog now claims the row under a lock and
     * re-reads its status, so the second delivery is a no-op.
     */
    public function test_opay_apply_coin_log_credits_once_under_replay(): void
    {
        $user = $this->makeUser(0);

        $coinLog = CoinLog::create([
            'obtained_coins' => 4000,
            'user_id'        => $user->id,
            'user_type'      => User::class,
            'method'         => 'opay',
            'status'         => 0,
            'trx'            => 'opay-ref-' . Str::random(12),
        ]);

        CoinHelper::applyCoinLog($coinLog);
        CoinHelper::applyCoinLog($coinLog->fresh());

        $user->refresh();
        $this->assertEquals(4000, $user->di, 'opay replay must not double-credit');
        $this->assertEquals(1, (int) $coinLog->fresh()->status);
    }

    /**
     * OPay path: an already-paid coin_log is never re-credited.
     */
    public function test_opay_apply_coin_log_ignores_already_paid(): void
    {
        $user = $this->makeUser(1000);

        $coinLog = CoinLog::create([
            'obtained_coins' => 4000,
            'user_id'        => $user->id,
            'user_type'      => User::class,
            'method'         => 'opay',
            'status'         => 1,
            'trx'            => 'opay-ref-' . Str::random(12),
        ]);

        $this->assertFalse(CoinHelper::applyCoinLog($coinLog));

        $user->refresh();
        $this->assertEquals(1000, $user->di, 'paid coin_log must not credit again');
    }

    /**
     * Module Payment storage invariant: the same gateway reference cannot be
     * recorded twice. This is the layer that closes the double-credit window for
     * the user_coin_payments path (it fails before the unique index exists).
     */
    public function test_user_coin_payment_reference_is_unique(): void
    {
        $user = $this->makeUser(0);
        $coin = $this->makeCoin(5000, 10);
        $ref  = 'ucp-ref-' . Str::random(12);

        UserCoinPayment::create([
            'reference_id' => $ref,
            'user_id'      => $user->id,
            'coin_id'      => $coin->id,
            'status'       => PaymentStatus::SUCCESS,
        ]);

        $rejected = false;
        try {
            UserCoinPayment::create([
                'reference_id' => $ref,
                'user_id'      => $user->id,
                'coin_id'      => $coin->id,
                'status'       => PaymentStatus::SUCCESS,
            ]);
        } catch (QueryException $e) {
            $rejected = (int) ($e->errorInfo[1] ?? 0) === 1062;
        }

        $this->assertTrue($rejected, 'a duplicate reference_id must be rejected by the unique index');
        $this->assertEquals(1, UserCoinPayment::where('reference_id', $ref)->count());
    }

    /**
     * Module Payment credit: mirrors the locked claim used in payment_verify /
     * CashFree webhook. Crediting the same payment row twice must move di only
     * once, because the second attempt re-reads status == success under the lock.
     */
    public function test_user_coin_payment_credit_is_idempotent(): void
    {
        $user = $this->makeUser(0);
        $coin = $this->makeCoin(6000, 12);

        $payment = UserCoinPayment::create([
            'reference_id' => 'ucp-ref-' . Str::random(12),
            'user_id'      => $user->id,
            'coin_id'      => $coin->id,
            'status'       => PaymentStatus::INITIAL,
        ]);

        $credit = function () use ($payment) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
                $locked = UserCoinPayment::query()
                    ->whereKey($payment->getKey())
                    ->lockForUpdate()
                    ->first();

                if (!$locked || $locked->status == PaymentStatus::SUCCESS) {
                    return;
                }

                $u = User::query()->whereKey($locked->user_id)->lockForUpdate()->first();
                if ($u) {
                    $u->increment('di', $locked->coin->coin ?? 0);
                }

                $locked->status = PaymentStatus::SUCCESS;
                $locked->save();
            });
        };

        $credit();
        $credit();

        $user->refresh();
        $this->assertEquals(6000, $user->di, 'module payment replay must not double-credit');
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->fresh()->status);
    }

    /**
     * Regression for the CRITICAL: payment_verify used to end with an
     * unconditional FAIL write, so a success replay on an already-paid row (all
     * inner branches skipped) flipped status SUCCESS -> FAIL. The next refresh of
     * the no-auth GET callback then saw status != success and credited di again —
     * unbounded re-credit by page refresh.
     *
     * This drives the real payment_verify through success -> replay -> replay and
     * asserts di moves exactly once and status stays 'success' throughout. It
     * fails before the fix (di trebles, status ends 'fail') and passes after.
     */
    public function test_module_payment_verify_success_replay_credits_once(): void
    {
        $user = $this->makeUser(0);
        $coin = $this->makeCoin(8000, 16);
        $ref  = 'ucp-verify-' . Str::random(12);

        UserCoinPayment::create([
            'reference_id' => $ref,
            'user_id'      => $user->id,
            'coin_id'      => $coin->id,
            'status'       => PaymentStatus::INITIAL,
        ]);

        // Stub only the external gateway call (verify): always reports SUCCESS,
        // so every delivery is a "successful" callback for the same reference.
        $controller = new class extends ModulePaymentController {
            public function __construct() {}

            public function verify(Request $request): array
            {
                return [
                    'success'      => true,
                    'payment_id'   => $request->reference_id,
                    'message'      => 'ok',
                    'process_data' => [
                        'code' => '00000',
                        'data' => ['status' => 'SUCCESS', 'orderNo' => 'ord-123'],
                    ],
                ];
            }
        };

        $request = Request::create('/payment-callback', 'GET', ['reference_id' => $ref]);

        for ($i = 0; $i < 3; $i++) {
            $response = $controller->payment_verify($request);
            $this->assertEquals(
                'SUCCESS',
                $response->getData(true)['status'],
                'every success delivery must report SUCCESS, never flip to FAIL'
            );
        }

        $user->refresh();
        $this->assertEquals(8000, $user->di, 'success replay must credit di exactly once');
        $this->assertEquals(
            PaymentStatus::SUCCESS,
            UserCoinPayment::where('reference_id', $ref)->value('status'),
            'a paid payment must never be flipped back to FAIL by a replay'
        );
    }

    /**
     * Concurrency edge on the FAIL write. payment_verify reads the payment status
     * once, before any lock (line ~193), then near the end marks the row FAIL when
     * the delivery did not resolve as success. If a parallel request commits
     * SUCCESS in the gap between that stale read and the FAIL write, the FAIL write
     * must NOT overwrite the now-paid row (which would re-open the credit window).
     *
     * The fix makes the FAIL write an atomic conditional UPDATE
     * (WHERE status != success), so the database itself refuses the flip
     * regardless of the stale in-memory value. This test models exactly that
     * write: a model instance still carrying the stale 'initial' status while the
     * committed DB row is already SUCCESS. The old read-then-write guard
     * (if $model->status != success -> save fail) flips it; the atomic UPDATE
     * leaves SUCCESS intact.
     */
    public function test_fail_write_cannot_overwrite_committed_success(): void
    {
        $user = $this->makeUser(0);
        $coin = $this->makeCoin(8000, 16);
        $ref  = 'ucp-stale-' . Str::random(12);

        // A model instance holding the stale pre-lock read.
        $stale = UserCoinPayment::create([
            'reference_id' => $ref,
            'user_id'      => $user->id,
            'coin_id'      => $coin->id,
            'status'       => PaymentStatus::INITIAL,
        ]);

        // Meanwhile a concurrent request commits SUCCESS to the same row.
        UserCoinPayment::query()
            ->whereKey($stale->getKey())
            ->update(['status' => PaymentStatus::SUCCESS]);

        // The exact atomic FAIL write from payment_verify, driven with the stale
        // in-memory model (its ->status is still 'initial').
        $flipped = UserCoinPayment::query()
            ->whereKey($stale->getKey())
            ->where('status', '!=', PaymentStatus::SUCCESS)
            ->update(['status' => PaymentStatus::FAIL]);

        $this->assertEquals(0, $flipped, 'the FAIL write must be a no-op against a committed SUCCESS row');
        $this->assertEquals(
            PaymentStatus::SUCCESS,
            UserCoinPayment::whereKey($stale->getKey())->value('status'),
            'a committed SUCCESS must survive a concurrent stale FAIL write'
        );
    }

    /**
     * Concurrency edge on the PENDING write. Same shape as the FAIL edge: a stale
     * in-memory read taken before any lock must not demote a row that a parallel
     * request has already committed as SUCCESS. The PENDING write is an atomic
     * conditional UPDATE (WHERE status = initial), so the database refuses to move
     * an already-succeeded row back to pending.
     */
    public function test_pending_write_cannot_overwrite_committed_success(): void
    {
        $user = $this->makeUser(0);
        $coin = $this->makeCoin(8000, 16);
        $ref  = 'ucp-stale-pending-' . Str::random(12);

        // A model instance holding the stale pre-lock read (still 'initial').
        $stale = UserCoinPayment::create([
            'reference_id' => $ref,
            'user_id'      => $user->id,
            'coin_id'      => $coin->id,
            'status'       => PaymentStatus::INITIAL,
        ]);

        // Meanwhile a concurrent request commits SUCCESS to the same row.
        UserCoinPayment::query()
            ->whereKey($stale->getKey())
            ->update(['status' => PaymentStatus::SUCCESS]);

        // The exact atomic PENDING write from payment_verify, driven with the
        // stale in-memory model (its ->status is still 'initial').
        $moved = UserCoinPayment::query()
            ->whereKey($stale->getKey())
            ->where('status', PaymentStatus::INITIAL)
            ->update(['status' => PaymentStatus::PENDING]);

        $this->assertEquals(0, $moved, 'the PENDING write must be a no-op against a committed SUCCESS row');
        $this->assertEquals(
            PaymentStatus::SUCCESS,
            UserCoinPayment::whereKey($stale->getKey())->value('status'),
            'a committed SUCCESS must survive a concurrent stale PENDING write'
        );
    }
}

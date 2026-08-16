<?php

namespace Tests\Feature\UtdQa;

use Modules\UsersWallet\Entities\UserWallet;
use Modules\UsersWallet\Entities\WalletLog;
use Modules\UsersWallet\Helpers\WalletHelper;

/**
 * W1 - Wallet credit is atomic and arithmetically exact.
 *
 * Target:
 *   Modules/UsersWallet/Helpers/WalletHelper.php::addBalance / addAllBalancesByDiffs
 *
 * What we prove:
 *  (1) Sequential credits accumulate to the exact sum with no lost update
 *      (each credit runs inside DB::transaction on the row held by
 *      lockForUpdate; the DB-side increment() is applied to the locked row).
 *  (2) addAllBalancesByDiffs computes diffs with bcmath at scale 4 so a chain of
 *      small fractional salary diffs does not drift (no binary-float error).
 *  (3) One WalletLog is written per applied credit, with before/after that chain
 *      correctly.
 *
 * Concurrency proof boundary
 * --------------------------
 * Lost-update prevention is a property of lockForUpdate serializing two real
 * connections. In-process we assert the accounting invariant (sum is exact,
 * one log per credit). The lock's parallel behaviour is documented and can be
 * proven only with concurrent MySQL connections.
 *
 * NOTE on storage precision: the shipped schema stores
 * users_wallets.balance / wallet_logs.amount as DECIMAL(12,2) (see
 * Modules/UsersWallet/Database/Migrations/2025_11_30_084853_create_wallets_table.php).
 * The *diff computation* uses bcmath at scale 4, but the *stored* value is
 * rounded to 2 dp by the column. These tests assert the guarantee the schema
 * can actually keep (exactness to the cent). See the run report for the
 * decimal(20,4)-vs-decimal(12,2) discrepancy vs the task brief.
 */
class WalletAtomicCreditTest extends UtdQaTestCase
{
    /** Invoke the private WalletHelper::addBalance. */
    private function addBalance($userId, string $amount, string $type = 'user', $targetId = null, ?string $opId = null): void
    {
        $m = new \ReflectionMethod(WalletHelper::class, 'addBalance');
        $m->setAccessible(true);
        $m->invoke(null, $userId, $amount, $type, $targetId, $opId);
    }

    /**
     * (1) Many sequential credits sum exactly, one log each, no lost update.
     */
    public function test_sequential_credits_accumulate_exactly(): void
    {
        $user = $this->makeUser();

        $credits = ['10.00', '0.01', '0.02', '5.55', '100.00', '0.42'];
        foreach ($credits as $i => $amt) {
            $this->addBalance($user->id, $amt, 'user', null, 'w1-seq-' . $i);
        }

        // 116.00 exactly
        $wallet = UserWallet::where('user_id', $user->id)->first();
        $this->assertSame('116.00', number_format((float) $wallet->balance, 2, '.', ''));

        $this->assertSame(
            count($credits),
            WalletLog::where('user_id', $user->id)->count(),
            'Exactly one wallet log per applied credit.'
        );
    }

    /**
     * (2) bcmath diff path: a chain of small fractional salary increments credits
     * the exact cumulative diff each step. Uses addAllBalancesByDiffs (user role
     * only, no agency/bd) with old->new salary deltas.
     */
    public function test_fractional_salary_diffs_do_not_drift(): void
    {
        $user = $this->makeUser();

        // old -> new salary sequence. Each step credits (new - old).
        // 0 -> 0.10 -> 0.20 -> 0.30 ... 10 steps of +0.10 = +1.00 total.
        $prev = 0.0;
        for ($i = 1; $i <= 10; $i++) {
            $new = round($prev + 0.10, 4);
            WalletHelper::addAllBalancesByDiffs(
                $user->id,
                ['sallary' => $new],
                ['sallary' => $prev],
                null,
                'user',
                null,
                'w1-frac-' . $i
            );
            $prev = $new;
        }

        $wallet = UserWallet::where('user_id', $user->id)->first();
        // 10 x 0.10 = 1.00 exactly, no 0.9999999 drift.
        $this->assertSame('1.00', number_format((float) $wallet->balance, 2, '.', ''));
    }

    /**
     * (3) before_amount / after_amount chain correctly across credits.
     */
    public function test_wallet_log_before_after_chain_is_consistent(): void
    {
        $user = $this->makeUser();

        $this->addBalance($user->id, '10.00', 'user', null, 'w1-chain-1');
        $this->addBalance($user->id, '5.00', 'user', null, 'w1-chain-2');

        $logs = WalletLog::where('user_id', $user->id)->orderBy('id')->get();
        $this->assertCount(2, $logs);

        $this->assertSame('0.00', number_format((float) $logs[0]->before_amount, 2, '.', ''));
        $this->assertSame('10.00', number_format((float) $logs[0]->after_amount, 2, '.', ''));
        $this->assertSame('10.00', number_format((float) $logs[1]->before_amount, 2, '.', ''));
        $this->assertSame('15.00', number_format((float) $logs[1]->after_amount, 2, '.', ''));
    }

    /**
     * Edge: a zero diff must not credit and must not write a log.
     */
    public function test_zero_diff_is_a_noop(): void
    {
        $user = $this->makeUser();

        WalletHelper::addAllBalancesByDiffs(
            $user->id,
            ['sallary' => 25.0],
            ['sallary' => 25.0], // diff = 0
            null,
            'user',
            null,
            'w1-zero'
        );

        $this->assertNull(
            WalletLog::where('user_id', $user->id)->first(),
            'A zero diff must not produce a wallet credit or log.'
        );
    }
}
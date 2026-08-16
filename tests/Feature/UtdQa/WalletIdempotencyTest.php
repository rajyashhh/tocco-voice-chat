<?php

namespace Tests\Feature\UtdQa;

use App\Jobs\UpdateUserWalletBalances;
use Modules\UsersWallet\Entities\UserWallet;
use Modules\UsersWallet\Entities\WalletLog;

/**
 * W2 - Wallet credit is idempotent per (operationId, role).
 *
 * Targets:
 *   Modules/UsersWallet/Helpers/WalletHelper.php::addBalance (exists() guard)
 *   app/Jobs/UpdateUserWalletBalances.php (carries a stable operationId)
 *
 * What we prove:
 *  (1) Running the SAME job (same operationId) twice credits the wallet ONCE;
 *      the second run is a no-op (retry / at-least-once delivery safe).
 *  (2) A DIFFERENT operationId credits normally (the guard keys on the id, it
 *      does not swallow legitimate distinct credits).
 *  (3) The same operationId under a DIFFERENT role/type is a distinct credit
 *      (the idempotency key is (operation_uuid, type), matching the unique
 *      index backstop).
 *
 * This exercises the application-level exists() check inside a locked
 * transaction. The DB-level UNIQUE(operation_uuid, type) index is the second
 * line of defence against a true race; its enforcement is proven separately
 * (see report) because it requires two concurrent connections inserting the
 * same key. Here we prove the logical guard, which is what protects against the
 * common case: a job retried after a transient failure.
 */
class WalletIdempotencyTest extends UtdQaTestCase
{
    private function runJob($userId, float $newSalary, float $oldSalary, string $type, string $opId): void
    {
        // sync queue: dispatch executes handle() inline.
        (new UpdateUserWalletBalances(
            $userId,
            ['sallary' => $newSalary],
            ['sallary' => $oldSalary],
            null,
            $type,
            null,
            $opId
        ))->handle();
    }

    /**
     * (1) Same operationId twice -> credited once.
     */
    public function test_same_operation_id_credits_once(): void
    {
        $user = $this->makeUser();

        $this->runJob($user->id, 20.0, 0.0, 'user', 'w2-op-A');
        $this->runJob($user->id, 20.0, 0.0, 'user', 'w2-op-A'); // replay

        $wallet = UserWallet::where('user_id', $user->id)->first();
        $this->assertSame('20.00', number_format((float) $wallet->balance, 2, '.', ''), 'A replayed job must not double-credit.');

        $this->assertSame(
            1,
            WalletLog::where('user_id', $user->id)->where('operation_uuid', 'w2-op-A')->where('type', 'user')->count(),
            'Exactly one wallet log for the (operationId, role) pair.'
        );
    }

    /**
     * (2) Different operationId -> credited again (guard does not over-suppress).
     */
    public function test_different_operation_id_credits_again(): void
    {
        $user = $this->makeUser();

        $this->runJob($user->id, 20.0, 0.0, 'user', 'w2-op-B1');
        $this->runJob($user->id, 5.0, 0.0, 'user', 'w2-op-B2');

        $wallet = UserWallet::where('user_id', $user->id)->first();
        $this->assertSame('25.00', number_format((float) $wallet->balance, 2, '.', ''));
        $this->assertSame(2, WalletLog::where('user_id', $user->id)->count());
    }

    /** Invoke the private WalletHelper::addBalance (composite-key proof). */
    private function addBalance($userId, string $amount, string $type, ?string $opId): void
    {
        $m = new \ReflectionMethod(\Modules\UsersWallet\Helpers\WalletHelper::class, 'addBalance');
        $m->setAccessible(true);
        $m->invoke(null, $userId, $amount, $type, null, $opId);
    }

    /**
     * (3) The idempotency key is the composite (operation_uuid, type), matching
     * the unique-index backstop. The SAME operationId under a DIFFERENT role/type
     * is a distinct credit; the guard keys on both columns, not on the id alone.
     *
     * Note: this exercises WalletHelper::addBalance directly with two roles,
     * because the wallet-diff job path always credits the user diff as
     * type='user' (the job's $type argument does not re-label the user credit),
     * so the composite key can only be varied at the helper boundary.
     */
    public function test_same_operation_id_different_role_is_distinct(): void
    {
        $user = $this->makeUser();

        // Same op id, two different roles crediting the SAME wallet user id.
        $this->addBalance($user->id, '10.00', 'user', 'w2-op-C');
        $this->addBalance($user->id, '7.00', 'agency_owner', 'w2-op-C');

        $wallet = UserWallet::where('user_id', $user->id)->first();
        $this->assertSame('17.00', number_format((float) $wallet->balance, 2, '.', ''));
        $this->assertSame(
            2,
            WalletLog::where('user_id', $user->id)->where('operation_uuid', 'w2-op-C')->count(),
            'Same op id under two roles must be two distinct credits (key is operation_uuid + type).'
        );

        // And a replay of EITHER (opId, type) pair is still suppressed.
        $this->addBalance($user->id, '10.00', 'user', 'w2-op-C'); // replay of the first pair
        $this->assertSame(
            '17.00',
            number_format((float) UserWallet::where('user_id', $user->id)->first()->balance, 2, '.', ''),
            'Replaying an already-applied (opId, type) pair must be a no-op.'
        );
    }

    /**
     * Replay stress: five identical runs still credit exactly once.
     */
    public function test_multiple_replays_credit_once(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->runJob($user->id, 12.34, 0.0, 'user', 'w2-op-D');
        }

        $wallet = UserWallet::where('user_id', $user->id)->first();
        $this->assertSame('12.34', number_format((float) $wallet->balance, 2, '.', ''));
        $this->assertSame(1, WalletLog::where('user_id', $user->id)->count());
    }
}
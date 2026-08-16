<?php

namespace App\Services;

use App\Models\ShippingAdminTransaction;
use App\Models\ShippingAgency;
use App\Models\ShippingSuperAdmin;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Country\Entities\SuperAdmin;

/**
 * The single money engine for the shipping super admin layer. Both flows it owns
 * are coin-only (admin_users.di is an integer coin balance) and are hardened the
 * same way:
 *
 *   - Atomic: the debit, the credit and both ledger legs commit together inside
 *     one DB::transaction, or nothing does.
 *   - Locked: every balance row is held under lockForUpdate BEFORE it is read,
 *     acquired in a fixed order (ascending admin_users.id within a table; and,
 *     across tables, admin_users before agencies) so concurrent moves serialize
 *     without deadlocking.
 *   - Non-negative: the sender's sufficiency check runs INSIDE the lock, so a
 *     race cannot oversubscribe a balance.
 *   - Idempotent: the caller supplies operation_uuid; the presence of the OUT leg
 *     for that uuid short-circuits the whole move to a no-op. The unique
 *     (operation_uuid, type) index is the DB-level backstop.
 *   - Bounded: credits are rejected if they would push the receiver past
 *     self::MAX_COINS (BIGINT headroom).
 *
 * Trust boundary: the sender is always the authenticated actor resolved by the
 * caller (never read from the request), and the receiver's scope is enforced by
 * the caller before delegating here.
 */
class ShippingSuperAdminWalletService
{
    /**
     * Signed BIGINT ceiling with headroom. admin_users.di is signed BIGINT and
     * agencies.coins is unsigned BIGINT; this bound is safe for both.
     */
    public const MAX_COINS = 9000000000000000000;

    /**
     * Global freeze kill-switch for the shipping super admin layer, the coin
     * counterpart of BD's `bd_stop_charge`. When engaged, the shipping super
     * admin's ONLY outbound money move (chargeAgency) is refused. Read via
     * Common::stopSwitch (fail-closed) exactly like bd_stop_charge; a baseline
     * '0' row is seeded so a fresh clone defaults to NOT frozen. Enforced here in
     * the money engine — not only in the controller — so no caller can bypass it.
     */
    public const SHIPPING_STOP_CHARGE_KEY = 'shipping_super_admin_stop_charge';

    /**
     * Country Manager -> Shipping Super Admin. Debits the country manager's coin
     * balance (di) and credits the shipping super admin's coin balance (di).
     *
     * @throws \RuntimeException on validation / insufficient-balance / overflow.
     * @return bool true on apply, false when the operation was already applied.
     */
    public function fundFromCountryManager(
        SuperAdmin $countryManager,
        ShippingSuperAdmin $target,
        int $coins,
        string $operationUuid
    ): bool {
        $this->assertPositive($coins);

        return $this->applyIdempotent(fn () => DB::transaction(function () use ($countryManager, $target, $coins, $operationUuid) {
            // Idempotency short-circuit (in-transaction, so it is consistent with
            // the row locks below).
            if ($this->alreadyApplied($operationUuid, ShippingAdminTransaction::FUND_OUT)) {
                return false;
            }

            // Both balances live on admin_users -> lock in ascending id order and
            // read di from the very same locked read (never a separate, unlocked
            // query, which under REPEATABLE READ would return the transaction's
            // stale snapshot instead of the post-lock value).
            [$firstId, $secondId] = $this->ascending($countryManager->id, $target->id);
            $balances = [
                $firstId  => $this->lockDi($firstId),
                $secondId => $this->lockDi($secondId),
            ];

            $senderBefore   = $balances[$countryManager->id];
            $receiverBefore = $balances[$target->id];

            if ($senderBefore < $coins) {
                throw new \RuntimeException(__('balance not enough'));
            }
            $this->assertNoOverflow($receiverBefore, $coins);

            $this->addDi($countryManager->id, -$coins);
            $this->addDi($target->id, $coins);

            $this->writeLeg(
                $operationUuid,
                ShippingAdminTransaction::FUND_OUT,
                'fund',
                'country_manager',
                $countryManager->id,
                'shipping_super_admin',
                $target->id,
                $coins,
                $senderBefore,
                $senderBefore - $coins,
                $target->country_id,
                $countryManager->id
            );
            $this->writeLeg(
                $operationUuid,
                ShippingAdminTransaction::FUND_IN,
                'fund',
                'country_manager',
                $countryManager->id,
                'shipping_super_admin',
                $target->id,
                $coins,
                $receiverBefore,
                $receiverBefore + $coins,
                $target->country_id,
                $countryManager->id
            );

            return true;
        }));
    }

    /**
     * Shipping Super Admin -> Shipping Agency. Debits the shipping super admin's
     * coin balance (di) and credits the agency's coin balance (agencies.coins).
     *
     * @throws \RuntimeException on validation / insufficient-balance / overflow.
     * @return bool true on apply, false when the operation was already applied.
     */
    public function chargeAgency(
        ShippingSuperAdmin $sender,
        ShippingAgency $agency,
        int $coins,
        string $operationUuid
    ): bool {
        $this->assertPositive($coins);

        return $this->applyIdempotent(fn () => DB::transaction(function () use ($sender, $agency, $coins, $operationUuid) {
            if ($this->alreadyApplied($operationUuid, ShippingAdminTransaction::CHARGE_OUT)) {
                return false;
            }

            // Global freeze gate (coin counterpart of bd_stop_charge). Refused
            // BEFORE any balance is read or written, inside the transaction so a
            // freeze toggled mid-flight can never leave a partial move. Fail-closed
            // via Common::stopSwitch: an unknown switch state stops the money flow.
            if (\App\Helpers\Common::stopSwitch(self::SHIPPING_STOP_CHARGE_KEY)) {
                throw new \RuntimeException(__('api_responses.freez_charge'));
            }

            // Cross-table lock: fixed order admin_users then agencies. Read di
            // from the locked read itself, not a separate unlocked query.
            $senderBefore = $this->lockDi($sender->id);
            $agencyBefore = (int) DB::table('agencies')
                ->where('id', $agency->id)
                ->lockForUpdate()
                ->value('coins');

            if ($senderBefore < $coins) {
                throw new \RuntimeException(__('balance not enough'));
            }
            $this->assertNoOverflow($agencyBefore, $coins);

            $this->addDi($sender->id, -$coins);
            DB::table('agencies')->where('id', $agency->id)->increment('coins', $coins);

            $this->writeLeg(
                $operationUuid,
                ShippingAdminTransaction::CHARGE_OUT,
                'charge',
                'shipping_super_admin',
                $sender->id,
                'shipping_agency',
                $agency->id,
                $coins,
                $senderBefore,
                $senderBefore - $coins,
                $agency->country_id,
                $sender->id
            );
            $this->writeLeg(
                $operationUuid,
                ShippingAdminTransaction::CHARGE_IN,
                'charge',
                'shipping_super_admin',
                $sender->id,
                'shipping_agency',
                $agency->id,
                $coins,
                $agencyBefore,
                $agencyBefore + $coins,
                $agency->country_id,
                $sender->id
            );

            return true;
        }));
    }

    /**
     * Runs a wallet move and converts a unique-index collision on
     * (operation_uuid, type) into the same graceful "already processed" no-op
     * that alreadyApplied() returns. This closes the window where two concurrent
     * requests carrying the same operation_uuid both pass the in-transaction
     * existence check and then race on the INSERT: the loser's transaction is
     * rolled back by DB::transaction before this catch runs, so no partial state
     * survives. The catch is narrowed to duplicate-entry (MySQL error 1062); any
     * other QueryException still propagates.
     *
     * @param  \Closure():bool  $move
     */
    private function applyIdempotent(\Closure $move): bool
    {
        try {
            return $move();
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return false;
            }
            throw $e;
        }
    }

    private function assertPositive(int $coins): void
    {
        if ($coins <= 0) {
            throw new \RuntimeException(__('This value is not allowed'));
        }
        if ($coins > self::MAX_COINS) {
            throw new \RuntimeException(__('This value is not allowed'));
        }
    }

    private function assertNoOverflow(int $before, int $coins): void
    {
        if ($before > self::MAX_COINS - $coins) {
            throw new \RuntimeException(__('This value is not allowed'));
        }
    }

    private function alreadyApplied(string $operationUuid, string $outType): bool
    {
        return ShippingAdminTransaction::where('operation_uuid', $operationUuid)
            ->where('type', $outType)
            ->exists();
    }

    /**
     * @return array{0:int,1:int} the two ids sorted ascending.
     */
    private function ascending(int $a, int $b): array
    {
        return $a <= $b ? [$a, $b] : [$b, $a];
    }

    /**
     * Acquire the row lock on admin_users and return the coin balance (di) from
     * that same locked read, so the value reflects any committed concurrent move
     * rather than the transaction's stale snapshot.
     */
    private function lockDi(int $id): int
    {
        return (int) DB::table('admin_users')->where('id', $id)->lockForUpdate()->value('di');
    }

    private function addDi(int $id, int $delta): void
    {
        if ($delta >= 0) {
            DB::table('admin_users')->where('id', $id)->increment('di', $delta);
        } else {
            DB::table('admin_users')->where('id', $id)->decrement('di', -$delta);
        }
    }

    private function writeLeg(
        string $operationUuid,
        string $type,
        string $operation,
        string $senderType,
        int $senderId,
        string $receiverType,
        int $receiverId,
        int $coins,
        int $before,
        int $after,
        $countryId,
        int $createdBy
    ): void {
        ShippingAdminTransaction::create([
            'operation_uuid' => $operationUuid,
            'type'           => $type,
            'operation'      => $operation,
            'sender_type'    => $senderType,
            'sender_id'      => $senderId,
            'receiver_type'  => $receiverType,
            'receiver_id'    => $receiverId,
            'coins'          => $coins,
            'before_amount'  => $before,
            'after_amount'   => $after,
            'country_id'     => $countryId,
            'created_by'     => $createdBy,
        ]);
    }
}
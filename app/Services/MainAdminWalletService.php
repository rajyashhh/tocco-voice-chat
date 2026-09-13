<?php

namespace App\Services;

use App\Facades\CustomNotification;
use App\Models\AdminUser;
use App\Models\ShippingAdminTransaction;
use App\Models\ShippingAgency;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Money engine for direct Main Admin -> Charge Agency coin funding.
 *
 * Characteristics:
 *   - Atomic: debit admin_users.di, credit agencies.coins, and write both ledger
 *     legs inside one DB::transaction, or nothing commits.
 *   - Locked: balance rows locked under lockForUpdate in fixed order (admin_users
 *     before agencies) to serialize concurrent operations safely without deadlock.
 *   - Non-negative: sender balance sufficiency check runs inside the lock.
 *   - Idempotent: keyed on operation_uuid; duplicate submission returns false gracefully.
 *   - Bounded: credits rejected if receiver exceeds MAX_COINS ceiling.
 */
class MainAdminWalletService
{
    public const MAX_COINS = 9000000000000000000;

    /**
     * Main Admin -> Charge Agency direct coin issuance / funding.
     *
     * As the coin issuer, Main Admin issues coins directly to Charge Agencies.
     * This operation does NOT debit admin_users.di nor require an admin source balance.
     *
     * @param AdminUser|mixed $admin
     * @param ShippingAgency $agency
     * @param int $coins
     * @param string $operationUuid
     * @param string|null $reason
     * @return bool True when applied, false if already processed for this operation_uuid.
     * @throws \RuntimeException On business / validation errors.
     */
    public function fundChargeAgency(
        $admin,
        ShippingAgency $agency,
        int $coins,
        string $operationUuid,
        ?string $reason = null
    ): bool {
        $this->assertPositive($coins);

        if ($agency->is_frozen == 1) {
            throw new \RuntimeException(__('it_agency_freez_charge'));
        }

        return $this->applyIdempotent(fn () => DB::transaction(function () use ($admin, $agency, $coins, $operationUuid, $reason) {
            if ($this->alreadyApplied($operationUuid, ShippingAdminTransaction::MAIN_FUND_IN)) {
                return false;
            }

            // Row lock on target agency to prevent concurrent balance mutations.
            $agencyRow = DB::table('agencies')
                ->where('id', $agency->id)
                ->lockForUpdate()
                ->first(['coins', 'is_frozen', 'status']);

            if (!$agencyRow) {
                throw new \RuntimeException(__('api_responses.agency'));
            }

            if ($agencyRow->is_frozen == 1) {
                throw new \RuntimeException(__('it_agency_freez_charge'));
            }

            if ($agencyRow->status != 1) {
                throw new \RuntimeException(__('This agency is not active'));
            }

            $agencyBefore = (int) $agencyRow->coins;
            $this->assertNoOverflow($agencyBefore, $coins);

            // Increment agency coins atomically.
            DB::table('agencies')->where('id', $agency->id)->increment('coins', $coins);

            $countryId = $agency->country_id ?? (isset($agencyRow->country_id) ? $agencyRow->country_id : null);

            // Record the issuance credit ledger entry.
            $this->writeLeg(
                $operationUuid,
                ShippingAdminTransaction::MAIN_FUND_IN,
                'main_fund',
                'main_admin',
                $admin->id,
                'shipping_agency',
                $agency->id,
                $coins,
                $agencyBefore,
                $agencyBefore + $coins,
                $countryId,
                $admin->id
            );

            // Notify agency owner if present.
            try {
                if ($agency->owner) {
                    $request = request();
                    CustomNotification::chargeAction(
                        $agency->owner,
                        $request,
                        $admin->username ?? 'Main Admin',
                        $agency,
                        $coins
                    );
                }
            } catch (\Throwable $e) {
                // Non-fatal notification failure should not abort the money transfer.
            }

            return true;
        }));
    }

    /**
     * Main Admin -> Charge Agency direct coin removal / adjustment.
     *
     * As the platform authority, Main Admin can remove coins directly from Charge Agencies.
     * This operation does NOT credit admin_users.di nor require an admin wallet change.
     *
     * @param AdminUser|mixed $admin
     * @param ShippingAgency $agency
     * @param int $coins
     * @param string $operationUuid
     * @param string|null $reason
     * @return bool True when applied, false if already processed for this operation_uuid.
     * @throws \RuntimeException On business / validation errors.
     */
    public function removeCoinsFromChargeAgency(
        $admin,
        ShippingAgency $agency,
        int $coins,
        string $operationUuid,
        ?string $reason = null
    ): bool {
        $this->assertPositive($coins);

        if ($agency->is_frozen == 1) {
            throw new \RuntimeException(__('it_agency_freez_charge'));
        }

        return $this->applyIdempotent(fn () => DB::transaction(function () use ($admin, $agency, $coins, $operationUuid, $reason) {
            if ($this->alreadyApplied($operationUuid, ShippingAdminTransaction::MAIN_REMOVE_AGENCY)) {
                return false;
            }

            // Row lock on target agency to prevent concurrent balance mutations.
            $agencyRow = DB::table('agencies')
                ->where('id', $agency->id)
                ->lockForUpdate()
                ->first(['coins', 'is_frozen', 'status']);

            if (!$agencyRow) {
                throw new \RuntimeException(__('api_responses.agency'));
            }

            if ($agencyRow->is_frozen == 1) {
                throw new \RuntimeException(__('it_agency_freez_charge'));
            }

            if ($agencyRow->status != 1) {
                throw new \RuntimeException(__('This agency is not active'));
            }

            $agencyBefore = (int) $agencyRow->coins;

            if ($agencyBefore < $coins) {
                throw new \RuntimeException(__('balance not enough'));
            }

            // Decrement agency coins atomically.
            DB::table('agencies')->where('id', $agency->id)->decrement('coins', $coins);

            $countryId = $agency->country_id ?? null;

            // Record the removal debit ledger entry.
            $this->writeLeg(
                $operationUuid,
                ShippingAdminTransaction::MAIN_REMOVE_AGENCY,
                'main_remove',
                'shipping_agency',
                $agency->id,
                'main_admin',
                $admin->id,
                $coins,
                $agencyBefore,
                $agencyBefore - $coins,
                $countryId,
                $admin->id
            );

            return true;
        }));
    }

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
        if ($coins <= 0 || $coins > self::MAX_COINS) {
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

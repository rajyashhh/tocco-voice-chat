<?php

namespace Modules\UsersWallet\Helpers;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\UsersWallet\Entities\UserWallet;
use Modules\UsersWallet\Entities\WalletLog;
use App\Models\Agency;
use App\Models\PaymentWithdrawType;
use Modules\UsersWallet\Entities\UserWithdrawal;

class WalletHelper
{
    /**
     *
     * @param int $userId
     * @param array $newData  ['sallary'=>..,'agency_sallary'=>..,'dB'=>..]
     * @param array|null $oldData ['sallary'=>..,'agency_sallary'=>..,'dB'=>..]
     * @param int|null $agencyId
     * @param string $type
     */
    public static function addAllBalancesByDiffs(int $userId, array $newData, ?array $oldData = null, ?int $agencyId = null, string $type = 'system' ,$target_id = null, ?string $operationId = null)
    {

        // Diffs come from decimal(20,4) salary columns: compute with bcmath at scale 4
        // to avoid binary-float drift before the value is credited to the wallet.
        $user_diff   = bcsub(sprintf('%.4f', $newData['sallary'] ?? 0), sprintf('%.4f', $oldData['sallary'] ?? 0), 4);
        $agency_diff = bcsub(sprintf('%.4f', $newData['agency_sallary'] ?? 0), sprintf('%.4f', $oldData['agency_sallary'] ?? 0), 4);
        $bd_diff     = bcsub(sprintf('%.4f', $newData['dB'] ?? 0), sprintf('%.4f', $oldData['dB'] ?? 0), 4);

        if (!$userId) {
            throw new Exception('معرف المستخدم غير موجود.');
        }

        if (bccomp($user_diff, '0', 4) !== 0) {
            self::addBalance($userId, $user_diff, 'user' ,$target_id, $operationId);
        }

        $ownerId = null;
        $bdId    = null;

        if ($agencyId) {
            $agency = Agency::find($agencyId);
            if ($agency) {
                $ownerId = $agency->app_owner_id;
                $bdId    = @$agency?->bd?->app_id;
            }
        }
        if ($ownerId && bccomp($agency_diff, '0', 4) !== 0) {
            self::addBalance($ownerId, $agency_diff, 'agency_owner',$target_id, $operationId);
        }

        if ($bdId && bccomp($bd_diff, '0', 4) !== 0) {
            self::addBalance($bdId, $bd_diff, 'bd' ,$target_id, $operationId);
        }
    }


    private static function addBalance($userId, $amount, $type ,$target_id, ?string $operationId = null)
    {
        UserWallet::firstOrCreate(['user_id' => $userId]);

        DB::transaction(function () use ($userId, $amount, $type, $target_id, $operationId) {
            // Hold the wallet row so the credit is serialized (no lost update) and the
            // idempotency check + write are atomic against concurrent retries.
            $wallet = UserWallet::where('user_id', $userId)->lockForUpdate()->first();

            // Idempotency: a given (operation, role) pair is applied at most once.
            // A retry of the same job — or a partial re-run — becomes a no-op instead
            // of a double credit. A unique index on (operation_uuid, type) is the backstop.
            if ($operationId !== null) {
                $already = WalletLog::where('operation_uuid', $operationId)
                    ->where('type', $type)
                    ->exists();
                if ($already) {
                    return;
                }
            }

            $before = $wallet->balance;
            // DB-side arithmetic on the locked row.
            $wallet->increment('balance', $amount);
            $wallet->refresh();
            $after = $wallet->balance;

            WalletLog::create([
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'amount' => $amount,
                'operation' => 'add',
                'type' => $type,
                'before_amount' => $before,
                'after_amount' => $after,
                'related_id' => $target_id,
                'operation_uuid' => $operationId,
            ]);
        });
    }


    /**
     * Creates a pending withdrawal against an admin-defined withdraw method.
     *
     * Server-side trust boundary: the method, the minimum, and every required
     * field are re-validated here from the DB — nothing from the client is
     * trusted. $fields is the client-submitted map of withdraw_field_id => value
     * for the chosen method's fields.
     *
     * B3 (kept): the balance check and pending debit run inside DB::transaction
     * with the wallet row held under lockForUpdate, so availability is re-read
     * atomically and concurrent withdrawals can't oversubscribe the wallet.
     */
    public static function createWithdrawal($userId, $amount, $paymentWithdrawTypeId, array $fields = [])
    {
        $type = PaymentWithdrawType::with('withdrawFields')->find($paymentWithdrawTypeId);
        if (!$type) {
            throw new Exception('Withdraw method not found.');
        }

        if ($amount < (float) $type->min_value) {
            throw new Exception('Amount is below the minimum for this withdraw method.');
        }

        // Validate submitted values against THIS method's defined fields only.
        // Every defined field is required; unknown field ids are rejected.
        $definedFieldIds = $type->withdrawFields->pluck('id')->all();
        $storedFields = [];
        foreach ($type->withdrawFields as $field) {
            $value = $fields[$field->id] ?? null;
            if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
                throw new Exception('Missing required field: ' . ($field->name ?? $field->id));
            }
            if ($field->type === 'int' && !is_numeric($value)) {
                throw new Exception('Field must be numeric: ' . ($field->name ?? $field->id));
            }
            $storedFields[] = [
                'field_id' => $field->id,
                'name'     => $field->name,
                'name_en'  => $field->name_en,
                'type'     => $field->type,
                'value'    => $field->type === 'int' ? (string) $value : trim((string) $value),
            ];
        }
        foreach (array_keys($fields) as $submittedId) {
            if (!in_array((int) $submittedId, $definedFieldIds, true)) {
                throw new Exception('Unknown field for this withdraw method.');
            }
        }

        $meta = [
            'payment_withdraw_type_id' => $type->id,
            'method_name'              => $type->name,
            'method_name_en'           => $type->name_en,
            'fields'                   => $storedFields,
        ];

        UserWallet::firstOrCreate(['user_id' => $userId]);

        return DB::transaction(function () use ($userId, $amount, $type, $meta) {
            $wallet = UserWallet::where('user_id', $userId)->lockForUpdate()->first();

            $before = $wallet?->balance ?? 0;
            $available = wallet_available_by_wallet($wallet);

            if ($available < $amount) {
                throw new Exception('Insufficient balance.');
            }

            $wallet->increment('pending_amount', $amount);

            $userWithdrawal = UserWithdrawal::create([
                'user_id'                  => $userId,
                'amount'                   => $amount,
                'payment_withdraw_type_id' => $type->id,
                'status'                   => 'pending',
                'meta'                     => $meta,
            ]);

            WalletLog::create([
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'amount' => $amount,
                'operation' => 'withdrawal_pending',
                'type' => 'user',
                'before_amount' => $before,
                'after_amount' => $wallet->balance,
                'related_id' => $userWithdrawal->id,
            ]);

            return $userWithdrawal;
        });
    }
    

}

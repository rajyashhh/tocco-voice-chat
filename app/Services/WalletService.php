<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;
use Modules\UsersWallet\Entities\UserWallet;
use Modules\UsersWallet\Entities\WalletLog;

class WalletService
{
    /**
     * Store transaction with effect on wallet + transaction classification.
     * Uses users_wallets and wallet_logs tables (Modules\UsersWallet)
     *
     * @param int $userId
     * @param string $type  [add, cut, pending]
     * @param float $amount
     * @param string|null $transactionsType [agency_transaction, user_transaction, etc.]
     * @param string|null $description
     * @param array|string|null $descriptionData
     * @param string $message
     * @return WalletLog|null
     */
    public static function storeTransaction(
        int $userId,
        string $type,
        float $amount,
        ?string $transactionsType = null,
        ?string $description = null,
        $descriptionData = null,
        $message = 'charge'
    ): ?WalletLog {
        return DB::transaction(function () use ($userId, $type, $amount, $transactionsType, $description, $descriptionData, $message) {
            // Get or create wallet (users_wallets table)
            $wallet = UserWallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0, 'cut_amount' => 0, 'pending_amount' => 0]
            );

            // Calculate before amount
            $beforeAmount = wallet_available_by_wallet($wallet);

            // Determine operation and update wallet
            $operation = null;
            $logAmount = 0;

            switch ($type) {
                case 'add':
                    $wallet->balance += $amount;
                    $operation = 'add';
                    $logAmount = $amount;
                    break;

                case 'cut':
                    // Check if sufficient balance before cutting
                    $availableBalance = wallet_available_by_wallet($wallet);
                    // if ($availableBalance < $amount) {
                    //     throw new Exception("Insufficient balance. Available: $availableBalance, Required: $amount");
                    // }
                    
                    $wallet->cut_amount += $amount;
                    $operation = 'subtract';
                    $logAmount = -$amount; // Store positive amount in log
                    break;

                case 'pending':
                    $wallet->pending_amount += $amount;
                    // Pending updates wallet but doesn't create log entry
                    break;

                default:
                    throw new Exception("Unsupported wallet transaction type: $type");
            }

            $wallet->save();

            // Create log entry for add/cut operations only
            if ($operation !== null) {
                $relatedId = null;
                if (is_array($descriptionData)) {
                    $relatedId = $descriptionData['receiver_id'] 
                        ?? $descriptionData['agency_id'] 
                        ?? $descriptionData['target_id'] 
                        ?? null;
                }

                return WalletLog::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $userId,
                    'amount' => $logAmount, // Always store positive amount
                    'operation' => $operation,
                    'type' => $transactionsType ?? $message,
                    'before_amount' => $beforeAmount,
                    'after_amount' => wallet_available_by_wallet($wallet),
                    'related_id' => $relatedId,
                ]);
            }

            return null;
        });
    }

    /**
     * Recalculate wallet balance from logs (for fixing corrupted data)
     * 
     * @param int $userId
     * @return array ['old_balance' => float, 'new_balance' => float, 'corrected' => bool]
     */
    public static function recalculateWalletBalance(int $userId): array
    {
        return DB::transaction(function () use ($userId) {
            $wallet = UserWallet::where('user_id', $userId)->first();
            
            if (!$wallet) {
                // throw new Exception("Wallet not found for user: $userId");
            }

            $oldBalance = $wallet->balance;
            $oldCutAmount = $wallet->cut_amount;
            $oldPendingAmount = $wallet->pending_amount;
            $oldAvailable = wallet_available_by_wallet($wallet);

            // Recalculate from logs
            $logs = WalletLog::where('user_id', $userId)
                ->orderBy('created_at', 'asc')
                ->get();

            $calculatedBalance = 0;
            $calculatedCutAmount = 0;

            foreach ($logs as $log) {
                $amount = abs($log->amount); // Always use absolute value
                
                if ($log->operation === 'add') {
                    $calculatedBalance += $amount;
                } elseif ($log->operation === 'subtract') {
                    $calculatedCutAmount += $amount;
                }
            }

            // Update wallet with corrected values
            $wallet->balance = $calculatedBalance;
            $wallet->cut_amount = $calculatedCutAmount;
            // Keep pending_amount as is (it's managed separately)
            $wallet->save();

            $newAvailable = wallet_available_by_wallet($wallet);

            return [
                'user_id' => $userId,
                'old' => [
                    'balance' => $oldBalance,
                    'cut_amount' => $oldCutAmount,
                    'pending_amount' => $oldPendingAmount,
                    'available' => $oldAvailable,
                ],
                'new' => [
                    'balance' => $wallet->balance,
                    'cut_amount' => $wallet->cut_amount,
                    'pending_amount' => $wallet->pending_amount,
                    'available' => $newAvailable,
                ],
                'corrected' => ($oldBalance != $wallet->balance || $oldCutAmount != $wallet->cut_amount),
                'logs_count' => $logs->count(),
            ];
        });
    }

    /**
     * Validate wallet integrity
     * 
     * @param int $userId
     * @return array ['valid' => bool, 'issues' => array]
     */
    public static function validateWallet(int $userId): array
    {
        $wallet = UserWallet::where('user_id', $userId)->first();
        
        if (!$wallet) {
            return [
                'valid' => false,
                'issues' => ['Wallet not found']
            ];
        }

        $issues = [];

        // Check for negative balance
        if ($wallet->balance < 0) {
            $issues[] = "Negative balance: {$wallet->balance}";
        }

        // Check for negative cut_amount
        if ($wallet->cut_amount < 0) {
            $issues[] = "Negative cut_amount: {$wallet->cut_amount}";
        }

        // Check for negative pending_amount
        if ($wallet->pending_amount < 0) {
            $issues[] = "Negative pending_amount: {$wallet->pending_amount}";
        }

        // Check if cut_amount exceeds balance
        if ($wallet->cut_amount > $wallet->balance) {
            $issues[] = "Cut amount ({$wallet->cut_amount}) exceeds balance ({$wallet->balance})";
        }

        $available = wallet_available_by_wallet($wallet);
        if ($available < 0) {
            $issues[] = "Negative available balance: {$available}";
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'wallet' => [
                'balance' => $wallet->balance,
                'cut_amount' => $wallet->cut_amount,
                'pending_amount' => $wallet->pending_amount,
                'available' => $available,
            ]
        ];
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuplicateOrderCleanupService
{
    private int $batchSize = 1000;
    private int $daysBack = 7;

    /**
     * Execute the cleanup process
     */
    public function cleanup(): array
    {
        $fromDate = now()->subDays($this->daysBack)->format('Y-m-d H:i:s');

        Log::info('=== Starting Duplicate Orders Cleanup ===');
        Log::info("Date filter: created_at >= {$fromDate}");

        // Step 1: Calculate refunds
        $refundData = $this->calculateRefunds($fromDate);
        Log::info('Refund calculation complete', [
            'affected_users' => count($refundData),
            'total_refund_amount' => array_sum(array_column($refundData, 'refund_amount'))
        ]);

        // Step 2: Get partitions
        $partitions = $this->getRelevantPartitions($fromDate);
        Log::info('Relevant partitions found: ' . count($partitions));

        if (empty($partitions)) {
            Log::warning('No relevant partitions found for the last 7 days');
            return [
                'status' => 'no_partitions',
                'message' => 'No relevant partitions found'
            ];
        }

        $totalRemoved = 0;

        // Step 3: Clean partitions
        foreach ($partitions as $partitionName) {
            Log::info("Processing partition: {$partitionName}");
            $removedInPartition = $this->cleanPartition($partitionName, $fromDate);
            $totalRemoved += $removedInPartition;
            Log::info("Partition {$partitionName} done — removed: {$removedInPartition}");
        }

        // Step 4: Apply refunds
        $refundsSummary = $this->applyRefunds($refundData);

        // Step 5: Verify
        $remainingDuplicates = $this->countDuplicates($fromDate);

        if ($remainingDuplicates > 0) {
            Log::warning("WARNING: Still have {$remainingDuplicates} duplicate orders after cleanup!");
        } else {
            Log::info("SUCCESS: All duplicates in last {$this->daysBack} days cleaned successfully!");
        }

        Log::info('=== Cleanup Summary ===', [
            'from_date'             => $fromDate,
            'total_removed'         => $totalRemoved,
            'remaining_duplicates'  => $remainingDuplicates,
            'users_refunded'        => $refundsSummary['users_refunded'],
            'total_refunded'        => $refundsSummary['total_refunded'],
        ]);

        return [
            'status' => 'completed',
            'from_date' => $fromDate,
            'total_removed' => $totalRemoved,
            'remaining_duplicates' => $remainingDuplicates,
            'users_refunded' => $refundsSummary['users_refunded'],
            'total_refunded' => $refundsSummary['total_refunded'],
        ];
    }

    /**
     * Get relevant partitions
     */
    private function getRelevantPartitions(string $fromDate): array
    {
        $currentYm  = (int) date('Ym');
        $previousYm = (int) date('Ym', strtotime('-1 month'));

        $partitions = DB::select("
            SELECT PARTITION_NAME
            FROM INFORMATION_SCHEMA.PARTITIONS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'coin_game_users_archive'
              AND PARTITION_NAME IS NOT NULL
              AND PARTITION_NAME IN ('p{$currentYm}', 'p{$previousYm}', 'pMax')
            ORDER BY PARTITION_ORDINAL_POSITION
        ");

        return array_column($partitions, 'PARTITION_NAME');
    }

    /**
     * Clean partition - delete ALL duplicates regardless of date
     */
    private function cleanPartition(string $partitionName, string $fromDate): int
    {
        $totalRemoved = 0;

        do {
            // Delete ALL duplicates (not just those from last 7 days)
            // This ensures we clean up all historical duplicates
            $idsToDelete = DB::select("
                SELECT t1.id
                FROM coin_game_users_archive PARTITION ({$partitionName}) t1
                INNER JOIN (
                    SELECT order_id, MIN(id) as keep_id
                    FROM coin_game_users_archive PARTITION ({$partitionName})
                    WHERE order_id IS NOT NULL
                    GROUP BY order_id
                    HAVING COUNT(*) > 1
                ) t2 ON t1.order_id = t2.order_id
                WHERE t1.id != t2.keep_id
                LIMIT {$this->batchSize}
            ");

            if (empty($idsToDelete)) {
                Log::info("Partition {$partitionName} — no more duplicates to delete");
                break;
            }

            $ids = array_column($idsToDelete, 'id');
            Log::info("Partition {$partitionName} — found " . count($ids) . " duplicate IDs to delete: " . implode(',', $ids));

            // Get the records before deleting to log them
            $recordsToDelete = DB::select("
                SELECT id, order_id, user_id, coins, type
                FROM coin_game_users_archive PARTITION ({$partitionName})
                WHERE id IN (" . implode(',', $ids) . ")
            ");

            foreach ($recordsToDelete as $record) {
                Log::info("Deleting duplicate record: id={$record->id}, order_id={$record->order_id}, user_id={$record->user_id}, coins={$record->coins}, type={$record->type}");
            }

            DB::statement("
                DELETE FROM coin_game_users_archive PARTITION ({$partitionName})
                WHERE id IN (" . implode(',', $ids) . ")
            ");

            $removed = count($ids);
            $totalRemoved += $removed;

            Log::info("Partition {$partitionName} — batch deleted: {$removed}");

            usleep(50000); // 50ms

        } while (true);

        Log::info("Partition {$partitionName} — total removed: {$totalRemoved}");
        return $totalRemoved;
    }

    /**
     * Count duplicates
     */
    private function countDuplicates(string $fromDate): int
    {
        $result = DB::select("
            SELECT COUNT(*) as count
            FROM (
                SELECT order_id
                FROM coin_game_users_archive
                WHERE order_id IS NOT NULL
                  AND created_at >= '{$fromDate}'
                GROUP BY order_id
                HAVING COUNT(*) > 1
            ) as dups
        ");

        return $result[0]->count ?? 0;
    }

    /**
     * Calculate refunds - process ALL duplicates regardless of date
     */
    private function calculateRefunds(string $fromDate): array
    {
        $refundData = [];

        // Get ALL duplicates (not filtered by date) to refund all historical duplicates
        $duplicateOrders = DB::select("
            SELECT 
                order_id,
                user_id,
                COUNT(*) as record_count,
                SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as total_add,
                SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) as total_deduct,
                MIN(id) as first_id,
                MIN(created_at) as min_created_at,
                MAX(created_at) as max_created_at
            FROM coin_game_users_archive
            WHERE order_id IS NOT NULL
            GROUP BY order_id, user_id
            HAVING COUNT(*) > 1
        ");

        Log::info('Found total duplicate orders (all time): ' . count($duplicateOrders));
        foreach ($duplicateOrders as $dup) {
            Log::info("Duplicate order: {$dup->order_id}, user: {$dup->user_id}, count: {$dup->record_count}, created: {$dup->min_created_at} to {$dup->max_created_at}, add: {$dup->total_add}, deduct: {$dup->total_deduct}");
        }

        foreach ($duplicateOrders as $order) {
            Log::info("Processing order: {$order->order_id}, user: {$order->user_id}, total_add: {$order->total_add}, total_deduct: {$order->total_deduct}");

            // For type=1 (additions): Calculate extra additions
            if ($order->total_add > 0) {
                $firstRecord = DB::selectOne("
                    SELECT coins
                    FROM coin_game_users_archive
                    WHERE order_id = ?
                    AND type = 1
                    ORDER BY id ASC
                    LIMIT 1
                ", [$order->order_id]);

                if ($firstRecord) {
                    $originalAddition = $firstRecord->coins;
                    $totalAdded = $order->total_add;
                    $extraAddition = $totalAdded - $originalAddition;

                    Log::info("Type=1 (addition): original={$originalAddition}, total={$totalAdded}, extra={$extraAddition}");

                    if ($extraAddition > 0) {
                        if (!isset($refundData[$order->user_id])) {
                            $refundData[$order->user_id] = [
                                'user_id' => $order->user_id,
                                'refund_amount' => 0,
                                'affected_orders' => []
                            ];
                        }

                        $refundData[$order->user_id]['refund_amount'] += $extraAddition;
                        $refundData[$order->user_id]['affected_orders'][] = [
                            'order_id' => $order->order_id,
                            'extra_deduction' => $extraAddition,
                            'type' => 1
                        ];
                    }
                }
            }

            // For type=0 (deductions): Calculate extra deductions
            if ($order->total_deduct > 0) {
                $firstRecord = DB::selectOne("
                    SELECT coins
                    FROM coin_game_users_archive
                    WHERE order_id = ?
                    AND type = 0
                    ORDER BY id ASC
                    LIMIT 1
                ", [$order->order_id]);

                if ($firstRecord) {
                    $originalDeduction = $firstRecord->coins;
                    $totalDeducted = $order->total_deduct;
                    $extraDeduction = $totalDeducted - $originalDeduction;

                    Log::info("Type=0 (deduction): original={$originalDeduction}, total={$totalDeducted}, extra={$extraDeduction}");

                    if ($extraDeduction > 0) {
                        if (!isset($refundData[$order->user_id])) {
                            $refundData[$order->user_id] = [
                                'user_id' => $order->user_id,
                                'refund_amount' => 0,
                                'affected_orders' => []
                            ];
                        }

                        $refundData[$order->user_id]['refund_amount'] += $extraDeduction;
                        $refundData[$order->user_id]['affected_orders'][] = [
                            'order_id' => $order->order_id,
                            'extra_deduction' => $extraDeduction,
                            'type' => 0
                        ];
                    }
                }
            }
        }

        Log::info('Refund data prepared for users: ' . count($refundData));
        foreach ($refundData as $userId => $data) {
            Log::info("User {$userId}: refund_amount={$data['refund_amount']}, orders=" . count($data['affected_orders']));
        }

        return $refundData;
    }

    /**
     * Apply refunds
     */
    private function applyRefunds(array $refundData): array
    {
        $usersRefunded = 0;
        $totalRefunded = 0;

        foreach ($refundData as $userId => $data) {
            try {
                DB::transaction(function () use ($userId, $data, &$usersRefunded, &$totalRefunded) {
                    $user = DB::table('users')
                        ->where('id', $userId)
                        ->lockForUpdate()
                        ->first();

                    if (!$user) {
                        Log::warning("User {$userId} not found for refund");
                        return;
                    }

                    foreach ($data['affected_orders'] as $orderInfo) {
                        $orderId = $orderInfo['order_id'];
                        $extraAmount = $orderInfo['extra_deduction'];
                        $orderType = $orderInfo['type'];

                        Log::info("Applying refund for user {$userId}, order {$orderId}, type={$orderType}, amount={$extraAmount}");

                        if ($orderType == 0) {
                            // Type 0: Deduction - refund by adding coins back
                            DB::table('users')
                                ->where('id', $userId)
                                ->increment('di', $extraAmount);

                            Log::info("Refund (type=0 deduction) applied to user {$userId}: +{$extraAmount}");
                        } elseif ($orderType == 1) {
                            // Type 1: Addition - deduct the extra amount (remove coins)
                            DB::table('users')
                                ->where('id', $userId)
                                ->decrement('di', $extraAmount);

                            Log::info("Deduction (type=1 addition) applied to user {$userId}: -{$extraAmount}");
                        }

                        $usersRefunded++;
                        $totalRefunded += $extraAmount;
                    }
                });
            } catch (\Exception $e) {
                Log::error("Failed to apply refund to user {$userId}: " . $e->getMessage());
            }
        }

        return [
            'users_refunded' => $usersRefunded,
            'total_refunded' => $totalRefunded
        ];
    }
}

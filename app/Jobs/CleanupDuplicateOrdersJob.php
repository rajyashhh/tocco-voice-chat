<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupDuplicateOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour
    public int $tries = 1;

    private int $batchSize = 500;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Log::info('=== CleanupDuplicateOrdersJob Started ===');

        try {
            // Step 1: Get all duplicate orders from coin_game_users_archive
            $duplicateOrders = DB::select("
                SELECT 
                    order_id,
                    user_id,
                    COUNT(*) as record_count,
                    SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as total_add,
                    SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) as total_deduct,
                    MIN(id) as keep_id,
                    MIN(created_at) as min_created_at
                FROM coin_game_users_archive
                WHERE order_id IS NOT NULL
                GROUP BY order_id, user_id
                HAVING COUNT(*) > 1
            ");

            Log::info('Found duplicate orders: ' . count($duplicateOrders));

            if (empty($duplicateOrders)) {
                Log::info('No duplicate orders found. Job completed.');
                return;
            }

            $totalDeleted = 0;
            $totalUsersFixed = 0;

            foreach ($duplicateOrders as $order) {
                Log::info("Processing order: {$order->order_id}, user: {$order->user_id}, count: {$order->record_count}, total_add: {$order->total_add}, total_deduct: {$order->total_deduct}");

                DB::transaction(function () use ($order, &$totalDeleted, &$totalUsersFixed) {
                    // Get all duplicate IDs (keep the first one = MIN id)
                    $duplicateIds = DB::select("
                        SELECT id, coins, type
                        FROM coin_game_users_archive
                        WHERE order_id = ?
                          AND user_id = ?
                          AND id != ?
                        ORDER BY id ASC
                    ", [$order->order_id, $order->user_id, $order->keep_id]);

                    if (empty($duplicateIds)) {
                        Log::info("No duplicates to delete for order: {$order->order_id}");
                        return;
                    }

                    // Calculate the extra amount to fix
                    $extraAdd = 0;
                    $extraDeduct = 0;

                    foreach ($duplicateIds as $dup) {
                        if ($dup->type == 1) {
                            // type=1 means addition (user gained coins)
                            $extraAdd += $dup->coins;
                        } elseif ($dup->type == 0) {
                            // type=0 means deduction (user lost coins)
                            $extraDeduct += $dup->coins;
                        }
                    }

                    Log::info("Order {$order->order_id}: extra_add={$extraAdd}, extra_deduct={$extraDeduct}, duplicates_to_delete=" . count($duplicateIds));

                    // Delete the duplicate records (keep only the first one)
                    $idsToDelete = array_column($duplicateIds, 'id');
                    DB::statement("
                        DELETE FROM coin_game_users_archive
                        WHERE id IN (" . implode(',', $idsToDelete) . ")
                    ");

                    $deleted = count($idsToDelete);
                    $totalDeleted += $deleted;
                    Log::info("Deleted {$deleted} duplicate records for order: {$order->order_id}");

                    // Fix user balance based on type
                    if ($extraAdd > 0) {
                        // User gained extra coins by mistake → deduct them
                        DB::table('users')
                            ->where('id', $order->user_id)
                            ->decrement('di', $extraAdd);

                        Log::info("User {$order->user_id}: deducted extra addition of {$extraAdd} coins (type=1)");
                        $totalUsersFixed++;
                    }

                    if ($extraDeduct > 0) {
                        // User lost extra coins by mistake → refund them
                        DB::table('users')
                            ->where('id', $order->user_id)
                            ->increment('di', $extraDeduct);

                        Log::info("User {$order->user_id}: refunded extra deduction of {$extraDeduct} coins (type=0)");
                        $totalUsersFixed++;
                    }
                });

                usleep(10000); // 10ms between orders
            }

            // Final verification
            $remainingDuplicates = DB::selectOne("
                SELECT COUNT(*) as count
                FROM (
                    SELECT order_id
                    FROM coin_game_users_archive
                    WHERE order_id IS NOT NULL
                    GROUP BY order_id
                    HAVING COUNT(*) > 1
                ) as dups
            ");

            Log::info('=== CleanupDuplicateOrdersJob Completed ===', [
                'total_duplicate_orders_processed' => count($duplicateOrders),
                'total_records_deleted' => $totalDeleted,
                'total_users_fixed' => $totalUsersFixed,
                'remaining_duplicates' => $remainingDuplicates->count ?? 0,
            ]);

        } catch (\Exception $e) {
            Log::error('CleanupDuplicateOrdersJob failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}

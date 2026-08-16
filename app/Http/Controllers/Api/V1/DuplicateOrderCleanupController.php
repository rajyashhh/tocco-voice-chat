<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\Controller;

class DuplicateOrderCleanupController extends Controller
{
    /**
     * Trigger the duplicate order cleanup migration
     * 
     * This endpoint allows authorized users to trigger the cleanup process
     * which will:
     * 1. Calculate refunds for users affected by duplicate deductions
     * 2. Remove duplicate records from coin_game_users_archive
     * 3. Apply refunds to users' accounts
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function triggerCleanup(Request $request)
    {
        try {
            // Verify authorization (you can add your own authorization logic)
            if (!$this->isAuthorized($request)) {
                return response()->json([
                    'errorCode' => 4003,
                    'errorMsg' => 'Unauthorized access',
                    'data' => []
                ], 403);
            }

            // Enable the migration flag
            Config::set('app.allow_duplicate_cleanup_migration', true);

            // Run the migration
            \Artisan::call('migrate', [
                '--path' => 'database/migrations/2026_04_14_120600_cleanup_duplicate_orders_archive_last_7_days.php',
                '--force' => true
            ]);

            $output = \Artisan::output();

            Log::info('Duplicate cleanup migration triggered successfully', [
                'triggered_by' => $request->user()?->id ?? 'unknown',
                'timestamp' => now()
            ]);

            return response()->json([
                'errorCode' => 0,
                'errorMsg' => 'Cleanup process completed successfully',
                'data' => [
                    'status' => 'completed',
                    'timestamp' => now(),
                    'output' => $output
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Duplicate cleanup migration failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'errorCode' => 5000,
                'errorMsg' => 'Cleanup process failed',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get a report of duplicate orders that would be cleaned up
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDuplicateReport(Request $request)
    {
        try {
            $daysBack = $request->get('days_back', 7);
            $fromDate = now()->subDays($daysBack)->format('Y-m-d H:i:s');

            // Get duplicate orders - check both coin_game_users and coin_game_users_archive
            $duplicateOrders = DB::select("
                SELECT 
                    order_id,
                    user_id,
                    COUNT(*) as record_count,
                    SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as total_deduct,
                    SUM(CASE WHEN type = 2 THEN coins ELSE 0 END) as total_add,
                    MIN(created_at) as first_created_at,
                    MAX(created_at) as last_created_at
                FROM (
                    SELECT order_id, user_id, coins, type, created_at
                    FROM coin_game_users
                    WHERE order_id IS NOT NULL
                      AND created_at >= '{$fromDate}'
                    UNION ALL
                    SELECT order_id, user_id, coins, type, created_at
                    FROM coin_game_users_archive
                    WHERE order_id IS NOT NULL
                      AND created_at >= '{$fromDate}'
                ) combined
                GROUP BY order_id, user_id
                HAVING COUNT(*) > 1
                ORDER BY order_id DESC
            ");

            // Calculate refunds
            $refundSummary = [];
            $totalRefundAmount = 0;

            foreach ($duplicateOrders as $order) {
                // Get the first (original) deduction amount
                $firstRecord = DB::selectOne("
                    SELECT coins
                    FROM coin_game_users_archive
                    WHERE order_id = ?
                    AND type = 1
                    ORDER BY id ASC
                    LIMIT 1
                ", [$order->order_id]);

                if ($firstRecord && $order->total_deduct > 0) {
                    $originalDeduction = $firstRecord->coins;
                    $extraDeduction = $order->total_deduct - $originalDeduction;

                    if ($extraDeduction > 0) {
                        if (!isset($refundSummary[$order->user_id])) {
                            $refundSummary[$order->user_id] = [
                                'user_id' => $order->user_id,
                                'total_refund' => 0,
                                'orders_count' => 0
                            ];
                        }

                        $refundSummary[$order->user_id]['total_refund'] += $extraDeduction;
                        $refundSummary[$order->user_id]['orders_count']++;
                        $totalRefundAmount += $extraDeduction;
                    }
                }
            }

            return response()->json([
                'errorCode' => 0,
                'errorMsg' => 'success',
                'data' => [
                    'days_back' => $daysBack,
                    'from_date' => $fromDate,
                    'duplicate_orders_count' => count($duplicateOrders),
                    'affected_users_count' => count($refundSummary),
                    'total_refund_amount' => $totalRefundAmount,
                    'refund_summary' => array_values($refundSummary),
                    'duplicate_orders' => $duplicateOrders
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get duplicate report: ' . $e->getMessage());

            return response()->json([
                'errorCode' => 5000,
                'errorMsg' => 'Failed to generate report',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Check authorization
     * You can customize this based on your authorization requirements
     */
    private function isAuthorized(Request $request): bool
    {
        // Check if user is authenticated and has admin role
        // Customize this based on your authorization logic
        $user = $request->user();
        
        if (!$user) {
            return false;
        }

        // Check if user has admin or super admin role
        // Adjust based on your role system
        return in_array($user->role ?? null, ['admin', 'super_admin', 'superadmin']);
    }
}

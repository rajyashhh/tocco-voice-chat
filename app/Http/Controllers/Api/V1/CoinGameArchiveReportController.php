<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CoinGameUserArchive;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoinGameArchiveReportController extends Controller
{
    /**
     * Display HTML report of duplicate orders from coin_game_users_archive
     * Shows aggregated data grouped by order_id with counts and totals
     */
    public function htmlReport(Request $request)
    {
        try {
            $page = (int) $request->get('page', 1);
            $perPage = 50;
            $offset = ($page - 1) * $perPage;

            // Get total count first (fast query)
            $totalCount = DB::selectOne("
                SELECT COUNT(*) as count
                FROM (
                    SELECT order_id
                    FROM coin_game_users_archive
                    WHERE order_id IS NOT NULL
                    GROUP BY order_id, user_id
                    HAVING COUNT(*) > 1
                ) as dups
            ")->count ?? 0;

            // Get summary stats (fast aggregation)
            $summaryRaw = DB::selectOne("
                SELECT 
                    COUNT(DISTINCT order_id) as total_duplicate_orders,
                    SUM(record_count) as total_records_affected,
                    SUM(total_deduct) as total_coins_deducted,
                    SUM(total_add) as total_coins_added,
                    COUNT(DISTINCT user_id) as affected_users_count
                FROM (
                    SELECT 
                        order_id,
                        user_id,
                        COUNT(*) as record_count,
                        SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) as total_deduct,
                        SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as total_add
                    FROM coin_game_users_archive
                    WHERE order_id IS NOT NULL
                    GROUP BY order_id, user_id
                    HAVING COUNT(*) > 1
                ) as sub
            ");

            $summary = [
                'total_duplicate_orders' => $summaryRaw->total_duplicate_orders ?? 0,
                'total_records_affected' => $summaryRaw->total_records_affected ?? 0,
                'total_coins_deducted' => $summaryRaw->total_coins_deducted ?? 0,
                'total_coins_added' => $summaryRaw->total_coins_added ?? 0,
                'total_net_change' => ($summaryRaw->total_coins_added ?? 0) - ($summaryRaw->total_coins_deducted ?? 0),
                'affected_users_count' => $summaryRaw->affected_users_count ?? 0,
            ];

            // Get paginated records
            $report = DB::select("
                SELECT 
                    order_id,
                    user_id,
                    COUNT(*) as record_count,
                    SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) as total_deduct,
                    SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as total_add,
                    MIN(created_at) as first_created_at,
                    MAX(created_at) as last_created_at
                FROM coin_game_users_archive
                WHERE order_id IS NOT NULL
                GROUP BY order_id, user_id
                HAVING COUNT(*) > 1
                ORDER BY order_id DESC
                LIMIT {$perPage} OFFSET {$offset}
            ");

            // Get user IDs to batch load
            $userIds = array_unique(array_column($report, 'user_id'));
            $users = DB::table('users')
                ->whereIn('id', $userIds)
                ->select('id', 'name', 'di')
                ->get()
                ->keyBy('id');

            $enrichedReport = collect($report)->map(function ($item) use ($users) {
                $user = $users[$item->user_id] ?? null;
                $netChange = $item->total_add - $item->total_deduct;
                $currentDi = $user ? $user->di : 0;
                $expectedDi = $currentDi - $netChange;

                return [
                    'order_id' => $item->order_id,
                    'user_id' => $item->user_id,
                    'user_name' => $user ? $user->name : 'Unknown',
                    'current_di' => $currentDi,
                    'record_count' => $item->record_count,
                    'total_deduct' => (int)$item->total_deduct,
                    'total_add' => (int)$item->total_add,
                    'net_change' => $netChange,
                    'expected_di_after_cleanup' => $expectedDi,
                    'expected_di_after_changes' => $expectedDi,
                    'type_summary' => [
                        'deduct' => (int)$item->total_deduct > 0 ? 'نقصان' : 'لا يوجد',
                        'add' => (int)$item->total_add > 0 ? 'زيادة' : 'لا يوجد'
                    ],
                    'first_created_at' => $item->first_created_at,
                    'last_created_at' => $item->last_created_at
                ];
            });

            $totalPages = (int) ceil($totalCount / $perPage);

            return view('coin_game_archive_report', [
                'summary' => $summary,
                'records' => $enrichedReport->values(),
                'timestamp' => now(),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1,
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error("CoinGameArchiveReport Error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return view('coin_game_archive_report_error', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get detailed records for a specific order_id
     */
    public function orderDetails(Request $request)
    {
        try {
            $orderId = $request->get('order_id');
            
            if (!$orderId) {
                return response()->json([
                    'errorCode' => 4005,
                    'errorMsg' => 'order_id parameter is required',
                    'data' => []
                ]);
            }

            // Get all records for this order_id
            $records = DB::table('coin_game_users_archive')
                ->where('order_id', $orderId)
                ->select(
                    'id',
                    'user_id',
                    'coins',
                    'type',
                    'game_id',
                    'round_id',
                    'order_id',
                    'app_profit_coins',
                    'created_at',
                    'updated_at',
                    'created_ym'
                )
                ->orderBy('created_at', 'ASC')
                ->get();

            if ($records->isEmpty()) {
                return response()->json([
                    'errorCode' => 4004,
                    'errorMsg' => 'No records found for this order_id',
                    'data' => []
                ]);
            }

            // Get user information
            $userId = $records->first()->user_id;
            $user = User::select('id', 'name', 'di')->find($userId);

            // Calculate totals
            $totalDeduct = $records->where('type', 1)->sum('coins');
            $totalAdd = $records->where('type', 2)->sum('coins');
            $netChange = $totalAdd - $totalDeduct;

            return response()->json([
                'errorCode' => 0,
                'errorMsg' => 'success',
                'data' => [
                    'order_id' => $orderId,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'current_di' => $user->di
                    ],
                    'summary' => [
                        'record_count' => count($records),
                        'total_deduct' => (int)$totalDeduct,
                        'total_add' => (int)$totalAdd,
                        'net_change' => $netChange,
                        'expected_di_after_changes' => $user->di + $netChange
                    ],
                    'records' => $records->map(function ($record) {
                        return [
                            'id' => $record->id,
                            'coins' => (int)$record->coins,
                            'type' => (int)$record->type,
                            'type_label' => $record->type == 1 ? 'نقصان' : 'زيادة',
                            'game_id' => $record->game_id,
                            'round_id' => $record->round_id,
                            'app_profit_coins' => (int)$record->app_profit_coins,
                            'created_at' => $record->created_at,
                            'created_ym' => $record->created_ym
                        ];
                    })->values()
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error("CoinGameArchiveReport orderDetails Error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'errorCode' => 500,
                'errorMsg' => 'Server error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get report filtered by user_id
     */
    public function userDuplicateOrders(Request $request)
    {
        try {
            $userId = $request->get('user_id');
            
            if (!$userId) {
                return response()->json([
                    'errorCode' => 4005,
                    'errorMsg' => 'user_id parameter is required',
                    'data' => []
                ]);
            }

            $user = User::select('id', 'name', 'di')->find($userId);
            
            if (!$user) {
                return response()->json([
                    'errorCode' => 4004,
                    'errorMsg' => 'User not found',
                    'data' => []
                ]);
            }

            // Get duplicate orders for this user
            $report = DB::table('coin_game_users_archive')
                ->select(
                    'order_id',
                    'user_id',
                    DB::raw('COUNT(*) as record_count'),
                    DB::raw('SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as total_deduct'),
                    DB::raw('SUM(CASE WHEN type = 2 THEN coins ELSE 0 END) as total_add'),
                    DB::raw('MIN(created_at) as first_created_at'),
                    DB::raw('MAX(created_at) as last_created_at')
                )
                ->where('user_id', $userId)
                ->whereNotNull('order_id')
                ->groupBy('order_id', 'user_id')
                ->having(DB::raw('COUNT(*)'), '>', 1)
                ->orderBy('order_id', 'DESC')
                ->get();

            $enrichedReport = $report->map(function ($item) use ($user) {
                $netChange = $item->total_add - $item->total_deduct;
                
                return [
                    'order_id' => $item->order_id,
                    'record_count' => $item->record_count,
                    'total_deduct' => (int)$item->total_deduct,
                    'total_add' => (int)$item->total_add,
                    'net_change' => $netChange,
                    'type_summary' => [
                        'deduct' => (int)$item->total_deduct > 0 ? 'نقصان' : 'لا يوجد',
                        'add' => (int)$item->total_add > 0 ? 'زيادة' : 'لا يوجد'
                    ],
                    'first_created_at' => $item->first_created_at,
                    'last_created_at' => $item->last_created_at
                ];
            });

            return response()->json([
                'errorCode' => 0,
                'errorMsg' => 'success',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'current_di' => $user->di
                    ],
                    'duplicate_orders_count' => count($enrichedReport),
                    'total_records_affected' => $enrichedReport->sum('record_count'),
                    'total_coins_deducted' => $enrichedReport->sum('total_deduct'),
                    'total_coins_added' => $enrichedReport->sum('total_add'),
                    'total_net_change' => $enrichedReport->sum('net_change'),
                    'orders' => $enrichedReport->values()
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error("CoinGameArchiveReport userDuplicateOrders Error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'errorCode' => 500,
                'errorMsg' => 'Server error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

 public function triggerCleanup(Request $request)
    {
        try {
            Log::info('=== Cleanup Trigger Started ===', [
                'triggered_by' => $request->user()?->id ?? 'unknown',
                'timestamp' => now()
            ]);

            // Dispatch the job to handle large data asynchronously
            \App\Jobs\CleanupDuplicateOrdersJob::dispatch();

            Log::info('CleanupDuplicateOrdersJob dispatched to queue');

            return response()->json([
                'errorCode' => 0,
                'errorMsg' => 'Cleanup job dispatched successfully',
                'data' => [
                    'status' => 'queued',
                    'timestamp' => now(),
                    'message' => 'Cleanup job has been dispatched to the queue. Check logs for progress.'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Duplicate cleanup dispatch failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'errorCode' => 5000,
                'errorMsg' => 'Cleanup dispatch failed',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }


}

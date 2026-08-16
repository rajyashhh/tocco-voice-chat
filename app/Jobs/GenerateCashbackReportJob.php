<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateCashbackReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout: 10 دقائق
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cacheKey = 'cashback_missing_report';

        try {
            Log::info('🔄 [Job] Starting cashback report generation in background');

            // وضع علامة "processing"
            Cache::put($cacheKey, 'processing', 600);

            $startTime = microtime(true);

            // تنفيذ الكويري
            $users = DB::select("
                WITH all_logs_with_next AS (
                    SELECT
                        user_id,
                        id,
                        type,
                        amount,
                        amount_before,
                        (amount_before + amount) AS calculated_after,
                        LEAD(amount_before) OVER (PARTITION BY user_id ORDER BY id) AS next_amount_before,
                        created_at
                    FROM user_coin_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ),
                cashback_issues AS (
                    SELECT
                        user_id,
                        amount AS cashback_amount,
                        CASE
                            WHEN next_amount_before IS NOT NULL
                                AND calculated_after > next_amount_before
                            THEN (calculated_after - next_amount_before)
                            ELSE 0
                        END AS missing_amount,
                        created_at
                    FROM all_logs_with_next
                    WHERE type = 'cashback'
                )
                SELECT
                    user_id,
                    COUNT(*) AS total_cashback_operations,
                    SUM(CASE WHEN missing_amount > 0 THEN 1 ELSE 0 END) AS times_missing,
                    CAST(SUM(cashback_amount) AS SIGNED) AS total_cashback_logged,
                    CAST(SUM(missing_amount) AS SIGNED) AS his_right,
                    ROUND((SUM(missing_amount) / NULLIF(SUM(cashback_amount), 0)) * 100, 2) AS loss_percentage,
                    MIN(created_at) AS first_cashback_at,
                    MAX(created_at) AS last_cashback_at
                FROM cashback_issues
                GROUP BY user_id
                HAVING his_right > 0
                ORDER BY his_right DESC
                LIMIT 1000
            ");

            // حساب الإحصائيات
            $summary = [
                'total_affected_users' => count($users),
                'total_missing_cashback' => array_sum(array_column($users, 'his_right')),
                'total_cashback_logged' => array_sum(array_column($users, 'total_cashback_logged')),
                'average_loss_percentage' => count($users) > 0
                    ? round(array_sum(array_column($users, 'loss_percentage')) / count($users), 2)
                    : 0,
            ];

            $executionTime = round((microtime(true) - $startTime), 2);

            $report = [
                'users' => $users,
                'summary' => $summary,
                'generated_at' => now()->toDateTimeString(),
                'execution_time' => $executionTime,
            ];

            // حفظ في الكاش (ساعة واحدة)
            Cache::put($cacheKey, $report, 3600);

            Log::info('✅ [Job] Cashback report generated successfully', [
                'users_count' => count($users),
                'execution_time' => $executionTime . 's',
                'total_missing' => $summary['total_missing_cashback'],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [Job] Failed to generate cashback report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // حذف علامة processing
            Cache::forget($cacheKey);

            throw $e;
        }
    }
}

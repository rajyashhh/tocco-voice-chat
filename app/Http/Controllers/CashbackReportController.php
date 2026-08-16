<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CashbackReportController extends Controller
{
    /**
     * عرض تقرير الكاش باك المفقود
     * Route: GET /admin/cashback-report
     */
    public function index(Request $request)
    {
        // محاولة جلب التقرير من الكاش
        $cacheKey = 'cashback_missing_report';
        $cacheDuration = 3600; // ساعة واحدة

        $report = Cache::remember($cacheKey, $cacheDuration, function () {
            return $this->generateReport();
        });

        // إذا التقرير لسه بيتم إنشاؤه
        if ($report === 'processing') {
            return response()->json([
                'status' => 'processing',
                'message' => 'التقرير قيد الإنشاء، يرجى الانتظار...',
                'retry_after' => 10, // ثواني
            ], 202);
        }

        // إرجاع التقرير
        return response()->json([
            'status' => 'success',
            'data' => $report['users'],
            'summary' => $report['summary'],
            'generated_at' => $report['generated_at'],
        ]);
    }

    /**
     * تحديث التقرير (إعادة حسابه)
     * Route: POST /admin/cashback-report/refresh
     */
    public function refresh(Request $request)
    {
        $cacheKey = 'cashback_missing_report';

        // حذف الكاش القديم
        Cache::forget($cacheKey);

        // إرسال job لإعادة حساب التقرير
        \App\Jobs\GenerateCashbackReportJob::dispatch();

        return response()->json([
            'status' => 'success',
            'message' => 'تم بدء إعادة حساب التقرير في الخلفية',
        ]);
    }

    /**
     * توليد التقرير (يعمل في الخلفية)
     */
    private function generateReport()
    {
        try {
            Log::info('🔍 Starting cashback report generation');

            $startTime = microtime(true);

            // الكويري الأساسي
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

            // حساب الإحصائيات الإجمالية
            $summary = [
                'total_affected_users' => count($users),
                'total_missing_cashback' => array_sum(array_column($users, 'his_right')),
                'total_cashback_logged' => array_sum(array_column($users, 'total_cashback_logged')),
                'average_loss_percentage' => count($users) > 0
                    ? round(array_sum(array_column($users, 'loss_percentage')) / count($users), 2)
                    : 0,
            ];

            $executionTime = round((microtime(true) - $startTime), 2);

            Log::info("✅ Cashback report generated successfully", [
                'users_count' => count($users),
                'execution_time' => $executionTime . 's',
                'total_missing' => $summary['total_missing_cashback'],
            ]);

            return [
                'users' => $users,
                'summary' => $summary,
                'generated_at' => now()->toDateTimeString(),
                'execution_time' => $executionTime,
            ];

        } catch (\Exception $e) {
            Log::error('❌ Failed to generate cashback report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * عرض صفحة HTML بسيطة (optional)
     */
    public function html(Request $request)
    {
        return view('admin.cashback-report');
    }

    /**
     * تصدير CSV
     */
    public function exportCsv(Request $request)
    {
        $cacheKey = 'cashback_missing_report';
        $report = Cache::get($cacheKey);

        if (!$report || $report === 'processing') {
            return response()->json([
                'error' => 'التقرير غير متاح، يرجى الانتظار أو تحديث التقرير',
            ], 404);
        }

        $users = $report['users'];

        // إنشاء CSV
        $csvData = "User ID,Total Cashback Operations,Times Missing,Total Cashback Logged,Missing Cashback (His Right),Loss Percentage,First Cashback,Last Cashback\n";

        foreach ($users as $user) {
            $csvData .= sprintf(
                "%d,%d,%d,%d,%d,%.2f%%,%s,%s\n",
                $user->user_id,
                $user->total_cashback_operations,
                $user->times_missing,
                $user->total_cashback_logged,
                $user->his_right,
                $user->loss_percentage,
                $user->first_cashback_at,
                $user->last_cashback_at
            );
        }

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="cashback_report_' . date('Y-m-d_H-i-s') . '.csv"');
    }
}

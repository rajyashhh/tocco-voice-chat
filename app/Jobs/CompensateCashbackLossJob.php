<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompensateCashbackLossJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes (instead of 10)
    public $tries = 1;
    public $maxExceptions = 3; // Allow 3 exceptions before failing

    private $startDate;
    private $endDate;
    private $batchId;
    private $preloadedUsers; // المستخدمين المحملين مسبقاً

    public function __construct($startDate = null, $endDate = null, $batchId = null, $preloadedUsers = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->batchId = $batchId ?? 'comp_' . date('YmdHis');
        $this->preloadedUsers = $preloadedUsers;
    }

    public function handle()
    {
        try {
            Log::info('Starting cashback compensation job', [
                'batch_id' => $this->batchId,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'preloaded' => !empty($this->preloadedUsers),
            ]);

            // الخطوة 1: جلب المستخدمين المتضررين
            // استخدام البيانات المحملة مسبقاً أو جلبها من الداتابيز
            if (!empty($this->preloadedUsers)) {
                $affectedUsers = $this->preloadedUsers;
                Log::info('Using preloaded users', [
                    'batch_id' => $this->batchId,
                    'count' => \count($affectedUsers),
                ]);
            } else {
                // تحديد الفترة الزمنية
                $dateFilter = $this->startDate && $this->endDate
                    ? "AND created_at BETWEEN '{$this->startDate}' AND '{$this->endDate}'"
                    : 'AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';

                $affectedUsers = DB::select("
                    WITH all_logs_with_next AS (
                        SELECT
                            user_id,
                            type,
                            amount,
                            amount_before,
                            (amount_before + amount) AS calculated_after,
                            LEAD(amount_before) OVER (PARTITION BY user_id ORDER BY id) AS next_amount_before,
                            created_at
                        FROM user_coin_logs
                        WHERE 1=1 {$dateFilter}
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
                        CAST(SUM(missing_amount) AS SIGNED) AS compensation_amount,
                        COUNT(*) AS affected_operations
                    FROM cashback_issues
                    WHERE user_id NOT IN (
                        -- استبعاد المستخدمين اللي اتعوضوا قبل كده
                        SELECT DISTINCT user_id
                        FROM user_coin_logs
                        WHERE type = 'compensation'
                            AND sub_type = 'cashback_loss_refund'
                    )
                    GROUP BY user_id
                    HAVING compensation_amount > 0
                    ORDER BY compensation_amount DESC
                ");
            }

            if (empty($affectedUsers)) {
                Log::info('No users to compensate', ['batch_id' => $this->batchId]);
                return;
            }

            $totalUsers = \count($affectedUsers);
            $totalAmount = array_sum(array_column($affectedUsers, 'compensation_amount'));
            $processedUsers = 0;
            $failedUsers = [];

            Log::info('Found users to compensate', [
                'batch_id' => $this->batchId,
                'total_users' => $totalUsers,
                'total_amount' => $totalAmount,
            ]);

            // الخطوة 2: معالجة كل مستخدم (مع Sleep لتخفيف الضغط)
            foreach ($affectedUsers as $user) {
                try {
                    DB::beginTransaction();

                    // جلب المستخدم مع قفل
                    $dbUser = DB::table('users')
                        ->where('id', $user->user_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$dbUser) {
                        throw new \Exception('المستخدم غير موجود');
                    }

                    $balanceBefore = $dbUser->di;
                    $balanceAfter = $balanceBefore + $user->compensation_amount;

                    // تحديث رصيد المستخدم
                    $updated = DB::table('users')
                        ->where('id', $user->user_id)
                        ->where('di', $balanceBefore)  // Optimistic locking
                        ->update([
                            'di' => $balanceAfter,
                            'updated_at' => now(),
                        ]);

                    if (!$updated) {
                        throw new \Exception('فشل التحديث - تم تعديل الرصيد من طلب آخر');
                    }

                    // تسجيل في user_coin_logs باستخدام Helper
                    \App\Helpers\UserCoinLogHelper::logByType(
                        $user->user_id,
                        $user->compensation_amount,
                        $balanceBefore,
                        \App\Enums\UserCoinLogType::COMPENSATION,
                        'Cashback Loss Refund',  // item_name
                        0  // helper_amount
                    );

                    DB::commit();
                    $processedUsers++;

                    // تخفيف الضغط على الداتابيز (sleep بعد كل 50 user)
                    if ($processedUsers % 50 === 0) {
                        Log::info('Progress update', [
                            'batch_id' => $this->batchId,
                            'processed' => $processedUsers,
                            'total' => $totalUsers,
                            'progress' => round(($processedUsers / $totalUsers) * 100, 2) . '%'
                        ]);
                        usleep(100000); // 0.1 second pause
                    }

                } catch (\Exception $e) {
                    DB::rollBack();
                    $failedUsers[] = [
                        'user_id' => $user->user_id,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to compensate user', [
                        'batch_id' => $this->batchId,
                        'user_id' => $user->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // تسجيل النتائج النهائية
            $finalResults = [
                'batch_id' => $this->batchId,
                'total_users' => $totalUsers,
                'processed' => $processedUsers,
                'failed' => \count($failedUsers),
                'total_amount' => $totalAmount,
                'failed_users' => $failedUsers,
            ];

            Log::info('Compensation job completed', $finalResults);

            // حفظ النتائج النهائية في ملف
            $resultFile = storage_path("logs/compensation_result_{$this->batchId}.json");
            file_put_contents($resultFile, json_encode([
                'completed_at' => now()->toDateTimeString(),
                'results' => $finalResults,
            ], JSON_PRETTY_PRINT));

            // 🔔 إرسال إشعار للـ Admin (إذا كان النظام يدعمه)
            try {
                if (class_exists('\App\Helpers\AdminNotificationHelper')) {
                    \App\Helpers\AdminNotificationHelper::send(
                        \App\Enums\AdminNotificationType::SYSTEM_ALERT,
                        "✅ تم تعويض {$processedUsers} مستخدم بنجاح",
                        [
                            'total_amount' => $totalAmount,
                            'failed_count' => \count($failedUsers),
                            'batch_id' => $this->batchId,
                        ]
                    );
                }
            } catch (\Exception $notificationError) {
                Log::warning('Failed to send admin notification', [
                    'error' => $notificationError->getMessage(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Compensation job failed', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

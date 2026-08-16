<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\UserCoinLogHelper;
use App\Enums\UserCoinLogType;

class CashbackCompensationController extends Controller
{
    /**
     * عرض المستخدمين المتأثرين مع المبالغ المستحقة
     */
    public function preview(Request $request)
    {
        try {
            $users = $this->getAffectedUsers();

            $summary = [
                'total_users' => count($users),
                'total_compensation' => array_sum(array_column($users, 'his_right')),
                'max_compensation' => count($users) > 0 ? max(array_column($users, 'his_right')) : 0,
                'min_compensation' => count($users) > 0 ? min(array_column($users, 'his_right')) : 0,
            ];

            return response()->json([
                'status' => 'success',
                'users' => $users,
                'summary' => $summary,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to preview compensation', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'فشل جلب البيانات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تعويض مستخدم واحد
     */
    public function compensateUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $userId = $request->user_id;
        $amount = $request->amount;
        $reason = $request->reason ?? 'تعويض عن الكاش باك المفقود';

        DB::beginTransaction();

        try {
            // جلب المستخدم مع قفل
            $user = DB::table('users')
                ->where('id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                throw new \Exception('المستخدم غير موجود');
            }

            $balanceBefore = $user->di;
            $balanceAfter = $balanceBefore + $amount;

            // تحديث الرصيد
            $updated = DB::table('users')
                ->where('id', $userId)
                ->where('di', $balanceBefore) // Optimistic locking
                ->update([
                    'di' => $balanceAfter,
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                throw new \Exception('فشل التحديث - تم تعديل الرصيد من طلب آخر');
            }

            // تسجيل في user_coin_logs
            UserCoinLogHelper::logByType(
                $userId,
                $amount,
                $balanceBefore,
                UserCoinLogType::COMPENSATION,
                $reason,  // item_name
                0  // helper_amount
            );

            DB::commit();

            Log::info('✅ User compensated successfully', [
                'user_id' => $userId,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم التعويض بنجاح',
                'data' => [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Compensation failed', [
                'user_id' => $userId,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'فشل التعويض: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تعويض كل المستخدمين المتأثرين (batch)
     */
    public function compensateAll(Request $request)
    {
        $request->validate([
            'confirm' => 'required|boolean|accepted',
            'reason' => 'nullable|string|max:255',
            'min_amount' => 'nullable|numeric|min:0',
        ]);

        $reason = $request->reason ?? 'تعويض تلقائي عن الكاش باك المفقود';
        $minAmount = $request->min_amount ?? 0;

        try {
            $users = $this->getAffectedUsers($minAmount);

            if (empty($users)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يوجد مستخدمين للتعويض',
                ], 400);
            }

            $results = [
                'total' => count($users),
                'success' => 0,
                'failed' => 0,
                'total_compensated' => 0,
                'errors' => [],
            ];

            foreach ($users as $user) {
                DB::beginTransaction();

                try {
                    $userId = $user->user_id;
                    $amount = $user->his_right;

                    // جلب المستخدم مع قفل
                    $dbUser = DB::table('users')
                        ->where('id', $userId)
                        ->lockForUpdate()
                        ->first();

                    if (!$dbUser) {
                        throw new \Exception('المستخدم غير موجود');
                    }

                    $balanceBefore = $dbUser->di;
                    $balanceAfter = $balanceBefore + $amount;

                    // تحديث الرصيد
                    $updated = DB::table('users')
                        ->where('id', $userId)
                        ->where('di', $balanceBefore)
                        ->update([
                            'di' => $balanceAfter,
                            'updated_at' => now(),
                        ]);

                    if (!$updated) {
                        throw new \Exception('فشل التحديث');
                    }

                    // تسجيل في user_coin_logs
                    UserCoinLogHelper::logByType(
                        $userId,
                        $amount,
                        $balanceBefore,
                        UserCoinLogType::COMPENSATION,
                        $reason,  // item_name
                        0  // helper_amount
                    );

                    DB::commit();

                    $results['success']++;
                    $results['total_compensated'] += $amount;

                    Log::info('✅ Batch compensation success', [
                        'user_id' => $userId,
                        'amount' => $amount,
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();

                    $results['failed']++;
                    $results['errors'][] = [
                        'user_id' => $userId ?? null,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('❌ Batch compensation failed for user', [
                        'user_id' => $userId ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::warning('📊 Batch compensation completed', [
                'total' => $results['total'],
                'success' => $results['success'],
                'failed' => $results['failed'],
                'total_amount' => $results['total_compensated'],
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "تم تعويض {$results['success']} من {$results['total']} مستخدم",
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Batch compensation failed', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'فشل التعويض الجماعي: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تعويض مستخدمين محددين
     */
    public function compensateSelected(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'required|integer|exists:users,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $userIds = $request->user_ids;
        $reason = $request->reason ?? 'تعويض عن الكاش باك المفقود';

        try {
            // جلب المبالغ المستحقة
            $affectedUsers = $this->getAffectedUsers();
            $userAmounts = [];

            foreach ($affectedUsers as $user) {
                if (in_array($user->user_id, $userIds)) {
                    $userAmounts[$user->user_id] = $user->his_right;
                }
            }

            if (empty($userAmounts)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يوجد مستخدمين مؤهلين للتعويض في القائمة المحددة',
                ], 400);
            }

            $results = [
                'total' => count($userAmounts),
                'success' => 0,
                'failed' => 0,
                'total_compensated' => 0,
                'details' => [],
            ];

            foreach ($userAmounts as $userId => $amount) {
                DB::beginTransaction();

                try {
                    $dbUser = DB::table('users')
                        ->where('id', $userId)
                        ->lockForUpdate()
                        ->first();

                    if (!$dbUser) {
                        throw new \Exception('المستخدم غير موجود');
                    }

                    $balanceBefore = $dbUser->di;
                    $balanceAfter = $balanceBefore + $amount;

                    $updated = DB::table('users')
                        ->where('id', $userId)
                        ->where('di', $balanceBefore)
                        ->update([
                            'di' => $balanceAfter,
                            'updated_at' => now(),
                        ]);

                    if (!$updated) {
                        throw new \Exception('فشل التحديث');
                    }

                    UserCoinLogHelper::logByType(
                        $userId,
                        $amount,
                        $balanceBefore,
                        UserCoinLogType::COMPENSATION,
                        null,
                        $reason
                    );

                    DB::commit();

                    $results['success']++;
                    $results['total_compensated'] += $amount;
                    $results['details'][] = [
                        'user_id' => $userId,
                        'amount' => $amount,
                        'status' => 'success',
                    ];

                } catch (\Exception $e) {
                    DB::rollBack();

                    $results['failed']++;
                    $results['details'][] = [
                        'user_id' => $userId,
                        'amount' => $amount,
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            Log::warning('📊 Selected compensation completed', [
                'total' => $results['total'],
                'success' => $results['success'],
                'failed' => $results['failed'],
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "تم تعويض {$results['success']} من {$results['total']} مستخدم",
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل التعويض: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * جلب المستخدمين المتأثرين
     */
    private function getAffectedUsers($minAmount = 0)
    {
        $users = DB::select("
            WITH all_logs_with_next AS (
                SELECT
                    user_id,
                    type,
                    amount,
                    amount_before,
                    (amount_before + amount) AS calculated_after,
                    LEAD(amount_before) OVER (PARTITION BY user_id ORDER BY id) AS next_amount_before
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
                    END AS missing_amount
                FROM all_logs_with_next
                WHERE type = 'cashback'
            )
            SELECT
                user_id,
                CAST(SUM(missing_amount) AS SIGNED) AS his_right
            FROM cashback_issues
            GROUP BY user_id
            HAVING his_right > ?
            ORDER BY his_right DESC
        ", [$minAmount]);

        return $users;
    }
}

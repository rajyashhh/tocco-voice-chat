<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Facades\UserHandling;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\CalculateUserTargetJob;
use Illuminate\Support\Facades\Auth;
use App\Models\MonthlyDiamondReceive;
use Modules\FixedTarget\Services\FixedTargetService;
use Modules\FixedTarget\Services\FixedTargetV2Service;

class DiamondController extends Controller
{


    public function calculateMonthlyDiamondReceived()
    {
        $timezone = getTimezone();
        $date = Carbon::now($timezone);
        $currentMonth = $date->month;
        $currentYear = $date->year;
        $startOfMonth = Carbon::now($timezone)->startOfMonth()->copy()->setTimezone('UTC');

        $processedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        DB::table('users')
            ->select('id', 'agency_id')
            ->whereNotNull('agency_id')
            ->where('agency_id', '>', 0)
            ->whereIn('type_user', [1, 2])
            ->orderBy('id')
            ->chunk(500, function ($users) use ($timezone, $startOfMonth, $currentMonth, $currentYear, &$processedCount, &$updatedCount, &$skippedCount) {
                foreach ($users as $user) {
                    try {
                        $join = DB::table('users_joined_agencies')
                            ->where('user_id', $user->id)
                            ->where('agency_id', $user->agency_id)
                            ->orderByDesc('join_date')
                            ->first();

                        $startDate = $startOfMonth;
                        if ($join && Carbon::parse($join->join_date, $timezone)->greaterThan($startOfMonth)) {
                            $startDate = Carbon::parse($join->join_date, $timezone)->setTimezone('UTC');
                        }

                        $totalReceived = DB::table('gift_logs')
                            ->where('receiver_id', $user->id)
                            ->where('created_at', '>=', $startDate)
                            ->where('agency_id', $user->agency_id)
                            ->selectRaw('SUM(giftPrice) as total')
                            ->value('total');

                        $newMonthlyDiamond = $totalReceived ?? 0;

                        $currentRecord = MonthlyDiamondReceive::where('user_id', $user->id)
                            ->where('month', $currentMonth)
                            ->where('year', $currentYear)
                            ->first();

                        $oldMonthlyDiamond = $currentRecord ? $currentRecord->monthly_diamond_received : 0;

                        if ($newMonthlyDiamond != $oldMonthlyDiamond) {
                            uploadMonthlyDiamondReceive($user->id, $newMonthlyDiamond);

                            DB::table('users')
                                ->where('id', $user->id)
                                ->update([
                                    'salary_is_updated' => 1,
                                ]);

                            $updatedCount++;
                        } else {
                            $skippedCount++;
                        }

                        $processedCount++;
                    } catch (\Throwable $e) {
                        Log::error("Failed to calculate monthly diamond for user {$user->id}: " . $e->getMessage());
                    }
                }
            });

        return response()->json([
            'status' => true,
            'message' => "تم معالجة {$processedCount} مستخدم - تم التحديث: {$updatedCount} - تم تخطيهم: {$skippedCount}",
            'processed_count' => $processedCount,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount
        ]);
    }

    public function calculateSalary()
    {
        $month = request()->month ?? now()->month;
        $year = request()->year ?? now()->year;
        User::query()
            ->where('agency_id', '!=', 0)
            ->where('salary_is_updated', 1)
            ->where('type_user', '!=', 0)
            ->chunk(500, function ($users) use ($month, $year) {
                foreach ($users as $user) {
                    try {
                        $targetService = new FixedTargetV2Service($user, month: $month, year: $year);
                        $targetService->calculateTarget();
                    } catch (\Throwable $e) {

                        $this->error("Failed user ID {$user->id}");
                    }
                }
            });

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث الماس الشهري لجميع المستخدمين (type_user = 0).'
        ]);
    }

    public function calculateSalaryV2()
    {
        $month = request()->month ?? now()->month;
        $year = request()->year ?? now()->year;

        User::query()
            ->where('agency_id', '!=', 0)
            // ->where('salary_is_updated', 1)
            ->where('type_user', '!=', 0)
            ->chunk(500, function ($users) use ($month, $year) {
                foreach ($users as $user) {
                    try {
                        CalculateUserTargetJob::dispatch($user, $month, $year)->onQueue('calculate-target');
                    } catch (\Throwable $e) {
                        dd($e->getMessage());
                    }
                }
            });

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث الماس الشهري لجميع المستخدمين (type_user = 0).'
        ]);
    }


    public function copyMonthlyDiamondReceive()
    {
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                MonthlyDiamondReceive::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'month'   => now()->month,
                        'year'    => now()->year,
                    ],
                    [
                        'monthly_diamond_received' => $user->monthly_diamond_received,
                        'old_diamond' => $user->monthly_diamond_received,
                    ]
                );
            }
        });
        return 'done!';
    }

    /**
     * Fix monthly diamonds for users with discrepancies only
     * Much faster than calculateMonthlyDiamondReceived() because it only processes users with differences
     */
    public function fixMonthlyDiamondDiscrepancies(Request $request)
    {
        // Use UTC+4 timezone (Cairo/Dubai) instead of getTimezone() which returns UTC
        $timezone = 'Asia/Dubai'; // UTC+4
        $date = Carbon::now($timezone);
        $currentMonth = $date->month;
        $currentYear = $date->year;
        $startOfMonth = Carbon::now($timezone)->startOfMonth()->copy()->setTimezone('UTC');

        $processedCount = 0;
        $updatedCount = 0;
        $errors = [];
        $dryRun = $request->get('dry_run', false); // Add dry-run mode for testing

        // Calculate end of month properly
        $endOfMonth = Carbon::now($timezone)->endOfMonth()->copy()->setTimezone('UTC');

        // Get all users with discrepancies using the same query from the report
        $usersWithDiscrepancies = DB::select("
            SELECT
                u.id AS user_id,
                u.name,
                u.agency_id,
                mdr.monthly_diamond_received AS monthly_table_diamonds,
                COALESCE(SUM(gl.giftPrice), 0) AS actual_diamonds_from_gifts,
                (mdr.monthly_diamond_received - COALESCE(SUM(gl.giftPrice), 0)) AS difference
            FROM users u
            LEFT JOIN monthly_diamond_receives mdr ON mdr.user_id = u.id
                AND mdr.month = ?
                AND mdr.year = ?
            LEFT JOIN gift_logs gl ON gl.receiver_id = u.id
                AND gl.created_at >= ?
                AND gl.created_at < ?
                AND gl.agency_id = u.agency_id
            WHERE u.agency_id IS NOT NULL
            GROUP BY u.id, u.name, u.agency_id, mdr.monthly_diamond_received
            HAVING difference != 0 OR (mdr.monthly_diamond_received IS NULL AND actual_diamonds_from_gifts > 0)
        ", [
            $currentMonth,
            $currentYear,
            $startOfMonth->toDateTimeString(),
            $endOfMonth->toDateTimeString()
        ]);

        Log::info("Found users with discrepancies", [
            'count' => count($usersWithDiscrepancies),
            'month' => $currentMonth,
            'year' => $currentYear
        ]);

        foreach ($usersWithDiscrepancies as $userData) {
            try {
                $userId = $userData->user_id;
                $agencyId = $userData->agency_id;

                // Get join date to calculate from correct start date
                $join = DB::table('users_joined_agencies')
                    ->where('user_id', $userId)
                    ->where('agency_id', $agencyId)
                    ->orderByDesc('join_date')
                    ->first();

                $startDate = $startOfMonth;
                if ($join && Carbon::parse($join->join_date, $timezone)->greaterThan($startOfMonth)) {
                    $startDate = Carbon::parse($join->join_date, $timezone)->setTimezone('UTC');
                }

                // Calculate correct total from gift_logs
                $totalReceived = DB::table('gift_logs')
                    ->where('receiver_id', $userId)
                    ->where('created_at', '>=', $startDate)
                    ->where('agency_id', $agencyId)
                    ->selectRaw('SUM(giftPrice) as total')
                    ->value('total');

                $correctMonthlyDiamond = $totalReceived ?? 0;

                // Update using uploadMonthlyDiamondReceive (which uses UPSERT)
                if (!$dryRun) {
                    uploadMonthlyDiamondReceive($userId, $correctMonthlyDiamond);

                    // Mark salary as updated
                    DB::table('users')
                        ->where('id', $userId)
                        ->update(['salary_is_updated' => 1]);

                    $updatedCount++;
                }

                $processedCount++;

                Log::info($dryRun ? "DRY RUN - Would fix monthly diamond for user" : "Fixed monthly diamond for user", [
                    'user_id' => $userId,
                    'name' => $userData->name,
                    'old_value' => $userData->monthly_table_diamonds,
                    'new_value' => $correctMonthlyDiamond,
                    'difference' => $userData->difference,
                    'dry_run' => $dryRun
                ]);

            } catch (\Throwable $e) {
                $errors[] = [
                    'user_id' => $userData->user_id ?? 'unknown',
                    'error' => $e->getMessage()
                ];
                Log::error("Failed to fix monthly diamond", [
                    'user_id' => $userData->user_id ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $message = $dryRun
            ? "DRY RUN: سيتم معالجة {$processedCount} مستخدم (لم يتم التحديث فعلياً)"
            : "تم معالجة {$processedCount} مستخدم - تم التحديث: {$updatedCount}";

        return response()->json([
            'status' => true,
            'message' => $message,
            'dry_run' => $dryRun,
            'processed_count' => $processedCount,
            'updated_count' => $updatedCount,
            'errors_count' => count($errors),
            'errors' => $errors
        ]);
    }
}

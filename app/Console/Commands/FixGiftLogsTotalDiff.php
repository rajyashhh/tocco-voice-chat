<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixGiftLogsTotalDiff extends Command
{
    protected $signature = 'gift-logs:fix-total-diff
                            {--chunk=500 : عدد المستخدمين في كل دفعة}
                            {--dry-run : عرض النتائج فقط بدون إدراج}';

    protected $description = 'يلف على كل المستخدمين ويحسب الفرق بين total_diamond_send وإجمالي giftPrice في gift_logs ويضيف سجل تصحيح';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $chunk    = (int) $this->option('chunk');

        if ($isDryRun) {
            $this->warn('⚠️  وضع المعاينة (dry-run) — لن يُضاف أي سجل.');
        }

        $this->info('🔍 جاري معالجة المستخدمين...');

        $inserted = 0;
        $skipped  = 0;

        DB::table('users')
            ->select('id', 'total_diamond_send')
            ->where('total_diamond_send', '>', 0)
            ->orderBy('id')
            ->chunk($chunk, function ($users) use ($isDryRun, &$inserted, &$skipped) {

                $now = now()->toDateTimeString();

                foreach ($users as $user) {
                    $userTotal = (float) ($user->total_diamond_send ?? 0);

                    if ($userTotal <= 0) {
                        $skipped++;
                        continue;
                    }

                    // Calculate gift logs total: SUM(total * giftNum) for valid gift logs
                    // Exclude logs where receiver_id is NULL or 0
                    // Also exclude existing correction records to avoid double-counting
                    $giftLogsTotal = DB::table('gift_logs')
                        ->where('sender_id', $user->id)
                        ->where(function ($query) {
                            $query->whereNotNull('receiver_id')
                                  ->where('receiver_id', '!=', 0);
                        })
                        ->where('giftName', '!=', 'diff_correction')
                        ->selectRaw('SUM(CAST(total AS DECIMAL(20,2)) * CAST(giftNum AS DECIMAL(20,2))) as total')
                        ->value('total');

                    // Add existing correction records to the sum
                    $correctionTotal = DB::table('gift_logs')
                        ->where('sender_id', $user->id)
                        ->where('giftName', 'diff_correction')
                        ->selectRaw('SUM(CAST(total AS DECIMAL(20,2)) * CAST(giftNum AS DECIMAL(20,2))) as total')
                        ->value('total');

                    $giftLogsTotal   = (float) ($giftLogsTotal ?? 0);
                    $correctionTotal = (float) ($correctionTotal ?? 0);
                    $totalWithCorrection = $giftLogsTotal + $correctionTotal;

                    $diff = $userTotal - $totalWithCorrection;

                    if (abs($diff) < 1) {
                        $skipped++;
                        continue;
                    }

                    if ($isDryRun) {
                        $this->line(sprintf(
                            'user_id=%d | total_diamond_send=%.2f | gift_logs_sum=%.2f | diff=%.2f',
                            $user->id,
                            $userTotal,
                            $totalWithCorrection,
                            $diff
                        ));
                    } else {
                        // Check if a correction record already exists for this user
                        $existingCorrection = DB::table('gift_logs')
                            ->where('sender_id', $user->id)
                            ->where('giftName', 'diff_correction')
                            ->first();

                        if ($existingCorrection) {
                            // Update existing correction record instead of inserting a new one
                            $newTotal = (int) ($existingCorrection->total + $diff);
                            DB::table('gift_logs')
                                ->where('id', $existingCorrection->id)
                                ->update([
                                    'total'      => $newTotal,
                                    'updated_at' => $now,
                                ]);
                        } else {
                            // Insert new correction record
                            DB::table('gift_logs')->insert([
                                'type'             => 2,
                                'giftId'           => 0,
                                'roomowner_id'     => 0,
                                'giftName'         => 'diff_correction',
                                'giftNum'          => 1,
                                'giftPrice'        => 0,
                                'sender_id'        => $user->id,
                                'receiver_id'      => 0,
                                'is_play'          => 1,
                                'platform_obtain'  => 0,
                                'receiver_obtain'  => 0,
                                'roomowner_obtain' => 0,
                                'app_profit_coins' => 0,
                                'total'            => (int) $diff,
                                'created_at'       => $now,
                                'updated_at'       => $now,
                            ]);
                        }

                        $inserted++;
                    }
                }
            });

        $this->newLine();

        if ($isDryRun) {
            $this->info('✅ انتهت المعاينة.');
        } else {
            $this->info("✅ تم معالجة {$inserted} مستخدم، وتجاوز {$skipped} مستخدم (فرق = 0).");
        }

        return self::SUCCESS;
    }
}

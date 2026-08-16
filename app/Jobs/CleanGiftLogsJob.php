<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanGiftLogsJob  implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        User::select('id', 'agency_id')
            ->where('agency_id', '!=', 0)
            ->chunk(500, function ($users) {
                foreach ($users as $user) {
    
                    $monthlyLimit = $user->monthly_diamond_received;
    
                    // استعلام cursor لجلب السجلات تدريجيًا (lazy loading)
                    $giftLogsCursor = DB::table('gift_logs as gl')
                        ->join('gifts as g', 'gl.giftId', '=', 'g.id')
                        ->where('gl.receiver_id', $user->id)
                        ->where('gl.created_at', '>=', '2025-07-31 21:00:00')
                        ->where('gl.created_at', '<', '2025-08-30 21:00:00')
                        ->orderBy('gl.created_at', 'asc')
                        ->select('gl.id', 'gl.giftPrice', 'g.type as gift_type')
                        ->cursor();
    
                    $total = 0;
                    $type6Logs = [];
                    $otherLogs = [];
    
                    // نقرأ السجلات واحدة واحدة لحساب المجموع وتصنيفها
                    foreach ($giftLogsCursor as $log) {
                        $total += $log->giftPrice;
                        if ($log->gift_type == 6) {
                            $type6Logs[] = $log;
                        } else {
                            $otherLogs[] = $log;
                        }
                    }
    
                    // إذا المجموع ضمن الرصيد، لا تعديل
                    if ($total <= $monthlyLimit) {
                        continue;
                    }
    
                    // نبدأ حذف/تعديل حتى نصل للرصيد المطلوب
                    $remainingToRemove = $total - $monthlyLimit;
    
                    // حذف/تعديل سجلات النوع 6 أولًا
                    foreach ($type6Logs as $log) {
                        if ($remainingToRemove <= 0) break;
    
                        if ($log->giftPrice <= $remainingToRemove) {
                            DB::table('gift_logs')->where('id', $log->id)->delete();
                            $remainingToRemove -= $log->giftPrice;
                        } else {
                            $newPrice = $log->giftPrice - $remainingToRemove;
                            DB::table('gift_logs')->where('id', $log->id)->update(['giftPrice' => $newPrice]);
                            $remainingToRemove = 0;
                        }
                    }
    
                    // إذا بقيت كمية للحذف، نعالج بقية الأنواع
                    if ($remainingToRemove > 0) {
                        foreach ($otherLogs as $log) {
                            if ($remainingToRemove <= 0) break;
    
                            if ($log->giftPrice <= $remainingToRemove) {
                                DB::table('gift_logs')->where('id', $log->id)->delete();
                                $remainingToRemove -= $log->giftPrice;
                            } else {
                                $newPrice = $log->giftPrice - $remainingToRemove;
                                DB::table('gift_logs')->where('id', $log->id)->update(['giftPrice' => $newPrice]);
                                $remainingToRemove = 0;
                            }
                        }
                    }
    
                }
            });
    }
    

    
    
}

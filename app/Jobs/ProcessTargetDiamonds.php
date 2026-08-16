<?php

namespace App\Jobs;

use App\Models\GiftLog;
use App\Models\Target;
use App\Models\UserSallary;
use App\Models\MonthlyDiamondReceive;
use App\Models\TargetEdit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class ProcessTargetDiamonds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Target $target;

    public function __construct(Target $target)
    {
        $this->target = $target;
    }


    public function handle()
    {
        $this->processSalaries();
    }


    protected function processSalaries(): void
    {
        $target = $this->target;
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;

        $salaries = UserSallary::where('target_id', $target->id)
            ->where('is_finished', false)
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        foreach ($salaries as $salary) {
            $this->processSingleSalary($salary, $target, $month, $year);
        }
    }


    protected function processSingleSalary(UserSallary $salary, Target $target, int $month, int $year): void
    {
        $salary->is_finished = 1;
        $salary->save();

        $current_diamond_record = MonthlyDiamondReceive::where('user_id', $salary->user_id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $current_diamond = $current_diamond_record->monthly_diamond_received ?? 0;
        $target_diamond = $target->diamonds ?? 0;

        $extra_diamond = max($current_diamond - $target_diamond, 0);

        if ($extra_diamond > 0) {
            MonthlyDiamondReceive::updateOrCreate(
                [
                    'user_id' => $salary->user_id,
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'monthly_diamond_received' => $extra_diamond,
                    'old_diamond' => $current_diamond,
                ]
            );
            GiftLog::create([
                'giftId' => 0,
                'roomowner_id' => 0,
                'giftPrice' => $extra_diamond,
                'giftNum' => 1,
                'sender_id' => 0,
                'receiver_id' => $salary->user_id,
            ]);
        }
    }
}

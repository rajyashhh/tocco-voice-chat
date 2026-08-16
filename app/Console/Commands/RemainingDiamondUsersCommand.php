<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\GiftLog;
use App\Models\UserSallary;
use App\Enums\UserCoinLogType;
use Illuminate\Console\Command;
use App\Models\RemainingDiamond;
use App\Helpers\UserCoinLogHelper;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Illuminate\Support\Facades\Log;
use App\Models\MonthlyDiamondReceive;
use App\Traits\Salaries\UserSalaryTrait;


class RemainingDiamondUsersCommand extends Command
{
    use UserSalaryTrait;

    protected $signature = 'remaining-diamonds';
    protected $description = 'Process remaining diamonds after 30 days';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $remaining_diamonds_action = Common::getSettingValue('remaining_diamonds_action') ?? 0;
            if (!$remaining_diamonds_action) {
                $this->info('Remaining Diamonds Action is disabled. Exiting command.');
                return;
            }
            $setting = Common::getSettingValue('remaining_diamonds') ?? 'nothing';

            if ($setting === 'nothing') {
                $this->info('Remaining Diamonds Command Run Successfully !');
                return;
            }

            $timezone = getTimezone();
            $dt = Carbon::now($timezone);

            // Previous month
            $previous = $dt->copy()->subMonth();
            $month = $previous->month;
            $year  = $previous->year;

            // Cache percentage once
            $exchangePercentage = $setting === 'coins'
                ? (Common::getSettingValue('exchange_coin_percentage') ?? 1)
                : 0;

            // 🔥 Process 100 users per chunk
            UserSallary::where([
                'month' => $month,
                'year' => $year,
                'is_finished' => 0
            ])
                ->with('user')
                ->chunk(100, function ($userSalaries) use ($setting, $exchangePercentage, $dt, $month, $year) {

                    foreach ($userSalaries as $userSalary) {

                        $user = $userSalary->user;
                        $diamonds = $userSalary->remaining_diamond ?? 0;

                        // Skip if no user or no diamonds
                        if (!$user || $diamonds <= 0) {
                            continue;
                        }

                        // Prevent double processing in same month
                        $remainingDiamonds = RemainingDiamond::where('user_id', $user->id)
                            ->whereBetween('created_at', [$dt->copy()->startOfMonth(), $dt->copy()->endOfMonth()])
                            ->first();

                        if ($remainingDiamonds) {
                            continue;
                        }

                        // Process based on type
                        if ($setting === 'coins') {
                            $this->processCoins($user, $diamonds, $exchangePercentage, $month, $year);
                        }

                        if ($setting === 'diamonds') {
                            $this->processDiamonds($user, $diamonds, $dt, $month, $year);
                        }
                    }
                });

            $this->info('Remaining Diamonds Command Run Successfully !');
        } catch (\Exception $exception) {
            $this->error('Remaining Diamonds failed: ' . $exception->getMessage());
        }
    }


    /**
     * Process remaining diamonds as coins
     */
    private function processCoins($user, int $diamonds, float $exchangePercentage, $month, $year)
    {
        $exchangeCoin = floor(($exchangePercentage / 100) * $diamonds);
        $amountBefore = $user->di;

        UserCoinLogHelper::logByType(
            $user->id,
            $exchangeCoin,
            $amountBefore,
            UserCoinLogType::REMAINING_DIAMONDS
        );

        $user->di += $exchangeCoin;
        $user->save();

        RemainingDiamond::create([
            'user_id' => $user->id,
            'amount' => $exchangeCoin,
            'type' => 'coins',
            'remaining' => $diamonds,
            'month' => $month,
            'year' => $year,
        ]);
        CustomNotification::remainingDiamonds($user, 'coins', $month, $exchangeCoin);
    }

    /**
     * Process remaining diamonds as diamonds
     */
    private function processDiamonds($user, int $diamonds, Carbon $dt, $month, $year)
    {
        $monthDiamondReceive = MonthlyDiamondReceive::firstOrNew(
            [
                'user_id' => $user->id,
                'month'   => $dt->month,
                'year'    => $dt->year,
            ]
        );

        $monthDiamondReceive->monthly_diamond_received += $diamonds;
        $monthDiamondReceive->save();

        GiftLog::create([
            'giftId' => 0,
            'roomowner_id' => 0,
            'giftPrice' => $diamonds,
            'giftNum' => 1,
            'sender_id' => 0,
            'receiver_id' => $user->id,
        ]);

        RemainingDiamond::create([
            'user_id' => $user->id,
            'amount' => $diamonds,
            'type' => 'diamonds',
            'remaining' => $diamonds,
            'month' => $month,
            'year' => $year,
        ]);

        CustomNotification::remainingDiamonds($user, 'diamonds', $month, $diamonds);
    }
}

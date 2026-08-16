<?php

namespace Modules\Events\Console;

use App\Helpers\UserRewardsChargeKing;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Events\Entities\ChargeKingReward;
use Modules\Events\Repositories\ChargeKingRepository;

class ChargeKingWinnerCommand extends Command
{
    protected $signature = 'charge-king-winner';

    protected $description = 'Crowns the top 3 chargers of the previous month and pays their rank rewards, once per month';

    private const CATCH_UP_MONTHS = 6;

    public function __construct(private readonly ChargeKingRepository $chargeKingRepository)
    {
        parent::__construct();
    }

    public function handle()
    {
        $rewardsByRank = ChargeKingReward::whereIn('rank', [1, 2, 3])->get()->groupBy('rank');

        for ($monthsAgo = self::CATCH_UP_MONTHS; $monthsAgo >= 1; $monthsAgo--) {
            $reference = Carbon::now()->subMonths($monthsAgo);
            $month = $reference->format('Y-m');

            if (DB::table('charge_king_winners')->where(['month' => $month, 'rank' => 1])->exists()) {
                continue;
            }

            $this->crownMonth(
                $month,
                $reference->copy()->startOfMonth()->toDateString(),
                $reference->copy()->endOfMonth()->toDateString(),
                $rewardsByRank
            );
        }
    }

    private function crownMonth(string $month, string $fromDate, string $tillDate, $rewardsByRank): void
    {
        $leaderBoard = $this->chargeKingRepository->top($fromDate, $tillDate, 3);

        if ($leaderBoard->isEmpty()) {
            return;
        }

        foreach ($leaderBoard as $index => $user) {
            $rank = $index + 1;

            if (DB::table('charge_king_winners')->where(['month' => $month, 'rank' => $rank])->exists()) {
                continue;
            }

            $rewards = $rewardsByRank->get($rank, collect());

            if ($rewards->isEmpty() && $rank === 1) {
                $prize = (int) (Setting::where('key', 'charge_king_prize')->value('value') ?? 100000);
                $rewards = collect([new ChargeKingReward(['rank' => 1, 'type' => 'coins', 'target' => $prize, 'expire' => 0])]);
            }

            $coinsAmount = (int) $rewards->where('type', 'coins')->sum('target');

            try {
                DB::transaction(function () use ($user, $month, $rank, $rewards, $coinsAmount) {
                    DB::table('charge_king_winners')->insert([
                        'user_id' => $user->id,
                        'month' => $month,
                        'rank' => $rank,
                        'prize' => $coinsAmount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($rewards as $reward) {
                        switch ($reward->type) {
                            case 'coins':
                                UserRewardsChargeKing::assignCoins($reward->target, $user);
                                break;
                            case 'vip':
                                UserRewardsChargeKing::assignVip($reward->target, $reward->expire, $user);
                                break;
                            case 'ware':
                                UserRewardsChargeKing::assignWare($reward->target, $reward->expire, $user);
                                break;
                            case 'badge':
                                UserRewardsChargeKing::assignBadge($reward->target, $reward->expire, $user);
                                break;
                            case 'achievement':
                                UserRewardsChargeKing::assignAchievement($reward->target, $reward->expire, $user);
                                break;
                        }
                    }
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                    continue;
                }
                throw $e;
            }

            try {
                \App\Facades\CustomNotification::chargeKingWinner($user, $rank, $coinsAmount);
            } catch (\Throwable $e) {
                Log::warning('charge-king-winner notification failed: ' . $e->getMessage());
            }
        }
    }
}
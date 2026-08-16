<?php

namespace Modules\CP\Console;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Ware;
use App\Helpers\Common;
use App\Models\GiftLog;
use App\Helpers\UserCommon;
use Modules\Vip\Entities\OVip;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Events\Entities\Winner;
use App\Helpers\UserRewardsWeeklyCp;
use Modules\CP\Entities\CpWinnerReward;
use Modules\CP\Entities\WeeklyCpWinner;
use Modules\CP\Http\Services\CpService;
use Modules\Events\Entities\WeeklyStar;
use Modules\Achievement\Entities\UserAchievementLevel;

class WeeklyCpWinnerConsole extends Command
{
    protected $signature = 'weekly-cp-winner';

    protected $description = 'Command description';
    protected $cpService;
    public function __construct(CpService $cpService)
    {
        parent::__construct();

        $this->cpService = $cpService;
    }

    public function handle()
    {
        $weeklyCp = WeeklyStar::dayEnd()->where('type', 'weekly_cp')
            ->with('gifts', 'weeklyCpGifts')
            ->latest()
            ->first();

        if (!$weeklyCp) {
            return '';
        }

        $giftIds = $weeklyCp->gifts->pluck('id');
        $leaderBoard = GiftLog::whereIn('giftId', $giftIds)->select(DB::raw('sum(giftPrice) as totalGiftNum'), 'cp_id')
            ->groupBy('cp_id')->whereBetween('created_at', [
                $weeklyCp->start_date,
                $weeklyCp->end_date
            ])->whereHas('cps', function ($q) {
                $q->relation();
            })->with('cp')->orderByDesc('totalGiftNum')->take(3)->get();

        foreach ($leaderBoard as $index => $entry) {
            $alreadyWinner = WeeklyCpWinner::where(['weekly_cp_id' => $weeklyCp->id, 'user_one_id' => $entry->cp->user_one_id, 'user_two_id' => $entry->cp->user_two_id, 'type_relation' => $entry->cp->relation->type])->exists();

            if (!$alreadyWinner) {
                WeeklyCpWinner::create([
                    'weekly_cp_id' => $weeklyCp->id,
                    'user_one_id' => $entry->cp->user_one_id,
                    'user_two_id' => $entry->cp->user_two_id,
                    'type_relation' => $entry->cp->relation->type,
                    'level' => $index + 1,
                    'total_price' => $entry->totalGiftNum,
                ]);
                $userOne =  UserRewardsWeeklyCp::getUserById($entry->cp->user_one_id);
                $userTwo = UserRewardsWeeklyCp::getUserById($entry->cp->user_two_id);
                $rewards = $weeklyCp->weeklyCpGifts->where('level', $index + 1);

                if (count($rewards) > 0) {

                    foreach ($rewards as $reward) {
                        switch ($reward->type) {
                            case 'coins':
                                UserRewardsWeeklyCp::assignCoins($reward->target, $userOne, $userTwo);
                                break;
                            case 'vip':
                                UserRewardsWeeklyCp::assignVip($reward->target, $reward->expire, $userOne, $userTwo);
                                break;
                            case 'ware':
                                $ware = Ware::find($reward->target);
                                UserRewardsWeeklyCp::assignWare($ware, $reward, $userOne, $userTwo);
                                break;
                            case 'achievement':
                                UserRewardsWeeklyCp::assignAchievement($reward->target, $reward->expire, $userOne, $userTwo);
                                break;
                            case 'badge':
                                UserRewardsWeeklyCp::assignBadge($reward->target, $reward->expire, $userOne, $userTwo);
                                break;
                        }

                        CpWinnerReward::create(['winner_id' => $userOne->id, 'reward_id' => $reward->id]);
                    }
                }
            }
        }

        $newWeeklyCp = $weeklyCp->replicate();
        $newWeeklyCp->start_date = now(getTimezone())->toDateString();
        $newWeeklyCp->end_date   = now(getTimezone())->addWeek()->toDateString();
        $newWeeklyCp->save();

        $newWeeklyCp->gifts()->sync($weeklyCp->gifts->pluck('id')->toArray());
    }
}

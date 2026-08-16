<?php

namespace Modules\Public\Jobs;

use App\Enums\UserCoinLogType;
use App\Helpers\UserCoinLogHelper;
use App\Helpers\UserCommon;
use App\Models\User;
use App\Models\Ware;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Public\Entities\LevelInterval;
use Modules\Public\Entities\RewardLevelInterval;
use Modules\Public\Entities\WinnerLevelInterval;
use Modules\Vip\Entities\OVip;

class RewardWinnerLevelInterval implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $userId;
    protected $level;
    protected $type;

    public function __construct($userId, $level, $type)
    {
        $this->userId = $userId;
        $this->level = $level;
        $this->type = $type;
    }

    public function handle()
    {

        $levelIntervals = LevelInterval::query()
            ->where('min', '<=', $this->level)
           // ->where('max', '>=', $this->level)
            ->where('type', $this->type)->orderBy('min')->get();


        if ($levelIntervals) {
            foreach ($levelIntervals as $levelInterval) {
                $rewards = RewardLevelInterval::where('level_interval_id', $levelInterval->id)->get();
                $user = User::query()->find($this->userId);
                if (!$user) return;
                $tokeReward  = WinnerLevelInterval::where([
                    'user_id' => $user->id,
                    //'user_level' => $this->level,
                    'min' => $levelInterval->min,
                    'max' => $levelInterval->max,
                    'level_interval_id' => $levelInterval->id
                ])->exists();
                if ($tokeReward) continue;
                foreach ($rewards as $rewad) {
 //dd(66662226);
                    if ($rewad->type == "coins") {
                        $amountBefore = $user->di;
                        $user->di += $rewad->target;
                        $user->save();

                         UserCoinLogHelper::logByType(
                        $user->id,
                        $rewad->target,
                        $amountBefore,
                        UserCoinLogType::LEVEL_INTERVAL,
                    );
                    } elseif ($rewad->type == "vip") {
                        $vip = OVip::query()->find($rewad->target);
                        if ($vip) UserCommon::addVipToUser($user, $vip, $rewad->expire, null, 'reward-winner-interval');
                    } elseif ($rewad->type == "ware") {
                        $ware = Ware::query()->find($rewad->target);
                        if ($ware) UserCommon::addWareToUser($user, $ware, $rewad->expire, null, 'reward-winner-interval');
                    } elseif ($rewad->type == "achievement") {
                        $dateTimestamp = Carbon::parse($rewad->expire)->format("Y-m-d H:i:s");

                        $attributes = [
                            'user_id'       => $user->id,
                            'custom_achievement_id' => $rewad->target,
                            'end_at' => $dateTimestamp,
                            'receive_type' => 'level-interval',
                        ];

                        UserAchievementLevel::create($attributes);
                    } else {
                        continue;
                    }

                    $data = [
                        'user_id' => $user->id,
                        'reward_level_interval_id' => $rewad->id,
                        'user_level' => $this->level,
                        'min' => $levelInterval->min,
                        'max' => $levelInterval->max,
                        'type' => $rewad->type,
                        'level_interval_id' => $levelInterval->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    WinnerLevelInterval::query()->create($data);
                }
            }
        }
    }
}

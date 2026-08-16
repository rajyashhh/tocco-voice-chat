<?php

namespace Modules\Public\Jobs;

use App\Models\Gift;
use App\Models\User;
use App\Models\Ware;
use App\Helpers\UserCommon;
use Illuminate\Bus\Queueable;
use App\Enums\UserCoinLogType;
use Illuminate\Support\Carbon;
use Modules\Vip\Entities\OVip;
use App\Helpers\UserCoinLogHelper;
use App\Facades\CustomNotification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Public\Entities\LevelInterval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Public\Entities\RewardLevelInterval;
use Modules\Public\Entities\WinnerLevelInterval;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Achievement\Http\Services\UserAchievementService;

class RewardWinnerLevel implements ShouldQueue
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

        $levelInterval = LevelInterval::where('min', '<=', $this->level)->where('type', $this->type)
            ->where('max', '>=', $this->level)
            ->first();


        if ($levelInterval) {
            $rewards = RewardLevelInterval::where('level_interval_id', $levelInterval->id)->get();
            $firstReward = $rewards->first();
            $user = User::query()->find($this->userId);
            if (!$user) return;
            foreach ($rewards as $rewad) {

                if ($rewad->type == "coins") {
                    $amountBefore = $user->di;
                    $user->di += $rewad->target;
                    $user->save();

                    UserCoinLogHelper::logByType(
                        $user->id,
                        $rewad->target,
                        $amountBefore,
                        UserCoinLogType::ROOM_LEVEL,
                    );
                } elseif ($rewad->type == "vip") {
                    $vip = OVip::query()->find($rewad->target);
                    if (!$vip) return;
                    UserCommon::addVipToUser($user, $vip, $rewad->expire, null, 'reward-winner-level');
                } elseif ($rewad->type == "ware") {
                    $ware = Ware::query()->find($rewad->target);
                    if (!$ware) return;
                    UserCommon::addWareToUser($user, $ware, $rewad->expire, null, 'reward-winner-level');
                } elseif ($rewad->type == "achievement") {
                    $dateTimestamp = Carbon::parse($rewad->expire)->format("Y-m-d H:i:s");

                    $attributes = [
                        'user_id'       => $user->id,
                        'custom_achievement_id' => $rewad->target,
                        'end_at' => $dateTimestamp,
                        'receive_type' => 'level',
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
            CustomNotification::RoomLevel($user->id, $this->level, $firstReward->rewardLevelInterval->type);
        }
    }
}

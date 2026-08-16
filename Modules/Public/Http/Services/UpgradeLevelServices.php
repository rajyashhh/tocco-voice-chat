<?php

namespace Modules\Public\Http\Services;

use Modules\Vip\Entities\Vip;
use Modules\Vip\Entities\OVip;
use App\Models\User;
use App\Models\Ware;
use App\Models\Banner;
use App\Models\Config;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Models\EarnedDiamond;
use Illuminate\Support\Facades\DB;
use Modules\Public\Entities\levelInterval;
use Modules\Public\Jobs\RewardWinnerLevel;
use App\Classes\Gifts\UpdateUserWhenSendGift;
use Modules\Public\Entities\RewardLevelInterval;
use Modules\Public\Entities\WinnerLevelInterval;
use Modules\Public\Jobs\RewardWinnerLevelInterval;
use Modules\Achievement\Entities\UserAchievementLevel;


class UpgradeLevelServices
{

    public function buyAristocracy(User &$user, $diamonds = null)
    {
        // $config = Common::getConfig('buy_aristocracy') ?? 0;
        $this->addDiamond($user, $diamonds);
        $this->earnedDiamond($user->id, $diamonds, 'Aristocracy',);
    }

    public function purchaseItem(User &$user, $diamonds = null)
    {
        $config = Common::getConfig('purchase_items') ?? 0;
        $this->addDiamond($user, $diamonds, $config);
        $this->earnedDiamond($user->id, $diamonds, 'ware', $config, 'send');
    }

    public function sendWorldChat(User &$user, $diamonds = null)
    {
        $config = Common::getConfig('send_world_chat') ?? 0;
        $this->addDiamond($user, $diamonds, $config);
        $this->earnedDiamond($user->id, $diamonds, 'send_world_chat', $config);
    }

    public function uploadReel(User &$user, $diamonds = null)
    {
        $config = Common::getConfig('upload_reel') ?? 0;
        $this->addDiamond($user, $diamonds, $config);
        $this->earnedDiamond($user->id, $diamonds, 'upload_reel', $config);
    }

    public function uploadMoment(User &$user, $diamonds = null)
    {
        $config = Common::getConfig('upload_moment') ?? 0;
        $this->addDiamond($user, $diamonds, $config);
        $this->earnedDiamond($user->id, $diamonds, 'upload_moment', $config);
    }

    public function  addDiamond(User &$user, $diamonds, $config = null)
    {
        $user->total_diamond_send += $diamonds != null ? $diamonds : $config;
        //check user levels updgrade or not
        $this->checkUserLevelUpgrated($user);
        $user->save();
    }
    public function earnedDiamond($userId, $diamond, $actionType, $config = null, $type = null)
    {
        $diamonds = $diamond == null ? $config : $diamond;
        EarnedDiamond::create([
            'action_mode' => $actionType,
            'diamonds' => $diamonds,
            'type' => $type,
            'user_id' => $userId,
        ]);
    }

    public function checkUserLevelUpgrated(User &$user)
    {
        $oldSenderLevel = $user->sender_level;
        $subSenderLevel = $user->sub_sender_level;

        $senderLevel = (new UpdateUserWhenSendGift())->getSenderLevel($user->total_sender_diamonds, 0, $subSenderLevel);
        $user->sender_level = $senderLevel;
        $user->save();
        if ($senderLevel > $oldSenderLevel) {

            $hadNotRewards = $this->hadNotRewards($user->id, $user->total_sender_level);
            if ($hadNotRewards) {
               
                dispatch(new RewardWinnerLevelInterval($user->id, $senderLevel, 2))->onQueue('level_rewards');
            }
        }
    }

    private function hadNotRewards(int $userId, int $senderLevel)
    {
        return !WinnerLevelInterval::query()->whereHas('levelInterval', function ($query) {
            $query->where('type', 2);
        })->where('min', '<=', $senderLevel)->where('max', '>=', $senderLevel)->where('user_id', $userId)->exists();
    }

    //    public function rewardWinnerLevel(User $user, $level)
    //    {
    //        $levelInterval = levelInterval::where('min_value', '<=', $level)
    //            ->where('max_value', '>=', $level)
    //            ->first();
    //
    //        $rewards = RewardLevelInterval::where('level_interval_id', $levelInterval->id)->get();
    //        foreach ($rewards as $rewad) {
    //
    //            if ($rewad->type == "coins") {
    //                $user->di += $rewad->target;
    //                $user->save();
    //            } elseif ($rewad->type == "vip") {
    //                $vip = OVip::query()->find($rewad->target);
    //                UserCommon::addVipToUser($user, $vip, $rewad->expire);
    //            } elseif ($rewad->type == "ware") {
    //                $ware = Ware::query()->find($rewad->target);
    //                UserCommon::addWareToUser($user, $ware, $rewad->expire);
    //            } elseif ($rewad->type == "achievement") {
    //                $attributes = [
    //                    'user_id'       => $user->id,
    //                    'custom_image' => $rewad->target,
    //                ];
    //
    //                UserAchievementLevel::create($attributes);
    //            } else {
    //                continue;
    //            }
    //
    //            $data= [
    //                'user_id' => $user->id,
    //                'reward_level_interval_id' => $rewad->id,
    //                'user_level' => $level,
    //                'min' => $levelInterval->min,
    //                'max' => $levelInterval->max,
    //                'type' => $rewad->type,
    //                'created_at' => now(),
    //                'updated_at' => now(),
    //            ];
    //            WinnerLevelInterval::query()->create($data);
    //        }
    //    }
}

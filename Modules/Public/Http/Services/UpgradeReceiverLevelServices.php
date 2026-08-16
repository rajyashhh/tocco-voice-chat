<?php

namespace Modules\Public\Http\Services;

use App\Classes\Gifts\UpdateUserWhenSendGift;
use App\Helpers\Common;
use App\Models\EarnedDiamond;
use App\Models\User;
use Log;
use Modules\Public\Entities\WinnerLevelInterval;
use Modules\Public\Jobs\RewardWinnerLevel;


class UpgradeReceiverLevelServices
{



    public function uploadMoment(User &$user, $diamonds = null)
    {
        $config = Common::getConfig('upload_moment') ?? 0;
        $this->addDiamond($user, $diamonds, $config);
        $this->earnedDiamond($user->id, $diamonds, 'upload_moment', $config);
    }

    public function  addDiamond(User &$user, $diamonds, $config)
    {
        $user->total_diamond_send += $diamonds != null ? $diamonds : $config;
        //check user levels updgrade or not
        $this->checkUserLevelUpgrated($user);
        $user->save();
    }
    public function earnedDiamond($userId, $diamond, $actionType, $config, $type = null)
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
        $oldReceiverLevel = $user->received_level;
        $subReceivedLevel = $user->sub_receiver_level;

        $receiverLevel = (new UpdateUserWhenSendGift())->getReceiverLevel($user->total_received_diamonds, 0, $subReceivedLevel);


        $user->received_level = $receiverLevel;


        if ($receiverLevel > $oldReceiverLevel) {
            $hadNotRewards = $this->hadNotRewards($user->id, $user->total_received_level);
            if ($hadNotRewards) {
                dispatch(new RewardWinnerLevel($user->id, $receiverLevel, 1))->onQueue('level_rewards');
            }
        }
    }

    private function hadNotRewards(int $userId, int $senderLevel)
    {
        return !WinnerLevelInterval::query()->whereHas('levelInterval', function ($query) {
            $query->where('type', 1);
        })->where('min', '<=', $senderLevel)->where('max', '>=', $senderLevel)->where('user_id', $userId)->exists();
    }
}

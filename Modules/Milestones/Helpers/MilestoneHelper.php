<?php

namespace Modules\Milestones\Helpers;

use App\Enums\UserCoinLogType;
use App\Helpers\Common;
use App\Helpers\StorageHelper;
use App\Helpers\UserCoinLogHelper;
use App\Models\Ware;
use Carbon\Carbon;
use App\Models\User;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Milestones\Entities\Milestone;
use Modules\Milestones\Entities\MilestoneReward;
use App\Helpers\UserCommon;
use Modules\RoleRewards\Entities\UserHistoryReward;
use Modules\Vip\Entities\OVip;


class MilestoneHelper
{


    public static function grantMilestoneToUser(User|int $user, string $slug): void
    {


        if (!$user instanceof User) {
            $user = User::find($user);
            if (!$user) {
                return;
            }
        }

        $milestone = Milestone::where('slug', $slug)->first();
        if (!$milestone) {
            return;
        }


        if (!$milestone->rewards || $milestone->rewards->isEmpty()) {
            return;
        }

        foreach ($milestone->rewards as $mr) {


            self::giveRewardToUser($user, $mr, $milestone);
        }
    }


    public static function giveRewardToUser(User $user, MilestoneReward $mr, $milestone = null): void
    {
        $receiveType = "Milestone:{$mr->milestone_id}";

        $existing = UserHistoryReward::withTrashed()
            ->where('user_id', $user->id)
            ->where('receive_type', $receiveType)
            ->where('rewardable_type', $mr->rewardable_type)
            ->where('rewardable_id', $mr->rewardable_id)
            ->first();

        if ($existing) {
            if (!$existing->trashed()) {
                return;
            }

            $existing->forceDelete();
        }

        UserHistoryReward::create([
            'user_id' => $user->id,
            'receive_type' => $receiveType,
            'sub_type' => 'milestons',
            'rewardable_id' => $mr->rewardable_id,
            'rewardable_type' => $mr->rewardable_type,
            'extra' => json_encode([
                'reward' => $mr?->reward,
                'type' => $mr?->type,
                'expire' => $mr?->expire,
            ]),
        ]);

        self::applyRewardEffect($user, $mr, $milestone);
    }


    protected static function applyRewardEffect(User $user, MilestoneReward $mr, $milestone = null): void
    {
        $receiveType = "Milestone:{$mr->milestone_id}";

        switch ($mr->type) {
            case 'coins':
                $amountBefore = $user->di;
                UserCoinLogHelper::logByType(
                    $user->id,
                    $mr->reward,
                    $amountBefore,
                    UserCoinLogType::MILESTONE,
                );

                $user->di += $mr->reward;
                $user->save();

                break;

            case 'vip':
                $vip = OVip::find($mr->rewardable_id);
               

                $img =  StorageHelper::url($vip->background_img);
             
                UserCommon::addVipToUser($user, $vip, $mr->expire, 0, $receiveType, 1,1, $milestone ?  __('milestone_vip_reward_message', [ 'milestone' => $milestone->name, 'vip'=> $vip->name  ]) : null , $img);
                break;

            case 'ware':
                $ware = Ware::find($mr->rewardable_id);
                if (!$ware) {
                    break;
                }
                UserCommon::addEvintsWareToUser($user, $ware, $mr->expire, 0, $receiveType, feature: $milestone ,message: $milestone ? __('milestone_ware_reward_message', ['milestone' => $milestone->name, 'ware' => $ware->name]) : null );
                break;

            case 'badge':
                Common::userBadge($user->id, $mr->rewardable_id, $mr->expire, $receiveType);
                break;

            case 'achievement':
                $dateTimestamp = Carbon::parse($mr->expire)->format("Y-m-d H:i:s");

                UserAchievementLevel::create([
                    'user_id' => $user->id,
                    // 'custom_image' => $mr->reward,
                    'custom_achievement_id' => $mr->rewardable_id,
                    'receive_type' => $receiveType,
                    'end_at' => $dateTimestamp,
                ]);
                break;
        }
    }


    public static function revokeRewardFromUser(User $user, MilestoneReward $mr): void
    {
        $receiveType = "Milestone:{$mr->milestone_id}";
        $rewards = UserHistoryReward::where('user_id', $user->id)
            ->where('receive_type', $receiveType)
            ->where('rewardable_type', $mr->rewardable_type)
            ->where('rewardable_id', $mr->rewardable_id)
            ->get();

        if ($rewards) {
            foreach ($rewards as $r) {
                self::removeRewardEffect($user, $r);
                $r->delete();
            }
        }
    }


    protected static function removeRewardEffect(User $user, UserHistoryReward $historyReward): void
    {
        $extra = json_decode($historyReward->extra, true);
        $type = $extra['type'] ?? null;
        $rid = $historyReward->rewardable_id;
        $receiveType = $historyReward->receive_type;

        switch ($type) {
            case 'coins':
                // UserCommon::removeCoinsFromUser($user->id, (int)($extra['reward'] ?? 0), $receiveType);
                break;

            case 'vip':
                UserCommon::removeVipFromUser($user, $rid, $receiveType);
                break;

            case 'ware':
                UserCommon::removeEventsWareFromUser($user, $rid, $receiveType);
                break;

            case 'badge':
                UserCommon::removeBadgeFromUserByReceiverType($user, $rid, $receiveType);
                break;

            case 'achievement':
                UserAchievementLevel::where('user_id', $user->id)
                    ->where('custom_image', $extra['reward'] ?? null)
                    ->where('receive_type', $receiveType)
                    ->delete();
                break;
        }
    }


    public static function removeReward($user, $slug)
    {
        $milestone = Milestone::where('slug', $slug)->with('rewards')->first();
        if ($milestone && $milestone->rewards && $milestone->rewards->count()) {
            foreach ($milestone->rewards as $reward) {


                self::revokeRewardFromUser($user, $reward);
            }
        }
        // else {
        //     Log::warning('No rewards found for milestone', [
        //         'milestone_slug' => $slug,
        //         'user_id' => $user->id ?? null,
        //     ]);
        // }
    }
}

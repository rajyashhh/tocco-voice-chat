<?php

namespace Modules\RoleRewards\Helpers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Ware;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Enums\UserCoinLogType;
use Modules\Vip\Entities\OVip;
use Modules\Badge\Entities\Badge;
use App\Helpers\UserCoinLogHelper;
use Modules\RoleRewards\Entities\RoleReward;
use Modules\RoleRewards\Entities\UserHistoryReward;
use Modules\Achievement\Entities\UserAchievementLevel;

class UserRoleRewardHelper
{


    public static function giveRoleRewards(User $user, int $roleId, $slug = null): void
    {
        $rewards = RoleReward::where('role_id', $roleId)->get();

        foreach ($rewards as $reward) {
            $exists = UserHistoryReward::where([
                'user_id'         => $user->id,
                'receive_type'    => "Role:$roleId",
                'rewardable_id'   => $reward->rewardable_id,
                'rewardable_type' => $reward->rewardable_type,
            ])->where('is_deleted', 0)->exists();

            if (! $exists) {
                UserHistoryReward::create([
                    'user_id'         => $user->id,
                    'sub_type'         => 'roles',
                    'receive_type'    => "Role:$roleId",
                    'rewardable_id'   => $reward->rewardable_id,
                    'rewardable_type' => $reward->rewardable_type,
                    'extra'           => ['expire' => $reward->expire],
                ]);

                self::applyReward($user, $reward, "Role:$roleId");
            }
        }
    }


    public static function revokeRoleRewards(User $user, int $roleId, $slug = null): void
    {
        $rewards = UserHistoryReward::where('user_id', $user->id)
            ->where('receive_type', "Role:$roleId")
            ->get();

        foreach ($rewards as $reward) {
            self::removeReward($user, $reward);
            $reward->update([
                'is_deleted' => 1
            ]);
            $reward->delete();
        }
    }




    public static function revoke(User $user, string $receiveType): void
    {
        $rewards = UserHistoryReward::where('user_id', $user->id)
            ->where('receive_type', $receiveType)
            ->get();

        foreach ($rewards as $reward) {
            self::removeReward($user, $reward);
            $reward->delete();
        }
    }


    protected static function applyReward(User $user, $reward, $receiveType): void
    {
        if ($reward->type === "vip") {
            $vip = OVip::find($reward->rewardable_id);
            UserCommon::addVipToUser($user, $vip, $reward->expire, null, $receiveType);
        } elseif ($reward->type === "ware") {
            $ware = Ware::find($reward->rewardable_id);
            UserCommon::addEvintsWareToUser($user, $ware, $reward->expire, null, $receiveType);
        } elseif ($reward->type === "achievement") {
            $dateTimestamp = Carbon::parse($reward->expire)->format("Y-m-d H:i:s");

            UserAchievementLevel::create([
                'user_id'      => $user->id,
                //'custom_image' => $reward->reward_achievement,
                'custom_achievement_id' => $reward->rewardable_id,
                'receive_type' => $receiveType,
                'end_at' => $dateTimestamp,
            ]);
        } elseif ($reward->type === "badge") {
            Common::userBadge($user->id, $reward->rewardable_id, $reward->expire, $receiveType);
        }
    }


    protected static function removeReward(User $user, UserHistoryReward $reward): void
    {
        if ($reward->rewardable_type === self::mapTypeToModel('vip')) {
            UserCommon::removeVipFromUser($user, $reward->rewardable_id, $reward->receive_type);
        } elseif ($reward->rewardable_type === self::mapTypeToModel('ware')) {
            UserCommon::removeEventsWareFromUser($user, $reward->rewardable_id, $reward->receive_type);
        } elseif ($reward->rewardable_type === self::mapTypeToModel('achievement')) {
            UserAchievementLevel::where('user_id', $user->id)
                ->where('custom_achievement_id', $reward->rewardable_id)
                ->where('receive_type',  $reward->receive_type)
                ->delete();
        } elseif ($reward->rewardable_type === self::mapTypeToModel('badge')) {
            UserCommon::removeBadgeFromUser($user, $reward->rewardable_id, $reward->receive_type);
        }
    }



    protected static function mapTypeToModel(string $type): string
    {
        return match ($type) {
            'coins'       => 'coins',
            'vip'         => OVip::class,
            'ware'        => Ware::class,
            'achievement' => UserAchievementLevel::class,
            'badge'       => Badge::class,
            default       => $type,
        };
    }



    public static function revokeRewardsFromAllUsersForRole(int $roleId, string $slug = null): void
    {
        $dashboardUsers = \Encore\Admin\Auth\Database\Administrator::whereHas('roles', function ($q) use ($roleId) {
            $q->where('id', $roleId);
        })->get();

        $appIds = $dashboardUsers->pluck('app_id')->filter()->unique();


        if ($appIds->isEmpty()) {
            return;
        }

        $users = User::whereIn('id', $appIds)->get();

        foreach ($users as $user) {
            self::revokeRoleRewards($user, $roleId, $slug);
        }
    }





    public static function syncRewardsForRole(int $roleId, string $slug = null): void
    {
        $dashboardUsers = \Encore\Admin\Auth\Database\Administrator::whereHas('roles', function ($q) use ($roleId) {
            $q->where('id', $roleId);
        })->get();

        $appIds = $dashboardUsers->pluck('app_id')->filter()->unique();
        if ($appIds->isEmpty()) return;

        $users = User::whereIn('id', $appIds)->get();

        foreach ($users as $user) {
            self::giveRoleRewards($user, $roleId, $slug);
        }
    }


    public static function revokeSpecificRewardFromAllUsers(
        int $roleId,
        string $slug,
        int $rewardId,
        string $rewardableType,
        int $rewardableId
    ): void {
        $dashboardUsers = \Encore\Admin\Auth\Database\Administrator::whereHas('roles', function ($q) use ($roleId) {
            $q->where('id', $roleId);
        })->get();

        $appIds = $dashboardUsers->pluck('app_id')->filter()->unique();

        if ($appIds->isEmpty()) {
            return;
        }

        $users = User::whereIn('id', $appIds)->get();

        foreach ($users as $user) {
            self::revokeOneReward($user, $roleId, $slug, $rewardableType, $rewardableId);
        }
    }

    protected static function revokeOneReward($user, int $roleId, string $slug, string $rewardableType, int $rewardableId): void
    {
        $rewards = UserHistoryReward::where('user_id', $user->id)
            ->where('receive_type', "Role:$slug:$roleId")
            ->where('rewardable_type', $rewardableType)
            ->where('rewardable_id', $rewardableId)
            ->get();

        foreach ($rewards as $reward) {
            self::removeReward($user, $reward);
            $reward->delete();
        }
    }
}

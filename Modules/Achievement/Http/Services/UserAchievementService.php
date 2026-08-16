<?php

namespace Modules\Achievement\Http\Services;

use App\Models\Gift;
use App\Models\User;
use Modules\Achievement\Entities\Achievement;
use Modules\Achievement\Entities\UserAchievement;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Achievement\Enums\AchievementType;
use Modules\Achievement\Http\Services\AchievementLevelsService;

class UserAchievementService
{
    public function insertCharging(User $user, $totalCoins): void
    {
        $userId      = $user->id;
        $achievement = Achievement::query()->where('type', AchievementType::RECHARGE_TARGET)->first();
        if (!$achievement) return;

        $userAchievement = UserAchievement::query()
            ->where('user_id', $userId)
            ->where('achievement_id', $achievement->id)
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->first();


        if ($userAchievement == null) {
            $totalTarget = UserAchievement::query()
                ->where('gift_achievement_id', null)
                ->where('user_id', $userId)
                ->where('achievement_id', $achievement->id)
                ->max('total_target') ?? 0;
            $userAchievement = UserAchievement::query()->create([
                'user_id'        => $userId,
                'achievement_id' => $achievement->id,
                'target'         => (int)$totalCoins,
                'total_target'   => (int)$totalTarget + (int)$totalCoins,
                'month'          => now()->month,
                'year'           => now()->year,
            ]);
        } else {
            $userAchievement->target += (int)$totalCoins;
            $userAchievement->total_target += (int)$totalCoins;
            $userAchievement->save();
        }
        (new AchievementLevelsService())->assignAchievementToUser($userAchievement);
    }

    public function roomTarget(User $user, $totalCoins)
    {
        $userId          = $user->id;
        $achievement     = Achievement::query()->where('type', AchievementType::ROOM_TARGET)->first();
        $userAchievement = UserAchievement::query()
            ->where('user_id', $userId)
            ->where('achievement_id', $achievement->id)
            ->where('gift_achievement_id', null)
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->first();

        if ($userAchievement == null) {
            $totalTarget        =  UserAchievement::query()
                ->where('gift_achievement_id', null)
                ->where('user_id', $userId)
                ->where('achievement_id', $achievement->id)
                ->max('total_target') ?? 0;
            $userAchievement    =  UserAchievement::query()->create([
                'user_id'        => $userId,
                'achievement_id' => $achievement->id,
                'target'         => (int)$totalCoins,
                'total_target'   => (int)$totalTarget + (int)$totalCoins,
                'month'          => now()->month,
                'year'           => now()->year,
            ]);
        } else {
            $userAchievement->target += (int)$totalCoins;
            $userAchievement->total_target += (int)$totalCoins;
            $userAchievement->save();
        }
        (new AchievementLevelsService())->assignAchievementToUser($userAchievement);
    }

    public function giftTarget(Gift $gift, $total)
    {
        $achievement = Achievement::query()->where('type', AchievementType::GIFT_TARGET)->first();

        $giftAchievement = $gift->achievement;
        if (!$giftAchievement) return;

        $userId          = $giftAchievement->user->id;
        $userAchievement = UserAchievement::query()
            ->where('user_id', $userId)
            ->where('achievement_id', $achievement->id)
            ->where('gift_achievement_id', $giftAchievement->id)
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->first();

        if ($userAchievement == null) {
            $totalTarget = UserAchievement::query()
                ->where('gift_achievement_id', $giftAchievement->id)
                ->where('user_id', $userId)
                ->where('achievement_id', $achievement->id)
                ->max('total_target') ?? 0;
            UserAchievement::query()->create([
                'user_id'             => $userId,
                'achievement_id'      => $achievement->id,
                'gift_achievement_id' => $giftAchievement->id,
                'target'              => (int)$total,
                'total_target'        => (int)$totalTarget + (int)$total,
                'month'               => now()->month,
                'year'                => now()->year,
            ]);
        } else {
            $userAchievement->target += (int)$total;
            $userAchievement->total_target += (int)$total;
            $userAchievement->save();
        }
        (new AchievementLevelsService())->assignAchievementToUser($userAchievement);
    }

    public function getUserAchievement(User $user)
    {
        $usersAchievementLevels =
            UserAchievementLevel::query()
            ->where('user_id', $user->id)
            ->where('is_enable', true)
            ->where('picked', true)
            ->with('customAchievement', 'customAchievement.images')
            ->where(function ($query) {
                $query->whereNull('end_at')
                    ->orWhere('end_at', '>', now());
            })
            ->leftJoin('achievement_levels', 'user_achievement_levels.achievement_level_id', 'achievement_levels.id')
            ->leftJoin('achievements', 'achievement_levels.achievement_id', 'achievements.id')
            ->orderByDesc('achievements.id')
            ->orderByDesc('achievement_levels.target')
            ->select([
                'user_achievement_levels.id',
                'achievement_levels.id as achievement_level_id',
                'achievement_levels.target',
                'achievement_levels.valid_image',
                'user_achievement_levels.custom_image',
                'user_achievement_levels.custom_achievement_id',
                'achievement_levels.ar_description',
                'achievement_levels.en_description',
                'achievements.type as type'
            ])->with('achievementLevel')->get();

        return $usersAchievementLevels;
    }


    public function roomAchievement(int $ownerId)
    {
        $roomAchievement   = Achievement::query()->where('type', AchievementType::ROOM_TARGET)
            ->with('levels:id,achievement_id,valid_image')->first();

        if (!$roomAchievement) {
            return collect();
        }

        $levels            = $roomAchievement->levels->flatten();
        $levelsIds         = $levels->pluck('id')->toArray();
        $usersAchievements = UserAchievementLevel::query()
            ->where('user_id', $ownerId)
            ->where('is_enable', true)
            ->where("picked", 1)
            ->whereNotNull('achievement_level_id')
            ->where(fn($query) => $query->whereIn('achievement_level_id', $levelsIds)->orWhere(fn($q) => $q->where('achievement_id', '=', null)->where('custom_image', '!=', null)))
            ->get();
        $usersAchievements = $usersAchievements->map(function ($item) use ($levels) {
            return ['image' => $levels->where('id', $item->achievement_level_id)?->value('valid_image') ?? $item->custom_image];
        });


        return $usersAchievements;
    }
}

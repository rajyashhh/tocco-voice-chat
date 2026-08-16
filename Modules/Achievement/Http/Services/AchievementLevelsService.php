<?php

namespace Modules\Achievement\Http\Services;

use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Achievement\Entities\AchievementLevel;
use Modules\Achievement\Entities\UserAchievement;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Achievement\Enums\TargetType;

class AchievementLevelsService
{

    private Collection $userAchievementLevels;
    private int $countTargets = 2;

    public function __construct() { }

    /**
     * @param int $userId
     * @param int $achievement_id
     * @return void
     */
    public function assignAchievementToUser( ?UserAchievement $userAchievement): void
    {
        if (!$userAchievement) return;
        $month = now()->month;
        $year  = now()->year;

        /*$userAchievement = UserAchievement::query()
                                          ->selectRaw('target, total_target, user_id, achievement_id')
                                          ->where('month', $month)
                                          ->where('year', $year)
                                          ->where('achievement_id', $achievement_id)
                                          ->with([
                                                     'achievement.levels', 'user' => function ($query) {
                                                  $query->withoutAppends()->select(['id', 'name', 'notification_id']);
                                              }
                                                 ])
                                          ->first();*/

        $notificationIds = $this->approveAchievement($userAchievement);

        $this->sendAchievementNotifications($notificationIds);
    }

    /**
     * @param UserAchievement|null $userAchievement
     * @param array|null $notificationIds
     * @return array
     */
    public function approveAchievement(?UserAchievement $userAchievement, ?array $notificationIds = null): array
    {

        if ($notificationIds == null) $notificationIds = array_fill(0, $this->countTargets, []);

        if (!$userAchievement) return $notificationIds;

        $targetType = $this->getAchievement($userAchievement);

        if ($targetType) {
            switch ($targetType) {
                case TargetType::MONTHLY:
                    $notificationIds[0][] = @$userAchievement->user->notification_id;
                    break;
                case TargetType::DEFAULT:
                    $notificationIds[1][] = @$userAchievement->user->notification_id;
                    break;
                default:
                    break;
            }
        }

        return $notificationIds;
    }

    /**
     * @param UserAchievement $userAchievement
     * @return string|null
     */
    public function getAchievement(UserAchievement $userAchievement): ?TargetType
    {
        $achievement       = $userAchievement->achievement;
        $achievementLevels = $achievement->levels;
        $levelIds          = $this->getLevelsIds($achievementLevels);
        $defaultIds        = $this->getDefaultLevelsIds($achievementLevels);

        $currentTarget = $userAchievement->target;
        $totalTarget   = $userAchievement->total_target;


        $achievementLevel =
            $achievementLevels->where('target', '<=', $totalTarget)->where('target_type', TargetType::DEFAULT)->sortByDesc('target')->first();
        if ($achievementLevel == null) {
            $achievementLevel =
                $achievementLevels->where('target', '<=', $currentTarget)->where('target_type', '!=', TargetType::DEFAULT)->sortByDesc('target')->first();
        }

        $user                        = $userAchievement->user;
        $userId                      = $user->id;
        $this->userAchievementLevels = $this->getUserAchievementLevels($userId, $levelIds);

        if ($achievementLevel != null) {

            $ifGreaterThan =
                $this->checkIfComingLevelGreaterThanExists($achievementLevel->id, $userId, $levelIds, ($userAchievement->user_id == 43 && $userAchievement->achievement_id == 2));

            if ($ifGreaterThan) {
                try {
                    $this->assignAchievement($userId, $achievementLevel, $levelIds, $defaultIds,@$userAchievement?->gift_achievement_id);

                } catch (\Exception $e) {
                    //dd($e->getMessage());
                    return null;
                }
                return $achievementLevel->target_type;
            }
        }

        return null;
    }

    /**
     * @param $achievementLevels
     * @return mixed
     */
    public function getLevelsIds($achievementLevels): mixed
    {
        return $achievementLevels->sortBy('target')->pluck('id')->toArray();
    }

    /**
     * @param $achievementLevels
     * @return mixed
     */
    public function getDefaultLevelsIds($achievementLevels): mixed
    {
        return $achievementLevels->where('target_type', TargetType::DEFAULT)->sortBy('target')->pluck('id')->toArray();
    }

    /**
     * @param int $userId
     * @param array $levelIds
     * @return Builder[]|Collection
     */
    public function getUserAchievementLevels(int $userId, array $levelIds): array|Collection
    {
        return UserAchievementLevel::query()
                                   ->where('user_id', $userId)
                                   ->where('is_enable', true)
                                   ->whereIn('achievement_level_id', $levelIds)
                                   ->where(function ($query) {
                                       $query->where('end_at', '>=', today())->orWhere('end_at', null);
                                   })
                                   ->orderByDesc('id')->get();
    }

    /**
     * @param int $targetId
     * @param int $userId
     * @param array $levelIds is a sorted levels
     * @return bool
     */
    private function checkIfComingLevelGreaterThanExists(int $targetId, int $userId, array $levelIds, bool $isTest = false): bool
    {
        if (!isset($this->userAchievementLevels)) {
            $this->userAchievementLevels = $this->getUserAchievementLevels($userId, $levelIds);
        }
        //        $userAchievement = $this->userAchievementLevels->where('achievement_level_id', $targetId)->first();
        $userAchievement = $this->userAchievementLevels->sortBy(function ($item) use ($levelIds) {
            return array_search($item['achievement_level_id'], $levelIds);
        })->last();
        if ($userId != null) {
            $data=$this->checkIfLevelExpiredInMonth($userId,$targetId);
            if ($data) {
                return false;
            }
        }
        return ($userAchievement == null) ||
            $this->isGreater($userAchievement->achievement_level_id, $levelIds, $targetId);
    }

    /**
     * @param $achievementLevelId
     * @param array $levelIds
     * @param int $targetId
     * @return bool
     */
    public function isGreater($achievementLevelId, array $levelIds, int $targetId): bool
    {
        $prevIndex    = array_search($achievementLevelId, $levelIds);
        $currentIndex = array_search($targetId, $levelIds);

        return $currentIndex === false || ($currentIndex > $prevIndex);
    }

    public function assignAchievement(int $userId, AchievementLevel $achievementLevel, array $levelIds, array $defaultIds,$giftId=null): void
    {
        if (!isset($this->userAchievementLevels)) {
            $this->userAchievementLevels = $this->getUserAchievementLevels($userId, $levelIds);
        }
        // if ($this->userAchievementLevels->count() != 0) {
        //     $this->userAchievementLevels->toQuery()->whereNotIn('achievement_level_id', $defaultIds)->update(['is_enable' => false]);
        // }
        $userAchievementAll=UserAchievementLevel::where("user_id",$userId)->where(fn($q)=> $q->where('end_at', '>=', today())->orWhere('end_at', null))->where('achievement_level_id', '!=', null)->pluck("achievement_level_id")->toArray();
        $all_achievements=AchievementLevel::where("target" , '<=',$achievementLevel->target)
                            ->where("target_type",$achievementLevel->target_type)
                            ->where("achievement_id",$achievementLevel->achievement_id)
                             ->whereNotIn("id",$userAchievementAll)
                            ->get();
        // create $user achievement

        foreach ($all_achievements as $achievementLevel) {

            $attributes = [
                'user_id'              => $userId,
                'achievement_level_id' => $achievementLevel->id,
                'gift_achievement_id' => $giftId,
            ];
            $this->appendEndAt($attributes, $achievementLevel->target_type);

            UserAchievementLevel::query()->create($attributes);
        }
    }

    private function appendEndAt(array &$attributes, TargetType $targetType): void
    {
        $attributes['end_at'] = match ($targetType) {
            TargetType::MONTHLY => today()->addDays(30),
            TargetType::WEEKLY => today()->addDays(7),
            default => null,
        };

    }

    /**
     * @param array|null $notificationIds
     * @return void
     */
    public function sendAchievementNotifications(?array $notificationIds): void
    {
        if ($notificationIds == null || count($notificationIds) < $this->countTargets) return;

        if (count($notificationIds[0]) > 0) {
            Common::send_firebase_notification($notificationIds[0], config('app.name_en'), 'Congratulation you achieve new monthly achievement ends at ' . Carbon::now()->endOfMonth()->shortAbsoluteDiffForHumans());
        }

        if (count($notificationIds[1]) > 0) {
            Common::send_firebase_notification($notificationIds[1],config('app.name_ar'), 'تهانينا لقد ربحت وسام جديد دائم 🥇');
        }
    }

    public function setUserAchievementLevel(bool $isTest = false): void
    {
        if (!$isTest) {
            $lastMonth = now()->subMonth()->month;
            $lastYear  = now()->subMonth()->year;
        } else {
            $lastMonth = now()->month;
            $lastYear  = now()->year;
        }

        // select all from this month
        // else user didn't have all live achievement then check monthly achievement
        $userAchievements = UserAchievement::query()
                                           ->selectRaw('target, total_target, user_id, achievement_id')
                                           ->where('month', $lastMonth)
                                           ->where('year', $lastYear)
                                           ->with([
                                                      'achievement.levels', 'user' => function ($query) {
                                                   $query->withoutAppends()->select(['id', 'name', 'notification_id']);
                                               }
                                                  ])
                                           ->get()
                                           ->chunk(1000);

        foreach ($userAchievements as $userAchievementChunks) {
            $notificationIds = null;
            foreach ($userAchievementChunks as $userAchievement) {
                $notificationIds = $this->approveAchievement($userAchievement, $notificationIds);

            }
            $this->sendAchievementNotifications($notificationIds);
        }


    }

    public function assignAchievementLevelToUserByAdmin(int $userId, AchievementLevel $achievementLevel): bool
    {
        $achievement = $achievementLevel->achievement;

        $achievementLevels = $achievement->levels;
        $levelIds          = $this->getLevelsIds($achievementLevels);
        $defaultIds        = $this->getDefaultLevelsIds($achievementLevels);

        $ifGreaterThan = $this->checkIfComingLevelGreaterThanExists($achievementLevel->id, $userId, $levelIds);

        if ($ifGreaterThan) {
            try {
                $this->assignAchievement($userId, $achievementLevel, $levelIds, $defaultIds);
            } catch (\Exception $e) {
                return false;
            }
            return true;

        }
        return false;
    }

    public function getExpiredUserAchievementLevels(int $userId, int $levelId): array|Collection
    {
        return UserAchievementLevel::query()
                                   ->where([
                                               'user_id'              => $userId,
                                               'is_enable'            => false,
                                               'achievement_level_id' => $levelId
                                           ])
                                   ->orderByDesc('id')->get();
    }

    private function checkIfLevelExpired(int $levelId, int $userId): bool
    {
        return UserAchievementLevel::query()
                                   ->where([
                                               'user_id'              => $userId,
                                               'is_enable'            => false,
                                               'achievement_level_id' => $levelId
                                           ])->exists();
    }

    private function checkIfLevelExpiredInMonth(int $userId,int $levelId): bool
    {
        return UserAchievementLevel::query()
                                    ->whereMonth("created_at",date("m"))
                                    ->whereYear("created_at",date("Y"))
                                    ->where('user_id',$userId)
                                    ->where( 'is_enable',false)
                                    ->where( 'achievement_level_id',$levelId)
                                    ->exists();
    }

}

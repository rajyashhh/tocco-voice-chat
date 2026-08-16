<?php

namespace Modules\Achievement\Http\Repositories;

use App\Tik\Repositories\AbstractRepository;
use Modules\Achievement\Entities\AchievementLevel;
use Modules\Achievement\Entities\UserAchievementLevel;


class UserAchievementLevelRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new UserAchievementLevel());
    }

    public function all($perPage, $Page, $uuid)
    {
        return $this->model->with('user', 'achievement', 'giftAchievement')->when($uuid, function ($query) use ($uuid) {
            $query->whereHas('user', function ($userQuery) use ($uuid) {
                $userQuery->where('uuid', $uuid);
            });
        })->paginate($perPage, ['*'], 'page', $Page);
    }
}

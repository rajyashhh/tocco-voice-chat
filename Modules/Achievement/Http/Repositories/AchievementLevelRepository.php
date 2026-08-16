<?php

namespace Modules\Achievement\Http\Repositories;

use App\Tik\Repositories\AbstractRepository;
use Modules\Achievement\Entities\AchievementLevel;


class AchievementLevelRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new AchievementLevel());
    }

    public function all($achievementId, $perPage, $Page)
    {
        return $this->model->where('achievement_id', $achievementId)->paginate($perPage, ['*'], 'page', $Page);
    }

    public function getTarget($achievementId)
    {
        return $this->model->where('achievement_id', $achievementId)->pluck('target', 'id');
    }
}

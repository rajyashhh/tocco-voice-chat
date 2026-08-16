<?php

namespace Modules\Achievement\Http\Repositories;

use App\Tik\Repositories\AbstractRepository;
use Modules\Achievement\Entities\GiftAchievement;
use Request;

class GiftAchievementRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new GiftAchievement());
    }

    public function getByAchievementId($achievementId, $perPage, $Page)
    {
        return $this->model->where('achievement_id', $achievementId)->with('user', 'gift', 'Achievement')->paginate($perPage, ['*'], 'page', $Page);
    }

    public function all( $perPage, $Page)
    {
        return $this->model->with( 'gift')->paginate($perPage, ['*'], 'page', $Page);
    }

    public function store($request){
        $this->model->create($request->all());
    }
}

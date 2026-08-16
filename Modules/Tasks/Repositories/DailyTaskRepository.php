<?php

namespace Modules\Tasks\Repositories;

use App\Models\EntitiesDailyTask;
use App\Tik\Repositories\AbstractRepository;
//use Modules\Tasks\Repositories\Contracts\DailyTaskRepositoryInterface;
use Modules\Tasks\Entities\DailyTask;

class DailyTaskRepository extends AbstractRepository //implements DailyTaskRepositoryInterface
{
    public function __construct(DailyTask $model)
    {
        parent::__construct($model);
    }
    /*public function findById($taskId)
    {
        return DailyTask::findOrFail($taskId);
    }

    public function findByDayId($dayId)
    {
        return DailyTask::where('day_id', $dayId)->get();
    }*/
}

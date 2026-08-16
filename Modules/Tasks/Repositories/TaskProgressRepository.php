<?php

namespace Modules\Tasks\Repositories;


//use Modules\Tasks\Repositories\Contracts\TaskProgressRepositoryInterface;

use App\Tik\Repositories\AbstractRepository;
use Modules\Tasks\Entities\UserDayTaskProgress;

class TaskProgressRepository extends AbstractRepository//implements TaskProgressRepositoryInterface
{
    public function __construct(UserDayTaskProgress $model)
    {
        parent::__construct($model);
    }
    /*public function findUserTaskProgress($userId, $taskId)
    {
        return UserDayTaskProgress::where('user_id', $userId)
                                  ->where('task_id', $taskId)
                                  ->first();
    }*/
    public function save($taskProgress)
    {
        $taskProgress->save();
    }
    public function getByUserIdAndTaskId($userId, $taskId)
    {
        return $this->model->where('user_id', $userId)->where('task_id', $taskId)->first();
    }

}
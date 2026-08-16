<?php

namespace Modules\Tasks\Repositories;

use App\Tik\Repositories\AbstractRepository;
use Modules\Tasks\Entities\TaskReward;
use Modules\Tasks\Entities\UserTaskReward;

class UserTaskRewardRepository extends AbstractRepository//implements TaskRewardRepositoryInterface
{
    public function __construct(UserTaskReward $model)
    {
        parent::__construct($model);
    }
}
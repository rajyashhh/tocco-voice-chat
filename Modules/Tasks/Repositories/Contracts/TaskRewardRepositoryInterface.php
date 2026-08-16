<?php

namespace Modules\Tasks\Repositories\Contracts;

interface TaskRewardRepositoryInterface
{
    public function findByDayId($dayId);
    public function userHasReward($userId, $taskRewardId);
    public function createUserReward(array $data);
}
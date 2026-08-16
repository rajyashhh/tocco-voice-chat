<?php

namespace Modules\Tasks\Repositories\Contracts;

interface DailyTaskRepositoryInterface
{
    public function findById($taskId);
    public function findByDayId($dayId);
}

<?php

namespace Modules\Tasks\Repositories\Contracts;

interface TaskProgressRepositoryInterface
{
    public function findUserTaskProgress($userId, $taskId);
    public function save($taskProgress);
}
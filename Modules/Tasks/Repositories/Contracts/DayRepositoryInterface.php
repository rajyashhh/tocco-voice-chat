<?php

namespace Modules\Tasks\Repositories\Contracts;

interface DayRepositoryInterface
{
    public function findById($dayId);
    public function save($day);
}
<?php

namespace Modules\Tasks\Repositories;

use App\Tik\Repositories\AbstractRepository;
use Modules\Tasks\Entities\UserDayProgress;

class UserDayProgressRepository extends AbstractRepository
{
    public function __construct(UserDayProgress $model)
    {
        parent::__construct($model);
    }
    public function save($userDayProgress)
    {
        $userDayProgress->save();
    }
}
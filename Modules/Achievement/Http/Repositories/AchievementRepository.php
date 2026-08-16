<?php

namespace Modules\Achievement\Http\Repositories;

use App\Tik\Repositories\AbstractRepository;
use Modules\Achievement\Entities\Achievement;


class AchievementRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new Achievement());
    }

    public function all()
    {
        return $this->model->get();
    }
}

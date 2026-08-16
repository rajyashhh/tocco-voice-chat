<?php

namespace Modules\Tasks\Repositories;


//use Modules\Tasks\Repositories\Contracts\DayRepositoryInterface;

use App\Tik\Repositories\AbstractRepository;
use Modules\Tasks\Entities\Day;

class DayRepository extends AbstractRepository//implements DayRepositoryInterface
{
    public function __construct(Day $model)
    {
        parent::__construct($model);
    }
    /*public function findById($dayId)
    {
        return Day::find($dayId);
    }*/
    public function save($day)
    {
        $day->save();
    }
    public function getNextDay($dayId)
    {
        $currentDay = $this->findOrFail($dayId);
        if (!$currentDay) {
            return null;
        }
        return $this->model
            ->where('day_number', '>', $currentDay->day_number)
            ->orderBy('day_number', 'asc')
            ->first();
    }

}



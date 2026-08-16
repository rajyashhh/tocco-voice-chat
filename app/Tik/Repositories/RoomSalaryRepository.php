<?php

namespace App\Tik\Repositories;

use App\Models\RoomSalary;



class RoomSalaryRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new RoomSalary());
    }


    public function searchRoomSalary($RoomId)
    {
        return $this->model->query()->where('room_id', $RoomId)->where('is_paid', 0)->orderByDesc('id')->first();
    }


    public function incrementCutAmount($RoomId, $amount)
    {
        $roomSalary = $this->searchRoomSalary($RoomId);
        if( $roomSalary) $roomSalary->increment('cut_amount', $amount);
        return true;
    }
}

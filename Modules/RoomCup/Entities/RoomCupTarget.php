<?php

namespace Modules\RoomCup\Entities;

use Illuminate\Database\Eloquent\Model;

class RoomCupTarget extends Model
{
    protected $table = 'room_cup_targets';

    protected $fillable = [
        'total',
        'number_of_visitors',
        'number_of_admins',
        'owner_profit',
        'admin_profit',
        'total_profit',
        'owner_percentage',
        'admin_percentage'
    ];
}

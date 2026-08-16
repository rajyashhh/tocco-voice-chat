<?php

namespace Modules\CP\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpRoomHistory extends Model
{
    use HasFactory, TimestampsWithTimezone;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];
}

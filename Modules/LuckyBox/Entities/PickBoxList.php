<?php

namespace Modules\LuckyBox\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickBoxList extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];
}

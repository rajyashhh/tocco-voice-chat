<?php

namespace Modules\SpecialId\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialIdFram extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];
}

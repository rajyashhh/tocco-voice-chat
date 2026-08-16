<?php

namespace Modules\Public\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCounter extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];
}

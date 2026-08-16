<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function getAgeAttribute()
    {
        return Carbon::parse($this->birthday)->age;
    }
}

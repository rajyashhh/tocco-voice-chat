<?php

namespace Modules\SalaryTransaction\Entities;

use App\Models\Country;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeCountry extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}

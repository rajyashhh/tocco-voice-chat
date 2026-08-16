<?php

namespace Modules\SalaryTransaction\Entities;

use App\Models\ShippingAgency as ModelsAgency;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeAgency extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function agency()
    {
        return $this->belongsTo(ModelsAgency::class);
    }
}

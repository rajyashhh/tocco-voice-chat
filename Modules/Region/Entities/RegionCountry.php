<?php

namespace Modules\Region\Entities;

use App\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionCountry extends Model
{
    protected $fillable = ['region_id', 'country_id'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
    public function country() {
        return $this->belongsTo(Country::class);
    }
}

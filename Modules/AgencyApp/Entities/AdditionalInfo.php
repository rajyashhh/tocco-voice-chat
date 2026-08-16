<?php

namespace Modules\AgencyApp\Entities;

use App\Models\Agency;
use App\Models\Country;
use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class AdditionalInfo extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

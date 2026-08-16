<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class AgencyMangLink extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'agency_manger_app_dash';

    protected $guarded = [];
}

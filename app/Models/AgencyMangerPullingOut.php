<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyMangerPullingOut extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $table = 'agency_manger_pulling_out';

    protected $guarded = [];
}

<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencymAngerLink extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $table = 'agency_manger_app_dash';

    protected $guarded = [];
}

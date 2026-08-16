<?php

namespace App\Models\DashboardModels;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sheet extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];
}

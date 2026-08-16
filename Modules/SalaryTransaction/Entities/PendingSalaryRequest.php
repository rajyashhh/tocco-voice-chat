<?php

namespace Modules\SalaryTransaction\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingSalaryRequest extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];
}

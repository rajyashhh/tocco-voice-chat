<?php

namespace Modules\SalaryTransaction\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminCheck extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function request()
    {
        return $this->belongsTo(SalaryRequest::class, 'request_id');
    }
}

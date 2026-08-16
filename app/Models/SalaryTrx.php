<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class SalaryTrx extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'salary_trxs';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'oid');
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'oid');
    }
}

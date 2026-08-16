<?php

namespace Modules\Reals\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class ReportReals extends Model
{
    use TimestampsWithTimezone;

    protected $fillable = [];

    protected $guarded = [];

    public function reel()
    {
        return $this->hasOne(Real::class, 'id', 'real_id');
    }

    // protected $table = ['Report_reals'];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'Reporter_id');
    }

    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'Reported_id');
    }
}

<?php

namespace Modules\Moment\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class ReportMoment extends Model
{
    use TimestampsWithTimezone;

    protected $fillable = [];

    protected $guarded = [];

    // protected $table = ['Report_reals'];

    public function moment()
    {
        return $this->belongsTo(Moment::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'Reporter_id');
    }

    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'Reported_id');
    }
}

<?php

namespace Modules\AgencyApp\Entities;

use App\Facades\UserHandling;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class LeaveAgencyRequest extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Agent::class, 'admin_id');
    }

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            if ($model->status === 1) {
                $user = User::find($model->user_id);
                UserHandling::kickUserFromAgency($user, 1);
            }
        });
    }
}

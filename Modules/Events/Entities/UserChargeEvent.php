<?php

namespace Modules\Events\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class UserChargeEvent extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    public function winner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function event()
    {
        return $this->belongsTo(ChargeTargetEvent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rewardCharge()
    {
        return $this->belongsTo(RewardTarget::class, 'charge_event_id', 'charge_event_id');
    }

    public function rewardCharges()
    {
        return $this->hasMany(RewardTarget::class, 'charge_event_id', 'charge_event_id');
    }

    public function ChargeEvents()
    {
        return $this->belongsTo(ChargeTargetEvent::class, 'charge_event_id', 'id');
    }
}

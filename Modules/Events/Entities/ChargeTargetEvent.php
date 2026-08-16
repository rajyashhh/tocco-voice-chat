<?php

namespace Modules\Events\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeTargetEvent extends Model
{
    use HasFactory, TimestampsWithTimezone;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    protected $table = 'charge_events';

    public function rewards()
    {
        return $this->hasMany(RewardTarget::class, 'charge_event_id')->with('ware', 'vip');
    }

    public function ware()
    {
        return $this->rewards->ware();
    }

    public function users()
    {
        return $this->hasMany(UserChargeEvent::class, 'charge_event_id');
    }

    public function getWareAttribute()
    {
        $wares = $this->rewards->map(function ($reward) {
            return $reward->ware;
        })->filter();

        return $wares;
    }
}

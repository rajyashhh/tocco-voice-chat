<?php

namespace Modules\TribeReward\Entities;

use Illuminate\Database\Eloquent\Model;

class AgencyReward extends Model
{
    protected $fillable = ['agency_id', 'type', 'target_type', 'target_id', 'quantity', 'available_quantity', 'expire_days', 'expire_at'];
}

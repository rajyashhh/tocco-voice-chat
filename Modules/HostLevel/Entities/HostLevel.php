<?php

namespace Modules\HostLevel\Entities;

use Illuminate\Database\Eloquent\Model;

class HostLevel extends Model
{
    protected $guarded = [];

    public function rewards()
    {
        return $this->hasMany(HostLevelReward::class, 'host_level_id', 'id');
    }
}

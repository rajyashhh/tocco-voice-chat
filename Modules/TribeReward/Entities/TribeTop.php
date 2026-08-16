<?php

namespace Modules\TribeReward\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TribeTop extends Model
{
    protected $fillable = ['min', 'max'];

    public function tribeRewards(): HasMany
    {
        return $this->hasMany(TribeReward::class);
    }
}

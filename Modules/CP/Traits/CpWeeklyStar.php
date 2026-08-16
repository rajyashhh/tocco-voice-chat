<?php

namespace Modules\CP\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CpWeeklyStar
{
    public function scopeWeeklyCP(Builder $query)
    {
        return $query->where('type', 'weekly_cp');
    }

    public function isCp() : bool
    {
        return $this->type === 'weekly_cp';
    }
}

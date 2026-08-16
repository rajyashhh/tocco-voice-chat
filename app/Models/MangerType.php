<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Badge\Entities\Badge;

class MangerType extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'manger_type_badge')
            ->withPivot('expire')
            ->withTimestamps();
    }
}

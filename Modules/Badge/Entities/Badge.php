<?php

namespace Modules\Badge\Entities;

use App\Models\MangerType;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $guarded = [];

    public function images()
    {
        return $this->hasMany(BadgeImage::class);
    }

    public function mangerTypes()
    {
        return $this->belongsToMany(MangerType::class, 'manger_type_badge')
            ->withPivot('expire')
            ->withTimestamps();
    }
}

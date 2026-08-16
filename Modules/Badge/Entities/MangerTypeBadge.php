<?php

namespace Modules\Badge\Entities;

use App\Models\MangerType;
use Illuminate\Database\Eloquent\Model;

class MangerTypeBadge extends Model
{
    protected $table = 'manger_type_badge';

    protected $guarded = [];

    public function badge()
    {
        return $this->belongsTo(Badge::class, 'badge_id');
    }

    public function mangerType()
    {
        return $this->belongsTo(MangerType::class, 'manger_type_id');
    }
}
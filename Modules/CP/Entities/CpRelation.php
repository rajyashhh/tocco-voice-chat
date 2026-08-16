<?php

namespace Modules\CP\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class CpRelation extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    public function levels()
    {
        return $this->hasMany(CpLevel::class, 'cp_relation_id');
    }
}

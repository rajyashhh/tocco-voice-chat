<?php

namespace Modules\CP\Entities;

use Modules\Vip\Entities\Vip;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class CpLevel extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    /*     public function gifts(){
        return $this->hasManyThrough(
            CpLevelGift::class, // Final model (Gifts)
            Vip::class,         // Intermediate model (Vips)
            'id',               // Local key on Vips (relates to cp_level_gifts.vip_id)
            'vip_id',           // Foreign key on cp_level_gifts
            'id',               // Local key on cp_levels
            'id'                // Local key on Vips
        );
    } */

    public function gifts()
    {
        return $this->hasMany(CpLevelGift::class, 'vip_id', 'id');
    }
}

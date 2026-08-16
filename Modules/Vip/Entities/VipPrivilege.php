<?php

namespace Modules\Vip\Entities;

use App\Models\Ware;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class VipPrivilege extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'vip_privileges';

    public function getItem($vip)
    {
        $i = Ware::query()->where('get_type', 1)->where('type', $this->type)->where('level', $vip)->first();

        return $i;
    }

    public function vip()
    {
        return $this->belongsToMany(self::class, 'vip_prev', 'o_vip_privilege_id', 'o_vip_id', 'id', 'id');
    }
}

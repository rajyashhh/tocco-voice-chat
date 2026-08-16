<?php

namespace App\Repositories;

use App\Models\Ware;

class WareRepository
{

    public function getOVip($levels = [], $types = [])
    {
        return Ware::query()->where ('get_type',1)->whereIn('type',$types)->whereIn('level',$levels)/*->where('enable', true)*/->get();
    }

    public function getOVipNew($levels = [], $types = [])
    {
        return Ware::query()->where ('get_type',1)->whereIn('type',$types)->whereIn('level',$levels)->where('enable', true)->get();
    }
}

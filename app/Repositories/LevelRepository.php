<?php

namespace App\Repositories;

use Modules\Vip\Entities\Vip;
use Illuminate\Database\Eloquent\Model;

class LevelRepository
{
    public function getLevelsByTypeAndPattern($type, $patternNum)
    {
        return Vip::query()
            ->where(function ($query) use ($patternNum) {
                $query->whereRaw("level % $patternNum = 1")
                      ->orWhere('level', 1);
            })
            ->where('type', $type)
            ->orderBy('level')
            ->get();
    }
}
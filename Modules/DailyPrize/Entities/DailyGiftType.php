<?php

namespace Modules\DailyPrize\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyGiftType extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        self::deleted(function ($model) {
            DailyGift::where('type', $model->type)->delete();
        });
    }
}

<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Vip\Entities\OVip;

class CoinTargetGift extends Model
{
    use HasFactory, TimestampsWithTimezone;

    public $table = 'coins_target_gifts';

    protected $guarded = [];

    public function vip()
    {
        return $this->belongsTo(OVip::class, 'item_id');
    }

    public function ware()
    {
        return $this->belongsTo(Ware::class, 'item_id');
    }

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            if ($model->coins) {
                unset($model->coins);
            }
            if ($model->achievement) {
                unset($model->achievement);
            }
        });
    }
}

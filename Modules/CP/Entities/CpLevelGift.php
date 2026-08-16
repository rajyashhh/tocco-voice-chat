<?php

namespace Modules\CP\Entities;

use App\Models\Ware;
use Modules\Vip\Entities\OVip;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Modules\Achievement\Entities\CustomAchievement;

class CpLevelGift extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];
    protected $appends = ['type_ware'];

    public function cp_level()
    {
        return $this->belongsTo(CpLevel::class, 'vip_id');
    }

    public function vip()
    {
        return $this->belongsTo(OVip::class, 'item_id');
    }

    public function ware()
    {
        return $this->belongsTo(Ware::class, 'item_id');
    }

    public function getTypeWareAttribute()
    {
        return $this->ware?->type ?? null;
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
            if ($model->type_ware) {
                unset($model->type_ware);
            }
            if ($model->ware_item_id) {
                unset($model->ware_item_id);
            }
            if ($model->vip_item_id) {
                unset($model->vip_item_id);
            }
            unset($model->attributes['type_ware']);
            unset($model->attributes['ware_item_id']);
            unset($model->attributes['vip_item_id']);
        });
    }

    public function ware_item()
    {
        return $this->belongsTo(Ware::class, 'ware_item_id');
    }

    public function vip_item()
    {
        return $this->belongsTo(OVip::class, 'vip_item_id');
    }

    public function customAchievement()
    {
        return $this->hasOne(CustomAchievement::class, 'id', 'item_id');
    }
}

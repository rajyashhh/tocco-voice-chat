<?php

namespace Modules\Vip\Entities;

use App\Models\Ware;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Modules\Vip\Entities\VipPrivilege;

class OVip extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'o_vips';

    protected $fillable = [
        'name',
        'level',
        'price',
        'exp',
        'expire',
        'img',
    ];

    protected $hidden = ['privileges'];

    public function privilegs()
    {
        return $this->belongsToMany(VipPrivilege::class, 'vip_prev', 'o_vip_id', 'o_vip_privilege_id', 'id', 'id');
    }


    public function waresOvip()
    {
        return $this->hasMany(Ware::class, 'level', 'level')
            ->where('get_type', 1)
            ->where('enable', 1)
            ->where('is_active_for_vip', 1);
    }

    public function wareIcon()
    {
        return $this->hasOne(Ware::class, 'level', 'level')
            ->where('type', 12)
            ->where('get_type', 1);
    }

    public function wares()
    {
        return $this->hasMany(Ware::class, 'level', 'level');
    }

    public function getTranslation($key)
    {
        switch ($key) {
            case 1:
                return trans('Gemstone');
            case 3:
                return trans('Card Scroll');
            case 4:
                return trans('Avatar Frame');
            case 5:
                return trans('Bubble Frame');
            case 6:
                return trans('Entering Special Effects');
            case 7:
                return trans('Microphone Aperture');
            case 8:
                return trans('Badge');
            case 9:
                return trans('NoKick');
            case 10:
                return trans('Icon');
            case 11:
                return trans('intro animation');
            case 12:
                return trans('wapel');
            case 13:
                return trans('hide country and last login');
            case 14:
                return trans('vip gifts');
            case 15:
                return trans('no pan');
            case 16:
                return trans('hidden room');
            case 17:
                return trans('anonymous man');
            case 18:
                return trans('colored name');
            case 19:
                return trans('profile visitors hide in');
            case 20:
                return trans('hide last active');
            case 28:
                return trans('profile frame');
            default:
                return trans('Unknown'); // Fallback for unknown keys
        }
    }

    protected static function boot()
    {
        parent::boot();

        self::deleting(function ($oVip) {
            $oVip->privilegs()->detach();

            $oVip->wares()->forceDelete();
        });
    }

    public function wareIcon10()
    {
        return $this->hasOne(Ware::class, 'level', 'level')
            ->where('type', 10)
            ->where('get_type', 1);
    }
}

<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Modules\Region\Entities\AreaManager;
use Modules\Region\Entities\Region;
use Modules\Region\Entities\RegionCountry;
use Modules\SalaryTransaction\Entities\ChargeCountry;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Country\Entities\SuperAdmin;

class Country extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function chargeCountry()
    {
        return $this->hasMany(ChargeCountry::class, 'country_id');
    }

    public function transName()
    {
        return app()->getLocale() == 'ar' ? $this->name : $this->e_name;
    }

    public function supporters()
    {
        return $this->hasManyThrough(
            GiftLog::class,
            User::class,
            'country_id',
            'sender_id',
            'id',
            'id'
        )
        ->selectRaw('sender_id, users.country_id, SUM(giftPrice) as total_sent')
        ->groupBy('sender_id', 'users.country_id')
        ->with('sender')
        ->orderByDesc('total_sent');
    }

    public function rooms(): HasManyThrough
    {
        return $this->hasManyThrough(Room::class, User::class, 'country_id', 'uid');
    }

    public function superAdmin()
    {
        return $this->hasMany(SuperAdmin::class,'country_id');
    }
  

    public function scopeNonDefaultOrUnassigned($query)
    {
        return $query->where(function($q) {
            $q->whereNull('area_manager_id')
              ->orWhereHas('areaManager', function($q2) {
                 $q2->where('default', 0);
              });
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($country) {
            \Cache::forget('filter_countries_list');
        });

        static::deleted(function ($country) {
            \Cache::forget('filter_countries_list');
        });
    }

    public function regions()
    {
        return $this->belongsToMany(
            Region::class,
            'region_countries', 
            'country_id',
            'region_id'
        );
    }
    
    
    public function areaManagers()
    {
        return $this->hasManyThrough(
            AreaManager::class, 
            Region::class, 
            'id',            
            'id',            
            'id',            
            'manager_id' 
        );
    }
    public function getRegionManagerIdAttribute()
    {
        $region = $this->regions->first();
        return $region?->areaManager?->id ?? null;
    }
}

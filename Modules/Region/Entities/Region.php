<?php
namespace Modules\Region\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = ['name', 'manager_id'];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(AreaManager::class, 'manager_id');
    }

    public function regionCountries(): HasMany
    {
        return $this->hasMany(RegionCountry::class, 'region_id');
    }

    public function countries()
    {
        return $this->hasManyThrough(
            \App\Models\Country::class, 
            RegionCountry::class,       
            'region_id',               
            'id',                       
            'id',                       
            'country_id'                
        );
    }
}

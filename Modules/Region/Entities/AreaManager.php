<?php

namespace Modules\Region\Entities;

use App\Models\AdminUser;
use App\Traits\CreatedByTrait;
use Exception;
use App\Models\User;
use App\Models\Agency;
use App\Models\Country;
use App\Models\AreaPolygon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Country\Entities\SuperAdmin;

class AreaManager extends Authenticatable
{
    use HasFactory, SoftDeletes, CreatedByTrait;

    protected $table = 'admin_users';

    protected $attributes = [
        'type' => 'region',
    ];

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'username',
        'name',
        'password',
        'type',
        'default',
        'app_id',
        'country_id',
        'parent_id',
        'avatar',
        'phone',
        'email',
        'created_by',
    ];


    protected static function booted(): void
    {
        self::addGlobalScope('AreaManagerOnly', function (Builder $builder) {
            $builder->where('type', 'region');
        });

        parent::boot();

        static::saving(function ($model) {
            if (isset($model->area_name)) {
                unset($model->area_name);
            }
        });

        static::deleting(function ($manager) {

            if ($manager->default == 1) {
             
                    throw new Exception(__('can not delete default area admin'));
                
            }
            $defaultManager = self::where('default', 1)->first();

            if (!$defaultManager) {
                throw new \Exception('❌ لا يمكن الحذف — لا يوجد مدير افتراضي محدد.');
            }
            if ($manager->id !== $defaultManager->id) {
                \App\Models\Country::where('area_manager_id', $manager->id)
                    ->update(['area_manager_id' => $defaultManager->id]);
            }
        });
    }

    public function subAreaManager()
    {
        return $this->hasMany(SubAreaManager::class, 'parent_id', 'id');
    }

    public function appUser()
    {
        return $this->belongsTo(User::class, 'app_id');
    }

    public function polygon()
    {
        return $this->hasOne(AreaPolygon::class, 'area_manager_id');
    }

    public function agencies()
    {
        return $this->hasManyThrough(Agency::class, Country::class, 'area_manager_id', 'country_id', 'id', 'id');
    }

    // public function countries()
    // {
    //     return $this->hasMany(Country::class, 'area_manager_id');
    // }

    public function regions()
    {
        return $this->hasMany(Region::class, 'manager_id');
    }

    public function regionArea()
    {
        return $this->hasOne(Region::class, 'manager_id');
    }


    public function countries()
    {
        return $this->belongsToMany(
            Country::class,
            'region_countries',
            'region_id',
            'country_id'
        )->join('regions', 'region_countries.region_id', '=', 'regions.id')
            ->where('regions.manager_id', $this->id)
            ->select('countries.*');
    }

    public function countriesQuery()
    {
        return Country::whereHas('regions', function ($q) {
            $q->where('manager_id', $this->id);
        });
    }
    public function flag()
    {
        $managerId = $this->parent_id ?? $this->id;
        $region = Region::where('manager_id', $managerId)->with('countries')->first();
        $countries = $region->countries;

        $html = '<div class="user-type-badges">';
        foreach ($countries as $country) {
            $url = getImagePath($country->flag);

            if ($url) {
                $html .= handleShowImageWithTypes($country->id, $url, 30, 30, 4);
                // '<img src="' . e( $url) . '" alt="' . e($country->name) . '" style="width: 100px; height: 100px; object-fit: contain; border-radius: 4px; margin-right: 4px;">';

            }
        }

        $html .= '</div>';


        return $html;
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'manager_id', 'id');
    }
    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }
}

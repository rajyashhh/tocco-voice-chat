<?php

namespace Modules\Region\Entities;


use App\Models\AdminUser;
use App\Models\User;
use App\Models\Agency;
use App\Models\Country;
use App\Traits\CreatedByTrait;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubAreaManager extends Model
{
    use TimestampsWithTimezone, SoftDeletes, CreatedByTrait;

    protected $table = 'admin_users';

    protected $attributes = [
        'type' => 'sub_region',
    ];

    protected $dates = ['deleted_at'];

    public function appUser()
    {
        return $this->belongsTo(User::class, 'app_id');
    }

    public function agencies()
    {
        return $this->hasMany(Agency::class, 'country_id', 'country_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function countries(): HasMany
    {
        return $this->hasMany(Country::class, 'area_manager_id', 'parent_id');
    }

    public function countriesQuery()
    {
        $managerId = $this->parent_id ?: $this->id;

        return Country::whereHas('regions', function ($q) use ($managerId) {
            $q->where('manager_id', $managerId);
        });
    }

    protected static function booted(): void
    {

        self::addGlobalScope('subAreaManagerOnly', function (Builder $builder) {
            $builder->where('type', 'sub_region');
        });

        self::deleting(function (SubAreaManager $subAreaManager) {});
    }

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {});

        self::updating(function ($model) {});
    }

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }


    public function flag()
    {

        $region = Region::where('manager_id', $this->id)->with('countries')->first();
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
}

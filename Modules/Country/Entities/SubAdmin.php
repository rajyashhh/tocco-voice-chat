<?php

namespace Modules\Country\Entities;

use App\Models\Agency;
use App\Models\Country;
use App\Models\User;
use App\Traits\CreatedByTrait;
use DB;
use Exception;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubAdmin extends Model
{
    use TimestampsWithTimezone, SoftDeletes,CreatedByTrait;

    protected $table = 'admin_users';

    protected $attributes = [
        'type' => 'sub_country',
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

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'parent_id');
    }

    protected static function booted(): void
    {

        self::addGlobalScope('subAdminOnly', function (Builder $builder) {
            $builder->where('type', 'sub_country');
        });

        self::deleting(function (SuperAdmin $superAdmin) {});
    }

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {});

        self::updating(function ($model) {});
    }
    public function creator()
    {
        return $this->belongsTo(SuperAdmin::class, 'created_by');
    }
}

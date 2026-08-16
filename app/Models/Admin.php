<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Modules\Region\Entities\Region;

class Admin extends Administrator
{
    protected $table = 'admin_users';

    protected $appends = ['agency_id'];

    protected $fillable = ['username', 'password', 'name','app_id', 'avatar', 'is_preview','type'];

    protected $guarded = [];

    public function agency()
    {
        return $this->hasOne(Agency::class, 'owner_id');
    }
    public function user()
    {
         return $this->belongsTo(User::class, 'app_id');
    }

    public function getAgencyIdAttribute()
    {
        // If agency is already eager-loaded, use it directly (no extra query)
        if ($this->relationLoaded('agency')) {
            return $this->agency?->id;
        }

        return \Illuminate\Support\Facades\Cache::remember(
            "admin_agency_id_{$this->id}",
            300,
            fn() => @$this->agency?->id
        );
    }

    public function getImgAttribute()
    {
        return $this->attributes['avatar'];
    }

    public function getImageAttribute()
    {

        return getImagePath($this->attributes['avatar']);
    }

    public function agencies()
    {
        return $this->hasMany(Agency::class, 'agency_manger_id');
    }

    public function per()
    {
        return $this->hasMany(Agency::class, 'agency_manger_id');
    }

    public function countriesQuery()
    {
        return Country::whereHas('regions', function ($q) {
            $q->where('manager_id', $this->id);
        });
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class, 'manager_id');
    }

    protected static function boot()
    {
        parent::boot();

        // Listen for the 'deleting' event of the admin model
        self::deleting(function ($admin) {
            // The owner account (id=1) is sacrosanct — never deletable by anyone,
            // including other super-admins. Enforced at the model layer so EVERY
            // delete path (controller destroy, the DeleteUser row action, modules)
            // is covered regardless of route or permission.
            if ((int) $admin->id === 1) {
                abort(403, __('The owner account cannot be deleted.'));
            }

            $appId = $admin->app_id;
            $agencies = Agency::where('agency_manger_id', $appId)->get();
            $config = Config::where('name', 'system_default_manger')->first();

            $user = User::where('uuid', $config?->value)->first();
            $agenciesId = [];
            foreach ($agencies as $agency) {
                $agenciesId[] = $agency->id;
                $agency->agency_manger_id = $user?->id;
                $agency->save();
            }
            $agencyIds = implode(',', $agenciesId);
            AgencyMangerDeleted::create([
                'admin_id' => Auth::id(),
                'agency_manger_id' => $appId,
                'agencies_id' => $agencyIds,
            ]);
        });
    }
}

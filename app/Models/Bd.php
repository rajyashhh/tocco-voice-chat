<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Modules\Country\Entities\SuperAdmin;

class Bd extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'admin_users';

    protected $guarded = [];

    protected $attributes = [
        'type' => 'bd',
    ];

    public function appUser()
    {
        return $this->belongsTo(User::class, 'app_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'parent_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function agencies()
    {
        return $this->hasMany(Agency::class, 'bd_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(Charge::class, 'charger_id', 'id')
            ->where('user_charger_type', 'bd');
    }

    public function getAgenciesCountAttribute()
    {
        return $this->agencies()->count();
    }


    public function getTotalSalaryAttribute()
    {
        if ($this->relationLoaded('bdSalaries')) {
            return $this->bdSalaries->sum('salary');
        }
        return $this->bdSalaries()->sum('salary');
    }
    public function getTotalCutAttribute()
    {
        if ($this->relationLoaded('bdSalaries')) {
            return $this->bdSalaries->sum('cut_amount');
        }
        return $this->bdSalaries()->sum('cut_amount');
    }

    public function getNetSallaryAttribute()
    {
        if ($this->relationLoaded('bdSalaries')) {
            return floor($this->bdSalaries->sum(fn($s) => $s->salary - $s->cut_amount));
        }

        return floor($this->bdSalaries()->sum(DB::raw('salary - cut_amount')));
    }

    protected static function booted(): void
    {
        self::addGlobalScope('bdOnly', function (Builder $builder) {
            $builder->where('type', 'bd');
        });

        self::deleting(function (Bd $bd) {
            $defaultBd = self::where('default', 1)
                ->where('parent_id', $bd->parent_id)
                ->where('id', '!=', $bd->id)
                ->first();

                if (!$defaultBd) {
                    $superAdmin = $bd->parent;
                    if (! $superAdmin){
                        $defaultSuperAdmin = SuperAdmin::where('default', 1)->where('country_id', 0)->first();
                        $defaultBd = self::where('parent_id', $defaultSuperAdmin->id)->where('default', 1)->where('country_id', 0)->first();
                    } else {
                        $country = Country::find($superAdmin->country_id);
                        if (!$country) {
                            throw new Exception('Country not found for super admin.');
                        }

                        $countryName = $country->e_name;

                        $existingDefaultBd = self::where('default', 1)->where('country_id', $superAdmin->country_id)->first();
                        if ($existingDefaultBd) {
                            $defaultBd = $existingDefaultBd;
                        } else {
                            $newBdId = DB::table('admin_users')->insertGetId([
                                'parent_id'  => $superAdmin->id,
                                'username'   => 'bd' . $countryName . 'default',
                                'name'       => 'bd' . $countryName . 'default',
                                'password'   => Hash::make('bd' . $countryName . 'default'),
                                'default'    => 1,
                                'country_id' => $superAdmin->country_id,
                                'type'       => 'bd',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $defaultBd = self::find($newBdId);
                        }
                    }
                }

                Agency::where('bd_id', $bd->id)
                    ->update(['bd_id' => $defaultBd->id]);

            $userApp = User::find($bd->app_id);
            if ($userApp) {
                $userApp->is_bd = 0;
                $userApp->type_user = 0;
                $userApp->agency_id = 0;
                $userApp->save();
            }
        });
    }

    protected static function boot()
    {
        parent::boot();

        self::creating(function (Bd $model) {
            $model->type = 'bd';

            if ($model->default) {
                static::where('parent_id', $model->parent_id)
                    ->update(['default' => 0]);

                Agency::where(function ($query) {
                    $query->whereNull('bd_id')
                        ->orWhere('bd_id', 0);
                })->update(['bd_id' => $model->id]);
            }
        });

        self::updating(function (Bd $model) {
            if ($model->default) {
                static::where('parent_id', $model->parent_id)
                    ->where('id', '!=', $model->id)
                    ->update(['default' => 0]);

                Agency::where(function ($query) {
                    $query->whereNull('bd_id')
                        ->orWhere('bd_id', 0);
                })->update(['bd_id' => $model->id]);
            }

            if ($model->isDirty('app_id')) {
                $oldAppId = $model->getOriginal('app_id');
                if ($oldAppId) {
                    $userApp = User::find($oldAppId);
                    if ($userApp) {
                        $userApp->is_bd = 0;
                        $userApp->type_user = 0;
                        $userApp->agency_id = 0;
                        $userApp->save();
                    }
                }
            }
        });
    }



    public function incrementCutAmountInBdSallary(int $amount)
    {
        $lastBdSalary = $this->bdSalaries()->latest()->first();

        if ($lastBdSalary) {
            $newAmount = max(0, $lastBdSalary->cut_amount + $amount);
            $lastBdSalary->update(['cut_amount' => $newAmount]);

            return true;
        }

        return false;
    }

    public function bdSalaries()
    {
        return $this->hasMany(BdSalary::class,  'bd_id', 'id');
    }
    public function getBdSalaryAttribute()
    {
        if ($this->relationLoaded('bdSalaries')) {
            return floor($this->bdSalaries->sum(fn($s) => $s->salary - $s->cut_amount));
        }
        return floor($this->bdSalaries()->sum(DB::raw('salary - cut_amount')));
    }

    public function salaries()
    {
        return $this->hasMany(BdSalary::class, 'bd_id', 'id');
    }
    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }
}

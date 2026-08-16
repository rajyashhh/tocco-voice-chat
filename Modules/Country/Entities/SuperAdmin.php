<?php

namespace Modules\Country\Entities;

use App\Models\Admin;
use App\Models\AdminUser;
use App\Models\Agency;
use App\Models\Bd;
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
use Modules\Region\Entities\AreaManager;

class SuperAdmin extends Model
{
    use TimestampsWithTimezone, SoftDeletes ,CreatedByTrait;

    protected $table = 'admin_users';
    protected $guarded = [];

    protected $dates = ['deleted_at'];

    protected $attributes = [
        'type' => 'country',
    ];

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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AreaManager::class, 'parent_id');
    }
//
//    public function transactions()
//    {
//        return $this->hasMany(Charge::class, 'charger_id', 'id')
//            ->where('user_charger_type', 'bd');
//    }

    public function subSuperAdmins()
    {
        return $this->hasMany(SubAdmin::class, 'parent_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    //
    //    public function transactions()
    //    {
    //        return $this->hasMany(Charge::class, 'charger_id', 'id')
    //            ->where('user_charger_type', 'bd');
    //    }

    //    public function getAgenciesCountAttribute()
    //    {
    //        return $this->agencies()->count();
    //    }


    //    public function getTotalSalaryAttribute()
    //    {
    //        return $this->bdSalaries()->sum('salary');
    //    }
    //    public function getTotalCutAttribute()
    //    {
    //        return $this->bdSalaries()->sum('cut_amount');
    //    }
    //
    //    public function getNetSallaryAttribute()
    //    {
    //        $userSallary = $this->bdSalaries()
    //        ->sum(DB::raw('salary - cut_amount'));
    //
    //       return floor($userSallary);
    //    }

    protected static function booted(): void
    {

        self::addGlobalScope('superAdminOnly', function (Builder $builder) {
            $builder->where('type', 'country');
        });

        self::deleting(function (SuperAdmin $superAdmin) {

            if ($superAdmin->default == 1) {
                throw new Exception(__('can not delete default super admin'));
            }
            $defaultSuperAdmin = self::where('default', 1)
                ->where('id', '!=', $superAdmin->id)
                ->first();

            if ($defaultSuperAdmin) {
                Bd::where('parent_id', $superAdmin->id)
                    ->update(['parent_id' => $defaultSuperAdmin->id]);
            } else {

                throw new Exception(__('dashboard.no_default_country_manager_to_transfer'));
            }
            $userApp = User::find($superAdmin->app_id);
            if ($userApp) {
                $userApp->is_super_admin = 0;
                $userApp->type_user = 0;
                $userApp->agency_id = 0;
                $userApp->save();
            }
        });
    }

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->type = 'country';

            if ($model->default) {
                static::query()->update(['default' => 0]);
                Bd::where(function ($query) {
                    $query->whereNull('parent_id')
                        ->orWhere('parent_id', 0);
                })->update(['parent_id' => $model->id]);
            }
        });

        self::updating(function ($model) {
            if ($model->default) {
                static::where('id', '!=', $model->id)->update(['default' => 0]);
                Bd::where(function ($query) {
                    $query->whereNull('parent_id')
                        ->orWhere('parent_id', 0);
                })->update(['parent_id' => $model->id]);
            }

            if ($model->app_id) {
                $userApp = User::find($model->getOriginal('app_id'));
                if ($userApp) {
                    $userApp->is_super_admin = 0;
                    $userApp->type_user = 0;
                    $userApp->agency_id = 0;
                    $userApp->save();
                }
            }
        });
    }


    //    public function incrementCutAmountInBdSallary(int $amount)
    //    {
    //        $lastBdSalary = $this->bdSalaries()->latest()->first();
    //
    //        if ($lastBdSalary) {
    //            $newAmount = max(0, $lastBdSalary->cut_amount + $amount);
    //            $lastBdSalary->update(['cut_amount' => $newAmount]);
    //
    //            return true;
    //        }
    //
    //        return false;
    //    }
    //
    //    public function bdSalaries()
    //    {
    //        return $this->hasMany(BdSalary::class,  'bd_id', 'id');
    //    }
    //    public function getBdSalaryAttribute()
    //    {
    //        $userSallary = $this->bdSalaries()
    //            ->sum(DB::raw('salary - cut_amount'));
    //
    //        return floor($userSallary);
    //    }


    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }
}

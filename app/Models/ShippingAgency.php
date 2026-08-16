<?php

namespace App\Models;

use App\Models\Scopes\ShippingAgencyScope;
use App\Traits\CreatedByTrait;
use App\Traits\PaymentGetWayTrait;
use App\Traits\TimestampsWithTimezone;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AgencyApp\Traits\AgencyAdditionalInfoTraits;
use Modules\SalaryTransaction\Entities\ChargeAgency;
use Modules\SalaryTransaction\Entities\SalaryRequest;
use Modules\SalaryTransaction\Traits\SalaryTransferTrait;

class ShippingAgency extends Model
{
    use AgencyAdditionalInfoTraits, PaymentGetWayTrait, SalaryTransferTrait, SoftDeletes, TimestampsWithTimezone ,CreatedByTrait;

    protected $table = 'agencies';

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    public function chargeAgency()
    {
        return $this->hasOne(ChargeAgency::class, 'agency_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function charges()
    {
        return $this->hasMany(Charge::class, 'user_id','id')->where('charger_type','agency');
    }
    public function senderCharges()
    {
        return $this->hasMany(Charge::class, 'charger_id','id')->where('charger_type','agency');
    }

    public function receiveShippingAgencyCharges()
    {
        return $this->hasMany(Charge::class, 'user_id','id')->where('user_type','agency');
    }

    public function Countries()
    {
        return $this->belongsToMany(Country::class, 'agency_countries', 'agency_id', 'country_id')->withTimestamps();
    }

    public function salaryRequests()
    {
        return $this->hasMany(SalaryRequest::class, 'agency_id');
    }

    public function mempers()
    {
        return $this->hasMany(User::class, 'agency_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function admins()
    {
        return $this->hasMany(AgencyUserJob::class, 'agency_id')->where('type', 'requestManger');
    }

    public function scopeOfOwner($query, $owner_id)
    {
        return $query->where('owner_id', $owner_id);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'app_owner_id', 'id');
    }

    public function agencyManger()
    {
        return $this->belongsTo(User::class, 'agency_manger_id', 'id');
    }

    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    public function dashOwner()
    {
        return $this->belongsTo(Admin::class, 'owner_id', 'id');
    }

    public function getUrlAttribute($val)
    {
        if (! $val) {
            return '';
        }

        return $val;
    }

    public function getContentsAttribute($val)
    {
        if (! $val) {
            return '';
        }

        return $val;
    }

    public function target($month = null, $year = null)
    {
        if (! $month) {
            $month = date('m');
        }
        if (! $year) {
            $year = date('Y');
        }

        return $this->hasMany(AgencySallary::class)->where('month', $month)->where('year', $year)->first();
    }

    public function getTargetAttribute($month = null, $year = null)
    {
        if (! $month) {
            $month = date('m');
        }
        if (! $year) {
            $year = date('Y');
        }

        return $this->hasMany(AgencySallary::class)
            ->where('month', $month)
            ->where('year', $year)
            ->first();
    }

    public function getTargetsAttribute($month = null, $year = null)
    {
        if (! $month) {
            $month = date('m');
        }
        if (! $year) {
            $year = date('Y');
        }

        return $this->hasMany(UserTarget::class)
            ->where('add_month', $month)
            ->where('add_year', $year)
            ->first();
    }

    public function getSalaryAttribute()
    {
        $salary = AgencySallary::query()->where('agency_id', $this->id)->where('is_paid', 0)->sum(DB::raw('sallary - cut_amount'));

        return $salary;
    }

    public function setSalaryAttribute()
    {
        $salary = AgencySallary::query()->where('agency_id', $this->id)->where('is_paid', 0)->sum(DB::raw('sallary - cut_amount'));
        $this->attributes['salary'] = $salary;

        return $salary;
    }

    public function getSalaryAttributeAgencyManger()
    {
        $salaryAgency = AgencySallary::query()->where('agency_id', $this->id)->where('is_paid', 0)->sum(DB::raw('sallary - cut_amount'));
        $attributes['salaryAgency'] = $salaryAgency;

        return $attributes;
    }

    public function AgencyUsersTargets()
    {
        return $this->hasMany(UserTarget::class, 'agency_id');
    }

    public function UserTarget()
    {
        return $this->hasMany(UserTarget::class, 'agency_id');
    }

    public function agencySalary()
    {
        return $this->hasOne(AgencySallary::class, 'agency_id')->orderByDesc('id')
            ->where('month', now()->month)->where('year', now()->year);
    }

    public function getLastMonthSalaryAttribute()
    {
        return $this->hasOne(AgencySallary::class, 'agency_id')->orderByDesc('id')
            ->where('month', now()->subMonth()->month)->where('year', now()->subMonth()->year)->where('is_paid', 0)->sum(DB::raw('sallary - cut_amount'));
    }

    public function salaries()
    {
        return $this->hasMany(UserSallary::class, 'sallary');
    }

    public function agencySalaries()
    {
        return $this->hasMany(AgencySallary::class, 'agency_id')->orderByDesc('id');
    }

    public function getTotalSallaryAgency($month = null, $year = null)
    {
        if ($month === null) {
            $month = now()->month;
        }

        if ($year === null) {
            $year = now()->year;
        }
        $agencySallary = AgencySallary::query()->where(function ($query) use ($year, $month) {
            $query->where(DB::raw('concat(year,"-", month)'), '<=', $year.'-'.$month);
        })->where('is_paid', 0)
            ->where('agency_id', $this->id)
            ->orderByDesc('id')
            ->sum(DB::raw('sallary'));

        return floor($agencySallary ?? 0);
    }

    public function getTotalCutAmountAgency($month = null, $year = null)
    {
        if ($month === null) {
            $month = now()->month;
        }

        if ($year === null) {
            $year = now()->year;
        }
        $agencySallary = AgencySallary::query()->where(function ($query) use ($year, $month) {
            $query->where(DB::raw('concat(year,"-", month)'), '<=', $year.'-'.$month);
        })->where('is_paid', 0)
            ->where('agency_id', $this->id)
            ->orderByDesc('id')
            ->sum(DB::raw('cut_amount'));

        return floor($agencySallary ?? 0);
    }

    public function getOldAgency($month = null, $year = null)
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $old =
            AgencySallary::query()->when(isset($month), function ($query) use ($month) {
                $query->where('month', '<=', $month);
            })->when(isset($year), function ($query) use ($year) {
                $query->where('year', '<=', $year);
            })->where('agency_id', $this->id)->whereRaw("CONCAT(year, LPAD(month, 2, '0')) != CONCAT('$currentYear', LPAD('$currentMonth', 2, '0'))")->where('is_paid', 0)->sum(DB::raw('sallary - cut_amount'));

        return $old;
    }

    public function getSalaryAgency($month = null, $year = null)
    {
        if ($month === null) {
            $month = now()->month;
        }

        if ($year === null) {
            $year = now()->year;
        }
        $agencySallary = AgencySallary::query()->where(function ($query) use ($year, $month) {
            $query->where(DB::raw('concat(year,"-", month)'), '<=', $year.'-'.$month);
        })->where('is_paid', 0)
            ->where('agency_id', $this->id)
            ->orderByDesc('id')
            ->sum(DB::raw('sallary - cut_amount'));

        return      round($agencySallary, 2);

    }

    public function getSalary($month = null, $year = null)
    {
        if ($month === null) {
            $month = now()->month;
        }

        if ($year === null) {
            $year = now()->year;
        }
        $agencySallary = AgencySallary::query()
            ->where(function ($query) use ($year) {
                $query->where('year', $year);
            })->where(function ($query) use ($month) {
                $query->where('month', $month);
            })->where('agency_id', $this->id)->sum(DB::raw('sallary - cut_amount'));

        return floor($agencySallary ?? 0);
    }

    public function joinRequests()
    {
        return $this->hasMany(AgencyJoinRequest::class, 'agency_id');
    }

    public function getIsFrozenAttribute($value)
    {
        return $value ?? 0;
    }

    protected static function boot()
    {
        parent::boot();
        self::addGlobalScope(new ShippingAgencyScope);

        self::saving(function ($model) {

            if (request()->has('charge_agency')) {
                if (request('charge_agency') == 1) {
                    ChargeAgency::firstOrCreate([
                        'agency_id' => $model->id,
                    ]);
                } else {
                    ChargeAgency::where('agency_id', $model->id)->delete();
                }
            }

            if (request()->has('appear_charger_agency')) {
                $user = User::find($model->app_owner_id);

                if ($user) {
                    if (request('appear_charger_agency') == 1) {
                        $user->update(['appear_charger_agency' => 1]);
                    } else {
                        $user->update(['appear_charger_agency' => 0]);
                    }
                }
            }
        });

        self::updating(function ($agency) {
            if (isset($agency->is_frozen)) {
                $agency->is_frozen = (bool) $agency->is_frozen;
            }
        });

        self::deleting(function ($agency) {
        });
    }

    public function coinLogs()
    {
        return $this->morphMany(CoinLog::class, 'owner', 'user_type', 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }
}

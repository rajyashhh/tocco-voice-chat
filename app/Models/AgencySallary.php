<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class AgencySallary extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'agency_sallaries';

    protected $guarded = [];

    public function getTotalSalaryAttribute()
    {
        return floor($this->sallary - $this->cut_amount);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class,'agency_id');
    }

    protected static function booted()
    {
        self::saved(function ($model) {
            if ($model->agency_id) {
                clearAgencyCache($model->agency_id);
            }
        });

        self::deleted(function ($model) {
            if ($model->agency_id) {
                clearAgencyCache($model->agency_id);
            }
        });
    }
    public function totalTargetDiamonds($month = null, $year = null)
{
    $month = $month ?? now()->month;
    $year = $year ?? now()->year;

    return $this->hasMany(UserSallary::class, 'user_agency_id', 'agency_id')
        ->where('month', $month)
        ->where('year', $year)
        ->selectRaw('user_agency_id, SUM(target_diamonds) as total_target_diamonds')
        ->groupBy('user_agency_id');
}

}

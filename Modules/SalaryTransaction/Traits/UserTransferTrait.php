<?php

namespace Modules\SalaryTransaction\Traits;

use App\Models\UserSallary;
use Modules\SalaryTransaction\Entities\AgencyTransferSalary;

trait UserTransferTrait
{
    
    public function userSalary()
    {

        $month = (int)@request()->month;
        $year  = (int)@request()->year;
        if (!$month) $month = now()->month;
        if (!$year) $year = now()->year;

        return $this->hasOne(UserSallary::class)->where('user_agency_id', $this->agency_id)->where('month', $month)->where('year', $year);
    }
    public function getMonthDiamondAttribute(): int
    {
        $diamond = $this->userSalary?->diamond;
        return $diamond ? (int)explode(' / ', $diamond)[0] : 0;
    }
}

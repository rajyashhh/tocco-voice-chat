<?php

namespace App\Helpers;

use App\Models\AgencyMangerPullingOut;
use App\Models\Config;
use Illuminate\Database\Eloquent\Collection;

class ManagerHelper
{

    public function getTotalAgenciesSalary(Collection $agencies, int $managerId) : int
    {
        if ($agencies->isEmpty()) return 0;
        $totalSalary = $agencies->toQuery()
        ->withSum('agencySalaries as total_salaries', 'sallary')->get()->sum('total_salaries');
        $config = Config::query()->where('name','agency_manager_percentage')->first();
        $percentage = intval($config->value ?? 100) / 100;
        $pullingOut = (float) AgencyMangerPullingOut::where('agency_manger_id',$managerId)->sum('amount');

        $result = $totalSalary * $percentage;
        $netSalary = $result - $pullingOut;
        return floor($netSalary);

    }
}

<?php

namespace Modules\SalaryTransaction\Traits;

use Modules\SalaryTransaction\Entities\AgencyTransferSalary;

trait SalaryTransferTrait
{
    
    public function getTransferSalaryAttribute(){
        $salary = AgencyTransferSalary::query()->where ('agency_id',$this->id)->sum (\DB::raw('salary - cut_amount - pending_usd'));
        return $salary;
    }
    public function getPendingSalaryAttribute(){
        $salary = AgencyTransferSalary::query()->where ('agency_id',$this->id)->sum ('pending_usd');
        return $salary;
    }

}

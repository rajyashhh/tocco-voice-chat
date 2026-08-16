<?php

namespace App\Facades;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;


/**
 * @method static getTotalAgenciesSalary(Collection $agencies, int $managerId)
 */
class
ManagerHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ManagerHelper';
    }


}

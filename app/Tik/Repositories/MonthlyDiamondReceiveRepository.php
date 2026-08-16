<?php

namespace App\Tik\Repositories;


use App\Models\MonthlyDiamondReceive;


class MonthlyDiamondReceiveRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new MonthlyDiamondReceive());
    }
}
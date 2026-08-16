<?php

namespace App\Tik\Repositories;

use App\Models\History;


class HistoryRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new History());
    }

    public function getByMonthAndYear($agencyId,$month,$year)
    {
        return $this->model->where('agency_id', $agencyId)->where('year', $year)->where('month', $month)->orderBy('diamond', 'desc')->with('user');
    }
}
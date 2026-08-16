<?php

namespace App\Tik\Repositories;

use App\Models\AgencySallary;


class AgencySalaryRepository extends AbstractRepository
{


    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new AgencySallary());
    }

    public function findByAgencyId($agencyId)
    {
        return $this->model->query()->where('agency_id', $agencyId)
                    ->where('is_paid', 0)
                    ->orderByDesc('id')
                    ->first();
    }

    public function incrementCutAmount($agencyId, $amount)
    {
        $agencySalary =   $this->findByAgencyId($agencyId);
        $agencySalary->increment('cut_amount', $amount);
    }

    public function findByMonthAndYear($agencyId, $month, $year)
    {
        return $this->model->query()->where('agency_id', $agencyId)->where('month', $month)
            ->where('year', $year)->first();
    }

    public function agencySalary($agencyId, $month, $year)
    {
        return $this->model->where("agency_id", $agencyId)->when(isset($month) && $year, function ($query) use ($month, $year) {
            $query->where('month',  $month)->where('year',  $year);
        })->get();
    }
}

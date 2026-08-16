<?php

namespace App\Tik\Repositories;

use App\Models\UserSallary;
use Illuminate\Support\Facades\DB;


class UserSalaryRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {

        parent::__construct(new UserSallary());
    }

    public function findByUserId($userId)
    {
        return $this->model->query()->where('user_id', $userId)->orderByDesc('id')->first();
    }

    public function findByUser($userId)
    {
        return $this->model->where('user_id', $userId)->where('month', now()->month)->where('year', now()->year)->first();
    }

    public function incrementCutAmount($userId, $usd)
    {
        $userSalary = $this->findByUserId($userId);
        $userSalary->increment('cut_amount', (int)$usd);
        return true;
    }
    public function getSumByMonthAndYear($agencyId, $month, $year, $type)
    {
        return $this->model->where('user_agency_id', $agencyId)->where('year', $year)->where('month', $month)->sum($type);
    }

    public function getByUser($userId, $month, $year, $order = null)
    {
        $builder = $this->model->where(['user_id' => $userId, 'month' => $month, 'year' => $year]);
        if ($order == 1) {
            $builder = $builder->orderByDesc('id');
        }
        return   $builder->first();
    }

    public function getByUsers($userIds)
    {
        return $this->model->whereIn("user_id", $userIds)->where("month", date("m"))->where("year", date("Y"))->get();
    }
    public function sum($userIds, $type)
    {
        return $this->getByUsers($userIds)->sum($type);
    }

    public function gitByMonthYear($userId, $month, $year, $type)
    {
        return $this->model->query()->where('user_id', $userId)
            ->where(function ($query) use ($year, $month) {
                $query->where(DB::raw('concat(year,"-", month)'), '<=', $year . '-' . $month);
            })
            ->sum($type);
    }

    public function TotalSalary($userId, $month, $year)
    {
        return $this->model->query()->where('user_id', $userId)
            ->where(function ($query) use ($year, $month) {
                $query->where(DB::raw('concat(year,"-", month)'), '<=', $year . '-' . $month);
            })
            ->sum(DB::raw('sallary - cut_amount'));
    }

    public function changeAgencyId($oldAgencyId, $newAgencyId)
    {
        $this->model->where('user_agency_id', $oldAgencyId)->where('month', now()->month)->where('year', now()->year)->update(['user_agency_id' => $newAgencyId]);
        return true;
    }

    public function userSalary($userId, $month, $year)
    {
        return $this->model->where("user_id", $userId)->when(isset($month) && $year, function ($query) use ($month, $year) {
            $query->where('month',  $month)->where('year',  $year);
        })->get();
    }

    public function  agencySalary($agencyId, $month, $year)
    {
        return $this->model->where('user_agency_id', $agencyId)->whereYear('created_at', $year)->whereMonth('created_at', $month)->sum('agency_sallary');
    }

    public function  newUserSalary($userId, $month, $year)
    {
        return $this->model->where('user_id', $userId)
            ->whereRaw("CONCAT(year, LPAD(month, 2, '0')) != CONCAT('$year', LPAD('$month', 2, '0'))")
            ->sum('sallary');
    }
}

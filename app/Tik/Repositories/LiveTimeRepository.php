<?php

namespace App\Tik\Repositories;

use Carbon\Carbon;
use App\Models\LiveTime;


class LiveTimeRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new LiveTime());
    }

    public function totalHoursUser($userId)
    {
        return $this->model->query()->where('uid', $userId)->whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->sum('hours');
    }

    public function getActiveByUserId($userId)
    {
        return $this->model->query()->where('uid', $userId)
            ->where('end_time', null)
            ->whereBetween('created_at', [today()->startOfDay(), today()->endOfDay()])
            ->orderByDesc('id')
            ->first();
    }

    public function totalHoursByMonth($userId)
    {
        return $this->model->query()->where('uid', $userId)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('hours');
    }

    public function totalHours($userId)
    {
        return $this->model->query()->where('uid', $userId)
            ->sum('hours');
    }

    public function getByGroupUserId($userId, $startDate, $endDate)
    {
        return $this->model->query()
            ->where('uid', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('uid', \DB::raw('DATE(created_at)'))
            ->havingRaw('SUM(hours) > 1')
            ->selectRaw('uid, SUM(hours) AS hnum, COUNT(DISTINCT DATE(created_at)) as days');
    }

    public function sumDays($userId, $startDate, $endDate, $date)
    {
        $day = Carbon::parse($date);
        return $this->getByGroupUserId($userId, $startDate, $endDate)->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])->sum("days");
    }

    public function getByCreatedAt($userId, $startDate, $endDate)
    {
        return $this->model->query()->where('uid', $userId)->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function SumHours($userId, $startDate, $endDate, $date)
    {
        $day = Carbon::parse($date);
        return $this->getByCreatedAt($userId, $startDate, $endDate)->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])->sum("hours");
    }

    public function totalUsersHoursDays($userIds, $type)
    {
        $builder = $this->model->query()
            ->whereIn('uid', $userIds)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        if ($type == 'days') {
            return      $builder->groupBy('uid')
                ->selectRaw('uid, SUM(hours) AS hnum, COUNT(DISTINCT DATE(created_at)) as days')
                ->havingRaw('SUM(hours) >= 1')->sum('days') ?? 0;
        } else {
            return $builder->sum('hours') ?? 0;
        }
    }

    public function getByDaily($userId, $start, $end)
    {
        return $this->model->query()
            ->selectRaw('sum(hours) as hours, max(created_at) as date')
            ->where('uid', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy(\DB::raw('date(created_at)'))
            ->orderBy('date', 'asc')
            ->get();
    }

    // public function getByDailybyAgency($userId, $userJoinedData, $start, $end)
    // {
        // $joinedDate = Carbon::parse($userJoinedData);

    //     $startDate = Carbon::parse($start);
    //     $endDate = Carbon::parse($end);
    
    //     if ($joinedDate->greaterThan($startDate)) {
    //         $startDate = $joinedDate;
    //     }
    //     return $this->model->query()
    //         ->selectRaw('sum(hours) as hours, max(created_at) as date')
    //         ->where('uid', $userId)
    //         ->whereBetween('created_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
    //         ->groupBy(\DB::raw('date(created_at)'))
    //         ->orderBy('date', 'asc')
    //         ->get();
    // }

    public function sumUserHoursByDate($userId,$date)
    {
        $day = Carbon::parse($date);
        return $this->model->query()->where('uid', $userId)->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]) ->sum('hours');
    }
}

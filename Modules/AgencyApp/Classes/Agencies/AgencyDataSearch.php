<?php

namespace Modules\AgencyApp\Classes\Agencies;


use App\Helpers\Common;
use App\Models\AgencySallary;
use App\Models\MonthlyDiamondReceive;
use App\Models\User;
use App\Models\UserSallary;
use Illuminate\Http\Request;
use Modules\AgencyApp\Transformers\AgancyHostReportResource;

class AgencyDataSearch
{
    public function fetchData(Request $request)
    {
        $user = $request->user();
        $agency_id = $user->agency_id;
        $month = $request->month;
        $year = $request->year;
        $keyword = $request->keyword;

        $isCurrentPeriod = $this->isCurrentPeriod($month, $year);

        $dataQuery = $this->getDataQuery($agency_id, $month, $year, $isCurrentPeriod);
        if ($keyword != null) {
            $dataQuery = $dataQuery->where('uuid', 'like', '%' . $keyword . '%');
        }
        $paginatedData = $this->paginateData($dataQuery, 15);
        $transformedData = AgancyHostReportResource::collection($paginatedData);

        //        $totalDiamond = $isCurrentPeriod ? $dataQuery->get()->sum('monthly_diamond_received') : $paginatedData->sum('target_diamonds');
        $users          = $dataQuery->get();
        $userIds = $users->pluck('id')->toArray();
        $totalDiamond = $isCurrentPeriod ? MonthlyDiamondReceive::whereIn('user_id', $userIds)->where('month', now()->month)->where('year', now()->year)->sum('monthly_diamond_received') : $users->sum('month_diamond');
        $totalusd     = $this->calculateTotalUSD($agency_id, $year, $month);
        $agencySalary = $this->getAgencySalary($agency_id, $month, $year);

        //        $responseData = [
        ////            'sum' => $totalDiamond ?: 0,
        ////            'sum_usd' => $totalusd ?: 0,
        ////            'Total_owner_usd' => $agencySalary->sallary ?? 0,
        //            'users' => $transformedData ?: 0,
        //        ];

        return $transformedData;
    }

    private function isCurrentPeriod($month, $year)
    {
        $currentMonth = date('m');
        $currentYear = date('Y');
        return $currentYear == $year && $currentMonth == $month;
    }

    private function getDataQuery($agency_id, $month, $year, $isCurrentPeriod)
    {
        //        if ($isCurrentPeriod) {
        return User::where('agency_id', $agency_id)->where(function ($query) {
            $query->where(function ($query) {
                $query->WhereDoesntHave('userAgencyJoined')->where(function ($query) {
                    $query->WhereHas('ownAgency')->orWhereHas('agencyJoinRequest', function ($query) {
                        $query->orderBy('id');
                    });
                });
            })->orWhereHas('userAgencyJoined', function ($query) {
                $query->orderBy('type')->orderBy('id');
            });
        });
        /*  } else {
            return UserTarget::where('agency_id', $agency_id)->where('add_year', $year)->where('add_month', $month)->orderBy('target_diamonds', 'desc')->with('user');
//            return History::where('agency_id', $agency_id)->where('year', $year)->where('month', $month)->orderBy('diamond', 'desc')->with('user');
        }*/
    }

    private function paginateData($query, $perPage = 15)
    {
        $page = request('page', 1);
        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function calculateTotalUSD($agency_id, $year, $month)
    {
        $ta = UserSallary::where('user_agency_id', $agency_id)->where('year', $year)->where('month', $month);
        return $ta->sum('sallary');
    }

    private function getAgencySalary($agency_id, $month, $year)
    {
        return AgencySallary::where('agency_id', $agency_id)->where('month', $month)->where('year', $year)->first();
    }
}

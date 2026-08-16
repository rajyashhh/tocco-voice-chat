<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencySallary;
use App\Models\User;
use App\Tik\Services\BackgroundService;
use Illuminate\Support\Facades\Validator;
use App\Tik\Services\RequestBackgroundImagService;
use Illuminate\Support\Facades\DB;

class AgencyStatisticController extends Controller
{
    public function statistic()
    {
        $agencies = DB::table('agencies')->count() ?? 0;
        $month = date('m');
        $year = date('Y');

        $activeAgencyCount = Agency::whereHas('agencySalaries', function ($q) use ($month, $year) {
            $q->where('month', $month)
                ->where('year', $year);
        })
            ->count();
        $agencySalaries = AgencySallary::where('month', $month)->where('year', $year)->sum(\DB::raw('sallary - cut_amount'));
        $users = User::where("is_host", 1)->whereNotNull("agency_id")->count();

        $topUsers = User::select("users.id", "users.name", "users.email")
            ->with("profile:id,avatar,user_id")
            ->join('monthly_diamond_receives as mdr', function ($join) {
                $join->on('mdr.user_id', '=', 'users.id')
                    ->where('mdr.month', now()->month)
                    ->where('mdr.year', now()->year);
            })
            ->orderByDesc("mdr.monthly_diamond_received")
            ->take(10)
            ->get();
        $agenciesWithSalaries = Agency::select("id", "name", "salary")->get();
        $data = [
            "agencies" => $agencies,
            "activeAgencyCount" => $activeAgencyCount,
            "agencySalaries" => $agencySalaries,
            "hosts" => $users,
            "topUsers" => $topUsers,
            "agenciesWithSalaries" => $agenciesWithSalaries,
        ];

        return Common::apiResponse(1, '', $data);
    }
}

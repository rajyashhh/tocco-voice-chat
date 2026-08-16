<?php

namespace Modules\ServerControl\Http\Controllers\Api;

use App\Helpers\Common;
use App\Models\Charge;
use App\Models\CoinLog;
use App\Models\Commission;
use App\Models\Config;
use App\Models\RequestTakeSalary;
use App\Models\UsdTransfer;
use Carbon\Carbon;
use Illuminate\Routing\Controller;

class AppEarnedController extends Controller
{
    public function index()
    {
        $start_date =request('start_date') != null? Carbon::createFromFormat('Y-m-d',request('start_date'))->startOfDay():Carbon::now()->startOfMonth()->startOfDay();
        $end_date = request('start_date') != null?Carbon::createFromFormat('Y-m-d',request('end_date'))->endOfDay():Carbon::now()->endOfMonth()->endOfDay();
            $lose2 = RequestTakeSalary::where("status",1)->whereBetween('created_at',[ $start_date, $end_date ])->sum("amount");
            $lose2 = $lose2 ;
            $first_earned_charge = Charge::where("charger_type","dash")->whereBetween('created_at',[ $start_date, $end_date ])->sum("usd");
            $second_earned_charge = CoinLog::whereIn('method',['huawei_pay','google_pay','apple_pay' ])->whereBetween('created_at',[ $start_date, $end_date ])->sum("paid_usd");
            $first_earned = Charge::where("charger_type","dash")->sum("usd");
            $second_earned = CoinLog::whereIn('method',['huawei_pay','google_pay','apple_pay' ])->sum("paid_usd");
            $earned = $first_earned + $second_earned;
            $earned_charge = $first_earned_charge + $second_earned_charge;
            
            $bonus = $earned * 0.1;
            $commission = Commission::sum("amount");
            $deduction = $bonus - $commission;

        $data = [
            'total_system_charge' => number_format(@$first_earned_charge ?? 0),
            'payments' =>  number_format(@$second_earned_charge ?? 0),
            'total_system_charge_plus_payments' => @$earned_charge ?? 0,
            'bonus' => number_format(@$deduction,2) ?? 0,
            'commission' => @$commission ?? 0,
        ];
        return Common::apiResponse(1, '', $data, 200);
    }

}

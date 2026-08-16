<?php

namespace App\Http\Controllers\Dashboard\Charing;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Charges\CoinLogChargesResource;
use App\Http\Resources\Dashboard\Charges\DashboardChargesResource;
use App\Models\Charge;
use App\Models\CoinLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminChargingReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
            'start' => 'required',
            'end' => 'required',
            'type' => 'required',
        ]);
        $start = Carbon::parse( $request->start)->startOfDay()->format('Y-m-d H:i:s');
        $end =Carbon::parse( $request->end)->endOfDay()->format('Y-m-d H:i:s');
        if($request->type === 'dashboard')
        {
            $data = Charge::where('charger_type','dash')->whereBetween('created_at',[$start,$end])->orderby('created_at','desc')->with('sender','receiver')->get();
            $response = DashboardChargesResource::collection($data);
        }
        else if($request->type === 'app')
        {
            $data = Charge::where('charger_type','!=','dash')->whereBetween('created_at',[$start,$end])->orderby('created_at','desc')->with('sender','receiver')->get();
            $response = DashboardChargesResource::collection($data);
        }
        else if($request->type === 'gateway')
        {
            $data = CoinLog::whereNotIn('method', ['huawei_pay', 'google_pay', 'apple_pay'])->whereBetween('created_at',[$start,$end])->orderby('created_at','desc')->with('user')->get();
            $response = CoinLogChargesResource::collection($data);
        }
        else if($request->type === 'purchas')
        {
            $data = CoinLog::where('method', $request->purchas_type)->whereBetween('created_at',[$start,$end])->orderby('created_at','desc')->with('user')->get();
            $response = CoinLogChargesResource::collection($data);
        }

       return [
        'data' =>$response,
        'start' =>$start,
        'end' =>$end,
       ];

    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}

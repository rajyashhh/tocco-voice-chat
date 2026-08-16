<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PercentageTargetController extends Controller
{
    public function index(){

        $errors = session()->get('errors');
        $errorMessage =  $errors ? $errors->first('msg') :  null;

        $data = [
            'hours' =>  settings()->get('hours'),
            'days' =>  settings()->get('days'),
            'moments' =>  settings()->get('moments'),
            'reels' => settings()->get('reels'),
            'error_message' => $errorMessage
        ];

        return Common::apiResponse(true, '', $data);
    }

    public function store(Request $request){
        $hours =  $request->hours;
        $days =  $request->days;
        $reels =  $request->reels;
        $moments =  $request->moments;

        $total = $hours + $days + $reels + $moments;
        if ($total != 50) {
            return   Common::apiResponse(false,'' , ['msg' => 'يجب المجموع يكون 50']);
        }
        settings()->set("hours", $hours);
        settings()->set("days", $days);
        settings()->set("reels", $reels);
        settings()->set("moments", $moments);

        return Common::apiResponse(true, '');
    }
}

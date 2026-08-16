<?php

namespace App\Http\Controllers\Api\V1;

use Auth;
use Modules\Vip\Entities\Vip;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChargeLevelController extends Controller
{
    public function chargeLevel(Request $request)
    {
        $user = $request->user();
        $currentLevel = Vip::where("level", @$user->total_charge_level)->where('type', 5)->orderBy('level')->first();
        if (!$currentLevel) {
            return Common::apiResponse(true, 'لا يملك اي مستوي', []);;
        }
        $secondLevel = Vip::where("type", 5)->where("level", ">", $currentLevel->level)->orderBy('level')->first();
        $remaining = 0;
        $progress = 0;
        if ($secondLevel != null) {
            $remaining = $secondLevel->exp - $user->exp;
            $exactlyValue = $secondLevel->exp - $currentLevel->exp;
            $progress = $remaining / $exactlyValue;
        }
        $data = [
            'current_level'  => $user->total_charge_level ?? 0,
            'current_exp'    => $currentLevel->exp ?? 0,
            'current_img'    => $currentLevel->img ?? '',
            'next_level'     => @$secondLevel->level ?? 0,
            'next_exp'       => @$secondLevel->exp ?? 0,
            'next_img'       => @$secondLevel->img ?? '',
            'remaining'         => @$remaining ?? 0,
            'progress'          => @$progress ?? 0,
        ];
        return Common::apiResponse(true, 'success', $data);
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Models\Setting;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Auth\Permission;
use Cache;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class AddTargetToJsonController extends Controller
{
    public $permission_name = 'agency-settings';


    public function targetPercentage(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        $hours =  $request->hours;
        $days =  $request->days;
        $reels =  $request->reels;
        $moments =  $request->moments;
        $diamonds = $request->diamonds;

        $total = $hours + $days + $reels + $moments + $diamonds;

        if ($total > 100) {
            return Redirect::back()->withErrors(['msg' => 'يجب ان يكون المجموع ليس اكبر من 100']);
        }

        foreach ($request->all() as $key => $value) {
            if (!is_null($value)) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
                
                Cache::forget($key);
                Cache::forever($key, $value);
            }
        }

        return Redirect::back();
    }
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hours'        => 'required|numeric',
            'days'        => 'required|numeric',
            'reels'        => 'nullable|numeric',
            'moments'         => 'nullable|numeric',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        $hours =  $request->hours;
        $days =  $request->days;
        $reels =  $request->reels;
        $moments =  $request->moments;

        $total = $hours + $days + $reels + $moments;
        if ($total != 50) {
            return Common::apiResponse(0, 'يجب المجموع يكون 50');
        }
        settings()->set("hours", $hours);
        settings()->set("days", $days);
        settings()->set("reels", $reels);
        settings()->set("moments", $moments);
        return Common::apiResponse(1, 'created successfully');
    }

    public function show()
    {
        $hours =  settings()->get('hours');
        $days =  settings()->get('days');
        $moments =  settings()->get('moments');
        $reels = settings()->get('reels');
        $data = [
            'hours'  => $hours,
            'days' => $days,
            'reels' => $reels,
            'moments' => $moments,
        ];
        return Common::apiResponse(1, '', $data);
    }
}

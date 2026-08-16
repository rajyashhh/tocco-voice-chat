<?php

namespace App\Http\Controllers\utd;

use Modules\Vip\Entities\OVip;
use App\Models\Ware;
use App\Helpers\Common;
use App\Enums\IntervalLevel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Public\Entities\RewardLevelInterval;
use App\Http\Resources\Api\V1\IntervalOVipResource;
use App\Http\Resources\Api\V1\IntervalWareResource;

class RewardLevelIntervalController extends Controller
{
    public function index($reward_level_interval)
    {
        $id = request('id');
        $perPage = request('per_page') ?? 10;
        $rewards = RewardLevelInterval::when($id, function ($query, $id) {
            return $query->where('id', $id);
        })->where('level_interval_id', $reward_level_interval)->with('ware','vip')->paginate($perPage);

        return Common::apiResponse(1, 'success', $rewards, 200);
    }

    public function show($reward_level_interval, $id)
    {
        $reward = RewardLevelInterval::where('level_interval_id', $reward_level_interval)->with('ware', 'vip')->findOrFail($id);



        return Common::apiResponse(1, 'success', $reward, 200);
    }

    public function store($reward_level_interval, Request $request)
    {

        if ($request->hasFile('target4')) {
            $target = Common::upload('images', $request->file('target4'));
        }

        $reward = RewardLevelInterval::create([
            'level_interval_id' => $reward_level_interval,
            'type' => $request->type,
           // 'target' =>  $target,
            'expire' => $request->expire,
        ]);


        return Common::apiResponse(1, 'success', $reward, 200);
    }

    public function update($reward_level_interval, Request $request, $id)
    {

        if ($request->hasFile('target4')) {
            $target = Common::upload('images', $request->file('target4'));
        } 
        
        RewardLevelInterval::where('level_interval_id', $reward_level_interval)->findOrFail($id)->update([
            'type' => $request->type,
           // 'target' => $target,
            'expire' => $request->expire,
        ]);


        return Common::apiResponse(1, 'success', [], 200);
    }

    public function destroy($reward_level_interval, $id)
    {
        RewardLevelInterval::where('level_interval_id', $reward_level_interval)->findOrFail($id)->delete();


        return Common::apiResponse(1, 'success', [], 200);
    }

    public function allType()
    {

        $data = IntervalLevel::getTranslatedOptions();
        return Common::apiResponse(1, 'success', $data, 200);
    }

    public function wareInterval()
    {

        $wares = Ware::query()->whereIn('type', [4, 5, 6])->get();

        return Common::apiResponse(1, 'success', IntervalWareResource::collection($wares), 200);
    }

    public function vipInterval()
    {
        $vips = OVip::query()->get();

        return Common::apiResponse(1, 'success', IntervalOVipResource::collection($vips), 200);
    }
}

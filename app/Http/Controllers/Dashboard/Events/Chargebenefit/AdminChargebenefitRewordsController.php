<?php

namespace App\Http\Controllers\Dashboard\Events\Chargebenefit;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Events\AdminChargebenefitResource;
use App\Http\Resources\Dashboard\Events\AdminPKEventRewords;
use App\Http\Resources\Dashboard\Events\AdminPKEventsResource;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;
use Modules\Events\Entities\RewardTarget;

class AdminChargebenefitRewordsController extends Controller
{
    use DashBoardTrait;
    public function index(Request $request)
    {
        $id = $request->input('id');
        $level1 = RewardTarget::where('charge_event_id', $id)->get();
        return  AdminPKEventRewords::collection($level1);
    }

    public function store(Request $request)
    {
        $request->validate([
            'event_id'    => 'required|exists:charge_events,id',
            'expire'      => 'required|numeric',
            'type'        => 'required',
            'img'         => 'nullable',
        ]);
        $img = null;
        if ($request->type !== 'achievement') {
            $request->validate([
                'target'       => 'required|numeric',
            ]);
        } else {
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'events') : null;
        }
        RewardTarget::insert([
            'charge_event_id'  =>  $request->event_id,
            'expire'           =>  $request->expire,
            'type'             => $request->type ,
            'target'           => $request->type !== 'achievement' ? $request->target : $img ?? 'sasa' ,
        ]);
        return $img ;
    }

    public function show(string $id)
    {
        $data = RewardTarget::find($id);
        return new AdminChargebenefitResource( $data);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'event_id'    => 'required|exists:charge_events,id',
            'expire'      => 'required|numeric',
            'type'        => 'required',
            'img'         => 'nullable',
        ]);

        $pkReward = RewardTarget::findOrFail($id);

        if ($request->type !== 'achievement') {
            $request->validate([
                'target' => 'required|numeric',
            ]);
        } else {
            if($request->hasFile('img'))
            {
                $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'events') : null;
            }
            else{
                $img = $pkReward->target;
            }
        }

        $pkReward->update([
            'charge_event_id'  =>  $request->event_id,
            'expire'           =>  $request->expire,
            'type'             => $request->type ,
            'target' => $request->type !== 'achievement' ? $request->target : $img ?? $pkReward->target,
        ]);

        return $img;
    }

    public function destroy(string $id)
    {
        $RewardTarget = RewardTarget::find($id);
        if( $RewardTarget->type == 'achievement' && $RewardTarget->target)
        {
            $this->delete_img($RewardTarget->target);
        }
        $RewardTarget->delete();
        return 200;
    }
}

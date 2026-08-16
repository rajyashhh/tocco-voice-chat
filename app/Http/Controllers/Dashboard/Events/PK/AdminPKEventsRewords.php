<?php

namespace App\Http\Controllers\Dashboard\Events\PK;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Events\AdminPKEventRewords;
use App\Http\Resources\Dashboard\Events\AdminPKEventsResource;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;
use Modules\Events\Entities\PkReward;

class AdminPKEventsRewords extends Controller
{
    use DashBoardTrait;
    public function index(Request $request)
    {
        $type = $request->input('type');
        $id = $request->input('id');
        $level1 = PkReward::where('pk_event_id', $id)->where('pk_type', $type)->where('level', 1)->get();
        $level2 = PkReward::where('pk_event_id', $id)->where('pk_type', $type)->where('level', 2)->get();
        $level3 = PkReward::where('pk_event_id', $id)->where('pk_type', $type)->where('level', 3)->get();

        return [
            'level1' => AdminPKEventRewords::collection($level1),
            'level2' => AdminPKEventRewords::collection($level2),
            'level3' => AdminPKEventRewords::collection($level3),
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'event_id'    => 'required|exists:pk_events,id',
            'expire'      => 'required|numeric',
            'level'       => 'required|numeric',
            'type'        => 'required',
            'img'         => 'nullable',
            'event_type'  => 'required',
        ]);
        $img = null;
        if ($request->type !== 'achievement') {
            $request->validate([
                'target'       => 'required|numeric',
            ]);
        } else {
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'events') : null;
        }
        PkReward::insert([
            'pk_event_id'  =>  $request->event_id,
            'pk_type'      =>  $request->event_type,
            'expire'       =>  $request->expire,
            'level'        => $request->level ,
            'type'         => $request->type ,
            'target'         => $request->type !== 'achievement' ? $request->target : $img ?? 'sasa' ,
        ]);
        return $img ;
    }


    public function show(string $id)
    {
        $data = PkReward::find($id);
        return new AdminPKEventsResource( $data);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'event_id'    => 'required|exists:pk_events,id',
            'expire'      => 'required|numeric',
            'level'       => 'required|numeric',
            'type'        => 'required',
            'img'         => 'nullable',
            'event_type'  => 'required',
        ]);

        $pkReward = PkReward::findOrFail($id);

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
            'pk_event_id' => $request->event_id,
            'pk_type' => $request->event_type,
            'expire' => $request->expire,
            'level' => $request->level,
            'type' => $request->type,
            'target' => $request->type !== 'achievement' ? $request->target : $img ?? $pkReward->target,
        ]);

        return $img;
    }

    public function destroy(string $id)
    {
        $PkReward = PkReward::find($id);
        if( $PkReward->type == 'achievement' && $PkReward->target)
        {
            $this->delete_img($PkReward->target);
        }
        $PkReward->delete();
        return 200;
    }
}

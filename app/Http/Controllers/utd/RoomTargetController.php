<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\RoomTarget;
use Illuminate\Http\Request;

class RoomTargetController extends Controller
{
    public function index(){

        $perPage = request('per_page')?? 10;
        $search = request('search');
        $imageColors = RoomTarget::when($search, function($q) use($search){
            $q->where('id', $search);
        })->paginate($perPage);


        return Common::apiResponse(true, '', $imageColors, 200);

    }

    public function store(Request $request){

        $room_target  = RoomTarget::create([
            'coins' => $request->coins,
            'usd' => $request->usd,
        ]);

        return Common::apiResponse(true, '',  $room_target, 200);
    }

    public function update(Request $request, $id){

        RoomTarget::findOrFail($id)->update([
            'coins' => $request->coins,
            'usd' => $request->usd,
        ]);

        return Common::apiResponse(1, 'Room Target updated successfully');


    }

    public function show($id){
        $room_target = RoomTarget::findOrFail($id);

        return Common::apiResponse(true, '', $room_target, 200);
    }

    public function destroy($id){

        RoomTarget::findOrFail($id)->delete();

        return Common::apiResponse(1, 'success', null, 200);
    }
}

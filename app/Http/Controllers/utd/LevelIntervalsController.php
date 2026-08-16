<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\LevelIntervalsResource;
use Illuminate\Http\Request;
use Modules\Public\Entities\LevelInterval;

class LevelIntervalsController extends Controller
{
    public function index(){

        
        $perPage = request('per_page') ?? 10;
        $id = request('id');
        $levelIntervals = LevelInterval::when($id, function ($query, $id) {
            return $query->where('id', $id);
        })->paginate($perPage);

        return Common::apiResponse(1, 'success', LevelIntervalsResource::collection($levelIntervals), 200);

    }

    public function store(Request $request){

        $level_interval = LevelInterval::create([
            'name' => $request->name,
            'type' => $request->type,
            'min' => $request->min,
            'max' => $request->max
        ]);

        return Common::apiResponse(1, 'success', $level_interval, 200);
    }

    public function update($id, Request $request){

        LevelInterval::findOrFail($id)->update([
            'name' => $request->name,
            'type' => $request->type,
            'min' => $request->min,
            'max' => $request->max
        ]);


        return Common::apiResponse(1, 'success', [], 200);

    }

    public function show($id){

        $levelInterval = LevelInterval::findOrFail($id);

        return Common::apiResponse(1, 'success', $levelInterval, 200);

    }

    public function destroy($id){
        LevelInterval::findOrFail($id)->delete();

        return Common::apiResponse(1, 'success', [], 200);
    }
}

<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Events\Entities\WeeklyStar;

class EventPeriodController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = WeeklyStar::with('gifts')->when($search,function($q)use($search){
            $q->where('id',$search);
        })
        ->where('type','event_period')
        ->paginate($perPage);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function show($id){
        $event = WeeklyStar::with('gifts')->where('type', 'event_period')->findOrFail($id);

        return Common::apiResponse(true, 'Success', $event);
    }

    public function delete($id){
        $event = WeeklyStar::where('type', 'event_period')->findOrFail($id);

        $event->gifts()->detach();
        $event->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function delete_all(Request $request){
        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        foreach($ids as $id){

            $result = WeeklyStar::where('type', 'event_period')->findOrFail($id);
            $result->gifts()->detach();
            $result->delete();
        }

        return Common::apiResponse(true, 'Success');
    }

    public function store(Request $request){

        $validatedData = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'gifts' => 'required'
        ]);

        $gifts = explode(',', $request->gifts);

        if(count($gifts) != 3){
            return Common::apiResponse(false, 'gifts must be 3');
        }
        $validatedData['type'] = 'event_period';

        $event = WeeklyStar::create($validatedData);
        $event->gifts()->sync($gifts);

        return Common::apiResponse(true, 'Success', $event);
    }


    public function update(Request $request, $id)
    {
        $event = WeeklyStar::where('type', 'event_period')->findOrFail($id);

        $validatedData = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'gifts' => 'nullable',
        ]);

        $event->update($validatedData);

        if ($request->has('gifts')) {
            $gifts = explode(',', $request->gifts);
            $event->gifts()->sync($gifts);
        }

        return Common::apiResponse(true,'Success');
    }
}

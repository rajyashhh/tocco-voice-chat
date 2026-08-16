<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DailyPrize\Entities\DailyGiftType;

class DailyGiftTypesController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $dailyGiftTypes = DailyGiftType::when($search,function($q)use($search){
            $q->where('id', $search);
        })->paginate($perPage);

        return Common::apiResponse(true, 'Success', $dailyGiftTypes);
    }

    public function show($id){

        $result = DailyGiftType::findOrFail($id);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function store(Request $request){
        $request->validate([
            'type' => 'required|in:1,2,3,4'
        ]);

        $result = DailyGiftType::create([
            'type' => $request->type
        ]);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function update(Request $request, $id){
        $request->validate([
            'type' => 'required|in:1,2,3,4'
        ]);

        $result = DailyGiftType::findOrFail($id);

        $result->update([
            'type' => $request->type
        ]);

        return Common::apiResponse(true, 'Success');
    }

    public function delete($id){
        $result = DailyGiftType::findOrFail($id);
        $result->delete();

        return Common::apiResponse(true, 'Success');
    }
}

<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\SpecialId\Entities\SpecialHistory;

class SpecialHistoryController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = SpecialHistory::when($search, function($q)use($search){
            $q->where('id', $search);
        })
        ->paginate($perPage);

        $result->getCollection()->transform(function ($item) {
            $item->created_at_diff = Carbon::parse($item->created_at)->diffForHumans();
            return $item;
        });

        return Common::apiResponse(true,'Success', $result);
    }

    public function delete_all(Request $request){

        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        SpecialHistory::whereIn('id', $ids)->delete();

        return Common::apiResponse(true,'Success');
    }

    public function delete($id){
        $result = SpecialHistory::findOrFail($id);
        $result->delete();

        return Common::apiResponse(true, 'Success');
    }
}

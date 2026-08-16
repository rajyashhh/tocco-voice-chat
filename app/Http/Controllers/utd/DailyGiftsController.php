<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\DailyGiftResource;
use App\Models\Ware;
use Illuminate\Http\Request;
use Modules\DailyPrize\Entities\DailyGift;

class DailyGiftsController extends Controller
{
    public function index($type){

        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $results = DailyGift::where('type', $type)->when($search, function($q) use($search){
            $q->where('id', $search);
        })
        ->paginate($perPage);



        return Common::apiResponse(true, 'Success', DailyGiftResource::collection($results));
    }

    public function show($type, $id){

        $result = DailyGift::where('type', $type)->findOrFail($id);

        return Common::apiResponse(true, 'Success', new DailyGiftResource($result) );
    }

    public function store($type, Request $request){

        $request->validate([
            'order' => 'required|in:1,2,3,4,5,6,7',
            'gift_type' => 'required',
            'target1'    => 'nullable|exists:wares,id',
            'target2'    => 'nullable|exists:o_vips,id',
            'target3'    => 'nullable|integer',
            'target4'    => 'nullable|file',
            'expir' => 'required|numeric'
        ]);


        $result = DailyGift::create([
            'order' => $request->order,
            'gift_type' => $request->gift_type,
            'target' => $request->target,
            'expir' => $request->expir,
            'type' => $type
        ]);
        $coins = Ware::find();

        return Common::apiResponse(true, 'Success', $result);
    }

    public function update($type, $id, Request $request){
        $request->validate([
            'order' => 'required|in:1,2,3,4,5,6,7',
            'gift_type' => 'required',
            'target1'    => 'nullable|exists:wares,id',
            'target2'    => 'nullable|exists:o_vips,id',
            'target3'    => 'nullable|integer',
            'target4'    => 'nullable|file',
            'expire' => 'required|numeric'
        ]);
        $result = DailyGift::where('type', $type)->findOrFail($id);

        $result->update([
            'order' => $request->order,
            'gift_type' => $request->gift_type,
            'target' => $request->target,
            'expire' => $request->expire
        ]);

        return Common::apiResponse(true, 'Success');

    }
    public function delete($type, $id){

        $result = DailyGift::where('type', $type)->findOrFail($id);
        $result->delete();
        return Common::apiResponse(true, 'Success');
    }
}

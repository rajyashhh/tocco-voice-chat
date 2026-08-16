<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CP\Entities\CpLevel;

class LevelController extends Controller
{
    public function index($relation_id){

        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = CpLevel::where('cp_relation_id', $relation_id)
        ->when($search,function($q)use($search){
            $q->where('id', $search);
        })
        ->paginate($perPage);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function show($relation_id, $id){
        $result = CpLevel::where('cp_relation_id', $relation_id)->findOrFail($id);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function delete($relation_id, $id){
        CpLevel::where('cp_relation_id', $relation_id)->findOrFail($id)->delete();

        return Common::apiResponse(true,'Success');
    }

    public function delete_all($relation_id,Request $request){
        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        CpLevel::where('cp_relation_id', $relation_id)->whereIn('id', $ids)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function store($relation_id, Request $request){

        $validated = $request->validate([
            'name_ar' => 'required',
            'name_en' => 'required',
            'level' => 'required',
            'exp' => 'required',
            'img' => 'nullable'
        ]);

        $validated['cp_relation_id']= $relation_id;

        if($request->hasFile('img')){
            $validated['img'] = Common::upload('images', $request->file('img'));
        }

        $result = CpLevel::create($validated);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function update($relation_id, $id, Request $request){

        $validated = $request->validate([
            'name_ar' => 'required',
            'name_en' => 'required',
            'level' => 'required',
            'exp' => 'required',
            'img' => 'nullable'
        ]);

        if($request->hasFile('img')){
            $validated['img'] = Common::upload('images', $request->file('img'));
        }

        $result = CpLevel::where('cp_relation_id', $relation_id)->findOrFail($id);

        $result->update($validated);


        return Common::apiResponse(true, 'Success', $result);
    }
}

<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\RoomCategory;
use Illuminate\Http\Request;

class RoomCategoryController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = RoomCategory::when($search,function($q)use($search){
            $q->where('id',$search);
        })
        ->paginate($perPage);

        return Common::apiResponse(true,'Success', $result);
    }

    public function show($id){
        $result = RoomCategory::findOrFail($id);

        return Common::apiResponse(true,'Success', $result);
    }

    public function delete($id){

        RoomCategory::findOrFail($id)->delete();

        return Common::apiResponse(true,'Success');
    }

    public function delete_all(Request $request){

        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        RoomCategory::whereIn('id',$ids)->delete();

        return Common::apiResponse(true,'Success');
    }

    public function store(Request $request){

        $validated = $request->validate([
            'parent_id' => 'required',
            'name' => 'required',
            'name_en' => 'required',
            'type' => 'nullable',
            'img' => 'nullable',
            'enable' => 'required'
        ]);

        if($request->hasFile('img')){
            $validated['img'] = Common::upload('images', $request->file('img'));
        }

        $result = RoomCategory::create($validated);

        return Common::apiResponse(true,'Success', $result);

    }

    public function parent()
    {
        $data = RoomCategory::query()->select('id', 'name')->where('enable', 1)->where('parent_id', 0)->get();
        $data = collect([['id' => 0, 'name' => 'root']])->merge($data);
        return Common::apiResponse(true, '', $data, 200);
    }

    public function update($id, Request $request){

        $request->validate([
            'parent_id' => 'required',
            'name' => 'required',
            'name_en' => 'required',
            'type' => 'nullable',
            'img' => 'nullable',
            'enable' => 'required'
        ]);
        $result = RoomCategory::findOrFail($id);

        if($request->hasFile('img')){
            $img = Common::upload('images', $request->file('img'));
            $result->update([
                'img' => $img
            ]);
        }

        $result->update([
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'name_en' => $request->name_en,
            'enable' => $request->enable
        ]);

        return Common::apiResponse(true,'Success');
    }

    public function update_status($id, Request $request){
        $request->validate([
            'enable' => 'required'
        ]);
        $result = RoomCategory::findOrFail($id);


        $result->update([
            'enable' => $request->enable
        ]);

        return Common::apiResponse(true,'Success');
    }
}

<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CP\Entities\CpRelation;

class CpRelationController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = CpRelation::when($search, function($q)use($search){
            $q->where('id', $search);
        })
        ->paginate($perPage);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function types(){
        $types = [
            'bro',
            'friend',
            'lovely',
            'solution'
        ];

        return Common::apiResponse(true, 'Success', $types);
    }

    public function show($id){

        $result = CpRelation::findOrFail($id);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function delete($id){
        CpRelation::findOrFail($id)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function delete_all(Request $request){
        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        CpRelation::whereIn('id', $ids)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function store(Request $request){

        $validated = $request->validate([
            'title' => 'required',
            'description' => ' required',
            'image' => 'required|image',
            'price' => 'required',
            'type' => 'required',
            'relations_number' => 'required'
        ]);

        $validated['image'] = Common::upload('images', $request->file('image'));

        $result = CpRelation::create($validated);

        return Common::apiResponse(true, 'Success', $result);

    }
    public function update($id, Request $request){

        $validated = $request->validate([
            'title' => 'required',
            'description' => ' required',
            'image' => 'nullable|image',
            'price' => 'required',
            'type' => 'required',
            'relations_number' => 'required'
        ]);

        if($request->hasFile('image')){
            $validated['image'] = Common::upload('images', $request->file('image'));
        }

        $result = CpRelation::findOrFail($id);

        $result->update($validated);

        return Common::apiResponse(true,'Success');

    }
}

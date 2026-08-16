<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SpecialId\Entities\SpecialIdFram;

class SpecialIdFramController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $results = SpecialIdFram::when($search, function($q)use($search){
            $q->where('id', $search);
        })->paginate($perPage);

        return Common::apiResponse(true, 'Success', $results);
    }

    public function show($id){
        $result = SpecialIdFram::findOrFail($id);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function store(Request $request){

        $request->validate([
            'title' => 'required',
            'img' => 'nullable',
            'color' => 'required'
        ]);

        $image = null;
        if($request->hasFile('img')){
            $image = Common::upload('images', $request->img);
        }

        $result =SpecialIdFram::create([
            'image' => $image,
            'title' => $request->title,
            'color' => $request->color
        ]);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function update($id, Request $request){

        $request->validate([
            'title' => 'required',
            'img' => 'nullable',
            'color' => 'required'
        ]);

        $result = SpecialIdFram::findOrFail($id);

        if($request->hasFile('img')){
            $image = Common::upload('images', $request->img);
            $result->update([
                'image' => $image
            ]);
        }
        $result->update([
            'title' => $request->title,
            'color' => $request->color,
        ]);


        return Common::apiResponse(true, 'Success');
    }

    public function delete($id){
        $result = SpecialIdFram::findOrFail($id);

        $result->delete();

        return Common::apiResponse(true, 'Success');
    }
}

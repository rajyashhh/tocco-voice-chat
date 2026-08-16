<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Background;
use Illuminate\Http\Request;

class BackgroundController extends Controller
{
    public function index(){

        $perPage = request('per_page')?? 10;
        $search = request('search');
        $backgrounds = Background::when($search,function($q)use($search){
            $q->where('id', $search);
        })->paginate($perPage);


        return Common::apiResponse(true, '', $backgrounds, 200);
    }

    public function store(Request $request){

        if($request->hasFile('img')){
            $image = Common::upload('images', $request->img);
        }

        $background  = Background::create([
            'img' => $image ?? '',
            'enable' => $request->enable
        ]);

        return Common::apiResponse(true, '',  $background, 200);
    }

    public function update(Request $request, $id){


        if($request->hasFile('img')){
            $image = Common::upload('images', $request->img);
            Background::findOrFail($id)->update([
                'img' => $image
            ]);
        }
        Background::findOrFail($id)->update([
            'enable' => $request->enable
        ]);

        return Common::apiResponse(1, 'Background updated successfully');


    }

    public function show($id){
        $background = Background::findOrFail($id);

        return Common::apiResponse(true, '', $background, 200);
    }

    public function destroy($id){

        Background::findOrFail($id)->delete();

        return Common::apiResponse(1, 'success', null, 200);
    }
}

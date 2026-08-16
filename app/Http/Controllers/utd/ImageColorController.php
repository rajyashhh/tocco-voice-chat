<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\ImageColor;
use Illuminate\Http\Request;

class ImageColorController extends Controller
{
    public function index(){

        $perPage = request('per_page')?? 10;
        $search = request('search');
        $imageColors = ImageColor::when($search, function($q) use($search){
            $q->where('id', $search);
        })->paginate($perPage);


        return Common::apiResponse(true, '', $imageColors, 200);

    }

    public function store(Request $request){

        $image = null;

        if($request->has('image')){
            $image = Common::upload('images', $request->image);
        }

        $ImageColor  = ImageColor::create([
            'name' => $request->name,
            'image' => $image,
            'color'=> $request->color
        ]);

        return Common::apiResponse(true, '',  $ImageColor, 200);
    }

    public function update(Request $request, $id){


        if($request->has('image')){
            $image = Common::upload('images', $request->image);
            ImageColor::findOrFail($id)->update([
                'image' => $image
            ]);
        }

        ImageColor::findOrFail($id)->update([
            'name' => $request->name,
            'color' => $request->color
        ]);

        return Common::apiResponse(1, 'Image Color updated successfully');


    }

    public function show($id){
        $imageColor = ImageColor::findOrFail($id);

        return Common::apiResponse(true, '', $imageColor, 200);
    }

    public function destroy($id){

        ImageColor::findOrFail($id)->delete();

        return Common::apiResponse(1, 'success', null, 200);
    }
}

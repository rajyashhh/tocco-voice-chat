<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(){

        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = Banner::when($search, function($q) use($search){
            $q->where('id', $search);
        })
        ->paginate($perPage);

        return Common::apiResponse(true,'Success', $result);
    }

    public function show($id){
        $result = Banner::findOrFail($id);

        return Common::apiResponse(true,'Success', $result);
    }

    public function delete_all(Request $request){

        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        Banner::whereIn('id', $ids)->delete();

        return Common::apiResponse(true,'Success');
    }

    public function delete($id){
        $result = Banner::findOrFail($id);
        $result->delete();
        return Common::apiResponse(true,'Success');
    }

    public function store(Request $request){
        $request->validate([
            'image_url' => 'required|image',
            'expire' => 'nullable',
            'publish' => 'nullable',
            'is_active' => 'nullable'
        ]);

        $image_url = Common::upload('banners', $request->file('image_url'));

        $result = Banner::create([
            'image_url' => $image_url,
            'expire' => $request->expire,
            'publish' => $request->publish,
            'is_active' => $request->is_active
        ]);

        return Common::apiResponse(true,'Success', $result);
    }

    public function update($id, Request $request){
        $request->validate([
            'image_url' => 'nullable',
            'expire' => 'required',
            'publish' => 'required',
            'is_active' => 'required'
        ]);

        $result = Banner::findOrFail($id);
        if($request->hasFile('image_url')){
            $image_url = Common::upload('banners', $request->file('image_url'));
            $result->update([
                'image_url' => $image_url,
            ]);
        }

        $result->update([
            'expire' => $request->expire,
            'publish' => $request->publish,
            'is_active' => $request->is_active
        ]);

        return Common::apiResponse(true,'Success', $result);
    }

    public function update_is_active($id,Request $request){

        $request->validate([
            'is_active' => 'required'
        ]);

        $result = Banner::findOrFail($id);

        $result->is_active = $request->is_active;

        $result->save();

        return Common::apiResponse(true,'Success');
    }
}

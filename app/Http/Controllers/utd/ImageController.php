<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = Image::when($search,function($q)use($search){
            $q->where('id',$search);
        })
        ->paginate($perPage);

        return Common::apiResponse(true,'Success', $result);
    }

    public function show($id){
        $result = Image::findOrFail($id);

        return Common::apiResponse(true,'Success', $result);
    }

    public function store(Request $request){
        $validatedData = $request->validate([
            'name'   => 'required|string|max:255',
            'url'    => 'required|file|mimes:jpeg,png,gif,svg,webp|max:2048',
            'type'   => 'required|integer|in:0,1',
            'status' => 'boolean',
        ]);

        if ($request->hasFile('url')) {
            $validatedData['url'] = Common::upload('images', $request->file('url'));
        }

        $result = Image::create($validatedData);

        return Common::apiResponse(true,'Success', $result);
    }

    public function update($id, Request $request){
        $validatedData = $request->validate([
            'name'   => 'required|string|max:255',
            'url'    => 'nullable|file|mimes:jpeg,png,gif,svg,webp|max:2048',
            'type'   => 'required|integer|in:0,1',
            'status' => 'boolean',
        ]);

        $result = Image::findOrFail($id);
        if ($request->hasFile('url')) {
            $validatedData['url'] = Common::upload('images', $request->file('url'));
        }

        $result->update($validatedData);

        return Common::apiResponse(true,'Success');
    }

    public function update_status($id, Request $request){
        $validatedData = $request->validate([
            'status' => 'boolean',
        ]);

        $result = Image::findOrFail($id);

        $result->update($validatedData);

        return Common::apiResponse(true,'Success');
    }

    public function delete($id){
        $result = Image::findOrFail($id);
        $result->delete();
        return Common::apiResponse(true,'Success');
    }

    public function delete_all(Request $request){
        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);
        Image::whereIn('id', $ids)->delete();

        return Common::apiResponse(true,'Success');
    }
}

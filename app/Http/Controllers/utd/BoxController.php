<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Box;
use Illuminate\Http\Request;

class BoxController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = Box::when($search,function($q)use($search){
            $q->where('id', $search);
        })
        ->paginate($perPage);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function show($id){
        $result = Box::findOrFail($id);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function delete($id){
        $result = Box::findOrFail($id);
        $result->delete();
        return Common::apiResponse(true, 'Success');
    }

    public function delete_all(Request $request){
        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);
        Box::whereIn('id', $ids)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function store(Request $request){

        $validatedData = $request->validate([
            'type'          => 'required|integer|in:0,1',
            'coins'         => 'required|integer|min:0',
            'users'         => 'required|integer|min:0',
            'image'         => 'nullable|image|mimes:jpeg,png,gif,svg,webp|max:2048',
            'has_label'     => 'boolean',
            'default_label' => 'nullable|string|max:255',
            'duration'      => 'required|integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            $validatedData['image'] = Common::upload('images', $request->file('image')) ;
        }

        $result = Box::create($validatedData);

        return Common::apiResponse(true, 'Success', $result);
    }


    public function update($id, Request $request){

        $validatedData = $request->validate([
            'type'          => 'required|integer|in:0,1',
            'coins'         => 'required|integer|min:0',
            'users'         => 'required|integer|min:0',
            'image'         => 'nullable|image|mimes:jpeg,png,gif,svg,webp|max:2048',
            'has_label'     => 'boolean',
            'default_label' => 'nullable|string|max:255',
            'duration'      => 'required|integer|min:0',
        ]);

        $result = Box::findOrFail($id);
        if ($request->hasFile('image')) {
            $validatedData['image'] = $request->file('image')->store('boxes', 'public');
        }

        $result->update($validatedData);

        return Common::apiResponse(true, 'Success', $result);
    }
}

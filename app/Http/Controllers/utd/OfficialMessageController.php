<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\OfficialMessage;
use Illuminate\Http\Request;

class OfficialMessageController extends Controller
{
    public function index(){
        $search = request('search');
        $user_id = request('user_id');
        $perPage = request('per_page') ?? 10;

        $result = OfficialMessage::when($search,function($q)use($search){
            $q->where('id', $search);
        })
        ->when($user_id, function($q)use($user_id){
            $q->where('user_id', $user_id);
        })
        ->where('type',2)
        ->paginate($perPage);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function show($id){
        $result = OfficialMessage::findOrFail($id);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function delete_all(Request $request){

        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',',$request->ids);


        OfficialMessage::whereIn('id', $ids)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function delete($id){

        OfficialMessage::findOrFail($id)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function store(Request $request){
        $validatedData = $request->validate([
            'title' => 'nullable|max:255',
            'img' => 'nullable|image',
            'user_id' => 'required|integer',
            'content' => 'required|string',
            'type' => 'required|integer',
            'url' => 'nullable|url',
        ]);

        if($request->hasFile('img')){
            $image = Common::upload('images', $request->file('img'));
            $validatedData['img'] = $image;
        }

        $result = OfficialMessage::create($validatedData);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function update($id, Request $request){
        $validatedData = $request->validate([
            'title' => 'nullable|max:255',
            'img' => 'nullable|image',
            'user_id' => 'required|integer',
            'content' => 'required|string',
            'type' => 'required|integer',
            'url' => 'nullable|url',
        ]);

        $result = OfficialMessage::findOrFail($id);

        if($request->hasFile('img')){
            $image = Common::upload('images', $request->file('img'));
            $validatedData['img'] = $image;
        }

        $result->update($validatedData);

        return Common::apiResponse(true, 'Success', $result);
    }
}

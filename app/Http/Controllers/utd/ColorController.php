<?php

namespace App\Http\Controllers\utd;


use App\Helpers\Common;
use App\Models\ImageColor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\WebSetting;

class ColorController extends Controller
{

    public function index(Request $request)
    {
        $id = $request->id;
        $perPage = $request->per_page;
        $page = $request->page;
        $data = ImageColor::when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate($perPage, ['*'], 'page', $page);
        return Common::apiResponse(true, 'done', $data);
    }


    public function all(){
        $search = request('search');

        $result = Color::when($search, function($q)use($search){
            $q->where('id', $search);
        })
        ->paginate(10)
        ->through(function($color){
            return [
                'id' => $color->id,
                'color' => $color->color,
                'status' => $color->status == 0? __('main colors'):__('button colors')
            ];
        });

        return Common::apiResponse(true, 'Success', $result);
    }

    public function show($id){

        $result = Color::findOrFail($id);
        $result->status = $result->status == 0? __('main colors'):__('button colors');
        return Common::apiResponse(true, 'Success', $result);
    }

    public function store(Request $request){
        $request->validate([
            'color' => 'required',
            'status' => 'required|boolean',
        ]);

        $result = Color::create([
            'color' => $request->color,
            'status' => $request->status
        ]);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function delete($id){

        Color::findOrFail($id)->delete();

        return Common::apiResponse(true, 'Success');

    }

    public function update($id, Request $request){
        $request->validate([
            'color' => 'required',
            'status' => 'required|boolean',
        ]);

        Color::findOrFail($id)->update([
            'color' => $request->color,
            'status' => $request->status
        ]);

        return Common::apiResponse(true, 'Success');
    }

    public function delete_all(Request $request){
        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        Color::whereIn('id', $ids)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function app_setting(Request $request){

        $request->validate([
            'logo' => 'required|file|image',
            'desc' => 'required'
        ]);

        $data = WebSetting::find(1);
        if (!$data) {
            $data = new WebSetting();
        }
        if ($request->hasFile('logo')) {
            $imagePath = Common::upload('images', $request->file('logo'));
            $data->logo = $imagePath;
        }

        $data->footer_description = $request->input('desc');
        $data->save();

        return Common::apiResponse(true,'success');
    }
}

<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Ware;
use Illuminate\Http\Request;

class SpecialWareController extends Controller
{
    public function index()
    {
        $id = request('id');
        $value = request('value');
        $sort = request('sort') ?? 'asc';
        $perPage = request('per_page') ?? 10;

        $result = Ware::when($id, function ($q) use ($id) {
            $q->where('id', $id);
        })
            ->when($value, function ($q) use ($value) {
                $q->where('value', $value);
            })
            ->where('type', 25)
            ->orderBy('id', $sort)
            ->paginate($perPage);

            $getTypeTranslations = [
                4 => trans('purchase'),
                6 => trans('limited time purchase'),
            ];

            $result->getCollection()->transform(function ($item) use ( $getTypeTranslations) {
                $item->get_type = $getTypeTranslations[$item->get_type] ?? trans('Unknown Get Type');
                return $item;
            });

        return Common::apiResponse(true, 'Success', $result);
    }

    public function show($id)
    {

        $result = Ware::findOrFail($id);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function store(Request $request)
    {

        $request->validate([
            'get_type' => 'required|in:4,6',
            'value' => 'required',
            'expire' => 'required',
            'title' => 'required',
            'title_en' => 'required',
            'price' => 'required|numeric',
            'enable' => 'required',
            'level' => 'required',
            'show_img' => 'required|image',
            'img2' => 'required',
            'color' => 'required',
            'num' => 'required'
        ]);

        $show_img = Common::upload('images', $request->file('show_img'));
        $img2 = Common::upload('images', $request->file('img2'));

        $ware = Ware::create([
            'type' => 25,
            'get_type' => $request->get_type,
            'value' => $request->value,
            'expire' => $request->expire,
            'title' =>  $request->title,
            'title_en' =>  $request->title_en,
            'price' =>  $request->price,
            'enable' =>  $request->enable,
            'level' =>  $request->level,
            'color' =>  $request->color,
            'num' =>  $request->num,
            'show_img' => $show_img,
            'img2' => $img2,
        ]);

        return Common::apiResponse(true, 'Success', $ware);
    }



    public function update(Request $request, $id)
    {

        $request->validate([
            'get_type' => 'required|in:4,6',
            'value' => 'required',
            'expire' => 'required',
            'title' => 'required',
            'title_en' => 'required',
            'price' => 'required|numeric',
            'enable' => 'required',
            'level' => 'required',
            'show_img' => 'nullable|image',
            'img2' => 'nullable|image|mimes:svg',
            'color' => 'required',
            'num' => 'required'
        ]);
        $ware = Ware::where('type',25)->findOrFail($id);


        if ($request->hasFile('show_img')) {

            $show_img = Common::upload('images', $request->file('show_img'));
            $ware->update([
                'show_img' => $show_img
            ]);
        }

        if ($request->hasFile('img2')) {
            $img2 = Common::upload('images', $request->file('img2'));
            $ware->update([
                'img2' => $img2
            ]);
        }


        $ware->update([
            'get_type' => $request->get_type,
            'value' => $request->value,
            'expire' => $request->expire,
            'title' =>  $request->title,
            'title_en' =>  $request->title_en,
            'price' =>  $request->price,
            'enable' =>  $request->enable,
            'level' =>  $request->level,
            'color' =>  $request->color,
            'num' =>  $request->num,
        ]);

        return Common::apiResponse(true, 'Success', $ware);
    }


    public function update_enable(Request $request, $id){
        $request->validate([
            'enable' => 'required',
        ]);
        $ware = Ware::where('type',25)->findOrFail($id);


        $ware->update([
            'enable' =>  $request->enable,
        ]);

        return Common::apiResponse(true, 'Success', $ware);
    }

    public function delete($id){
        $ware = Ware::where('type',25)->findOrFail($id);

        $ware->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function delete_all(Request $request){
        $request->validate([
            'ids' => 'required',
        ]);

        $ids = explode(',', $request->ids);

        Ware::where('type',25)->whereIn('id', $ids)->delete();

        return Common::apiResponse(true, 'Success');

    }
}

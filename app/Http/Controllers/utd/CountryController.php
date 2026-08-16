<?php

namespace App\Http\Controllers\utd;

use App\Facades\CustomNotification;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChargeResource;
use App\Models\Agency;
use App\Models\Charge;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Achievement\Http\Services\UserAchievementService;

class CountryController extends Controller
{

    public function index(Request $request)
    {
        $id = $request->id;
        $perPage = $request->per_page;
        $page = $request->page;
        $data = Country::when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate($perPage, ['*'], 'page', $page);
        return Common::apiResponse(true, 'done', $data);
    }

    public function show($id){
        $result = Country::findOrFail($id);

        return Common::apiResponse(true, 'success', $result);
    }

    public function delete($id){
        Country::findOrFail($id)->delete();

        return Common::apiResponse(true,'Success');
    }

    public function delete_all(Request $request){
        $request->validate([
            'ids' => 'required'
        ]);


        $ids = explode(',',$request->ids);

        Country::whereIn('id', $ids)->delete();
        return Common::apiResponse(true, 'Success');
    }

    public function store(Request $request){

        $validated = $request->validate([
            'name' => 'required',
            'e_name' => 'required',
            'phone_code' => 'required',
            'flag' => 'required|image',
            'iso' => 'required',
            'status' => 'required'
        ]);

        $validated['flag'] = Common::upload('images', $request->file('flag'));

        $result = Country::create($validated);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function update($id, Request $request){

        $validated = $request->validate([
            'name' => 'required',
            'e_name' => 'required',
            'phone_code' => 'required',
            'flag' => 'nullable|image',
            'iso' => 'required',
            'status' => 'required'
        ]);

        $result = Country::findOrFail($id);

        if($request->hasFile('flag')){

            $validated['flag'] = Common::upload('images', $request->file('flag'));
        }

        $result = $result->update($validated);

        return Common::apiResponse(true, 'Success');
    }

    public function update_status($id, Request $request){
        $request->validate([
            'status' => 'required'
        ]);

        $result = Country::findOrFail($id);

        $result->update([
            'status' => $request->status
        ]);

        return Common::apiResponse(true, 'Success');
    }
}

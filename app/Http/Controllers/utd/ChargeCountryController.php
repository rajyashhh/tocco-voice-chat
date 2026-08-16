<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Models\User;
use App\Models\Charge;
use App\Helpers\Common;
use App\Models\CoinLog;
use App\Models\Country;
use App\Models\CoinGameUser;
use Illuminate\Http\Request;
use App\Models\UserLuckyGift;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\SalaryTransaction\Entities\ChargeCountry;

class ChargeCountryController extends Controller
{

    public function all(Request $request)
    {
        $id = $request->id;
        $page = $request->page;
        $perPage = $request->per_page;
        try {
            $data = ChargeCountry::with('country')->when(isset($id), function ($query) use ($id) {
                $query->where('id', $id);
            })->paginate($perPage, ['*'], 'page', $page);
            return Common::apiResponse(true, 'success', $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'country_id' => 'required|integer|unique:charge_countries,country_id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            ChargeCountry::create($request->all());
            return Common::apiResponse(1, 'created successfully',  200);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
    }

    public function show($id)
    {
        try {
            $data = ChargeCountry::findOrFail($id);
            return Common::apiResponse(true, 'success', $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [

            'country_id' => 'required|integer|unique:charge_countries,country_id,' . $id,
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            ChargeCountry::where("id", $id)->update($request->all());
            return Common::apiResponse(true, 'update successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function destroy($id)
    {
        try {
            ChargeCountry::where("id", $id)->delete();
            return Common::apiResponse(true, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function country(Request $request)
    {
        $id = $request->id;
        $page = $request->page;
        $perPage = $request->per_page;
        $search = $request->search;
        $countries = Country::query()->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->when(isset($search), function ($query) use ($search) {
            $query->where('name', 'like', "%$search%")->orWhere('e_name', 'like', "%$search%");
        })->paginate($perPage, ['*'], 'page', $page);
        return Common::apiResponse(true, '', $countries);
    }
}

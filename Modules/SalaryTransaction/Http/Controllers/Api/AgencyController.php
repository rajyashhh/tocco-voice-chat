<?php

namespace Modules\SalaryTransaction\Http\Controllers\Api;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AgencyResource;
use App\Models\Agency;
use App\Models\ShippingAgency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\SalaryTransaction\Transformers\FilterAgancyResource;
use Modules\SalaryTransaction\Transformers\FilterAgencyMangerResource;

class AgencyController extends Controller
{
    public function get_info(?ShippingAgency $agency)
    {

        $user = Auth::user();


        if (is_null($agency->id)){
            $agency = ShippingAgency::query()->with("countries", 'AgencypaymentGateways')->whereAppOwnerId(@$user->id)->first();
        }
        
        if (is_null($agency)) {
            return Common::apiResponse(1, __("api_responses.agency"), []);
        }
        $result = new AgencyResource($agency);

        return Common::apiResponse(1, '', $result);
    }

    public function update_info(Request $request)
    {
        // $agency = Agency::query()->find($request->agency_id);
        $agency = ShippingAgency::query()->find($request->agency_id);
        if (!$agency) {
            $agency = Agency::query()->find($request->agency_id);
            }
        if (!$agency) {

            return Common::apiResponse(1, __("api_responses.agency_not_found"), []);
        }
        if ($request->has('phone')) {
            $agency->phone = $request->phone;
        }

        if ($request->has('image') && $request->file('image')) {
            $agency->img = Common::upload('agencies', $request->file('image'));
        }
        if ($request->has('name')) {
            $agency->name = $request->name;
        }
        if ($request->has('app_owner_id') && $request->app_owner_id != null && $request->app_owner_id != 0 ) {
            $agency->app_owner_id = $request->app_owner_id;
        }
        if ($request->has('paymentGateways')) {
            $payments = explode(',',$request->paymentGateways);
            $agency->AgencypaymentGateways()->sync($payments);
        }

        if ($request->has('countries')) {
            $countries = explode(',',$request->countries);
            $agency->Countries()->sync($countries);
        }
        $agency->save();
        $result = new AgencyResource($agency);

        return Common::apiResponse(1, __('admin.update_succeeded'), $result);
    }

}

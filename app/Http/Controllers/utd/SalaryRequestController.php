<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\SalaryRequestResource;
use Modules\SalaryTransaction\Entities\SalaryRequest;

class SalaryRequestController extends Controller
{

    public function all(Request $request)
    {
        $id = $request->id;
        $page = $request->page;
        $perPage = $request->per_page;
        $status = $request->status;
        $agencyId = $request->agency_id;
        $agencyOwnerId = $request->agency_owner_id;
        try {
            $data = SalaryRequest::when(isset($id), function ($query) use ($id) {
                $query->where('id', $id);
            })->when(isset($status), function ($query) use ($status) {
                $query->where('status', $status);
            })->when(isset($agencyId), function ($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->when(isset($agencyOwnerId), function ($query) use ($agencyOwnerId) {
                $query->where('agency_owner_id', $agencyOwnerId);
            })->with('agency', 'agencyOwner', 'host', 'payment_gateway', 'country')->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);
            return Common::apiResponse(true, 'success', SalaryRequestResource::collection($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agency_id' => 'required|integer|exists:agencies,id',
            'host_id' => 'required|integer|exists:users,id',
            'payment_gateway_id' => 'required|integer|exists:payment_gateways,id',
            'country_id' => 'required|integer|exists:countries,id',
            'agency_owner_id' => 'required|integer|exists:users,id',
            'status' => 'required|integer',
            'usd' => 'required|integer',
            'coins' => 'required|integer',
            'bill_image' => 'nullable',
            'host_check' => 'nullable|boolean',
            'note' => 'nullable',
            'request_admin_status' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        $data = [
            'agency_id' => $request->agency_id,
            'host_id' => $request->host_id,
            'payment_gateway_id' => $request->payment_gateway_id,
            'country_id' => $request->country_id,
            'agency_owner_id' => $request->agency_owner_id,
            'status' => $request->status,
            'usd' => $request->usd,
            'coins' => $request->coins,
            'host_check' => $request->host_check,
            'note' => $request->note,
            'request_admin_status' => $request->request_admin_status,
        ];
        if ($request->hasFile('bill_image')) {
            $data['bill_image'] = Common::upload('images', $request->file('bill_image'));
        }


        try {
            SalaryRequest::create($data);
            return Common::apiResponse(1, 'created successfully',  200);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
    }
}

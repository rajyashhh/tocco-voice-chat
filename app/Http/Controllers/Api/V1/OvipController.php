<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Tik\Services\OvipService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\V1\OVipPrivilegesResource;




class OvipController extends Controller
{

    public function __construct(private OvipService $ovipService) {}

    public function index()
    {
        $data = $this->ovipService->index();
        return Common::apiResponse(1, '', $data);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'level'       => 'required|numeric|unique:o_vips,level',
            'price'        => 'required|numeric',
            'exp'        => 'required|numeric',
            'expire'        => 'nullable|numeric',
            'name'         => 'nullable|string|max:255',
            'privileges'   => 'nullable',
            'image'          => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->ovipService->create($request);
            return Common::apiResponse(1, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
           'ovip_id' => 'required|integer|exists:o_vips,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }
        $data = $this->ovipService->show($request->ovip_id);
        return Common::apiResponse(1, '', $data);
    }
    public function showWithAllPrivileges(Request $request)
    {
        $data = $this->ovipService->showWithAllPrivileges($request->ovip_id);
        \request()->vipPrivileges = $data['all_privileges'];
        return Common::apiResponse(1, '', new OVipPrivilegesResource($data['o_vips']));
    }

    public function update(Request $request)
    {
        $id = $request->o_vip_id;
        $validator = Validator::make($request->all(), [
            'level' =>  'required|unique:o_vips,level,'.$id,
            'o_vip_id' => 'required|integer|exists:o_vips,id',
            'price'        => 'required|numeric',
            'exp'        => 'required|numeric',
            'expire'        => 'nullable|numeric',
            'name'         => 'nullable|string|max:255',
            'privileges'   => 'nullable',
            'image'          => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->ovipService->update($request);
            return Common::apiResponse(1, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function allVIP(Request $request)
    {
        $data = $this->ovipService->allVIP($request->search);
        return Common::apiResponse(1, '', $data);
    }
}

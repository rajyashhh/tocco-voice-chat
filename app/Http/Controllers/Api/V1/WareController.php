<?php

namespace App\Http\Controllers\Api\V1;

use Exception;

use App\Helpers\Common;
use Illuminate\Http\Request;

use App\Tik\Services\WareService;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class WareController extends Controller
{
    public function __construct(private WareService $wareService) {}


    public function index(Request $request)
    {
        $wares = $this->wareService->index($request->page, $request->per_page);
        return Common::apiResponse(1, '',  $wares);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'nullable|string|max:255',
            'name_en'         => 'nullable|string|max:255',
            'type'         => 'required|numeric',
            'level'         => 'nullable|numeric',
            'price'         => 'required|numeric',
            'img2'          => 'required|mimes:jpeg,png,jpg,gif,svg,mp4,svga',
            'show_img'          => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'image_type'         => 'required|string|max:255',
            'color'         => 'nullable',
            'is_active_for_vip'         => 'nullable|boolean',
            'enable'         => 'nullable|boolean',
            'get_type'         => 'required|numeric',
            'title'         => 'nullable|string|max:255',
            'title_en'         => 'nullable|string|max:255',
            'exp'         => 'nullable|numeric',
            'expire'  => 'required|numeric',
            'num'  => 'nullable|numeric',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->wareService->create($request);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, 'created successfully');
    }

    public function storeList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'nullable|string|max:255',
            'name_en'         => 'nullable|string|max:255',
            'type'         => 'required|numeric',
            'level'         => 'nullable|numeric',
            'price'         => 'required|numeric',
            'img2'          => 'required',
            'show_img'          => 'required',
            'image_type'         => 'required|string|max:255',
            'color'         => 'nullable',
            'is_active_for_vip'         => 'nullable|boolean',
            'enable'         => 'nullable|boolean',
            'get_type'         => 'required|numeric',
            'title'         => 'nullable|string|max:255',
            'title_en'         => 'nullable|string|max:255',
            'exp'         => 'nullable|numeric',
            'expire'  => 'required|numeric',
            'num'  => 'nullable|numeric',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->wareService->create($request);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, 'created successfully');
    }



    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ware_id' => 'required|integer|exists:wares,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }
        $data = $this->wareService->show($request->ware_id);
        return Common::apiResponse(1, '', $data);
    }


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ware_id'  => 'required|integer|exists:wares,id',
            'name'         => 'nullable|string|max:255',
            'name_en'         => 'nullable|string|max:255',
            'type'         => 'required',
            'level'         => 'nullable|numeric',
            'price'         => 'required|numeric',
            'img2'          => 'required|mimes:jpeg,png,jpg,gif,svg,mp4,svga',
            'show_img'          => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'image_type'         => 'required|string|max:255',
            'color'         => 'nullable',
            'is_active_for_vip'         => 'nullable|boolean',
            'enable'         => 'nullable|boolean',
            'get_type'         => 'required|numeric',
            'title'         => 'nullable|string|max:255',
            'title_en'         => 'nullable|string|max:255',
            'exp'         => 'nullable|numeric',
            'expire'  => 'required|numeric',
            'num'  => 'nullable|numeric',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->wareService->update($request);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, 'updated successfully');
    }

    public function enableSwitchUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enable' => 'required|boolean',
            'ware_id' => 'required|integer|exists:wares,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        $value =   $this->wareService->updateSwitch($request->enable, $request->ware_id, 'enable');
        if (!$value)  return Common::apiResponse(1, 'failed');
        return Common::apiResponse(1, 'updated successfully');
    }

    public function typeWare(Request $request)
    {
        return translate(TYPE_WARE);
    }

    public function getTypeWare(Request $request)
    {
        return translate(GET_TYPE_WARE);
    }

    public function profile_frame_wares(Request $request)
    {
        $wares = $this->wareService->profile_frame_wares($request->page, $request->per_page);
    
        $wares->each(function ($ware) {
            $ware->half_image_profile = (bool) $ware->half_image_profile;
            $ware->makeHidden(['image_type1', 'profile_frame_type']);
        });
        return Common::apiResponse(1, '',  $wares);
    }
    
}

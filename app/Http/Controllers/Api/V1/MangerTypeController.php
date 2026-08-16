<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Tik\Services\MangerTypeService;
use Illuminate\Support\Facades\Validator;



class MangerTypeController extends Controller
{

    public function __construct(private MangerTypeService $mangerTypeService) {}

    public function index()
    {
        $data = $this->mangerTypeService->index();
        return Common::apiResponse(1, '', $data);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name_en'         => 'required|string|max:255',
            'name_ar'         => 'required|string|max:255',
            'description_en'         => 'required',
            'description_ar'         => 'required',
            'image'          => 'nullable|mimes:jpeg,png,jpg,gif,svg',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->mangerTypeService->create($request);
            return Common::apiResponse(1, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'manger_type_id' => 'required|integer|exists:manger_types,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }
        $data = $this->mangerTypeService->show($request->manger_type_id);
        return Common::apiResponse(1, '', $data);
    }

    public function update(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'manger_type_id' => 'required|integer|exists:manger_types,id',
            'name_en'         => 'required|string|max:255',
            'name_ar'         => 'required|string|max:255',
            'description_en'         => 'required',
            'description_ar'         => 'required',
            'image'          => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->mangerTypeService->update($request);
            return Common::apiResponse(1, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}

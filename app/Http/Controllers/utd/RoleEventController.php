<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Enums\TypeGeneralRole;
use App\Http\Controllers\Controller;
use Modules\Events\Entities\GeneralRole;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RoleEventController extends Controller
{


    public function all(Request $request)
    {
        $id = $request->id;
        $perPage = $request->per_page;
        $page = $request->page;
        $data = GeneralRole::when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate($perPage, ['*'], 'page', $page);;
        return Common::apiResponse(true, 'done', $data);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|unique:general_roles,type',
            'url' => 'required',
            'sub_type' => 'nullable|string',
            'desc_en' => 'required',
            'desc_ar' => 'required',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            GeneralRole::create($request->all());
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => [
                'required',
                'string',
                Rule::unique('general_roles', 'type')->ignore($id)
            ],
            'url' => 'required',
            'sub_type' => 'nullable|string',
            'desc_en' => 'required',
            'desc_ar' => 'required',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            GeneralRole::where('id', $id)->update($request->all());
            return Common::apiResponse(true, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show($id)
    {
        $data = GeneralRole::findOrFail($id);
        return Common::apiResponse(true, 'done', $data);
    }

    public function destroy($id)
    {
        try {
            GeneralRole::where('id', $id)->delete();
            return Common::apiResponse(true, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function details(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'type' => 'required|string',

        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $data = GeneralRole::where('type', $request->type)->first();
            return Common::apiResponse(true, 'done', $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function types()
    {
        $data = TypeGeneralRole::getTranslatedOptions();
        return Common::apiResponse(true, 'done', $data);
    }
}

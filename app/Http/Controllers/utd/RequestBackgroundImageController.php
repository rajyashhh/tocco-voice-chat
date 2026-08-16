<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Tik\Services\RequestBackgroundImagService;

class RequestBackgroundImageController extends Controller
{

    public function __construct(private RequestBackgroundImagService $requestBackgroundImagService) {}

    public function all(Request $request)
    {
        $data = $this->requestBackgroundImagService->index($request->id, $request->per_page, $request->page);
        return Common::apiResponse(true, 'done', $data);
    }

    public function show($id)
    {
        try {
            $data = $this->requestBackgroundImagService->show($id);
            return Common::apiResponse(1, 'done',  $data, 200);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'owner_room_id' => 'required|integer|exists:users,id',
            'img' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|integer',
            'expair' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }


        try {
            $this->requestBackgroundImagService->createDash($request);
            return Common::apiResponse(1, 'done', 'created successfully', 200);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [

            'owner_room_id' => 'required|integer|exists:users,id',
            // 'img' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'img' => 'nullable',
            'status' => 'required|integer',
            'expair' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->requestBackgroundImagService->update($id, $request);
            return Common::apiResponse(1, 'done', 'updated successfully', 200);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
    }

    public function destroy($id)
    {
        try {
            $result =$this->requestBackgroundImagService->delete($id);
            if(!$result)return Common::apiResponse(false, 'Item Not found');
            return Common::apiResponse(true, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}

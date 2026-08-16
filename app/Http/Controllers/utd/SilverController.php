<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Tik\Services\SilverService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SilverController extends Controller
{

    public function __construct(private SilverService $silverService) {}

    public function all(Request $request)
    {
        $data = $this->silverService->all($request->id, $request->per_page, $request->page);
        return Common::apiResponse(true, 'done', $data);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coin' => 'required|integer',
            'silver' => 'required|integer',
            'sort' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->silverService->create($request);
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coin' => 'required|integer',
            'silver' => 'required|integer',
            'sort' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->silverService->update($id, $request);
            return Common::apiResponse(true, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show($id)
    {
        try {
            $data = $this->silverService->show($id);
            return Common::apiResponse(true, 'done', $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function destroy($id)
    {
        try {
            $this->silverService->delete($id);
            return Common::apiResponse(true, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}

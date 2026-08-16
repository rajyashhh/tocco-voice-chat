<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\UsersWallet\Services\ExchangeService;

class ExchangeController extends Controller
{

    public function __construct(private ExchangeService $exchangeService) {}

    public function all(Request $request)
    {
        $data = $this->exchangeService->all($request->id, $request->per_page, $request->page);
        return Common::apiResponse(true, 'done', $data);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'diamonds' => 'required|integer',
            'value' => 'required|integer',
            'type' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->exchangeService->createDashboard($request);
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'diamonds' => 'required|integer',
            'value' => 'required|integer',
            'type' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->exchangeService->update($id, $request);
            return Common::apiResponse(true, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show($id)
    {
        $data = $this->exchangeService->show($id);
        return Common::apiResponse(true, 'done', $data);
    }

    public function destroy($id)
    {
        try {
            $this->exchangeService->delete($id);
            return Common::apiResponse(true, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

   
}

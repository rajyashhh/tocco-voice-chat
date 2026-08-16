<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Tik\Services\CoreWalletsService;
use Illuminate\Support\Facades\Validator;



class CoreWalletsController extends Controller
{

    public function __construct(private CoreWalletsService $coreWalletsService) {}
    public function index(Request $request)
    {
        $data = $this->coreWalletsService->index($request->id, $request->per_page, $request->page);
        return Common::apiResponse(1, '', $data);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'coins'         => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->coreWalletsService->create($request);
            return Common::apiResponse(1, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "core_wallet_id" => 'required|integer|exists:core_wallets,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }
        $data = $this->coreWalletsService->show($request->core_wallet_id);
        return Common::apiResponse(1, '', $data);
    }

    public function update(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'coins'         => 'required|numeric',
            "core_wallet_id" => 'required|integer|exists:core_wallets,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->coreWalletsService->update($request);
            return Common::apiResponse(1, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function delete($id)
    {
        try {
            $this->coreWalletsService->delete($id);
            return Common::apiResponse(1, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use Exception;

use App\Helpers\Common;

use App\Rules\ValidUsd;
use Illuminate\Http\Request;

use App\Services\UserService;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\V1\TrashedUserResource;


class TrashedUserController extends Controller
{
    public function __construct(private UserService $userService) {}


    public function trashedAccount(Request $request)
    {
        $trashed = $this->userService->trashedAccount($request->per_page, $request->Page, $request->uuid,$request->id);
        return Common::apiResponse(true, 'success', TrashedUserResource::collection($trashed));
    }

    public function restore($id)
    {
        try {
            $this->userService->restoreAccount($id);
            return Common::apiResponse(1, 'restore successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function softDelete($id)
    {
        try {
            $this->userService->delete($id);
            return Common::apiResponse(1, 'delete successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}

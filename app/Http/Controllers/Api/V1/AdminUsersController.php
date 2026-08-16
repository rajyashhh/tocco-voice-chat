<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Tik\Services\AdminUsersService;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\V1\AdminUsersResource;
use App\Http\Resources\Api\V1\AdminUserShowResource;

class AdminUsersController extends Controller
{
    public function __construct(private AdminUsersService $adminUsersService) {}

    public function index(Request $request)
    {
        $data = $this->adminUsersService->index($request->id, $request->per_page, $request->page);
        return Common::apiResponse(1, '', AdminUsersResource::collection($data));
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'username'       => 'required|unique:admin_users,username',
            'name'           => 'required|string',
            'password'       => 'required',
            'app_id'         => 'required|unique:admin_users,app_id|exists:users,id',
            'image'         => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->adminUsersService->create($request);
            return Common::apiResponse(1, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'admin_user_id'  => 'required|integer',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }
        $data = $this->adminUsersService->show($request->admin_user_id);
        return Common::apiResponse(1, '', AdminUserShowResource::collection($data));
    }

    public function showUserAgency($agencyId, Request $request)
    {

        $data = $this->adminUsersService->showUserAgency($agencyId, $request->per_pageyyy, $request->page);
        return Common::apiResponse(1, '', $data);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Config;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\V1\RoleResource;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Api\V1\PermissionResource;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $id = $request->id;
        $perPage = $request->per_page;
        $page = $request->page;
        $permissionModel = config('admin.database.permissions_model');

        $permissions = $permissionModel::when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate($perPage, ['*'], 'page', $page);


        return Common::apiResponse(1, '', PermissionResource::collection($permissions));
    }

    public function show($id)
    {
        $permissionModel = config('admin.database.permissions_model');

        $permissions = $permissionModel::where('id', $id)->first();

        return Common::apiResponse(1, '', $permissions);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'slug' => 'required|string|max:255',
            'name' => 'required|string|max:255|unique:admin_permissions,name',
            'http_method' => 'required',
            'http_path' => 'required',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        $permissionModel = config('admin.database.permissions_model');


        $permission = new $permissionModel();
        $permission->slug = $request->input('slug');
        $permission->name = $request->input('name');
        $permission->http_method = $request->input('http_method');
        $permission->http_path = $request->input('http_path');
        $permission->save();
        \App\Models\Admin::flushAllCachedPermissions();

        return Common::apiResponse(1, ' created successfully');
    }


    public function update(Request $request, $id)
    {
        try {
            $permissionModel = config('admin.database.permissions_model');
            $permission = $permissionModel::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'slug' => 'required|string|max:255',
                'name' => 'required|string|max:255|unique:admin_roles,name',
                'http_method' => 'nullable',
                'http_path' => 'nullable',

            ]);
            if ($validator->fails()) {
                return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
            }



            $permission->slug = $request->input('slug');
            $permission->name = $request->input('name');
            $permission->http_method = $request->input('http_method');
            $permission->http_path = $request->input('http_path');
            $permission->save();
            \App\Models\Admin::flushAllCachedPermissions();

            return Common::apiResponse(1, ' updated successfully');
        } catch (ValidationException $e) {
            return Common::apiResponse(0, 'Validation failed', $e->errors());
        } catch (\Throwable $th) {
            return Common::apiResponse(0, 'An unexpected error occurred');
        }
    }

    public function destroy($id)
    {
        try {
            $permissionModel = config('admin.database.permissions_model');
            $permission = $permissionModel::findOrFail($id);
            $permission->delete();
            \App\Models\Admin::flushAllCachedPermissions();
            return Common::apiResponse(1, 'Role deleted successfully');
        } catch (ValidationException $e) {

            return Common::apiResponse(0, 'Validation failed', $e->errors());
        }
    }

    protected function getHttpMethodsOptions()
    {
        $model = config('admin.database.permissions_model');

        return array_combine($model::$httpMethods, $model::$httpMethods);
    }
}

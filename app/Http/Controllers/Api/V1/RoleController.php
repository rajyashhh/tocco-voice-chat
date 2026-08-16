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

class RoleController extends Controller
{
    public function index()
    {
        $roleModel = config('admin.database.roles_model');

        $roles = $roleModel::with('permissions')
            ->select('id', 'slug', 'name', 'desc_en', 'desc_ar', 'created_at', 'updated_at')
            ->get();

        return Common::apiResponse(1, '', RoleResource::collection($roles));
    }

    public function show($id)
    {
        $roleModel = config('admin.database.roles_model');

        $roles = $roleModel::with('permissions:name')
            ->select('id', 'slug', 'name', 'desc_en', 'desc_ar', 'created_at', 'updated_at')
            ->where('id', $id)->first();

        return Common::apiResponse(1, '', $roles);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'slug' => 'required|string|max:255',
            'name' => 'required|string|max:255|unique:admin_roles,name',
            'desc_en' => 'nullable',
            'desc_ar' => 'nullable',
            'permissions' => 'required',
        ]);
        if ($validator->fails()) {

            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        $roleModel = config('admin.database.roles_model');

        $role = new $roleModel();
        $role->slug = $request->input('slug');
        $role->name = $request->input('name');
        $role->desc_en = $request->input('desc_en');
        $role->desc_ar = $request->input('desc_ar');
        $role->save();

        if (is_string($request->permissions)) {
            $permissions = json_decode($request->permissions, true);
        }

        $role->permissions()->sync($permissions);
        \App\Models\Admin::flushAllCachedPermissions();
        return Common::apiResponse(1, 'Role created successfully', $role);
    }

    public function permissions()
    {
        $permissionModel = config('admin.database.permissions_model');
        $permissionsPaginated = $permissionModel::paginate(50);
        $permissions = $permissionsPaginated->getCollection()->groupBy(function ($item) {
            return $item->category ?? 'other';
        });
        return Common::apiResponse(1, '', $permissions);
    }

    public function update(Request $request, $id)
    {
        try {
            $roleModel = config('admin.database.roles_model');
            $role = $roleModel::findOrFail($id);

            $validated = $request->validate([
                'slug' => 'required|string|max:255',
                'name' => 'required|string|max:255|unique:admin_roles,name,' . $id,
                'desc_en' => 'nullable',
                'desc_ar' => 'nullable',
                'permissions' => 'required',
                // 'permissions.*' => 'exists:admin_permissions,id',
            ]);



            $role->slug = $request->input('slug');
            $role->name = $request->input('name');
            $role->desc_en = $request->input('desc_en');
            $role->desc_ar = $request->input('desc_ar');
            $role->save();

            if (is_string($request->permissions)) {
                $permissions = json_decode($request->permissions, true);
            }
            $role->permissions()->sync($permissions);
            \App\Models\Admin::flushAllCachedPermissions();

            return Common::apiResponse(1, 'Role updated successfully', $role);
        } catch (ValidationException $e) {
            return Common::apiResponse(0, 'Validation failed', $e->errors());
        } catch (\Throwable $th) {
            return Common::apiResponse(0, 'An unexpected error occurred');
        }
    }

    public function destroy($id)
    {
        $roleModel = config('admin.database.roles_model');
        $role = $roleModel::findOrFail($id);

        if (in_array($role->slug, ['administrator', 'admin', 'developer', 'agency', 'charger'])) {
            return response()->json([
                'message' => 'You cannot delete this role.',
            ], 403); // 403 Forbidden
        }

        $role->delete();
        \App\Models\Admin::flushAllCachedPermissions();
        return Common::apiResponse(1, 'Role deleted successfully');
    }

    // public function permissionsCategory()
    // {
    //     $permissionModel = config('admin.database.permissions_model');
    //     $permissionsPaginated = $permissionModel::groupBy(function ($item) {
    //         return $item->category ?? 'other';
    //     })->paginate(50);
    //     // $permissions = $permissionsPaginated->getCollection()->groupBy(function ($item) {
    //     //     return $item->category ?? 'other';
    //     // });
    //     return Common::apiResponse(1, '', $permissionsPaginated);
    // }

    public function permissionsCategory()
    {
        $permissionModel = config('admin.database.permissions_model');

        // Fetch all permissions first and then group by category
        $permissions = $permissionModel::all();

        // Group the permissions by category
        $permissionsGrouped = $permissions->groupBy(function ($item) {
            return $item->category ?? 'other';
        });

        // Prepare the response to match your desired format
        $formattedPermissions = [];
        foreach ($permissionsGrouped as $category => $items) {
            $formattedPermissions[__($category)] = PermissionResource::collection($items);
        }

        return Common::apiResponse(1, '', $formattedPermissions, 200);
    }

    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->toJson()], 422);
        }

        $roleId = $request->role_id;
        $password = \Str::random(12);
        $values = [
            'username' => \Str::random(9),
            'password' => $password,
        ];
        $admin = \App\Models\Admin::create([
            ...$values,
            'name' => \Str::random(9),
            'is_preview' => true,
            'password' => bcrypt($password)
        ]);

        $admin->roles()->sync(['role_id' => $roleId]);
        \App\Models\Admin::forgetCachedPermissionsFor($admin->id);

        return response()->json(['url' =>  url('/preview/admin/login') . '?token=' . $admin->id]);
    }
}

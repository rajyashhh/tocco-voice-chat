<?php

namespace App\Admin\Controllers;

use App\Models\Role;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\Permission;
use Illuminate\Support\Str;
use App\Enums\PermissionType;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;

class SuperRoleController extends MainController
{
    public $permission_name = 'super-roles';
    /**
     * {@inheritdoc}
     */
    protected function title()
    {
        return trans('Roles');
    }

    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            \Encore\Admin\Auth\Permission::check('edit-' . $this->permission_name);
        }

        $permissionType =  'country';
        $permissions = Permission::whereHas('permissionTypes', function ($q) use ($permissionType) {
            $q->where('type', $permissionType);
        })->get();

        $superAdminRole = Role::where('slug', 'country')->first();
        $selectedPermissions =   $superAdminRole->permissions->pluck('id')->toArray() ?? [];

        $areaPermissionType =  'region';
        // load permissions based on selected type
        $areaPermissions = Permission::whereHas('permissionTypes', function ($q) use ($areaPermissionType) {
            $q->where('type', $areaPermissionType);
        })->get();
        $areaManagerRole = Role::where('slug', 'region')->first();
        $areaSelectedPermissions =   $areaManagerRole->permissions->pluck('id')->toArray() ?? [];

        return parent::index($content
            ->header(__('Roles'))
            ->description('   ')
            ->body(view('admin.super-Permission-tabs', compact(['permissions', 'areaManagerRole', 'superAdminRole', 'permissionType', 'selectedPermissions', 'areaSelectedPermissions', 'areaPermissions', 'areaPermissionType']))));
    }





    public function updatePermissionRole(Request $request)
    {
        if (!Admin::user()->can('*')) {
            \Encore\Admin\Auth\Permission::check('edit-' . $this->permission_name);
        }

        $permission = request('permissions');
        $id = $request->role_id;

        $allowedSlugs = ['country' => 'country', 'region' => 'region'];
        $role = Role::whereIn('slug', array_keys($allowedSlugs))->find((int) $id);
        if (!$role) {
            abort(403, 'Unauthorized access');
        }

        // Whitelist: keep only permission IDs that actually belong to this role's
        // scope (same permissionTypes filter as index()), and never allow the
        // global '*' permission (id=1) to be attached to a scoped role.
        $permissionType = $allowedSlugs[$role->slug];
        $allowedPermissionIds = Permission::whereHas('permissionTypes', function ($q) use ($permissionType) {
            $q->where('type', $permissionType);
        })->where('slug', '!=', '*')->pluck('id')->all();

        $requestedIds = array_map('intval', (array) $permission);
        $safePermissionIds = array_values(array_intersect($requestedIds, $allowedPermissionIds));

        $this->syncRolePermissions($safePermissionIds, $role->id);

        admin_toastr(__('Updated successfully'), 'success');
        return back();
    }

    protected function syncRolePermissions($permissionString, $id)
    {


        // Sync with the role
        $role = Role::find($id);
        if ($role) {
            $role->permissions()->sync($permissionString);
            \App\Models\Admin::flushAllCachedPermissions();
        }
    }
}

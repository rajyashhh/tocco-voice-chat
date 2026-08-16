<?php

namespace Modules\Region\Http\Controllers;

use App\Models\Role;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\Permission;
use Illuminate\Support\Str;
use App\Enums\PermissionType;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Admin\Controllers\MainController;
use Modules\RoleRewards\Actions\DeleteRole;
use Encore\Admin\Auth\Permission as chPermission;

class RoleController extends MainController
{
    public $permission_name = 'roles';

    protected function title()
    {
        return trans('Roles');
    }

    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            chPermission::check('browse-' . $this->permission_name);
        }

        return parent::index($content
            ->title(__('Roles'))
            ->body($this->grid()));
    }

    public function edit($id, Content $content)
    {
        $roleAuthId = Common::getRoleAuthId(auth()->id());
        $role = Role::where('id', $id)
            ->where('admin_id', $roleAuthId)
            ->first();

        if (! $role) {
            abort(403, __('You do not have permission to edit this role.'));
        }

        return  parent::edit($id, $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->form($id)->edit($id)));
    }
    public function create(Content $content)
    {
        if (!Admin::user()->can('*')) {
            chPermission::check('create-' . $this->permission_name);
        }
        return parent::create($content
            ->title($this->title())
            ->description($this->description['create'] ?? trans('admin.create'))
            ->body($this->form()));
    }

    public function store()
    {
        $result = $this->form()->store();

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        admin_toastr(__('Save succeeded !'), 'success');
        return redirect()->back();
    }
    public function show($id, Content $content)
    {
        if (!Admin::user()->can('*')) {
            chPermission::check('browse-' . $this->permission_name);
        }
        return parent::show($id, $content
            ->title(trans(__('Roles')))
            ->body($this->detail($id)));
    }
    public function update($id)
    {
        $result = $this->form()->update($id);

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        return redirect()->back();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        \Admin::js('js/admin/preview.js');
        $roleModel = config('admin.database.roles_model');
        $roleAuthId = Common::getRoleAuthId(auth()->id());
  
        $grid = new Grid(new $roleModel());

        $grid->model()->where('admin_id', $roleAuthId);
        $grid->column('id', 'ID')->sortable();
        $grid->column('slug', trans('admin.slug'));

        $grid->column('name', trans('admin.name'));

        // $grid->column('preview', trans('admin.preview'))->display(function () {
        //     $id = $this->id; // Assuming 'id' is the record ID field
        //     return '<a href="javascript:void(0);" onclick="openPreview(' . $id . ')">
        //         <i class="fa fa-eye"></i>
        //     </a>';
        // });

        // $grid->column('rewards', __('Rewards'))->display(function () {
        //     $url = admin_url("role-rewards/{$this->id}");
        //     return '<a href="' . $url . '" class="btn btn-sm btn-info">
        //                 <i class="fa fa-gift"></i> ' . __('rewards') . '
        //             </a>';
        // });
        // $grid->column('permissions', trans('admin.permission'))->pluck('name')->take(7)->label();
        $grid->column('permissions', trans('admin.permission'))->display(function ($permissions) {
            return collect($permissions)->pluck('name')->take(7)->map(function ($name) {
                return __($name);
            });
        })->label();
        $grid->column('created_at', trans('admin.created_at'));
        $grid->column('updated_at', trans('admin.updated_at'));




        $grid->actions(function (Grid\Displayers\Actions $actions) {
            // $protectedSlugs = ['administrator', 'admin', 'developer', 'agency', 'charger'];

            // if (in_array($actions->row->slug, $protectedSlugs)) {
            //     $actions->disableDelete(); 
            // } else {
            $actions->disableDelete();
            $actions->add(new DeleteRole());
            // }
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->batch(function (Grid\Tools\BatchActions $actions) {
                $actions->disableDelete();
            });
        });




        $grid->disableExport();
         $this->extendGrid($grid);
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        $roleModel = config('admin.database.roles_model');

        $show = new Show($roleModel::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('slug', trans('admin.slug'));
        $show->field('name', trans('admin.name'));
        $show->field('permissions', trans('admin.permissions'))->as(function ($permission) {
            return $permission->pluck('name');
        })->label();
        $show->field('created_at', trans('admin.created_at'));
        $show->field('updated_at', trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */

    public function form($id = null)
    {
        $permissionModel = config('admin.database.permissions_model');
        $permissions = Permission::with('permissionTypes')
            ->whereHas('permissionTypes', fn($q) => $q->where('type', PermissionType::AREA_MANAGER->value))
            ->get();
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $roleModel());
        $this->disableFormTools($form);


        $form->text('name', trans('role name'))
            ->rules(function ($form) {
                // Get the record ID if editing, otherwise null
                $id = $form->model()?->id ?? null;

                // Get the type from request or from existing model when editing
                $type = PermissionType::AREA_MANAGER->value ?? $form->model()?->type;

                // Default to empty string if not found (avoids SQL issues)
                $type = $type ?? '';

                // Build unique rule with type condition
                return "required|unique:admin_roles,name," . ($id ?? 'NULL') . ",id,type," . $type;
            });


        $form->html(view('admin.area-manger-permission', [
            'permissions' => $permissions,

            'selectedPermissions' => $id != null ? Role::where('id', $id)->first()->permissions->pluck('id')->toArray() : [],
        ])->render());

        $form->text('desc_en', __('Description en'));
        $form->text('desc_ar', __('Description ar'));
        $form->image('image', __('Image'))->help('');

        $form->saving(function (Form $form) {
            $roleAuthId = Common::getRoleAuthId(auth()->id());

            $form->ignore('permissions');
            $form->model()->admin_id =  $roleAuthId;
            $form->model()->type = PermissionType::AREA_MANAGER->value;
            // Automatically generate slug from name *before saving*
            $form->model()->slug = Str::slug($form->name . '-' . PermissionType::AREA_MANAGER->value);
        });
        $form->saving(function (Form $form) {
            $form->ignore('permissions'); // handled manually
        });

        $form->saved(function (Form $form) {
            $form->slug = Str::slug(request('name') . '-' . PermissionType::AREA_MANAGER->value);
            $form->admin_id = Auth::id();
            $all = request('permissions_all');
            $selectedPermissionIds = array_filter(explode(',', $all));

            $permissionModel = config('admin.database.permissions_model');
            $allPermissions = $permissionModel::all();

            $slugToId = $allPermissions->pluck('id', 'slug')->toArray();
            $idToSlug = $allPermissions->pluck('slug', 'id')->toArray();

            $resourceActions = [];
            foreach ($selectedPermissionIds as $pid) {
                $slug = $idToSlug[$pid] ?? '';
                if (preg_match('/^(browse|create|edit|delete)\-(.+)$/', $slug, $m)) {
                    $action = $m[1];
                    $resource = $m[2];
                    $resourceActions[$resource][$action] = true;
                }
            }

            $finalPermissionIds = [];

            foreach ($resourceActions as $resource => $actions) {
                $hasBrowse = !empty($actions['browse']);
                $hasCrud   = !empty($actions['create']) || !empty($actions['edit']) || !empty($actions['delete']);

                if ($hasBrowse) {
                    if (isset($slugToId["browse-$resource"])) $finalPermissionIds[] = $slugToId["browse-$resource"];
                    foreach (['create', 'edit', 'delete'] as $act) {
                        if (!empty($actions[$act]) && isset($slugToId["$act-$resource"])) {
                            $finalPermissionIds[] = $slugToId["$act-$resource"];
                        }
                    }
                } elseif ($hasCrud) {
                    if (isset($slugToId["browse-$resource"])) $finalPermissionIds[] = $slugToId["browse-$resource"];
                    foreach (['create', 'edit', 'delete'] as $act) {
                        if (!empty($actions[$act]) && isset($slugToId["$act-$resource"])) {
                            $finalPermissionIds[] = $slugToId["$act-$resource"];
                        }
                    }
                }
            }

            foreach ($selectedPermissionIds as $pid) {
                $slug = $idToSlug[$pid] ?? '';
                if (!preg_match('/^(browse|create|edit|delete)\-(.+)$/', $slug)) {
                    $finalPermissionIds[] = $pid;
                }
            }

            $finalPermissionIds = array_unique($finalPermissionIds);

            $form->model()->permissions()->sync($finalPermissionIds);



            Admin::user()->load('roles', 'permissions');
             Cache::forget('admin_user_permissions_' . Admin::user()->id);

            // 🔹 Logout other users with this role (optional)
            $userIds = DB::table('admin_role_users')
                ->where('role_id', $form->model()->id)
                ->pluck('user_id')
                ->toArray();

            foreach ($userIds as $uid) {
                if ($uid != Admin::user()->id) {
                     Cache::forget('admin_user_permissions_' . $uid);
                }
            }
            \App\Models\Admin::flushAllCachedPermissions();
            admin_toastr(__('Updated successfully'), 'success');
        });

        return $form;
    }


    public function form1()
    {
        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $roleModel());

        $roleId = request()->route('role');

        $form->text('name', trans('admin.name'))->rules('required|unique:admin_roles,name,' . $roleId);
        $form->listbox('permissions', trans('admin.permissions'))->options($permissionModel::all()->pluck('name', 'id'));

        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        return $form;
    }


    public function getPermissionsByCategory($category)
    {
        $permissionModelClass = config('admin.database.permissions_model');

        $permissions = $permissionModelClass::where('category', $category)->get();
        $data = $permissions->map(function ($perm) {
            return [
                'id' => $perm->id,
                'name' => __($perm->name),
            ];
        });

        return response()->json(['permissions' => $data]);
    }


    public function destroy($id)
    {
        if (!Admin::user()->can('*')) {
            chPermission::check('delete-' . $this->permission_name);
        }
        $role = Role::findOrFail($id);

        // UserRoleRewardHelper::revokeRewardsFromAllUsersForRole($role->id, $role->slug);

        return parent::destroy($id);
    }
}

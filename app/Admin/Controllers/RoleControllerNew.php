<?php

namespace App\Admin\Controllers;

use App\Models\Role;
use App\Helpers\Common;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\Permission;
use Illuminate\Support\Str;
use App\Enums\PermissionType;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use Modules\RoleRewards\Actions\DeleteRole;
use Modules\RoleRewards\Helpers\UserRoleRewardHelper;

class RoleControllerNew extends MainController
{
    // Permission containers for portal positions (auto-attached on position creation) — hidden and locked here, never staff roles.
    public const PORTAL_ROLE_SLUGS = [
        'country',
        'region',
        'bd',
        'shipping_super_admin',
        'agency',
        'agency-owner',
    ];

    public $permission_name = 'roles';

    private function guardPortalRole($id): void
    {
        $slug = Role::whereKey($id)->value('slug');

        if (in_array($slug, self::PORTAL_ROLE_SLUGS, true)) {
            abort(403, __('This role is a position system role and is managed automatically'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function title()
    {
        return trans('Roles');
    }

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Roles'))
            ->body($this->grid()));
    }

    public function edit($id, Content $content)
    {
        $this->guardPortalRole($id);

        return parent::edit($id, $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->form($id)->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title($this->title())
            ->description($this->description['create'] ?? trans('admin.create'))
            ->body($this->form()));
    }

    public function store()
    {
        if (!Admin::user()->can('*')) {
            abort(403);
        }

        return $this->form()->store();
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans(__('Roles')))
            ->body($this->detail($id)));
    }

    public function update($id)
    {
        if (!Admin::user()->can('*')) {
            abort(403);
        }

        $this->guardPortalRole($id);

        return $this->form()->update($id);
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
        // Identity resolved centrally: super-admin preview honoured, real
        // managers pinned to their own id (no raw request/session override).
        $roleAuthId = Common::resolveAreaManagerId();

        $grid = new Grid(new $roleModel());

        if ($roleAuthId) {
            $grid->model()->where('admin_id', $roleAuthId);
        } else {
            $grid->model()->where('admin_id', null);
        }
        $grid->model()->whereNotIn('slug', self::PORTAL_ROLE_SLUGS);
        $grid->column('id', 'ID')->sortable();
        $grid->column('slug', trans('admin.slug'));

        $grid->column('name', trans('admin.name'));

        $grid->column('preview', trans('admin.preview'))->display(function () {
            $id = $this->id; // Assuming 'id' is the record ID field
            return '<a href="javascript:void(0);" onclick="openPreview(' . $id . ')">
                <i class="fa fa-eye"></i>
            </a>';
        });

        $grid->column('rewards', __('Rewards'))->display(function () {
            $url = admin_url("role-rewards/{$this->id}");
            return '<a href="' . $url . '" class="btn btn-sm btn-info">
                        <i class="fa fa-gift"></i> ' . __('rewards') . '
                    </a>';
        });
        // $grid->column('permissions', trans('admin.permission'))->pluck('name')->take(7)->label();
        $grid->column('permissions', trans('admin.permission'))->display(function ($permissions) {
            return collect($permissions)->pluck('name')->take(7)->map(function ($name) {
                return __($name);
            });
        })->label();
        $grid->column('created_at', trans('admin.created_at'));
        $grid->column('updated_at', trans('admin.updated_at'));


        $grid->actions(function (Grid\Displayers\Actions $actions) {

            $actions->disableDelete();
            $actions->add(new DeleteRole());
        });


        $grid->disableExport();
        Admin::style('
            .box {
                overflow: auto !important;
            }
        ');
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
        $permissions = Permission::where(function ($q) {
            $q->whereHas('permissionTypes', function ($q) {
                $q->where('type', 'admin');
            })
                ->orWhere('category', 'general');
        })->get();

        // Resilience: the admin permission-type taxonomy (permission_types pivot)
        // is not seeded on every white-label instance. When it is empty the filter
        // above collapses to ~1 row and the role form shows only "select all" — you
        // can't build a granular (e.g. view-only) role. Fall back to ALL permissions
        // so the full tree always renders; they group by their own `category` column.
        if ($permissions->count() <= 1) {
            $permissions = Permission::all();
        }
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $roleModel());
        $this->disableFormTools($form);

        $form->text('name', trans('role name'))->rules(function ($form) {
            // Get the record ID if editing, otherwise null
            $id = $form->model()?->id ?? null;

            // Get the type from request or from existing model when editing
            $type = PermissionType::ADMIN->value ?? $form->model()?->type;

            // Default to empty string if not found (avoids SQL issues)
            $type = $type ?? '';
            return [
                'required',
                'unique:admin_roles,name,' . ($id ?? 'NULL') . ',id,type,' . $type,
                function ($attribute, $value, $fail) {
                    if (in_array(Str::slug($value), \App\Admin\Controllers\RoleControllerNew::PORTAL_ROLE_SLUGS, true)) {
                        $fail(__('This role name is reserved for position system roles'));
                    }
                },
            ];
        });

        $form->html('<div class="full-column-width">');
        // Custom tabbed view
        $form->html(view('admin.permissions-tabs', [
            'permissions' => $permissions,
            'selectedPermissions' => $id != null ? Role::where('id', $id)->first()->permissions->pluck('id')->toArray() : [],
        ])->render());
        $form->html('</div>');
        $form->text('desc_en', __('Description en'));
        $form->text('desc_ar', __('Description ar'));
        $form->image('image', __('Image'))->help('');


        $form->saving(function (Form $form) {
            $form->ignore('permissions');

            // Automatically generate slug from name *before saving*
            $form->model()->slug = Str::slug($form->name);
        });
        $form->saving(function (Form $form) {
            $form->ignore('permissions'); // handled manually
        });

        $form->saved(function (Form $form) {
            $form->slug = Str::slug(request('name'));

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
                $hasCrud = !empty($actions['create']) || !empty($actions['edit']) || !empty($actions['delete']);

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
        $role = Role::findOrFail($id);

        if (in_array($role->slug, self::PORTAL_ROLE_SLUGS, true)) {
            return response()->json([
                'status'  => false,
                'message' => __('This role is a position system role and is managed automatically'),
            ]);
        }

        UserRoleRewardHelper::revokeRewardsFromAllUsersForRole($role->id, $role->slug);

        \App\Models\Admin::flushAllCachedPermissions();

        return parent::destroy($id);
    }
}

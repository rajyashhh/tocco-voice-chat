<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Facades\Admin;

class RoleController extends MainController
{
    public $permission_name = 'roles';
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
        return parent::edit($id,$content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->form1()->edit($id)));
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

        $result = $this->form()->store();

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        admin_toastr(__('Save succeeded !'), 'success');
        return redirect()->back();
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

        $result = $this->form1()->update($id);

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

        $grid = new Grid(new $roleModel());
 
        $grid->column('id', 'ID')->sortable();
        $grid->column('slug', trans('admin.slug'));
        $grid->column('name', trans('admin.name'));
        $grid->column('preview', trans('admin.preview'))->display(function () {
            $id = $this->id; // Assuming 'id' is the record ID field
            return '<a href="javascript:void(0);" onclick="openPreview(' . $id . ')">
                <i class="fa fa-eye"></i>
            </a>';
        });

        $grid->column('permissions', trans('admin.permission'))->pluck('name')->take(7)->label();

        $grid->column('created_at', trans('admin.created_at'));
        $grid->column('updated_at', trans('admin.updated_at'));

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            if (
                $actions->row->slug == 'administrator' ||
                $actions->row->slug == 'admin' ||
                $actions->row->slug == 'developer' ||
                $actions->row->slug == 'agency' ||
                $actions->row->slug == 'charger'
            ) {
                $actions->disableDelete();
            }
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->batch(function (Grid\Tools\BatchActions $actions) {
                $actions->disableDelete();
            });
        });

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
    public function form()
    {
        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $roleModel());


        $form->text('slug', trans('admin.slug'))->rules('required|unique:admin_roles,slug');

        $form->text('name', trans('admin.name'))->rules('required|unique:admin_roles,name');
        $form->listbox('permissions', trans('admin.permissions'))->options($permissionModel::all()->pluck('name', 'id'));
        $form->text('desc_en', __('Description en'));
        $form->text('desc_ar', __('Description ar'));
        $form->image('image', __('Image'))->help('Image will appear beside user in app');

        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        $form->saved(function (Form $form) {
            // Clear permissions cache for current user
            Admin::user()->load('roles', 'permissions');
            Cache::forget('admin_user_permissions_' . Admin::user()->id);

            // Clear cache for all users with this role (important for Octane)
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

        $form->saved(function (Form $form) {
            // Clear permissions cache for current user
            Admin::user()->load('roles', 'permissions');
            Cache::forget('admin_user_permissions_' . Admin::user()->id);

            // Clear cache for all users with this role (important for Octane)
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
        });

        return $form;
    }
}

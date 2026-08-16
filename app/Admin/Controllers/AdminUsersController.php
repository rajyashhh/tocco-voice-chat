<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Admin\Actions\KickOfAgencyAction;
use App\Facades\ManagerHelper;
use App\Models\User;
use App\Models\Agency;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\AdminUser;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Hash;

class AdminUsersController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    public $permission_name = 'admin-users';

    public function show($id, Content $content)
    {

        return parent::show($id, $content->title(trans('admins'))->row("<h3>" . __('Agencies') . "</h3>")->row(function ($row) use ($id) {
            $row->column(12, $this->agencies($id));
        }));
    }

    public function show2($id, Agency $agency, Content $content)
    {
        return parent::show($id, $content->title(trans('admins'))->row("<h3>" . __('Users Agencies in') . ' ' . $agency->name . "</h3>")->row(function ($row) use ($id, $agency) {
            $row->column(12, $this->usersGrid($agency));
        }));
    }
    /**
     * Title for current resource.
     *
     * @var string
     */
    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Managers'))
            ->body($this->grid()));
    }


    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('admins'))
            ->body($this->form()->edit($id)));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('admins'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AdminUser());
        $grid->model()
            ->whereHas('roles', fn($q) => $q->where('slug', 'like', '%_genc%_anager%'))
            ->where('app_id', '!=', 0)->with(['user' => fn($q) => $q->with('profile')->withCount('agencies')]);

        $grid->model()->with(['managerAgencies' => fn($q) => $q->withSum('agencySalaries as total_salaries', 'sallary')]);

        /*$adminUser = AdminUser::load([ 'managerAgencies' => fn($q) => $q->withSum('agencySalaries as total_salaries', 'sallary')])->get();
        dd($adminUser->get(11));*/
        // $admin = \App\Models\AdminUser::with(['user'=> fn($q) => $q->withCount('agencies')])->get();
        // dd($admin);

        $grid->column('id', __('Id'));

        $grid->column('user.name', __('User'))->display(function () {
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($this->user?->profile?->avatar) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            return '
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="'.$url.'" alt="User Image" style="width: 40px; height: 40px;">
                    <div>
                        <a href="/admin/users/'.$this->user_id.'" style="text-decoration: none; color:rgb(253, 253, 253); font-weight: bold;">'.$this->user?->name.'</a>
                        <div style="font-size: 12px; color: #fff;">' .'Uuid: '.$this->user?->uuid.'</div>
                    </div>
                </div>
            ';
        });

        $grid->column('user.agencies_count', __('Agency Count'))->display(function () {
            return $this->user?->agencies_count ?? 0;
        });

        $grid->column('managerAgencies.total_salaries', __('salary'))->display(function ($_) {
            $icon = asset('images/dollar-icon.png'); // Ensure this path is correct
            return '<img src="'.$icon.'" alt="$" style="width: 20px; height: 20px; margin-right: 5px;">' . ManagerHelper::getTotalAgenciesSalary($this->managerAgencies, $this->app_id);
        });



        $grid->actions(function (Grid\Displayers\Actions $actions) {
            // Disable the "View" action
            // $actions->disableEdit(); // Disable the "Edit" action
            $actions->disableDelete();
            $actions->disableEdit();
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(AdminUser::findOrFail($id));

        // $show->field('id', __('Id'));
        // $show->field('username', __('Username'));
        // $show->field('password', __('Password'));
        // $show->field('name', __('Name'));
        // $show->field('avatar', __('Avatar'));
        // $show->field('remember_token', __('Remember token'));
        // $show->field('created_at', __('Created at'));
        // $show->field('updated_at', __('Updated at'));
        // $show->field('di', __('Di'));
        // $show->field('Agency_manger', __('Agency manger'));
        // $show->field('app_id', __('App id'));

        $this->extendShow($show);

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $userModel = config('admin.database.users_model');
        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $userModel());

        $userTable = config('admin.database.users_table');
        $connection = config('admin.database.connection');

        //$form->display('id', 'ID');
        $form->text('username', trans('admin.username'))
            ->creationRules(['required', "unique:{$connection}.{$userTable}"])
            ->updateRules(['required', "unique:{$connection}.{$userTable},username,{{id}}"]);

        $form->text('name', trans('admin.name'))->rules('required');
        $form->image('avatar', trans('admin.avatar'));
        $form->password('password', trans('admin.password'))->rules('required|confirmed');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });

        $form->ignore(['password_confirmation']);

        //$form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::all()->pluck('name', 'id'));
        //$form->multipleSelect('permissions', trans('admin.permissions'))->options($permissionModel::all()->pluck('name', 'id'));
        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        $form->select('app_id', __('user'))->options('/api/search/users2')->ajax('/api/search/users2', 'id', 'name')->creationRules('required|unique:admin_users,app_id');



        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = Hash::make($form->password);
            }
            User::where('id', @request()->app_id)->update([
                'is_manger' => true,
            ]);
            $form->Agency_manger = true;
        });
        $form->saved(function (Form $form) {
            $form->model()->roles()->attach(['role_id' => 13, 'user_id' => $form->model()->getAttribute('id')]);
            \App\Models\Admin::forgetCachedPermissionsFor($form->model()->getAttribute('id'));
        });


        return $form;
    }


    protected function agencies($id)
    {
        $AdmenUser = AdminUser::find($id);


        $grid = new Grid(new Agency());
        $grid->id(__('ID'));
        $grid->model()->where('agency_manger_id', $AdmenUser->app_id)->with('owner')->withCount('users');
        $grid->column('app_owner_id', trans('owner id'))->modal('owner info', function ($model) {
            return Common::getusersShow($model->app_owner_id);
        });
        $grid->column('name', trans('name'));
        $grid->column('notice', trans('notice'));
        $grid->column('owner.name', trans('owner'));
        $grid->column('phone', trans('phone'));
        $grid->column('img', trans('img'))->image('', 30);
        $grid->column('users_count', trans('Users Count'));

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            // Disable the "View" action
            // $actions->disableEdit(); // Disable the "Edit" action
            $actions->disableDelete();
            $actions->disableEdit();
        });
        $grid->disableCreateButton();

        return $grid;
    }

    protected function usersGrid($agency)
    {
        if (gettype($agency) == 'string') {
            $agency = Agency::find($agency);
        }

        $grid = new Grid(new User());
        $grid->model()->where('agency_id', $agency->id);
        $grid->id(__('ID'));

        $grid->column('name', trans('name'));
        $grid->column('uuid', trans('uuid'));
        $grid->column('salary', trans('salary'));
        $grid->column('monthly_diamond_received', trans('monthly diamond'));

        $grid->actions(function ($actions) use ($agency) {
            $model = $actions->row;

            // Disable the "View" action
            // $actions->disableEdit(); // Disable the "Edit" action
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
            if ($model->agency_id >= 1 && $model->id != $agency->app_owner_id) {
                $actions->add(new KickOfAgencyAction());
            }
        });

        $grid->disableCreateButton();

        return $grid;
    }
}

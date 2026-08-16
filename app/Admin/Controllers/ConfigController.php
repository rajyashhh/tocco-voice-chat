<?php

namespace App\Admin\Controllers;

use Modules\Vip\Entities\OVip;
use App\Models\Config;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Enums\ConfigType;
use App\Models\AdminUser;
use Illuminate\Support\Str;
use App\Enums\ConfigCategory;
use Encore\Admin\Layout\Content;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Admin;
use App\Services\AppFeatureService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;
use Encore\Admin\Controllers\HasResourceActions;

class ConfigController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'config';
    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("config");
    }

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        if (! \Encore\Admin\Facades\Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }
        return parent::index($content
            ->title(trans('configs'))
            ->body($this->grid()));
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return parent::show($id,$content
            ->title(trans('configs'))
            ->body($this->detail($id)));
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
        $form = $this->form()->edit($id);
        if ($form->model()->type == 'integer') {
            $form->valueInteger = $form->model()->value;
        } elseif ($form->model()->type == 'select') {
            $form->valueSelect = $form->model()->value;
        }
        return parent::edit($id,$content
            ->title(trans('configs'))
            ->body($form));
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
            ->title(trans('configs'))
            ->body($this->form()));
    }

    /**
     * Store interface.
     *
     * @return mixed
     */
    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Config);

        $grid->model()->where('is_hidden', 0);
        $grid->id('ID');
        $grid->name(trans('name'));
        $grid->column('value', trans('value'))->display(function ($text) {
            return Str::limit($text, 50, '...');
        })->editable();

        $grid->column('desc', trans('description'))->display(function ($desc) {
            return Lang::has('dashboard.' . $desc) ? __('dashboard.' . $desc) : $desc;
        });
//        Admin::style('.dropdown-toggle {
//            background-color: #f8f9fa;
//            color: #333;
//            border: 1px solid #ccc;
//        }
//        .dropdown-menu {
//            min-width: 200px;
//            background-color: #fff;
//            border: 1px solid #ccc;
//            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
//        }
//        .dropdown-item {
//            color: #333;
//                padding: 0 5px;
//                display: block;
//        }');
        Admin::js('vendor/vue/vue-2.6.10.min.js');


        $grid->tools(function ($tools) {
            // استخدم append() لإضافة الزر بجانب زر الإضافة
            $action = admin_url('configs/change-time-zone');
            $csrf = csrf_token();
            $tools->append('<div class="dropdown" style="margin-top:10px;">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                ' . trans('select_time_zone') . '
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <form method="POST" action="' . $action . '" style="margin:0;"><input type="hidden" name="_token" value="' . $csrf . '"><button type="submit" name="time_zone" value="EET" class="dropdown-item">Egypt</button></form>
                <form method="POST" action="' . $action . '" style="margin:0;"><input type="hidden" name="_token" value="' . $csrf . '"><button type="submit" name="time_zone" value="AST" class="dropdown-item">SAD</button></form>
                <form method="POST" action="' . $action . '" style="margin:0;"><input type="hidden" name="_token" value="' . $csrf . '"><button type="submit" name="time_zone" value="CET" class="dropdown-item">Morocco</button></form>
            </div>
        </div>');
        });
        $this->extendGrid($grid);

        return $grid;
    }

    /**
     * Change the current admin's time zone (mutating action).
     * Dedicated POST endpoint protected by CSRF (web middleware) and permission check.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeTimeZone(\Illuminate\Http\Request $request)
    {
        if (! \Encore\Admin\Facades\Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }

        $request->validate([
            'time_zone' => 'required|string|in:EET,AST,CET',
        ]);

        $admin_user = AdminUser::find(auth()->user()->id);
        if ($admin_user) {
            $admin_user->time_zone = $request->input('time_zone');
            $admin_user->save();
        }

        return redirect(admin_url('configs'));
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Config::findOrFail($id));

        $show->id('ID');
        $show->name(trans('name'));
        $show->value(trans('value'));
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
        $form = new Form(new Config);
        $this->disableFormTools($form);

        $form->display('ID');
        $form->text('name', trans('name'));
        $form->textarea('desc', trans('description'));
        $form->select('category', trans('category'))
            ->options(ConfigCategory::getTranslatedOptions())
            ->required();
        $form->select('type', trans('type'))
            ->options(ConfigType::getTranslatedOptions())
            ->required()
            ->when("select", function () use ($form) {
                $form->select('sub_type', trans('sub_type'))->options(function () {
                    $ops = ['1' => __("yes_or_no"), '2' => __("true_and_false")];
                    return $ops;
                })->when("1", function () use ($form) {
                    $ops = ['yes' => __("yes"), 'no' => __("no")];
                    $form->select('valueSelect', trans('value'))->options($ops);
                })->when("2", function () use ($form) {
                    $ops = ['true' => __("true"), 'false' => __("false")];
                    $form->select('valueSelect', trans('value'))->options($ops);
                });
            })
            ->when("integer", function () use ($form) {
                $form->number('valueInteger', trans('value'));
            })
            ->when("string", function () use ($form) {
                $form->text('value', trans('value'));
            });

        $form->saving(function (Form $form) {
            if ($form->type == 'integer') {
                $form->value = $form->valueInteger;
            } elseif ($form->type == 'select') {
                $form->value = $form->valueSelect;
            }
        });

        return $form;
    }
}

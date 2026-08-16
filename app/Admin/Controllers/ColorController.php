<?php

namespace App\Admin\Controllers;

use App\Models\Color;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use App\Models\WebSetting;
use App\Rules\HexColor;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

class ColorController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */

    public $permission_name = 'color';

    public function __construct()
    {
        $this->title = __('colors');
    }

    public function index(Content $content)
    {
        return $content
            ->title(trans('colors'))
            ->body($this->grid());
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
        return $content
            ->title(trans('colors'))
            ->body($this->detail($id));
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
        return $content
            ->title(trans('colors'))
            ->body($this->form()->edit($id));
    }

    public function create(Content $content)
    {
        return $content
            ->title(trans('colors'))
            ->body($this->form());
    }

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
        $grid = new Grid(new Color());

        $grid->column('id', __('Id'));
        $grid->column('color', __('Color'));
        $grid->column('status', __('status'))->select(
            [
                0 => trans('main colors'),
                1 => trans('button colors'),
                2 => trans('second colors'),

            ]
        );


        $grid->footer(function ($query) {
            return view('admin.dashboard.app-setting', ['color' => $query]);
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
        $show = new Show(Color::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('color', __('Color'));
        $show->field('status', __('Status'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Color());

        $form->color('color', __('Color'))->rules(['nullable', new HexColor()]);
        $form->select('status', __('Status'))->options(
            [
                0 => __('main colors'),
                1 => __('button colors'),
                2 => __('second colors')
            ]
        );

        return $form;
    }

    public function appSetting(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        $data = WebSetting::find(1);
        if (!$data) {
            $data = new WebSetting();
        }
        if ($request->hasFile('logo')) {
            $imagePath = Common::upload('images', $request->file('logo'));
            $data->logo = $imagePath;
        }

        $data->footer_description = $request->input('desc');
        $data->save();

        admin_toastr('تم تحديث الصورة والوصف بنجاح', 'success');
        return back();
        // return redirect()->back()->with('success', 'تم تحديث الصورة والوصف بنجاح');
    }
}

<?php

namespace App\Admin\Controllers;

use App\Helpers\Common;
use App\Models\Admin;
use App\Models\Charge;
use App\Http\Controllers\Controller;
use App\Models\ImageColor;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Grid\Displayers\Image;
use App\Rules\HexColor;

class ImageColorController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'id-color';

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('ID color'))
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('image-color'))
            ->body($this->form()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('image-color'))
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
        return parent::edit($id, $content
            ->title(trans('image-color'))
            ->body($this->form()->edit($id)));
    }



    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ImageColor);
        $grid->model()->orderByDesc('id');
        $grid->id("id", __('id'));
        $grid->column('image', __('image'))->image('', 30);
        $grid->Column('color', __('color'))->display(function () {
            return "<div style='width: 50px; height: 50px; background-color: $this->color'></div>";
        });
        $grid->disableExport();
        $this->extendGrid($grid);
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
        $show = new Show(Charge::findOrFail($id));

        //        $show->id('ID');
        //        $show->charger_id('charger_id');
        //        $show->charger_type('charger_type');
        //        $show->user_id('user_id');
        //        $show->user_type('user_type');
        //        $show->amount('amount');
        //        $show->amount_type('amount_type');
        //        $show->created_at(trans('admin.created_at'));
        //        $show->updated_at(trans('admin.updated_at'));
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
        $form = new Form(new ImageColor);
        $this->disableFormTools($form);

        $form->display('id');
        $form->text('name', __('name'));
        $form->file('image', __('image'));
        $form->color('color', __('color'))->rules(['nullable', new HexColor()]);

        return $form;
    }
}

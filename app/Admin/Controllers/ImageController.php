<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Helpers\Common;
use App\Models\Image;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ImageController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }

    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
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
            ->header(trans('admin.detail'))
            ->description(trans('admin.description'))
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
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Image);

        $grid->id( __ ('ID'));
        $grid->name(__('name'));
        $grid->column('url',__('url'))->image ('',30);
        $grid->column('type',__('type'));
        $grid->column('status',__('status'))->switch (Common::getSwitchStates ());
        $grid->disableExport();
        return $grid;
    }


    protected function detail($id)
    {
        $show = new Show(Image::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('name', 'Name');
        $show->field('url', 'url');
        $show->field('type', 'type');
        $show->field('status', 'status');

        return $show;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Image);

        $form->display( __ ('ID'));
        $form->text('name', __('name'));
        $form->file('url', __('url'));
        $form->select('type', __('type'))->options ([0=>__('pk'),1=>__('other')]);
        $form->switch('status', __('status'))->states (Common::getSwitchStates ());


        return $form;
    }
}

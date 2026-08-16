<?php

namespace App\Admin\Controllers;

use App\Models\BanType;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BanTypeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'BanType';

    protected function title()
    {
        return trans('Ban Types');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BanType());

        $grid->column('id', __('Id'));
        $grid->column('name_ar', __('Name ar'));
        $grid->column('name_en', __('Name en'));
        $grid->column('route', __('URL'));

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
        $show = new Show(BanType::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name_ar', __('Name ar'));
        $show->field('name_en', __('Name en'));
        $show->field('route', __('URL'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BanType());

        $form->text('name_ar', __('Name ar'));
        $form->text('name_en', __('Name en'));
        $form->text('route', __('URL'));
        $form->text('method', __('type'));

        return $form;
    }
}

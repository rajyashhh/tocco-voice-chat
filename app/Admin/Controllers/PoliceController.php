<?php

namespace App\Admin\Controllers;

use App\Models\Police;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PoliceController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Police';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Police());

        $grid->column('id', __('Id'));
        if (app()->getLocale() == 'ar') {
            $grid->column('title', __('title'));
            $grid->column('body', __('body'));
        } else {
            $grid->column('title_en', __('title'));
            $grid->column('body_en', __('body'));
        }
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
        $show = new Show(Police::findOrFail($id));

        $show->field('id', __('Id'));
        if (app()->getLocale() == 'ar') {
            $show->field('title', __('title'));
            $show->field('body', __('body'));
        } else {
            $show->field('title_en', __('title'));
            $show->field('body_en', __('body'));
        }
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Police());

        $form->textarea('title', __('title'));
        $form->textarea('body', __('body'));
        $form->textarea('title_en', __('title_en'));
        $form->textarea('body_en', __('body_en'));

        return $form;
    }
}

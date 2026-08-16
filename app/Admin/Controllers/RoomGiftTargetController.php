<?php

namespace App\Admin\Controllers;

use App\Models\RoomGiftTarget;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RoomGiftTargetController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'RoomGiftTarget';

    protected function title()
    {
        return trans('Room Gift Targets');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RoomGiftTarget());

        $grid->column('id', __('Id'));
        $grid->column('target', __('Target'));
        $grid->column('image', __('Image'))->image('', 50);
        $grid->column('coins', __('coins'));
        

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
        $show = new Show(RoomGiftTarget::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('coins', __('Coins'));
        $show->field('image', __('Image'));
        $show->field('target', __('Target'));
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
        $form = new Form(new RoomGiftTarget());
        
        $form->number('target', __('Target'))->required();
        $form->number('coins', __('Coins'))->required();
        $form->image('image', __('Image'));
        

        return $form;
    }
}

<?php

namespace Modules\Tasks\Http\Controllers;

use Modules\Tasks\Entities\UserTaskReward;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class UserTaskRewardController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'UserTaskReward';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UserTaskReward());

        $grid->column('id', __('Id'));
        $grid->column('user_id', __('User id'));
        $grid->column('task_reward_id', __('Task reward id'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

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
        $show = new Show(UserTaskReward::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('task_reward_id', __('Task reward id'));
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
        $form = new Form(new UserTaskReward());

        $form->number('user_id', __('User id'));
        $form->number('task_reward_id', __('Task reward id'));

        return $form;
    }
}

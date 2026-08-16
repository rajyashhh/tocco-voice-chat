<?php

namespace Modules\Tasks\Http\Controllers;//App\Admin\Controllers;

use Modules\Tasks\Entities\Day;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DayController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Day';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Day());

        $grid->column('id', __('Id'));
        $grid->column('day_number', __('Day number'));
        $grid->column('title', __('Title'));
        $grid->column('is_unlocked', __('Is unlocked'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->column(__('Actions'))->display(function () {
            $dayId = $this->getKey(); 
            $createUrl = url('admin/'.$dayId.'/day-tasks');
            $createUrlRewards = url('admin/'.$dayId.'/day-rewards');
            return "<a href='{$createUrl}' class='btn btn-sm btn-primary'>".__("Tasks")."</a> <a href='{$createUrlRewards}' class='btn btn-sm btn-primary'>".__("rewards")."</a>";
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
        $show = new Show(Day::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('day_number', __('Day number'));
        $show->field('title', __('Title'));
        $show->field('is_unlocked', __('Is unlocked'));
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
        $form = new Form(new Day());

        $form->number('day_number', __('Day number'));
        $form->text('title', __('Title'));
        $form->switch('is_unlocked', __('Is unlocked'));

        return $form;
    }
}

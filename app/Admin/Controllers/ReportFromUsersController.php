<?php

namespace App\Admin\Controllers;

use App\Models\Report_user;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;


class ReportFromUsersController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Report_user';

    public function index(Content $content)
    {
        return $content
            ->header(trans('User_Report'))
            // ->description(trans('admin.User_Report'))
            ->row(function ($row) {
                $row->column(10, $this->grid());
                // $row->column(2, view('admin.grid.users.ban'));
            });
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Report_user());

        $grid->model()->with(['user:id,uuid', 'Reporter:id,uuid']);

        $grid->column('id', __('Id'));
        $grid->column('type', __('Type'));
        $grid->column('report_details', __('Report details'));




        // $grid->column('user_id', __('User id'));
        $grid->column('user.uuid', __('User id'))->display(function ($uuid) {
            return $uuid ?? __('User not found');
        });

        $grid->column('Reporter.uuid', __('Reporter id'))->display(function ($uuid) {
            return $uuid ?? __('User not found');
        });
        $grid->column('image', __('image'))->image('', 200);


        $grid->disableExport();
        $grid->disableCreateButton();
        $grid->disableRowSelector();




        // $grid->column('created_at', __('Created at'));
        // $grid->column('updated_at', __('Updated at'));

        // $grid->disableExport ();
        // $grid->disableRowSelector ();
        $grid->disableActions();
        // $grid->disableFilter();
        // $grid->applyColumnFilter();

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
        $show = new Show(Report_user::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type', __('Type'));
        $show->field('report_details', __('Report details'));
        $show->field('user_id', __('User id'));
        $show->field('Reporter_id', __('Reporter id'));
        $show->field('image', __('image'));
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
        $form = new Form(new Report_user());

        $form->text('type', __('Type'));
        $form->textarea('report_details', __('Report details'));
        // $form->number('user_id', __('User id'));
        // $form->number('Reporter_id', __('Reporter id'));

        return $form;
    }
}

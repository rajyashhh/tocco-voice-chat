<?php

namespace Modules\Tasks\Http\Controllers;//App\Admin\Controllers;

use Modules\Tasks\Entities\UserDayTaskProgress;
//namespace App\Admin\Controllers;

//use App\Models\UserDayTaskProgress;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class UserDayTaskProgressController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'UserDayTaskProgress';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UserDayTaskProgress());



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
        $show = new Show(UserDayTaskProgress::findOrFail($id));



        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new UserDayTaskProgress());



        return $form;
    }
}

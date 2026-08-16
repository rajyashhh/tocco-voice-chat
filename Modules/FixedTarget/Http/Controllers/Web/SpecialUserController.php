<?php

namespace Modules\FixedTarget\Http\Controllers\Web;

use App\Admin\Controllers\MainController;
use App\Models\Target;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Modules\FixedTarget\Entities\FixedTarget;
use Modules\FixedTarget\Entities\SpecialUser;

class SpecialUserController extends MainController
{
    public $permission_name = 'special-user';


    public function grid()
    {
        $grid = new Grid(new SpecialUser());
        $grid->model()->orderBy('status', 'desc');

        $grid->id( __ ('ID'));
        
        $grid->column('user.uuid',__('uuid'));
        $grid->column('user.name',__('name'));

        $grid->column('status')->switch();

        
        
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableExport();
        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SpecialUser);

        $form->display(__ ('ID'));
        $form->select('user_id', __('user'))->options('/api/search/users2')->ajax('/api/search/users2', 'id', 'name')->creationRules('required|unique:special_users,user_id');
        $form->switch('status', __('status'));
        return $form;
    }

}






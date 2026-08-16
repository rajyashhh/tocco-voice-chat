<?php

namespace App\Admin\Controllers;

use App\Models\ChargeValue;
use App\Http\Controllers\Controller;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ChargeValueController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }

    use HasResourceActions;
    public $permission_name = 'charge';
    public $hiddenColumns = [

    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ChargeValue);

        $grid->id('ID');
        $grid->column('usd',__ ('usd'));
        $grid->column('value',__ ('value'));
        $grid->column('type',__ ('type'))->using (
            [
                0=>'coin',
                1=>'silver coin'
            ]
        );
        $grid->column('usd_img',__ ('usd image'))->image ('',30);
        $grid->column('type_img',__ ('type image'))->image ('',30);
        $this->extendGrid ($grid);
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
        $show = new Show(ChargeValue::findOrFail($id));

//        $show->id('ID');
//        $show->usd('usd');
//        $show->value('value');
//        $show->type('type');
//        $show->usd_img('usd_img');
//        $show->type_img('type_img');
//        $show->created_at(trans('admin.created_at'));
//        $show->updated_at(trans('admin.updated_at'));
        $this->extendShow ($show);
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ChargeValue);

        $form->display('ID');
        $form->decimal('usd', __('usd'));
        $form->number('value', __('value'));
        $form->select('type', __('type'))->options (
            [
                0=>__('coin'),
                1=>__('silver coin')
            ]
        );
        $form->image('usd_img', 'usd_img');
        $form->image('type_img', 'type_img');

        return $form;
    }
}

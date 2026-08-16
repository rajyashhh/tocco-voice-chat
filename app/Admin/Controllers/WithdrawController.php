<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\PaymentWithdrawType;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;

class WithdrawController extends  MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    /**
     * Title for current resource.
     *
     * @var string
     */
    public $permission_name = 'withdraw-type';

    public function index(Content $content)
    {
        return $content
            ->title(trans('withdraw-types'))
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
            ->title(trans('withdraw-types'))
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
            ->title(trans('withdraw-types'))
            ->body($this->form()->edit($id));
    }

    public function create(Content $content)
    {
        return $content
            ->title(trans('withdraw-types'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PaymentWithdrawType());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('name_en', __('name_en'));
        $grid->column('min_value',  __('min value'));
        $grid->column('exchange_rate',__('Exchange Rate'));
        $grid->column('image', __('Image'))->image('', 50);
        

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
        $show = new Show(PaymentWithdrawType::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('min_value', _('min value'));
        $show->field('exchange_rate', __('Exchange Rate'));
        $show->field('image', __('Image'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new PaymentWithdrawType());

        $form->text('name', __('Name'));
        $form->text('name_en', __('name_en'));
        $form->image('image', __('Image'));
        $form->number('min_value', __('min value'));
        $form->number('exchange_rate', __('Exchange Rate'));
        $form->hasMany('withdrawFields', function (Form\NestedForm $form) {
             $form->text('name',trans('name'));
             $form->text('name_en',trans('name_en'));
            $form->select('type', trans('type'))->options([
                'string' => 'string',
                'int' => 'integer',
            ]);
            $form->text('validate',trans('validate'));
        });

        return $form; 
    }
}

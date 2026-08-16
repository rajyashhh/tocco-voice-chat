<?php

namespace App\Admin\Controllers;



use Encore\Admin\Auth\Permission;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\PaymentGateway;
use App\Admin\Controllers\MainController;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\AdminController;

class PaymentGetWayController extends MainController
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
    public $permission_name = 'payment-gat-way';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Payment Methods for Charging Agentsadmin-users'))
            ->body($this->grid()));
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
        return parent::show($id,$content
            ->title(trans('payment-gateways'))
            ->body($this->detail($id)));
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
        return parent::edit($id,$content
            ->title(trans('payment-gateways'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('payment-gateways'))
            ->body($this->form()));
    }

    

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PaymentGateway());

        $grid->column('id', __('Id'));
        $grid->column('title', __('title'));
        $grid->column('photo', __('Photo'))->image('', 50);
        $this->extendGrid($grid);
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
        $show = new Show(PaymentGateway::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('title', __('Title'));
        $show->field('photo', __('Photo'));
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
        $form = new Form(new PaymentGateway());
        $this->disableFormTools($form);

        $form->text('title', __('Title'));
        $form->image('photo', __('Photo'));

        return $form;
    }
}

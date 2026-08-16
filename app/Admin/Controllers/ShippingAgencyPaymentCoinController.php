<?php

namespace App\Admin\Controllers;


use Config;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Enums\PaymentType;
use App\Models\PaymentCoin;
use App\Admin\Controllers\MainController;
use Encore\Admin\Layout\Content;

class ShippingAgencyPaymentCoinController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'paymentCoin';
    public $permission_name = 'payment-coin';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('payment-coins'))
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
        return parent::show($id, $content
            ->title(trans('payment-coins'))
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
        return parent::edit($id, $content
            ->title(trans('payment-coins'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('payment-coins'))
            ->body($this->form()));
    }

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PaymentCoin());
        $grid->model()->where('package_type','shipping_agency'); 
        $grid->column('id', __('Id'));
        $grid->column('title', __('title'));
        $grid->column('photo', __('image'))->image('', 50);

        if (Admin::user()->can('browse-' . 'coins') || Admin::user()->can('*')) {
            $grid->column(__('procedures'))->display(function () {
                $url1 = url('admin/coins/' . $this->id);
                $button1 = "<a href='{$url1}' class='btn btn-sm btn-info'>" . __('coins') . "</a>";
                return $button1;
            });
        }

        $status = [
            'on' => ['value' => 1, 'text' => 'open', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => 'close', 'color' => 'default'],
        ];

        $grid->column('status', __("status"))->switch($status);

        $grid->column('custom_message', __('Custom Message'));
        //            ->display(function () {
        //            $fawryConfig = config('services.fawry');
        //            $fawryCount = collect($fawryConfig)->every(function($value) {
        //                return $value != null;
        //            });
        //
        //            $payskyConfig = config('paysky');
        //            $payskyCount = collect($payskyConfig)->every(function($value) {
        //                return $value != null;
        //            });
        //
        //            $stripeConfig = config('stripe');
        //            $stripeCount = collect($stripeConfig)->every(function($value) {
        //                return $value != null;
        //            });
        //
        //            $opayConfig = config('nafezly-payments');
        //            $opayCount = collect($opayConfig)->every(function($value) {
        //                return $value != null;
        //            });
        //
        //            $url = url('admin/settings?firsttab=paymentCredentialSettings');
        //            $href = "<a href='{$url}'>". __('Please edit payment credential settings') ."</a>";
        //            switch ($this->title){
        //                case 'fawry':
        //                    if (Config::get('is_fawry_active') != 1 || !$fawryCount) {
        //                        return $href;
        //                    }
        //                    break;
        //                case 'sky pay':
        //                    if (Config::get('is_paysky_active') != 1 || !$payskyCount) {
        //                        return $href;
        //                    }
        //                    break;
        //                case 'stripe':
        //                    if (Config::get('is_stripe_active') != 1 || !$stripeCount) {
        //                        return $href;
        //                    }
        //                    break;
        //                case 'opay':
        //                    if (Config::get('is_opay_active') != 1 || !$opayCount) {
        //                        return $href;
        //                    }
        //                    break;
        //                default:
        //                    return "<span class='text-muted'>". __('Payment gateway is ready to use') ."</span>";
        //            }
        //            return "<span class='text-muted'>". __('Payment gateway is ready to use') ."</span>";
        //        });
        $grid->disableCreateButton();
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
        $show = new Show(PaymentCoin::findOrFail($id));

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
        $form = new Form(new PaymentCoin());
        $this->disableFormTools($form);

        $form->text('title', __('Title'))
            ->creationRules(['required', "unique:payment_coins,title,{{id}}"])
            ->updateRules(['required', "unique:payment_coins,title,{{id}}"]);

        $form->image('photo', __('Photo'));

        //        $form->hasMany('settings', 'Fields', function ($form) {
        //            $form->text('key', 'Input Name')
        //                ->rules(function ($form) {
        //                    $itemId = request()->route('items');
        //                    $id = $form->model ? $form->model->id : null;
        //
        //                    return [
        //                        'required',
        //                        "unique:settings,key,$id,id,item_id,$itemId"
        //                    ];
        //                });
        //            $form->text('value', 'Input Value')->required();
        //            $form->select('input_type', 'Input Type')->options([
        //                'input' => 'Input',
        //                'file' => 'File',
        //            ])->required();
        //            $form->hidden('type')->default('payment');
        //        });

        $status = [
            'on' => ['value' => 1, 'text' => 'open', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => 'close', 'color' => 'default'],
        ];
        $form->switch('status', __('status'))->states($status)->default(1);

        return $form;
    }
}

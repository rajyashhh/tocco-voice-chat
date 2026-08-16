<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\ChargeAction2;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Models\Charge;
use App\Models\ShippingAgency;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ChargeController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'coin-recharge';


    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        if (! \Encore\Admin\Facades\Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }
        return $content
            ->title(trans('charges'))
            ->body($this->grid());
    }

    public function store()
    {
        if (! \Encore\Admin\Facades\Admin::user()->can('*')) {
            Permission::check('create-' . $this->permission_name);
        }

        return parent::store();
    }




    /**
     * Make a grid builder.
     *
     * @return Grid
     */

    protected function grid()
    {
        $grid = new Grid(new ShippingAgency());
        $countryID = Common::filterCountryIds();

        $grid->model()
            ->when($countryID, fn($q) =>
            $q->where(function ($q) use ($countryID) {
                $q->whereIn('country_id', $countryID)
                    ->orWhereHas('owner', fn($q) => $q->whereIn('country_id', $countryID));
            }))
            ->with([
                'owner:id,name,uuid',
                'owner.profile:id,user_id,avatar',
                'owner.country',
                'owner.senderLevel',
                'owner.receiverLevel',
                'owner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
            ]);
        $grid->disableRowSelector();

        $grid->filter(function (Grid\Filter $filter) {

            $filter->expand();

            $filter->disableIdFilter();

            $filter->where(function ($query) {
                $query->where('name', 'like', "%{$this->input}%")->orWhere('id', $this->input);
            }, __('Agency name or id'));

            $filter->where(function ($query) {
                $query->whereHas('owner', function ($q) {
                    $q->where('uuid', 'like', "%{$this->input}%");
                });
            }, __('Owner uuid'));
        });

        $grid->model()->orderByDesc('id');

        $grid->id(__('ID'));

        $grid->column('name', __('Agency'))
            ->display(function ($name) {
                $id = $this->id;
                $path = @$this->img;
                $defaultImage = asset("images/icon-agency.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $image = handleShowImageWithTypes($this->id, $url, 40, 40);
                $profileUrl = url("admin/shipping-agencies/profile/{$id}");

                return "
                        <div style='display: flex; align-items: center; gap: 10px;'>
                            $image
                            <a href='{$profileUrl}' target='_blank' style='text-decoration: none; color: inherit;'>
                                <div>
                                    <div>$name</div>
                                    <small style='color: #888;'>ID: {$id}</small>
                                </div>
                            </a>
                        </div>
                    ";
            });



        $grid->column('nameUser', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->owner);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());


        $grid->column('coins', __('coins'))->display(function ($coin) {
            $icon = asset('images/coin.jpg'); // تأكد من وجود الصورة في هذا المسار
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . number_format($coin) . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>

                </div>
            ";
        });
        $grid->column('usd', __('usd'))->display(function ($coin) {
            $shippingCoins = getSettingCash('shipping_coins');

            if ($shippingCoins) {
                $dollars = $this->coins / $shippingCoins;
                $numberFormatDollars = number_format($dollars);
            } else {
                $numberFormatDollars = __('please set agency coins in configs');
            }

            $icon = asset('images/dollar.jpg');
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . $numberFormatDollars . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>

                </div>
            ";
        });
        if (\Encore\Admin\Facades\Admin::user()->can('add-switch-' . $this->permission_name) || \Encore\Admin\Facades\Admin::user()->can('*') || \Encore\Admin\Facades\Admin::user()->can('charge-report-switch-' . $this->permission_name)) {
            $grid->column('actions', __('Actions'))
                ->display(function () {

                    return (new ChargeAction2())->setAgencyId($this->id)->render();
                })
                ->style('white-space: nowrap; width: 100px;');
        }

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableActions();

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
        $show = new Show(Charge::findOrFail($id));

        //        $show->id('ID');
        //        $show->charger_id('charger_id');
        //        $show->charger_type('charger_type');
        //        $show->user_id('user_id');
        //        $show->user_type('user_type');
        //        $show->amount('amount');
        //        $show->amount_type('amount_type');
        //        $show->created_at(trans('admin.created_at'));
        //        $show->updated_at(trans('admin.updated_at'));
        $this->extendShow($show);
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Charge);

        //        $form->display('ID');
        //        $form->text('charger_id', 'charger_id');
        //        $form->text('charger_type', 'charger_type');
        //        $form->text('user_id', 'user_id');
        //        $form->text('user_type', 'user_type');
        //        $form->text('amount', 'amount');
        //        $form->text('amount_type', 'amount_type');
        //        $form->display(trans('admin.created_at'));
        //        $form->display(trans('admin.updated_at'));

        return $form;
    }
}
